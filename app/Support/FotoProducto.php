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
 * Usa GD, que viene con PHP. La foto se abre completa en memoria (ver BYTES_POR_PIXEL):
 * por eso MAX_PIXELES limita la resolución (la validación lo revisa con reglaResolucion()
 * antes de llegar aquí), memory_limit sube a MEMORIA si está más bajo y, si aun así no
 * alcanza, no se abre (ver asegurarMemoria()).
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

    /**
     * Fotos por guardado. Menos que max_file_uploads de PHP (20 por defecto): si se eligen
     * más, PHP descarta las que pasan de ese número sin avisar; así la validación las ve y
     * avisa. También acota el tiempo: cada foto tarda de 1 a 4 s en convertirse.
     */
    public const MAX_FOTOS = 10;

    private const MEMORIA = '512M';

    /**
     * Memoria que gasta abrir y convertir la foto, en bytes por píxel según el formato, más
     * MEMORIA_FIJA y el doble del archivo. Medido con memory_get_peak_usage(true), que es lo
     * que cuenta memory_limit: GD guarda 4 bytes por píxel y el PNG además arma una copia
     * entera al leerlo (JPEG 12 MP = 68 MB, PNG transparente de 50 MP = 388 MB). Las cuentas
     * dan un poco más que lo medido, para no quedarse cortas.
     */
    private const BYTES_POR_PIXEL = [IMAGETYPE_JPEG => 4.5, IMAGETYPE_PNG => 8.5];

    /** Los demás formatos (WebP, GIF, BMP): unos 4 bytes por píxel medidos. */
    private const BYTES_POR_PIXEL_OTROS = 6;

    /** La foto achicada, la girada y el WebP: lo mismo para cualquier foto. */
    private const MEMORIA_FIJA = 24 * 1024 * 1024;

    /** Lo que se deja del envío (post_max_size) para el resto del formulario. */
    private const MARGEN_FORMULARIO = 1024 * 1024;

    /**
     * Devuelve la foto convertida a WebP.
     *
     * @throws RuntimeException si no se puede convertir (GD sin WebP, formato que GD no
     *                          lee, demasiados píxeles, no alcanza la memoria): quien llama
     *                          decide qué hacer.
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

        self::asegurarMemoria(self::memoriaNecesaria($ancho, $alto, $tipo, (int) @filesize($ruta)));
        self::reiniciarTiempo();

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
                    'La foto :position tiene demasiada resolución (máximo %d megapíxeles; las del iPad tienen 12).',
                    intdiv(self::MAX_PIXELES, 1_000_000)
                ));
            }
        };
    }

    /** Reglas de las fotos, las mismas al crear y al editar ('imagenes' e 'imagenes.*'). */
    public static function reglas(): array
    {
        return [
            'imagenes'   => ['nullable', 'array', 'max:' . self::MAX_FOTOS],
            'imagenes.*' => ['image', 'max:' . self::MAX_KB, self::reglaResolucion()],
        ];
    }

    /**
     * Mensajes de las reglas de fotos. Dicen cuál falló: :position es 1, 2, 3... en el
     * orden en que se eligieron (el de las miniaturas del formulario).
     */
    public static function mensajes(): array
    {
        return [
            'imagenes.max'        => 'Puedes subir hasta :max fotos por vez.',
            'imagenes.*.uploaded' => 'La foto :position no se pudo subir: pesa más de lo que permite el servidor.',
            'imagenes.*.image'    => 'La foto :position no es una imagen válida (JPG, PNG, etc.).',
            'imagenes.*.max'      => sprintf('La foto :position pesa más de %d MB.', intdiv(self::MAX_KB, 1024)),
        ];
    }

    /**
     * Límites que pone PHP antes de que las fotos lleguen a Laravel, para que el formulario
     * los revise antes de enviar: si el envío pasa de post_max_size, PHP descarta el
     * formulario entero; si una foto pasa de upload_max_filesize, llega vacía; si son más
     * que max_file_uploads, descarta las de sobra sin avisar.
     *
     * @return array{porFoto: int, total: int|null, cantidad: int}
     *   porFoto:  bytes por foto (el menor entre MAX_KB, upload_max_filesize y el total)
     *   total:    bytes de todas las fotos juntas (post_max_size menos lo del resto del
     *             formulario), o null si el servidor no pone límite
     *   cantidad: fotos por guardado (el menor entre MAX_FOTOS y max_file_uploads)
     */
    public static function limitesDelServidor(): array
    {
        $envio = self::enBytes((string) ini_get('post_max_size'));
        $total = $envio > 0 ? max(0, $envio - self::MARGEN_FORMULARIO) : null;

        $porFoto = self::MAX_KB * 1024;
        $subida  = self::enBytes((string) ini_get('upload_max_filesize'));
        if ($subida > 0) {
            $porFoto = min($porFoto, $subida);
        }
        if ($total !== null) {
            $porFoto = min($porFoto, $total);
        }

        $archivos = (int) ini_get('max_file_uploads');

        return [
            'porFoto'  => $porFoto,
            'total'    => $total,
            'cantidad' => $archivos > 0 ? min(self::MAX_FOTOS, $archivos) : self::MAX_FOTOS,
        ];
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

    /** Bytes que necesita abrir y convertir esta foto (ver BYTES_POR_PIXEL). */
    private static function memoriaNecesaria(int $ancho, int $alto, int $tipo, int $bytesArchivo): int
    {
        $porPixel = self::BYTES_POR_PIXEL[$tipo] ?? self::BYTES_POR_PIXEL_OTROS;

        return (int) ceil($ancho * $alto * $porPixel) + 2 * $bytesArchivo + self::MEMORIA_FIJA;
    }

    /**
     * Sube memory_limit a MEMORIA si está más bajo y revisa que alcance para la foto.
     *
     * Si no alcanza, lanza RuntimeException antes de abrirla: abrirla igual terminaba en
     * "Allowed memory size exhausted", un error fatal que no se puede atrapar y que perdía
     * el guardado entero. Pasa si el hosting no deja subir memory_limit: ini_set
     * deshabilitada o un tope propio.
     *
     * @throws RuntimeException
     */
    private static function asegurarMemoria(int $necesaria): void
    {
        // function_exists: PHP 8 quita del todo las funciones deshabilitadas
        // (disable_functions), y llamarla daba un Error que el @ no evita
        $limite = self::enBytes((string) ini_get('memory_limit'));

        if ($limite !== -1 && $limite < self::enBytes(self::MEMORIA) && function_exists('ini_set')) {
            @ini_set('memory_limit', self::MEMORIA);
            $limite = self::enBytes((string) ini_get('memory_limit'));
        }

        $usada = memory_get_usage(true);

        if ($limite !== -1 && $usada + $necesaria > $limite) {
            throw new RuntimeException(sprintf(
                'No alcanza la memoria para abrir la foto: necesita unos %d MB y quedan %d MB (memory_limit = %s).',
                intdiv($necesaria, 1048576),
                intdiv(max(0, $limite - $usada), 1048576),
                ini_get('memory_limit')
            ));
        }
    }

    /**
     * Reinicia el contador de max_execution_time para esta foto (set_time_limit() empieza a
     * contar de nuevo): 10 fotos grandes seguidas pueden pasar de 30 s, y el guardado se
     * cortaba a la mitad. Sin límite (0, como en la consola) no se toca, para no ponerle uno.
     */
    private static function reiniciarTiempo(): void
    {
        $segundos = (int) ini_get('max_execution_time');

        if ($segundos > 0 && function_exists('set_time_limit')) {
            @set_time_limit($segundos);
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
