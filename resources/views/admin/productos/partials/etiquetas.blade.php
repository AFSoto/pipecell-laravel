{{--
    Etiquetas del producto: marca, tipo (Original, Genérico...) y marco (solo pantallas:
    true = con marco, false = sin marco, null = no aplica). Solo las etiquetas:
    la tarjeta y la fila ponen el contenedor.

    La marca distingue productos con el mismo nombre (dos "Batería iPhone 14" de distinta
    marca). En pantallas no sale: ya va en el nombre (ver Producto::marcaVisible()).
--}}
@if ($producto->marca_visible !== null)
    <span class="inline-flex items-center text-xs font-medium px-2 py-0.5 bg-gray-100 text-gray-700 rounded-full" title="Marca">
        {{ $producto->marca_visible }}
    </span>
@endif
@if ($producto->tipoProducto)
    <span class="inline-flex items-center text-xs font-medium px-2 py-0.5 bg-blue-50 text-blue-700 rounded-full">
        {{ $producto->tipoProducto->nombre }}
    </span>
@endif
@if ($producto->marco === true)
    <span class="inline-flex items-center text-xs font-semibold px-2 py-0.5 bg-violet-600 text-white rounded-full">
        Con marco
    </span>
@elseif ($producto->marco === false)
    <span class="inline-flex items-center text-xs font-semibold px-2 py-0.5 bg-slate-400 text-white rounded-full">
        Sin marco
    </span>
@endif
