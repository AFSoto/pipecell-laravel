<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\ProductoImagen;
use App\Support\FotoProducto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Symfony\Component\Process\Process;
use Tests\Concerns\CreaDatosDeReparacion;
use Tests\TestCase;

/**
 * Fotos de productos: se aceptan hasta 10 por vez, de hasta 20 MB y 50 megapíxeles, y se
 * guardan convertidas a WebP de hasta 1600 px, derechas según la orientación EXIF del iPad
 * (ver FotoProducto). Las fotos se generan con GD; las de orientación llevan un bloque
 * EXIF armado a mano.
 */
class FotosProductoTest extends TestCase
{
    use RefreshDatabase;
    use CreaDatosDeReparacion;

    private Categoria $categoria;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->actingAs($this->crearUsuario());
        $this->categoria = Categoria::create(['nombre' => 'Accesorios', 'estado' => 'activo']);
    }

    /** Lo que manda el formulario de crear (o de editar) con estas fotos. */
    private function datosFormulario(array $imagenes, string $nombre = 'Cargador 20W'): array
    {
        return [
            'categoria_id'  => $this->categoria->id,
            'codigo'        => '',
            'nombre'        => $nombre,
            'descripcion'   => '',
            'marca'         => '',
            'precio_compra' => '',
            'precio_venta'  => '45000',
            'stock'         => '3',
            'stock_minimo'  => '1',
            'imagenes'      => $imagenes,
        ];
    }

    /** Crea un producto con estas fotos y revisa que se haya guardado sin errores. */
    private function crearConFotos(array $imagenes): TestResponse
    {
        return $this->post(route('admin.productos.store'), $this->datosFormulario($imagenes))
            ->assertRedirect(route('admin.productos.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success')
            ->assertSessionMissing('error');
    }

    /**
     * Convierte la foto con FotoProducto en otro PHP, como en un hosting que no deja subir
     * la memoria: memory_limit de 128M e ini_set deshabilitada. Retorna lo que pasó:
     * "WebP de 1600×1200", la excepción, o el error fatal si PHP murió.
     */
    private function convertirEnHostingLimitado(string $foto): string
    {
        $ruta = tempnam(sys_get_temp_dir(), 'foto');
        file_put_contents($ruta, $foto);

        $codigo = 'require ' . var_export(base_path('vendor/autoload.php'), true) . ';'
            . 'try { $i = getimagesizefromstring(App\Support\FotoProducto::aWebp($argv[1])); echo "WebP de {$i[0]}×{$i[1]}"; }'
            . ' catch (Throwable $e) { echo get_class($e), ": ", $e->getMessage(); }';

        $proceso = new Process([PHP_BINARY, '-d', 'memory_limit=128M', '-d', 'disable_functions=ini_set', '-r', $codigo, $ruta]);
        $proceso->run();
        unlink($ruta);

        return trim($proceso->getOutput() . $proceso->getErrorOutput());
    }

    /**
     * PNG válido de un solo color, comprimido de a una fila: pesa unos KB aunque tenga
     * muchos megapíxeles, y GD lo puede abrir de verdad (a diferencia de uno con solo el
     * encabezado).
     */
    private function pngLiso(int $ancho, int $alto): string
    {
        $bloque = fn (string $tipo, string $datos) => pack('N', strlen($datos)) . $tipo . $datos . pack('N', crc32($tipo . $datos));
        $zlib   = deflate_init(ZLIB_ENCODING_DEFLATE);
        $fila   = "\0" . str_repeat("\x80\x40\x20", $ancho); // filtro 0 + un píxel RGB por cada 3 bytes
        $datos  = '';

        for ($y = 0; $y < $alto; $y++) {
            $datos .= deflate_add($zlib, $fila, ZLIB_NO_FLUSH);
        }

        $datos .= deflate_add($zlib, '', ZLIB_FINISH);

        return "\x89PNG\r\n\x1a\n" . $bloque('IHDR', pack('NNCCCCC', $ancho, $alto, 8, 2, 0, 0, 0))
            . $bloque('IDAT', $datos) . $bloque('IEND', '');
    }

    /** La única foto guardada: [ruta, contenido, getimagesize del contenido]. */
    private function fotoGuardada(): array
    {
        $path      = ProductoImagen::sole()->path;
        $contenido = Storage::disk('public')->get($path);

        return [$path, $contenido, getimagesizefromstring($contenido)];
    }

    /** Color del píxel: ['red', 'green', 'blue', 'alpha' (0 = opaco, 127 = transparente)]. */
    private function color(string $contenido, int $x, int $y): array
    {
        $imagen = imagecreatefromstring($contenido);

        return imagecolorsforindex($imagen, imagecolorat($imagen, $x, $y));
    }

    private function esRojo(array $c): bool
    {
        return $c['red'] > 190 && $c['green'] < 70 && $c['blue'] < 70;
    }

    private function esAzul(array $c): bool
    {
        return $c['blue'] > 190 && $c['red'] < 70 && $c['green'] < 70;
    }

    /**
     * JPEG de 400×300 (azul, con el cuarto de arriba a la izquierda rojo) con un bloque EXIF
     * que dice cómo girarlo, como los del iPad. $orden: 'II' (Intel) o 'MM' (Motorola).
     */
    private function jpegConOrientacion(int $orientacion, string $orden = 'II'): string
    {
        $imagen = imagecreatetruecolor(400, 300);
        imagefilledrectangle($imagen, 0, 0, 399, 299, imagecolorallocate($imagen, 0, 0, 255));
        imagefilledrectangle($imagen, 0, 0, 199, 149, imagecolorallocate($imagen, 255, 0, 0));
        ob_start();
        imagejpeg($imagen, null, 95);
        $jpeg = ob_get_clean();

        $u16 = fn (int $v) => pack($orden === 'II' ? 'v' : 'n', $v);
        $u32 = fn (int $v) => pack($orden === 'II' ? 'V' : 'N', $v);

        // TIFF: encabezado + un directorio con una sola etiqueta: Orientation (0x0112), SHORT, 1 valor
        $tiff = $orden . $u16(42) . $u32(8)
            . $u16(1) . $u16(0x0112) . $u16(3) . $u32(1) . $u16($orientacion) . $u16(0)
            . $u32(0);
        $app1 = "Exif\0\0" . $tiff;

        // El APP1 va justo después del inicio del JPEG (FFD8)
        return substr($jpeg, 0, 2) . "\xFF\xE1" . pack('n', strlen($app1) + 2) . $app1 . substr($jpeg, 2);
    }

    // ── Conversión ──

    public function test_una_foto_del_ipad_se_guarda_en_webp_de_1600_px(): void
    {
        $this->crearConFotos([UploadedFile::fake()->image('foto.jpg', 4032, 3024)])
            ->assertSessionHas('success', 'Producto creado con éxito.');

        [$path, , $info] = $this->fotoGuardada();

        $this->assertStringStartsWith('productos/', $path);
        $this->assertStringEndsWith('.webp', $path);
        $this->assertSame([1600, 1200, 'image/webp'], [$info[0], $info[1], $info['mime']]);
        $this->assertTrue(ProductoImagen::sole()->es_principal);
    }

    public function test_una_foto_chica_no_se_agranda(): void
    {
        $this->crearConFotos([UploadedFile::fake()->image('foto.png', 800, 600)]);

        [, , $info] = $this->fotoGuardada();

        $this->assertSame([800, 600, 'image/webp'], [$info[0], $info[1], $info['mime']]);
    }

    public function test_una_foto_vertical_del_ipad_queda_derecha(): void
    {
        // Orientación 6: hay que girarla 90° a la derecha. El rojo pasa arriba a la derecha.
        $this->crearConFotos([UploadedFile::fake()->createWithContent('vertical.jpg', $this->jpegConOrientacion(6))]);

        [, $webp, $info] = $this->fotoGuardada();

        $this->assertSame([300, 400], [$info[0], $info[1]]);
        $this->assertTrue($this->esRojo($this->color($webp, 225, 100)), 'Arriba a la derecha debía quedar rojo');
        $this->assertTrue($this->esAzul($this->color($webp, 75, 100)), 'Arriba a la izquierda debía quedar azul');
    }

    public function test_la_orientacion_8_con_el_otro_orden_de_bytes_tambien_queda_derecha(): void
    {
        // Orientación 8 (girar 90° a la izquierda), EXIF en orden Motorola: el rojo pasa abajo a la izquierda
        $this->crearConFotos([UploadedFile::fake()->createWithContent('vertical.jpg', $this->jpegConOrientacion(8, 'MM'))]);

        [, $webp, $info] = $this->fotoGuardada();

        $this->assertSame([300, 400], [$info[0], $info[1]]);
        $this->assertTrue($this->esRojo($this->color($webp, 75, 300)), 'Abajo a la izquierda debía quedar rojo');
        $this->assertTrue($this->esAzul($this->color($webp, 75, 100)), 'Arriba a la izquierda debía quedar azul');
    }

    public function test_la_orientacion_3_queda_girada_180(): void
    {
        $this->crearConFotos([UploadedFile::fake()->createWithContent('alreves.jpg', $this->jpegConOrientacion(3))]);

        [, $webp, $info] = $this->fotoGuardada();

        $this->assertSame([400, 300], [$info[0], $info[1]]);
        $this->assertTrue($this->esRojo($this->color($webp, 300, 225)), 'Abajo a la derecha debía quedar rojo');
        $this->assertTrue($this->esAzul($this->color($webp, 100, 75)), 'Arriba a la izquierda debía quedar azul');
    }

    public function test_el_lector_de_orientacion_no_falla_con_datos_raros(): void
    {
        $this->assertSame(6, FotoProducto::orientacionExif($this->jpegConOrientacion(6)));
        $this->assertSame(8, FotoProducto::orientacionExif($this->jpegConOrientacion(8, 'MM')));

        // El APP1 dice medir 4 bytes: no alcanza para el bloque EXIF
        $largoRoto    = $this->jpegConOrientacion(6);
        $largoRoto[4] = "\x00";
        $largoRoto[5] = "\x04";

        $sinExif = imagecreatetruecolor(10, 10);
        ob_start();
        imagejpeg($sinExif);

        foreach ([
            'sin EXIF'         => ob_get_clean(),
            'vacío'            => '',
            'no es JPEG'       => "\x89PNG\r\n\x1a\n",
            'EXIF cortado'     => substr($this->jpegConOrientacion(6), 0, 30),
            'orden raro'       => str_replace("Exif\0\0II", "Exif\0\0XX", $this->jpegConOrientacion(6)),
            'valor 9'          => $this->jpegConOrientacion(9),
            'largo del APP1'   => $largoRoto,
        ] as $caso => $datos) {
            $this->assertSame(1, FotoProducto::orientacionExif($datos), $caso);
        }
    }

    public function test_un_png_transparente_conserva_la_transparencia(): void
    {
        $imagen = imagecreatetruecolor(200, 200);
        imagealphablending($imagen, false);
        imagesavealpha($imagen, true);
        imagefill($imagen, 0, 0, imagecolorallocatealpha($imagen, 0, 0, 0, 127));
        imagefilledrectangle($imagen, 50, 50, 149, 149, imagecolorallocatealpha($imagen, 0, 200, 0, 0));
        ob_start();
        imagepng($imagen);
        $png = ob_get_clean();

        $this->crearConFotos([UploadedFile::fake()->createWithContent('logo.png', $png)]);

        [, $webp] = $this->fotoGuardada();

        $this->assertGreaterThanOrEqual(120, $this->color($webp, 10, 10)['alpha'], 'La esquina debía seguir transparente');
        $this->assertSame(0, $this->color($webp, 100, 100)['alpha'], 'El centro debía seguir opaco');
    }

    public function test_al_editar_las_fotos_nuevas_tambien_se_convierten(): void
    {
        $producto = Producto::create([
            'categoria_id' => $this->categoria->id, 'nombre' => 'Cargador 20W', 'precio_venta' => 45000,
            'stock' => 3, 'stock_minimo' => 1, 'estado' => 'activo',
        ]);

        $this->patch(route('admin.productos.update', $producto), $this->datosFormulario([UploadedFile::fake()->image('nueva.jpg', 2000, 1500)]))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        [$path, , $info] = $this->fotoGuardada();

        $this->assertStringEndsWith('.webp', $path);
        $this->assertSame([1600, 1200], [$info[0], $info[1]]);
    }

    // ── Límites ──

    public function test_hasta_20_mb_se_acepta_y_mas_se_rechaza(): void
    {
        $this->crearConFotos([UploadedFile::fake()->image('foto.jpg', 1200, 900)->size(20000)]);

        [$path] = $this->fotoGuardada();
        $this->assertStringEndsWith('.webp', $path);

        $this->post(route('admin.productos.store'), $this->datosFormulario([UploadedFile::fake()->image('grande.jpg', 100, 100)->size(21000)], 'Otro'))
            ->assertSessionHasErrors(['imagenes.0' => 'La foto 1 pesa más de 20 MB.']);

        $this->assertSame(1, Producto::count());
    }

    public function test_el_aviso_dice_cual_foto_fallo(): void
    {
        $this->post(route('admin.productos.store'), $this->datosFormulario([
            UploadedFile::fake()->image('bien.jpg', 100, 100),
            UploadedFile::fake()->image('pesada.jpg', 100, 100)->size(21000),
            UploadedFile::fake()->create('notas.pdf', 10, 'application/pdf'),
        ]))->assertSessionHasErrors([
            'imagenes.1' => 'La foto 2 pesa más de 20 MB.',
            'imagenes.2' => 'La foto 3 no es una imagen válida (JPG, PNG, etc.).',
        ]);

        $this->assertSame(0, Producto::count());
    }

    public function test_mas_de_10_fotos_se_rechazan_sin_guardar_nada(): void
    {
        // Menos que max_file_uploads de PHP (20): si elige 25, PHP deja pasar 20 y este
        // aviso sale igual, en vez de descartar 5 sin decir nada
        $fotos = array_map(fn (int $i) => UploadedFile::fake()->image("foto{$i}.jpg", 20, 20), range(1, 11));

        $this->post(route('admin.productos.store'), $this->datosFormulario($fotos))
            ->assertSessionHasErrors(['imagenes' => 'Puedes subir hasta 10 fotos por vez.']);

        $this->assertSame(0, Producto::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_un_envio_mas_pesado_que_post_max_size_avisa_en_espanol(): void
    {
        if (ini_get('post_max_size') === '0') {
            $this->markTestSkipped('Este PHP no tiene post_max_size.');
        }

        // Así llega cuando las fotos juntas pasan de post_max_size: PHP descarta el
        // formulario entero y Laravel corta antes de la sesión (antes: "Content Too Large")
        $this->call('POST', route('admin.productos.store'), [], [], [], ['CONTENT_LENGTH' => 1024 ** 3])
            ->assertStatus(413)
            ->assertSee('Las fotos pesan demasiado')
            ->assertSee('no se guardó nada')
            ->assertDontSee('Content Too Large');

        $this->assertSame(0, Producto::count());
    }

    public function test_una_foto_de_demasiada_resolucion_se_rechaza_sin_abrirla(): void
    {
        // PNG que dice medir 8000×7000 (56 MP) y casi no pesa: solo el encabezado
        $bloque = fn (string $tipo, string $datos) => pack('N', strlen($datos)) . $tipo . $datos . pack('N', crc32($tipo . $datos));
        $png    = "\x89PNG\r\n\x1a\n" . $bloque('IHDR', pack('NNCCCCC', 8000, 7000, 8, 2, 0, 0, 0))
            . $bloque('IDAT', gzcompress('')) . $bloque('IEND', '');

        $this->post(route('admin.productos.store'), $this->datosFormulario([UploadedFile::fake()->createWithContent('enorme.png', $png)]))
            ->assertSessionHasErrors(['imagenes.0' => 'La foto 1 tiene demasiada resolución (máximo 50 megapíxeles; las del iPad tienen 12).']);

        $this->assertSame(0, Producto::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_una_foto_que_el_servidor_no_dejo_subir_avisa_en_espanol(): void
    {
        // Así llega cuando pesa más que upload_max_filesize del PHP del servidor
        $ruta = tempnam(sys_get_temp_dir(), 'foto');
        file_put_contents($ruta, 'x');
        $archivo = new UploadedFile($ruta, 'foto.jpg', 'image/jpeg', UPLOAD_ERR_INI_SIZE, true);

        $this->post(route('admin.productos.store'), $this->datosFormulario([$archivo]))
            ->assertSessionHasErrors(['imagenes.0' => 'La foto 1 no se pudo subir: pesa más de lo que permite el servidor.']);

        $this->assertSame(0, Producto::count());
    }

    public function test_una_foto_que_no_se_puede_convertir_se_guarda_tal_cual_y_lo_avisa(): void
    {
        Exceptions::fake();

        // Empieza como JPEG pero el resto es basura: GD no la puede leer
        $rara = "\xFF\xD8\xFF\xE0" . str_repeat("\x00basura", 200);

        $this->crearConFotos([UploadedFile::fake()->createWithContent('rara.jpg', $rara)])
            ->assertSessionHas('success', 'Producto creado con éxito. 1 foto quedó sin optimizar (se guardó con su peso original).');

        [$path, $contenido] = $this->fotoGuardada();

        $this->assertStringEndsWith('.jpg', $path);
        $this->assertSame($rara, $contenido);
        Exceptions::assertReported(RuntimeException::class);
    }

    // ── Hosting con poca memoria y sin ini_set ──

    public function test_sin_ini_set_y_con_128_mb_una_foto_del_ipad_se_convierte_igual(): void
    {
        // Antes: "Call to undefined function ini_set()" (PHP 8 quita las funciones
        // deshabilitadas) y ninguna foto se convertía
        $foto = UploadedFile::fake()->image('ipad.jpg', 4032, 3024);

        $this->assertSame('WebP de 1600×1200', $this->convertirEnHostingLimitado(file_get_contents($foto->getRealPath())));
    }

    public function test_si_no_alcanza_la_memoria_avisa_en_vez_de_romper_el_guardado(): void
    {
        // 30 MP: necesita unos 280 MB y en ese hosting hay 128. Antes se abría igual y PHP
        // moría con "Allowed memory size exhausted": error 500 y se perdía el guardado. Ahora
        // es una excepción, y el servicio guarda la foto original (ver el test anterior)
        $salida = $this->convertirEnHostingLimitado($this->pngLiso(6000, 5000));

        $this->assertStringStartsWith('RuntimeException: No alcanza la memoria para abrir la foto', $salida);
    }
}
