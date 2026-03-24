{{--
    Layout principal de la aplicación.

    Plantilla base que comparten todas las páginas públicas.
    Usa Blade Components para el navbar y footer:
    <x-navbar /> busca el archivo components/navbar.blade.php
    <x-footer /> busca el archivo components/footer.blade.php

    Las vistas hijas usan @extends('layouts.app') para heredar esta estructura
    y @section('content') para inyectar su contenido donde está el @yield('content').
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{--
        El título de la pestaña del navegador.
        @yield('title') permite que cada vista ponga su propio título.
        Si no lo define, muestra "PipeCell" por defecto.
    --}}
    <title>@yield('title', 'PipeCell')</title>

    {{--
        @vite carga Tailwind CSS y JS compilados.
        En desarrollo usa el servidor de Vite (npm run dev).
        En producción usa los archivos compilados (npm run build).
    --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-800 antialiased">

    {{-- Navbar reutilizable --}}
    <x-navbar />

    {{-- Contenido de cada vista hija --}}
    @yield('content')

    {{-- Footer reutilizable --}}
    <x-footer />

    {{-- Sección opcional para scripts adicionales --}}
    @yield('scripts')

</body>
</html>
