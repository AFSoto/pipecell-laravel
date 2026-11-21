<?php

namespace App\Http\Requests\Concerns;

use App\Models\Producto;
use App\Models\Reparacion;

/**
 * Reglas compartidas para el display usado en una reparación
 * (StoreReparacionRequest y UpdateReparacionRequest).
 *
 * Campos:
 *   - usa_display      → casilla "¿Se usó display?"
 *   - display_id       → producto de una categoría de pantallas
 *   - display_cantidad → unidades usadas (1 por defecto)
 *
 * Esta validación es la primera línea de defensa. La segunda es
 * ProductoService::ajustarStock(), que vuelve a verificar el stock con
 * lockForUpdate() dentro de la transacción.
 */
trait ValidaDisplayUsado
{
    /**
     * Si "¿Se usó display?" no está marcado, se ignora lo que haya quedado
     * seleccionado: display_id y display_cantidad pasan a null.
     */
    protected function normalizarCamposDisplay(): void
    {
        $usaDisplay = $this->boolean('usa_display');

        $this->merge([
            'usa_display'      => $usaDisplay,
            'display_id'       => $usaDisplay ? $this->input('display_id') : null,
            'display_cantidad' => $usaDisplay ? ($this->input('display_cantidad') ?: 1) : null,
        ]);
    }

    /**
     * @param  Reparacion|null  $reparacion  La reparación que se edita (null al crear).
     *                                       Lo que ya tiene asignado vuelve a contar como disponible.
     */
    protected function reglasDisplay(?Reparacion $reparacion = null): array
    {
        return [
            'usa_display'      => ['boolean'],
            // bail: si no es un número, no se sigue a la regla que consulta el producto
            // (con una lista, Producto::find() devolvía una colección y daba un 500)
            'display_id'       => ['bail', 'nullable', 'required_if_accepted:usa_display', 'integer', $this->reglaDisplayDisponible($reparacion)],
            'display_cantidad' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }

    protected function mensajesDisplay(): array
    {
        return [
            'display_id.required_if_accepted' => 'Selecciona el display que se usó.',
            'display_id.integer'              => 'El display seleccionado no es válido.',
            'display_cantidad.integer'        => 'La cantidad de displays debe ser un número entero.',
            'display_cantidad.min'            => 'La cantidad de displays debe ser al menos 1.',
            'display_cantidad.max'            => 'La cantidad de displays no puede ser mayor a 20.',
        ];
    }

    private function reglaDisplayDisponible(?Reparacion $reparacion): \Closure
    {
        return function (string $_attribute, mixed $value, \Closure $fail) use ($reparacion) {
            if (! $value) {
                return;
            }

            $producto = Producto::with('categoria')->find($value);

            if (! $producto) {
                $fail('El display seleccionado no existe.');

                return;
            }

            // Unidades de este mismo producto que la reparación ya tiene asignadas
            $asignadas = (int) ($reparacion?->repuestos()->where('producto_id', $producto->id)->value('cantidad') ?? 0);

            // Un display nuevo debe estar activo y ser de pantallas. Si es el mismo
            // que ya tenía, se acepta aunque después lo hayan desactivado.
            if ($asignadas === 0 && ($producto->estado !== 'activo' || ! $producto->esPantalla())) {
                $fail('El display seleccionado no está disponible.');

                return;
            }

            $disponible = $producto->stock + $asignadas;
            $cantidad   = (int) ($this->input('display_cantidad') ?: 1);

            if ($cantidad > $disponible) {
                $fail("No hay suficiente stock de «{$producto->nombre}». Disponible: {$disponible}.");
            }
        };
    }
}
