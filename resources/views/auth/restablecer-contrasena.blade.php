<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PipeCell — Nueva contraseña</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-[#0f172a] text-white">

    <div class="min-h-screen flex">

        {{-- Panel izquierdo - Marca --}}
        <div class="hidden lg:flex w-1/2 relative items-center justify-center px-20">
            <div class="absolute w-[500px] h-[500px] bg-blue-600/20 rounded-full blur-[120px]"></div>
            <div class="relative z-10 max-w-md">
                <img src="{{ asset('img/logo.png') }}" alt="PipeCell" class="h-30 mb-10">
                <h1 class="text-5xl font-bold leading-tight mb-6">
                    Crea tu nueva<br>contraseña.
                </h1>
                <p class="text-gray-400 text-lg leading-relaxed mb-12">
                    Elige una contraseña segura. Una vez guardada, el enlace quedará inválido de forma permanente.
                </p>
                <div class="space-y-5">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center flex-shrink-0 border border-white/10">
                            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-white font-medium text-sm">Mínimo 8 caracteres</p>
                            <p class="text-gray-500 text-xs">Usa letras, números y símbolos</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center flex-shrink-0 border border-white/10">
                            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-white font-medium text-sm">Cambio inmediato</p>
                            <p class="text-gray-500 text-xs">Tu sesión anterior no se ve afectada</p>
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

                    {{-- Ícono --}}
                    <div class="w-14 h-14 bg-blue-600/20 border border-blue-500/30 rounded-2xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>

                    <h2 class="text-2xl font-semibold mb-2">Nueva contraseña</h2>
                    <p class="text-gray-400 text-sm mb-8">
                        Crea una contraseña segura para tu cuenta en PipeCell.
                    </p>

                    {{-- Errores --}}
                    @if ($errors->any())
                        <div class="flex items-start gap-3 bg-red-500/10 border border-red-500/20 text-red-400 text-sm rounded-xl px-4 py-3 mb-6">
                            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                @foreach ($errors->all() as $error)
                                    <p>{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.update') }}" class="space-y-5" id="form-reset">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <input type="hidden" name="email" value="{{ $email }}">

                        {{-- Email (solo lectura, visual) --}}
                        <div>
                            <label class="text-sm text-gray-400 mb-2 block">Correo electrónico</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    value="{{ $email }}"
                                    readonly
                                    class="w-full pl-12 pr-4 py-3.5 bg-white/3 border border-white/5 rounded-xl text-gray-500 outline-none cursor-not-allowed"
                                >
                            </div>
                        </div>

                        {{-- Nueva contraseña --}}
                        <div>
                            <label for="password" class="text-sm text-gray-400 mb-2 block">Nueva contraseña</label>
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
                                    minlength="8"
                                    placeholder="Mínimo 8 caracteres"
                                    class="w-full pl-12 pr-12 py-3.5 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition"
                                >
                                <button type="button" onclick="togglePassword('password', this)"
                                        class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-500 hover:text-gray-300 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-icon="eye">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </div>
                            {{-- Indicador de fortaleza --}}
                            <div class="mt-2 h-1 rounded-full bg-white/10 overflow-hidden">
                                <div id="strength-bar" class="h-full rounded-full transition-all duration-300 w-0"></div>
                            </div>
                            <p id="strength-label" class="text-xs text-gray-600 mt-1"></p>
                        </div>

                        {{-- Confirmar contraseña --}}
                        <div>
                            <label for="password_confirmation" class="text-sm text-gray-400 mb-2 block">Confirmar contraseña</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                </div>
                                <input
                                    type="password"
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    required
                                    placeholder="Repite la contraseña"
                                    class="w-full pl-12 pr-12 py-3.5 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition"
                                    id="password_confirmation"
                                >
                                <button type="button" onclick="togglePassword('password_confirmation', this)"
                                        class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-500 hover:text-gray-300 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </div>
                            <p id="match-label" class="text-xs mt-1 hidden"></p>
                        </div>

                        <button
                            type="submit"
                            id="btn-submit"
                            class="w-full bg-blue-600 hover:bg-blue-500 text-white font-semibold py-3.5 rounded-xl transition-all duration-200 shadow-lg shadow-blue-600/25 hover:shadow-blue-500/40"
                        >
                            Guardar nueva contraseña
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

    <script>
        const passwordInput      = document.getElementById('password');
        const confirmInput       = document.getElementById('password_confirmation');
        const strengthBar        = document.getElementById('strength-bar');
        const strengthLabel      = document.getElementById('strength-label');
        const matchLabel         = document.getElementById('match-label');

        function togglePassword(fieldId, btn) {
            const field = document.getElementById(fieldId);
            const isText = field.type === 'text';
            field.type = isText ? 'password' : 'text';
            btn.querySelector('svg').style.opacity = isText ? '1' : '0.5';
        }

        function calcularFortaleza(pwd) {
            let score = 0;
            if (pwd.length >= 8)  score++;
            if (pwd.length >= 12) score++;
            if (/[A-Z]/.test(pwd)) score++;
            if (/[0-9]/.test(pwd)) score++;
            if (/[^A-Za-z0-9]/.test(pwd)) score++;
            return score;
        }

        passwordInput.addEventListener('input', function () {
            const score = calcularFortaleza(this.value);
            const niveles = [
                { label: '',              color: '',                 width: '0%'   },
                { label: 'Muy débil',     color: 'bg-red-500',      width: '20%'  },
                { label: 'Débil',         color: 'bg-orange-500',   width: '40%'  },
                { label: 'Regular',       color: 'bg-yellow-500',   width: '60%'  },
                { label: 'Fuerte',        color: 'bg-blue-500',     width: '80%'  },
                { label: 'Muy fuerte',    color: 'bg-green-500',    width: '100%' },
            ];
            const nivel = niveles[score] || niveles[0];
            strengthBar.className = `h-full rounded-full transition-all duration-300 ${nivel.color}`;
            strengthBar.style.width = nivel.width;
            strengthLabel.textContent = nivel.label;
            strengthLabel.className = `text-xs mt-1 ${score <= 1 ? 'text-red-400' : score <= 2 ? 'text-orange-400' : score <= 3 ? 'text-yellow-400' : score === 4 ? 'text-blue-400' : 'text-green-400'}`;

            checkMatch();
        });

        confirmInput.addEventListener('input', checkMatch);

        function checkMatch() {
            if (!confirmInput.value) {
                matchLabel.classList.add('hidden');
                return;
            }
            const match = passwordInput.value === confirmInput.value;
            matchLabel.classList.remove('hidden');
            matchLabel.textContent = match ? '✓ Las contraseñas coinciden' : '✗ Las contraseñas no coinciden';
            matchLabel.className = `text-xs mt-1 ${match ? 'text-green-400' : 'text-red-400'}`;
        }
    </script>

</body>
</html>
