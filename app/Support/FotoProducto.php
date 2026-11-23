<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Convierte la foto de un producto a WebP liviano antes de guardarla.
 *
 * Una foto del iPad (12 MP, de 2 a 5 MB) queda en unos 150-300 KB:
 *   1. Se reduce a LADO_MAXIMO px por el lado más largo (nunca se agranda). Las tarjetas
 *      la muestran a unos 300 px, así que alcanza de sobra para pantallas retina.
 *   2. Se gira según la orientación EXIF: el iPad guarda las fotos verticales "acostadas"
 *      con una marca que dice cómo girarlas. El navegador la respeta, pero al convertir se
 *      perdería y la foto saldría de lado.
 *   3. Se guarda en WebP con CALIDAD, conservando la transparencia de los PNG.
 * Al volver a codificar la foto se pierden los metadatos, incluida la ubicación GPS.
 *
 * Usa GD, que viene con PHP. La foto se abre completa en memoria, unos 4 bytes por píxel
 * (12 MP ≈ 64 MB): por eso MAX_PIXELES limita la resolución (la validación lo revisa con
 * reglaResolucion() antes de llegar aquí) y, mientras se convierte, memory_limit sube a
 * MEMORIA si está más bajo.
 */
final class FotoProducto
{
    /** Lado más largo de la foto guardada, en píxeles. */
    public const LADO_MAXIMO = 1600;

    /** Calidad WebP (0-100). */
    public const CALIDAD = 80;

    /** Peso máximo de cada foto que se sube, en KB (20 MB): lo guardado queda liviano igual. */
    public const MAX_KB = 20480;

    /** Resolución máxima (50 MP). Las fotos del iPad tienen 12 MP. */
    public const MAX_PIXELES = 50_000_000;

    private const MEMORIA = '512M';

    /**
     * Devuelve la foto convertida a WebP.
     *
     * @throws RuntimeException si no se puede convertir (GD sin WebP, formato que GD no
     *                          lee, demasiados píxeles): quien llama decide qué hacer.
     */
    public static function aWebp(string $ruta): string
    {
        if (! function_exists('imagewebp')) {
            throw new RuntimeException('El GD de este PHP no tiene soporte para WebP.');
        }

        $info = @getimagesize($ruta);

        if ($info === false) {
            throw new RuntimeException('No es una imagen que GD pueda leer.');
        }

        [$ancho, $alto, $tipo] = $info;

        if ($ancho * $alto > self::MAX_PIXELES) {
            throw new RuntimeException("La foto tiene demasiados píxeles ({$ancho}×{$alto}).");
        }

        self::asegurarMemoria();

        $contenido = file_get_contents($ruta);

        // @: un JPEG con un error menor igual se puede leer; si no se puede, devuelve false
        $imagen = @imagecreatefromstring($contenido);

        if ($imagen === false) {
            throw new RuntimeException('GD no pudo leer la foto.');
        }

        if (! imageistruecolor($imagen)) {
            imagepalettetotruecolor($imagen);
        }

        // Primero se achica y después se gira: girar la foto chica gasta mucho menos memoria
        $imagen = self::achicar($imagen, $ancho, $alto);

        if ($tipo === IMAGETYPE_JPEG) {
            $imagen = self::orientar($imagen, self::orientacionExif($contenido));
        }

        // Sin esto, el WebP pierde la transparencia de los PNG
        imagealphablending($imagen, false);
        imagesavealpha($imagen, true);

        ob_start();

        try {
            $ok = imagewebp($imagen, null, self::CALIDAD);
        } finally {
            $webp = (string) ob_get_clean();
            imagedestroy($imagen);
        }

        if (! $ok || $webp === '') {
            throw new RuntimeException('GD no pudo guardar la foto en WebP.');
        }

        return $webp;
    }

    /**
     * Regla de validación para cada foto: rechaza las de más de MAX_PIXELES sin abrirlas
     * (getimagesize() lee solo el encabezado). Un archivo chico puede ser una imagen enorme:
     * un PNG de un solo color de 20.000×20.000 pesa casi nada y necesitaría 1,6 GB al abrirlo.
     */
    public static function reglaResolucion(): \Closure
    {
        return function (string $_attribute, mixed $value, \Closure $fail) {
            if (! $value instanceof UploadedFile || ! $value->isValid()) {
                return;
            }

            $info = @getimagesize($value->getRealPath());

            if ($info !== false && $info[0] * $info[1] > self::MAX_PIXELES) {
                $fail(sprintf(
                    'La foto tiene demasiada resolución (máximo %d megapíxeles; las del iPad tienen 12).',
                    intdiv(self::MAX_PIXELES, 1_000_000)
                ));
            }
        };
    }

