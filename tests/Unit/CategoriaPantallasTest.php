<?php

namespace Tests\Unit;

use App\Models\Categoria;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Regla única para reconocer las categorías de pantallas (displays).
 */
class CategoriaPantallasTest extends TestCase
{
    public static function nombres(): array
    {
        return [
            'nombre del seeder'   => ['Repuestos - Pantallas', true],
            'en mayúsculas'       => ['PANTALLAS', true],
            'en singular'         => ['Pantalla', true],
            'con otras palabras'  => ['Pantallas y táctiles', true],
            'otra categoría'      => ['Repuestos - Baterías', false],
            'sinónimo no cuenta'  => ['Displays', false],
        ];
    }

    #[DataProvider('nombres')]
    public function test_reconoce_las_categorias_de_pantallas(string $nombre, bool $esperado): void
    {
        $this->assertSame($esperado, (new Categoria(['nombre' => $nombre]))->esDePantallas());
    }
}
