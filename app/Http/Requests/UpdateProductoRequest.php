<?php

namespace App\Http\Requests;

use App\Models\Categoria;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida los datos para actualizar un producto existente.
 *
 * La diferencia clave con StoreProductoRequest está en la regla de 'codigo':
 * se ignora el registro actual en la validación unique para que el producto
 * pueda guardarse con su propio código sin que falle por "ya existe".
 */
class UpdateProductoRequest extends FormRequest
{
    /**
     * Los errores de editar van en su propia bolsa: la página de productos reabre el
     * modal de editar con ellos. En la bolsa por defecto los tomaría el modal de crear,
     * y al "corregir y guardar" se crearía un producto nuevo en vez de actualizar.
     */
    protected $errorBag = 'editarProducto';

    /** Sesión: qué producto se estaba editando cuando falló (la página reabre su modal). */
    public const PRODUCTO_CON_ERRORES = 'producto_con_errores';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Además de volver con los errores, deja en la sesión el producto de la ruta
     * (/productos/{producto}): así la página sabe cuál reabrir sin depender de un campo
     * del formulario, que podría faltar o venir cambiado.
     */
    protected function failedValidation(Validator $validator): void
    {
        $this->session()->flash(self::PRODUCTO_CON_ERRORES, $this->route('producto')->id);

        parent::failedValidation($validator);
    }

    public function rules(): array
    {
        // Obtenemos el ID del producto que viene en la ruta (route model binding)
        // para ignorarlo en la validación unique del código
        $productoId = $this->route('producto')->id;

        return [
            'categoria_id'      => ['required', 'exists:categorias,id'],
            'tipo_producto_id'  => ['nullable', 'exists:tipo_productos,id'],
            'codigo'            => ['nullable', 'string', 'max:50', "unique:productos,codigo,{$productoId}"],
            'nombre'            => ['required', 'string', 'max:150', $this->reglaNombreUnico($productoId)],
            'descripcion'       => ['nullable', 'string'],
            'marco'             => ['nullable', 'boolean', $this->reglaMarco()],
            'marca'             => ['nullable', 'string', 'max:100', $this->reglaExclusivaPantalla('La marca es obligatoria para pantallas.')],
            'referencia'        => ['nullable', 'string', 'max:100', $this->reglaExclusivaPantalla('La referencia es obligatoria para pantallas.')],
            // Opcional: vacío se guarda como NULL ("sin precio de compra"), que no es lo mismo que 0
            'precio_compra'     => ['nullable', 'numeric', 'min:0'],
            'precio_venta'      => ['required', 'numeric', 'min:0'],
            'stock'             => ['required', 'integer', 'min:0'],
            'stock_minimo'      => ['nullable', 'integer', 'min:0'],
            'imagenes'          => ['nullable', 'array'],
            'imagenes.*'        => ['image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'categoria_id.required'     => 'Debes seleccionar una categoría.',
            'categoria_id.exists'       => 'La categoría seleccionada no existe.',
            'tipo_producto_id.exists'   => 'El tipo de producto seleccionado no existe.',
            'codigo.unique'             => 'Este código ya está en uso por otro producto.',
            'nombre.required'           => 'El nombre del producto es obligatorio.',
            'precio_compra.numeric'     => 'El precio de compra debe ser un número.',
            'precio_compra.min'         => 'El precio de compra no puede ser negativo.',
            'precio_venta.required'     => 'El precio de venta es obligatorio.',
            'precio_venta.min'          => 'El precio de venta no puede ser negativo.',
            'stock.required'            => 'El stock es obligatorio.',
            'stock.min'                 => 'El stock no puede ser negativo.',
            'imagenes.*.image'          => 'Cada archivo debe ser una imagen válida.',
            'imagenes.*.max'            => 'Cada imagen no puede superar los 2 MB.',
        ];
    }

    private function reglaNombreUnico(int $productoId): \Closure
    {
        return function ($attribute, $value, $fail) use ($productoId) {
            $query = \App\Models\Producto::where('categoria_id', $this->categoria_id)
                ->where('nombre', $value)
                ->where('estado', 'activo')
                ->where('id', '!=', $productoId);

            if ($this->tipo_producto_id) {
                $query->where('tipo_producto_id', $this->tipo_producto_id);
            } else {
                $query->whereNull('tipo_producto_id');
            }

            if (is_null($this->marco)) {
                $query->whereNull('marco');
            } else {
                $query->where('marco', (bool) $this->marco);
            }

            if ($query->exists()) {
                $fail('Ya existe un producto con ese nombre, categoría, tipo y configuración de marco.');
            }
        };
    }

    private function reglaMarco(): \Closure
    {
        return function ($attribute, $value, $fail) {
            $categoria = Categoria::find($this->categoria_id);
            if ($categoria?->esDePantallas() && is_null($value)) {
                $fail('Debes indicar si la pantalla viene con marco o no.');
            }
        };
    }

    private function reglaExclusivaPantalla(string $mensaje): \Closure
    {
        return function ($attribute, $value, $fail) use ($mensaje) {
            $categoria = Categoria::find($this->categoria_id);
            if ($categoria?->esDePantallas() && empty($value)) {
                $fail($mensaje);
            }
        };
    }
}
