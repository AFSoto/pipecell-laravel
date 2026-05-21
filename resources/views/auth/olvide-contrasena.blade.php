<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PipeCell — Recuperar contraseña</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-[#0f172a] text-white">

    <div class="min-h-screen flex">

        {{-- Panel izquierdo - Marca (igual al login) --}}
        <div class="hidden lg:flex w-1/2 relative items-center justify-center px-20">
            <div class="absolute w-[500px] h-[500px] bg-blue-600/20 rounded-full blur-[120px]"></div>
            <div class="relative z-10 max-w-md">
                <img src="{{ asset('img/logo.png') }}" alt="PipeCell" class="h-30 mb-10">
                <h1 class="text-5xl font-bold leading-tight mb-6">
                    ¿Olvidaste tu<br>contraseña?
                </h1>
                <p class="text-gray-400 text-lg leading-relaxed mb-12">
                    Sin problema. Ingresa tu correo y te enviaremos un enlace seguro para restablecerla.
                </p>
                <div class="space-y-5">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center flex-shrink-0 border border-white/10">
                            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-white font-medium text-sm">Enlace seguro y temporal</p>
                            <p class="text-gray-500 text-xs">Válido por 60 minutos</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center flex-shrink-0 border border-white/10">
                            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-white font-medium text-sm">Un solo uso</p>
                            <p class="text-gray-500 text-xs">El enlace se invalida al usarlo</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Panel derecho - Formulario --}}
        <div class="w-full lg:w-1/2 flex items-center justify-center px-6">
            <div class="w-full max-w-md">

                {{-- Logo móvil --}}
                <div class="lg:hidden mb-10 text-center">
                    <img src="{{ asset('img/logo.png') }}" alt="PipeCell" class="h-20 mx-auto">
                </div>

                <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl p-8 shadow-2xl">

                    {{-- Ícono de la clave --}}
                    <div class="w-14 h-14 bg-blue-600/20 border border-blue-500/30 rounded-2xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                        </svg>
                    </div>

                    <h2 class="text-2xl font-semibold mb-2">Recuperar contraseña</h2>
                    <p class="text-gray-400 text-sm mb-8">
                        Ingresa tu correo y recibirás un enlace para crear una nueva contraseña.
                    </p>

                    {{-- Mensaje de éxito --}}
                    @if (session('status'))
                        <div class="flex items-start gap-3 bg-green-500/10 border border-green-500/20 text-green-400 text-sm rounded-xl px-4 py-4 mb-6">
                            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>{{ session('status') }}</span>
                        </div>
                    @endif

                    {{-- Errores de validación --}}
                    @if ($errors->any())
                        <div class="flex items-center gap-3 bg-red-500/10 border border-red-500/20 text-red-400 text-sm rounded-xl px-4 py-3 mb-6">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                        @csrf

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

                        <button
                            type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-500 text-white font-semibold py-3.5 rounded-xl transition-all duration-200 shadow-lg shadow-blue-600/25 hover:shadow-blue-500/40"
                        >
                            Enviar enlace de recuperación
                        </button>
                    </form>

                    <p class="text-center text-xs text-gray-600 mt-8">&copy; {{ date('Y') }} PipeCell</p>
                </div>

                <p class="text-center text-sm text-gray-500 mt-6">
                    <a href="{{ route('login') }}" class="hover:text-blue-400 transition inline-flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Volver a iniciar sesión
                    </a>
                </p>
            </div>
        </div>

    </div>

</body>
</html>
