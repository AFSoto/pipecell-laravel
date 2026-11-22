{{--
    Ícono de la pestaña: el logo de PipeCell.

    public/favicon.ico (16, 32 y 48 px) y public/apple-touch-icon.png (180 px, para el
    iPad y el iPhone) salieron de public/img/logo-transparent.png. Si cambia el logo,
    hay que volver a generar los dos y subir el número de ?v=: así los navegadores
    dejan el ícono que tienen guardado y piden el nuevo.
--}}
<link rel="icon" href="{{ asset('favicon.ico') }}?v=1">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v=1">
