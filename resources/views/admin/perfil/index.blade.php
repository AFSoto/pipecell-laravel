@extends('layouts.admin')

@section('page-title', 'Mi perfil')

@section('content')

{{-- ── Notificaciones flash ── --}}
@foreach (['success_perfil', 'success_password', 'success_sesiones'] as $key)
    @if (session($key))
        <div class="mb-6 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-xl px-4 py-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session($key) }}
        </div>
    @endif
@endforeach

@if (session('error'))
    <div class="mb-6 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('error') }}
    </div>
@endif

{{-- ── Header ── --}}
<div class="mb-8">
    <h2 class="text-2xl font-bold text-gray-900">Mi perfil</h2>
    <p class="text-gray-500 mt-1 text-sm">Administra tu información personal y seguridad de la cuenta</p>
</div>

<div class="grid lg:grid-cols-3 gap-6">

    {{-- ════════════════════════════════════════════════════════════ --}}
    {{-- COLUMNA IZQUIERDA: Avatar + datos de solo lectura           --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    <div class="lg:col-span-1 space-y-5">

        {{-- Card: Avatar --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-6 text-center">
            <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-blue-700 rounded-2xl flex items-center justify-center text-white text-2xl font-bold mx-auto shadow-lg shadow-blue-200/60">
                {{ strtoupper(substr(auth()->user()->nombre, 0, 2)) }}
            </div>
            <h3 class="mt-4 text-lg font-semibold text-gray-900">{{ auth()->user()->nombre }}</h3>
            <p class="text-sm text-gray-500 mt-0.5">{{ auth()->user()->email }}</p>
            <span class="inline-flex items-center mt-3 text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-100 px-3 py-1 rounded-full">
                {{ auth()->user()->role->label() }}
            </span>
        </div>

        {{-- Card: Detalles de cuenta (solo lectura) --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-6 space-y-4">
            <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Detalles de la cuenta</h4>

            <div>
                <p class="text-xs text-gray-400 mb-0.5">Rol</p>
                <p class="text-sm font-medium text-gray-800">{{ auth()->user()->role->label() }}</p>
            </div>

            <div>
                <p class="text-xs text-gray-400 mb-0.5">Miembro desde</p>
                <p class="text-sm font-medium text-gray-800">
                    {{ auth()->user()->created_at->translatedFormat('d \d\e F \d\e Y') }}
                </p>
            </div>

            <div>
                <p class="text-xs text-gray-400 mb-0.5">Último inicio de sesión</p>
                <p class="text-sm font-medium text-gray-800">
                    @if (auth()->user()->last_login_at)
                        {{ auth()->user()->last_login_at->translatedFormat('d \d\e F \d\e Y, H:i') }}
                    @else
                        <span class="text-gray-400 italic">No disponible</span>
                    @endif
                </p>
            </div>
        </div>

    </div>

    {{-- ════════════════════════════════════════════════════════════ --}}
    {{-- COLUMNA DERECHA: Formularios y sesiones                     --}}
    {{-- ════════════════════════════════════════════════════════════ --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- ── Sección 1: Información personal ── --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <div class="mb-5">
                <h3 class="font-semibold text-gray-900">Información personal</h3>
                <p class="text-xs text-gray-400 mt-0.5">Actualiza tu nombre y correo electrónico</p>
            </div>

            <form method="POST" action="{{ route('admin.perfil.info') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Nombre completo *</label>
                        <input type="text" name="nombre" required
                               value="{{ old('nombre', auth()->user()->nombre) }}"
                               class="w-full px-3 py-2.5 border rounded-xl text-sm transition focus:outline-none focus:ring-2
                                      {{ $errors->getBag('perfil')->has('nombre') ? 'border-red-300 focus:ring-red-500/20' : 'border-gray-200 focus:ring-blue-500/20 focus:border-blue-400' }}">
                        @error('nombre', 'perfil')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Correo electrónico *</label>
                        <input type="email" name="email" required
                               value="{{ old('email', auth()->user()->email) }}"
                               class="w-full px-3 py-2.5 border rounded-xl text-sm transition focus:outline-none focus:ring-2
                                      {{ $errors->getBag('perfil')->has('email') ? 'border-red-300 focus:ring-red-500/20' : 'border-gray-200 focus:ring-blue-500/20 focus:border-blue-400' }}">
                        @error('email', 'perfil')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex justify-end pt-1">
                    <button type="submit"
                            class="px-5 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition shadow-sm shadow-blue-600/20">
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>

        {{-- ── Sección 2: Cambio de contraseña ── --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <div class="mb-5">
                <h3 class="font-semibold text-gray-900">Cambiar contraseña</h3>
                <p class="text-xs text-gray-400 mt-0.5">Elige una contraseña segura que no uses en otros sitios</p>
            </div>

            <form method="POST" action="{{ route('admin.perfil.password') }}" class="space-y-4" id="form-password">
                @csrf
                @method('PUT')

                {{-- Contraseña actual --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Contraseña actual *</label>
                    <div class="relative">
                        <input type="password" name="password_actual" id="password_actual" required
                               class="w-full px-3 py-2.5 pr-10 border rounded-xl text-sm transition focus:outline-none focus:ring-2
                                      {{ $errors->getBag('contrasena')->has('password_actual') ? 'border-red-300 focus:ring-red-500/20' : 'border-gray-200 focus:ring-blue-500/20 focus:border-blue-400' }}">
                        <button type="button" onclick="togglePassword('password_actual', this)"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600 transition rounded-lg hover:bg-gray-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                    @error('password_actual', 'contrasena')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Nueva contraseña --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Nueva contraseña *</label>
                    <div class="relative">
                        <input type="password" name="nueva_password" id="nueva_password" required
                               oninput="evaluarPassword(this.value)"
                               class="w-full px-3 py-2.5 pr-10 border rounded-xl text-sm transition focus:outline-none focus:ring-2
                                      {{ $errors->getBag('contrasena')->has('nueva_password') ? 'border-red-300 focus:ring-red-500/20' : 'border-gray-200 focus:ring-blue-500/20 focus:border-blue-400' }}">
                        <button type="button" onclick="togglePassword('nueva_password', this)"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600 transition rounded-lg hover:bg-gray-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                    @error('nueva_password', 'contrasena')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror

                    {{-- Indicador de fortaleza --}}
                    <div id="indicador-wrap" class="mt-3 hidden">
                        {{-- Barra de fortaleza --}}
                        <div class="flex gap-1 mb-2">
                            <div id="barra-1" class="h-1.5 flex-1 rounded-full bg-gray-100 transition-colors duration-300"></div>
                            <div id="barra-2" class="h-1.5 flex-1 rounded-full bg-gray-100 transition-colors duration-300"></div>
                            <div id="barra-3" class="h-1.5 flex-1 rounded-full bg-gray-100 transition-colors duration-300"></div>
                        </div>
                        <p id="label-fortaleza" class="text-xs font-medium text-gray-400"></p>

                        {{-- Reglas individuales --}}
                        <ul class="mt-3 space-y-1.5">
                            <li id="regla-longitud"   class="flex items-center gap-2 text-xs text-gray-400 transition-colors">
                                <span class="regla-icono w-4 h-4 rounded-full border border-gray-200 flex items-center justify-center flex-shrink-0 transition"></span>
                                Mínimo 8 caracteres
                            </li>
                            <li id="regla-mayuscula"  class="flex items-center gap-2 text-xs text-gray-400 transition-colors">
                                <span class="regla-icono w-4 h-4 rounded-full border border-gray-200 flex items-center justify-center flex-shrink-0 transition"></span>
                                Al menos una letra mayúscula
                            </li>
                            <li id="regla-minuscula"  class="flex items-center gap-2 text-xs text-gray-400 transition-colors">
                                <span class="regla-icono w-4 h-4 rounded-full border border-gray-200 flex items-center justify-center flex-shrink-0 transition"></span>
                                Al menos una letra minúscula
                            </li>
                            <li id="regla-numero"     class="flex items-center gap-2 text-xs text-gray-400 transition-colors">
                                <span class="regla-icono w-4 h-4 rounded-full border border-gray-200 flex items-center justify-center flex-shrink-0 transition"></span>
                                Al menos un número
                            </li>
                            <li id="regla-especial"   class="flex items-center gap-2 text-xs text-gray-400 transition-colors">
                                <span class="regla-icono w-4 h-4 rounded-full border border-gray-200 flex items-center justify-center flex-shrink-0 transition"></span>
                                Al menos un carácter especial (!@#$%...)
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Confirmar nueva contraseña --}}
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Confirmar nueva contraseña *</label>
                    <div class="relative">
                        <input type="password" name="nueva_password_confirmation" id="nueva_password_confirmation" required
                               oninput="evaluarConfirmacion()"
                               class="w-full px-3 py-2.5 pr-10 border rounded-xl text-sm transition focus:outline-none focus:ring-2
                                      {{ $errors->getBag('contrasena')->has('nueva_password') ? 'border-red-300 focus:ring-red-500/20' : 'border-gray-200 focus:ring-blue-500/20 focus:border-blue-400' }}">
                        <button type="button" onclick="togglePassword('nueva_password_confirmation', this)"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600 transition rounded-lg hover:bg-gray-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex justify-end pt-1">
                    <button type="submit"
                            class="px-5 py-2.5 text-sm font-semibold text-white bg-violet-600 hover:bg-violet-700 rounded-xl transition shadow-sm shadow-violet-600/20">
                        Cambiar contraseña
                    </button>
                </div>
            </form>
        </div>

        {{-- ── Sección 3: Sesiones activas ── --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <div class="flex items-start justify-between mb-5 gap-3">
                <div>
                    <h3 class="font-semibold text-gray-900">Sesiones activas</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Dispositivos donde tu cuenta está iniciada</p>
                </div>
                @if (count($sesiones) > 1)
                    <form method="POST" action="{{ route('admin.perfil.otras-sesiones') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                onclick="return confirm('¿Cerrar todas las sesiones excepto esta?')"
                                class="text-xs font-semibold text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 border border-red-100 px-3 py-2 rounded-xl transition whitespace-nowrap">
                            Cerrar otras sesiones
                        </button>
                    </form>
                @endif
            </div>

            @if (empty($sesiones))
                <p class="text-sm text-gray-400 italic text-center py-4">No hay información de sesiones disponible.</p>
            @else
                <div class="space-y-3">
                    @foreach ($sesiones as $sesion)
                        <div class="flex items-center gap-4 p-3.5 rounded-xl border
                                    {{ $sesion['es_actual'] ? 'border-blue-100 bg-blue-50/50' : 'border-gray-100 bg-gray-50/50' }}">

                            {{-- Ícono de dispositivo --}}
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0
                                        {{ $sesion['es_actual'] ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-500' }}">
                                @php
                                    $mobil = in_array($sesion['sistema'], ['iPhone', 'iPad', 'Android']);
                                @endphp
                                @if ($mobil)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                @endif
                            </div>

                            {{-- Info --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="text-sm font-medium text-gray-800">
                                        {{ $sesion['navegador'] }} · {{ $sesion['sistema'] }}
                                    </p>
                                    @if ($sesion['es_actual'])
                                        <span class="text-[10px] font-semibold text-blue-700 bg-blue-100 px-2 py-0.5 rounded-full">
                                            Sesión actual
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ $sesion['ip'] }} · Última actividad {{ $sesion['ultima_actividad']->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>{{-- /col-span-2 --}}

</div>

@endsection

@section('scripts')
<script>
    // ── Toggle visibilidad de contraseña ──
    function togglePassword(id, btn) {
        const input = document.getElementById(id);
        const mostrar = input.type === 'password';
        input.type = mostrar ? 'text' : 'password';

        const paths = btn.querySelectorAll('path');
        if (mostrar) {
            // Ojo tachado: path único con la línea diagonal, segundo path vacío
            paths[0].setAttribute('d', 'M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21');
            paths[1].setAttribute('d', '');
        } else {
            // Ojo abierto: círculo interior + óvalo exterior
            paths[0].setAttribute('d', 'M15 12a3 3 0 11-6 0 3 3 0 016 0z');
            paths[1].setAttribute('d', 'M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z');
        }
    }

    // ── Indicador de fortaleza de contraseña ──
    function evaluarPassword(valor) {
        const wrap = document.getElementById('indicador-wrap');

        if (valor.length === 0) {
            wrap.classList.add('hidden');
            return;
        }
        wrap.classList.remove('hidden');

        const reglas = {
            longitud:  valor.length >= 8,
            mayuscula: /[A-Z]/.test(valor),
            minuscula: /[a-z]/.test(valor),
            numero:    /[0-9]/.test(valor),
            especial:  /[^A-Za-z0-9]/.test(valor),
        };

        const cumplidas = Object.values(reglas).filter(Boolean).length;

        // Actualizar indicadores de cada regla
        Object.entries(reglas).forEach(([nombre, cumple]) => {
            const li   = document.getElementById('regla-' + nombre);
            const icon = li.querySelector('.regla-icono');

            if (cumple) {
                li.classList.replace('text-gray-400', 'text-emerald-600');
                icon.classList.add('bg-emerald-500', 'border-emerald-500');
                icon.innerHTML = '<svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>';
            } else {
                li.classList.replace('text-emerald-600', 'text-gray-400');
                icon.classList.remove('bg-emerald-500', 'border-emerald-500');
                icon.innerHTML = '';
            }
        });

        // Barras de fortaleza y etiqueta
        const config = cumplidas <= 2
            ? { barras: 1, color: 'bg-red-400',    label: 'Débil',   labelColor: 'text-red-500'    }
            : cumplidas <= 4
            ? { barras: 2, color: 'bg-amber-400',  label: 'Media',   labelColor: 'text-amber-500'  }
            : { barras: 3, color: 'bg-emerald-500', label: 'Fuerte', labelColor: 'text-emerald-600' };

        [1, 2, 3].forEach(n => {
            const barra = document.getElementById('barra-' + n);
            barra.className = 'h-1.5 flex-1 rounded-full transition-colors duration-300 '
                + (n <= config.barras ? config.color : 'bg-gray-100');
        });

        const labelEl = document.getElementById('label-fortaleza');
        labelEl.textContent  = config.label;
        labelEl.className    = 'text-xs font-medium transition-colors ' + config.labelColor;
    }
</script>
@endsection