    /**
     * Orientación EXIF de un JPEG: de 1 (derecha) a 8. Lector propio y pequeño para no
     * depender de la extensión exif del servidor. Ante cualquier dato raro devuelve 1.
     */
    public static function orientacionExif(string $jpeg): int
    {
        $largo = strlen($jpeg);

        if ($largo < 4 || substr($jpeg, 0, 2) !== "\xFF\xD8") {
            return 1;
        }

        // Recorre los segmentos del encabezado hasta el APP1 que empieza con "Exif"
        $pos = 2;

        while ($pos + 4 <= $largo && $jpeg[$pos] === "\xFF") {
            $marcador = ord($jpeg[$pos + 1]);

            // Empiezan los datos de la imagen (SOS) o termina el archivo (EOI): no hay EXIF
            if ($marcador === 0xDA || $marcador === 0xD9) {
                break;
            }

            $tamano = (ord($jpeg[$pos + 2]) << 8) | ord($jpeg[$pos + 3]);

            if ($marcador === 0xE1 && substr($jpeg, $pos + 4, 6) === "Exif\0\0") {
                return self::orientacionTiff(substr($jpeg, $pos + 10, max(0, $tamano - 8)));
            }

            $pos += 2 + $tamano;
        }

        return 1;
    }

    /** Busca la etiqueta Orientation (0x0112) en el primer directorio del bloque TIFF del EXIF. */
    private static function orientacionTiff(string $tiff): int
    {
        $orden = substr($tiff, 0, 2);

        if ($orden !== 'II' && $orden !== 'MM') {
            return 1;
        }

        // II = little endian (v, V); MM = big endian (n, N). null si el dato no alcanza
        $leer = fn (string $formato, int $pos, int $bytes) => $pos >= 0 && strlen($tiff) >= $pos + $bytes
            ? unpack($formato, $tiff, $pos)[1]
            : null;
        $u16 = fn (int $pos) => $leer($orden === 'II' ? 'v' : 'n', $pos, 2);
        $u32 = fn (int $pos) => $leer($orden === 'II' ? 'V' : 'N', $pos, 4);

        $directorio = $u32(4);
        $entradas   = $directorio === null ? null : $u16($directorio);

        for ($i = 0; $i < ($entradas ?? 0); $i++) {
            $entrada  = $directorio + 2 + $i * 12;
            $etiqueta = $u16($entrada);

            if ($etiqueta === null) {
                return 1;
            }

            if ($etiqueta === 0x0112) {
                $valor = $u16($entrada + 8);

                return $valor !== null && $valor >= 1 && $valor <= 8 ? $valor : 1;
            }
        }

        return 1;
    }

    /** Reduce la foto a LADO_MAXIMO por el lado más largo; si ya es más chica, la deja igual. */
    private static function achicar(\GdImage $imagen, int $ancho, int $alto): \GdImage
    {
        $escala = self::LADO_MAXIMO / max($ancho, $alto);

        if ($escala >= 1) {
            return $imagen;
        }

        $nuevoAncho = max(1, (int) round($ancho * $escala));
        $nuevoAlto  = max(1, (int) round($alto * $escala));

        $chica = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
        imagealphablending($chica, false);
        imagesavealpha($chica, true);
        imagefill($chica, 0, 0, imagecolorallocatealpha($chica, 0, 0, 0, 127));
        imagecopyresampled($chica, $imagen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);
        imagedestroy($imagen);

        return $chica;
    }

    /**
     * Gira o voltea la foto según la orientación EXIF, para que quede como se ve en el iPad.
     * imagerotate() gira en sentido contrario a las agujas del reloj: -90 = 90° a la derecha.
     */
    private static function orientar(\GdImage $imagen, int $orientacion): \GdImage
    {
        $girar = function (\GdImage $imagen, int $grados): \GdImage {
            $girada = imagerotate($imagen, $grados, 0);
            imagedestroy($imagen);

            return $girada;
        };

        switch ($orientacion) {
            case 2: imageflip($imagen, IMG_FLIP_HORIZONTAL); break;
            case 3: $imagen = $girar($imagen, 180); break;
            case 4: imageflip($imagen, IMG_FLIP_VERTICAL); break;
            case 5: $imagen = $girar($imagen, -90); imageflip($imagen, IMG_FLIP_HORIZONTAL); break;
            case 6: $imagen = $girar($imagen, -90); break;
            case 7: $imagen = $girar($imagen, 90); imageflip($imagen, IMG_FLIP_HORIZONTAL); break;
            case 8: $imagen = $girar($imagen, 90); break;
        }

        return $imagen;
    }

    /** Sube memory_limit a MEMORIA mientras se convierte, si está más bajo (y el hosting lo permite). */
    private static function asegurarMemoria(): void
    {
        $actual = self::enBytes((string) ini_get('memory_limit'));

        if ($actual !== -1 && $actual < self::enBytes(self::MEMORIA)) {
            @ini_set('memory_limit', self::MEMORIA);
        }
    }

    /** "512M" → bytes; "-1" (sin límite) → -1. */
    private static function enBytes(string $valor): int
    {
        $valor = trim($valor);

        if ($valor === '' || $valor === '-1') {
            return -1;
        }

        $numero = (int) $valor;

        return match (strtolower(substr($valor, -1))) {
            'g'     => $numero * 1024 ** 3,
            'm'     => $numero * 1024 ** 2,
            'k'     => $numero * 1024,
            default => $numero,
        };
    }
}
