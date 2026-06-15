<?php

namespace App\Services;

use App\Models\Categoria;

/**
 * Service para la lógica de negocio de categorías.
 *
 * Centraliza todas las operaciones sobre Categoria para que el
 * controller no tenga lógica de negocio interna.
 */
class CategoriaService
{
    /**
     * Crea una nueva categoría.
     *
     * La creación es simple (sin transacción) porque no hay operaciones
     * secundarias — solo se inserta el registro.
     *
     * @param array $datos Datos validados del StoreCategoriaRequest
     */
    public function crear(array $datos): Categoria
    {
        return Categoria::create([
            'nombre'      => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
            'estado'      => 'activo', // siempre activa al crear
        ]);
    }

    /**
     * Actualiza los datos de una categoría existente.
     *
     * @param Categoria $categoria La instancia a actualizar (route model binding)
     * @param array     $datos     Datos validados del UpdateCategoriaRequest
     */
    public function actualizar(Categoria $categoria, array $datos): Categoria
    {
        $categoria->update([
            'nombre'      => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
        ]);

        // fresh() recarga el modelo desde la BD para que los datos retornados
        // reflejen el estado exacto después del update
        return $categoria->fresh();
    }

    /**
     * Desactiva una categoría (soft delete por estado).
     *
     * No se usa SoftDeletes de Laravel porque preferimos un estado explícito
     * que el operario pueda ver y activar de nuevo desde el panel.
     *
     * Regla de negocio: no se puede desactivar si tiene productos activos,
     * porque eso dejaría productos huérfanos sin categoría visible.
     *
     * @param Categoria $categoria
     * @throws \RuntimeException Si la categoría tiene productos activos
     */
    public function eliminar(Categoria $categoria): void
    {
        // Contamos productos activos para aplicar la regla de negocio
        $productosActivos = $categoria->productos()->where('estado', 'activo')->count();

        if ($productosActivos > 0) {
            throw new \RuntimeException(
                "No se puede desactivar la categoría \"{$categoria->nombre}\" "
                . "porque tiene {$productosActivos} producto(s) activo(s). "
                . "Desactiva primero los productos o cámbialos de categoría."
            );
        }

        $categoria->update(['estado' => 'inactivo']);
    }
}
