{{--
    Vista de Login — Diseño oscuro profesional a dos columnas.

    Izquierda: panel de marca con logo, título y features.
    Derecha: formulario con efecto glass sobre fondo oscuro.

    No usa layout porque es una página independiente.
    En móvil solo se muestra el formulario (el panel se oculta).
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PipeCell — Acceso</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-[#0f172a] text-white">

    <div class="min-h-screen flex">

        {{-- ============================== --}}
        {{-- PANEL IZQUIERDO - Marca         --}}
        {{-- Se oculta en móvil (hidden lg)  --}}
        {{-- ============================== --}}
        <div class="hidden lg:flex w-1/2 relative items-center justify-center px-20">

            {{-- Glow decorativo de fondo --}}
            <div class="absolute w-[500px] h-[500px] bg-blue-600/20 rounded-full blur-[120px]"></div>

            <div class="relative z-10 max-w-md">

                {{-- Logo del negocio --}}
                <img src="{{ asset('img/logo.png') }}" alt="PipeCell" class="h-30 mb-10">

                {{-- Título impactante --}}
                <h1 class="text-5xl font-bold leading-tight mb-6">
                    Control total.<br>
                    Sin esfuerzo.
                </h1>

                {{-- Descripción --}}
                <p class="text-gray-400 text-lg leading-relaxed mb-12">
                    Administra reparaciones, inventario y flujo de trabajo desde un solo lugar.
                </p>

                {{-- Features del sistema --}}
                <div class="space-y-5">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center flex-shrink-0 border border-white/10">
                            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.42 15.17l-5.1-5.1a2.5 2.5 0 010-3.54l.71-.7a2.5 2.5 0 013.54 0l5.1 5.1m-4.25 4.24l4.24-4.24"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-white font-medium text-sm">Gestión de reparaciones</p>
                            <p class="text-gray-500 text-xs">Registro, seguimiento y entrega</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center flex-shrink-0 border border-white/10">
                            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-white font-medium text-sm">Control de cajas</p>
                            <p class="text-gray-500 text-xs">Organización física del local</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center flex-shrink-0 border border-white/10">
                            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-white font-medium text-sm">Dashboard de métricas</p>
                            <p class="text-gray-500 text-xs">Ventas diarias, mensuales y más</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================== --}}
        {{-- PANEL DERECHO - Formulario      --}}
        {{-- Visible siempre                 --}}
        {{-- ============================== --}}
        <div class="w-full lg:w-1/2 flex items-center justify-center px-6">

            <div class="w-full max-w-md">

                {{-- Logo solo visible en móvil (el de escritorio está en el panel izquierdo) --}}
                <div class="lg:hidden mb-10 text-center">
                    <img src="{{ asset('img/logo.png') }}" alt="PipeCell" class="h-20 mx-auto">
                </div>

                {{--
                    Card con efecto glass.
                    bg-white/5 = fondo blanco al 5% de opacidad
                    backdrop-blur-xl = desenfoque del fondo (glassmorphism)
                    border-white/10 = borde blanco sutil
                --}}
                <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl p-8 shadow-2xl">

                    <h2 class="text-2xl font-semibold mb-2">Iniciar sesión</h2>
                    <p class="text-gray-400 text-sm mb-8">Accede a tu panel de control</p>

                    {{--
                        Mensaje de error.
                        Aparece cuando el controlador manda withErrors().
                        Se muestra en rojo sutil para no romper el diseño oscuro.
                    --}}
                    {{-- Mensaje de éxito tras restablecer contraseña --}}
                    @if (session('status'))
                        <div class="flex items-center gap-3 bg-green-500/10 border border-green-500/20 text-green-400 text-sm rounded-xl px-4 py-3 mb-6">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="flex items-center gap-3 bg-red-500/10 border border-red-500/20 text-red-400 text-sm rounded-xl px-4 py-3 mb-6">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ $errors->first('email') }}
                        </div>
                    @endif

                    {{--
                        Formulario de login.
                        method="POST" envía los datos de forma segura.
                        @csrf genera el token de seguridad obligatorio en Laravel.
                    --}}
                    <form method="POST" action="{{ route('login') }}" class="space-y-6">
                        @csrf

                        {{-- Campo Email --}}
                        <div>
                            <label for="email" class="text-sm text-gray-400 mb-2 block">Correo electrónico</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    autofocus
                                    placeholder="admin@pipecell.com"
                                    class="w-full pl-12 pr-4 py-3.5 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition"
                                >
                            </div>
                        </div>

                        {{-- Campo Password --}}
                        <div>
                            <label for="password" class="text-sm text-gray-400 mb-2 block">Contraseña</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                </div>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    required
                                    placeholder="••••••••"
                                    class="w-full pl-12 pr-4 py-3.5 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition"
                                >
                            </div>
                        </div>

                        {{-- Olvidé mi contraseña --}}
                        <div class="flex justify-end">
                            <a href="{{ route('password.request') }}"
                               class="text-sm text-gray-500 hover:text-blue-400 transition">
                                ¿Olvidaste tu contraseña?
                            </a>
                        </div>

                        {{-- Botón de login --}}
                        <button
                            type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-500 text-white font-semibold py-3.5 rounded-xl transition-all duration-200 shadow-lg shadow-blue-600/25 hover:shadow-blue-500/40"
                        >
                            Iniciar sesión
                        </button>
                    </form>

                    {{-- Footer del card --}}
                    <p class="text-center text-xs text-gray-600 mt-8">
                        &copy; {{ date('Y') }} PipeCell
                    </p>
                </div>

                {{-- Link para volver a la landing --}}
                <p class="text-center text-sm text-gray-500 mt-6">
                    <a href="{{ route('landing') }}" class="hover:text-blue-400 transition inline-flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Volver al inicio
                    </a>
                </p>
            </div>
        </div>

    </div>

</body>
</html>
