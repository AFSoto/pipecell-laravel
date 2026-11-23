{{--
    Envío más pesado que post_max_size de PHP ("413 Content Too Large"): PHP descarta el
    formulario entero y Laravel corta antes de la sesión y del login (ValidatePostSize).
    Por eso esta página no usa el layout del panel.

    Lo normal es que no se vea: el formulario de productos suma las fotos antes de enviar
    y avisa ahí mismo (ver productos/index, problemasDeFotos).
--}}
@php
    $total = \App\Support\FotoProducto::limitesDelServidor()['total'];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Las fotos pesan demasiado</title>
    <style>
        body {
            margin: 0; min-height: 100vh; box-sizing: border-box; padding: 1.5rem;
            display: flex; align-items: center; justify-content: center;
            background: #f3f4f6; color: #374151;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        main { max-width: 28rem; background: #fff; border-radius: 1rem; padding: 2rem; box-shadow: 0 10px 25px rgb(0 0 0 / .08); }
        h1 { margin: 0 0 .75rem; font-size: 1.125rem; color: #111827; }
        p { margin: 0 0 .75rem; font-size: .9375rem; line-height: 1.5; }
        .acciones { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.25rem; }
        .acciones > * { font: inherit; font-size: .875rem; font-weight: 600; padding: .625rem 1.25rem; border-radius: .75rem; cursor: pointer; text-decoration: none; }
        button { border: 0; background: #2563eb; color: #fff; }
        a { background: #f3f4f6; color: #374151; }
    </style>
</head>
<body>
    <main>
        <h1>Las fotos pesan demasiado</h1>
        <p>Lo que se envió pesa más de lo que acepta el servidor{{ $total ? ' (hasta ' . intdiv($total, 1048576) . ' MB por envío)' : '' }}, así que no se guardó nada.</p>
        <p>Vuelve atrás y sube menos fotos a la vez: el resto lo puedes agregar después, editando el producto.</p>
        <div class="acciones">
            <button type="button" onclick="history.back()">Volver</button>
            <a href="{{ route('admin.productos.index') }}">Ir a Productos</a>
        </div>
    </main>
</body>
</html>
