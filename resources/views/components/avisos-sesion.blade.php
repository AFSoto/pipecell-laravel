{{--
    Mensajes que dejan los controladores con ->with() al guardar o al fallar algo.

    Flotan arriba a la derecha, así no empujan la página cuando se van. Los de éxito
    se van solos a los 4 s; los errores se quedan hasta cerrarlos con la ×.

    El contenedor es el mismo que usa mostrarNotificacion() en las páginas (lo busca
    por id antes de crear uno): todos los avisos se apilan en vez de taparse.
--}}
@php
    // success_*: los de Perfil, uno por cada formulario de esa página
    $exitos = collect(['success', 'success_perfil', 'success_password', 'success_sesiones'])
        ->map(fn (string $clave) => session($clave))
        ->filter();
    $error = session('error');
@endphp

<div id="contenedor-notificaciones" class="fixed top-6 right-6 z-50 flex flex-col items-end gap-2 pointer-events-none">
    @foreach ($exitos as $mensaje)
        <div data-aviso-sesion="exito" role="status"
             class="max-w-sm flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg text-sm font-medium transition-all duration-300 bg-green-50 border border-green-200 text-green-700">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>{{ $mensaje }}</span>
        </div>
    @endforeach

    @if ($error)
        <div data-aviso-sesion="error" role="alert"
             class="pointer-events-auto max-w-sm flex items-center gap-3 px-5 py-3 rounded-xl shadow-lg text-sm font-medium transition-all duration-300 bg-red-50 border border-red-200 text-red-700">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ $error }}</span>
            <button type="button" data-cerrar aria-label="Cerrar aviso"
                    class="ml-1 px-1 text-lg leading-none opacity-60 hover:opacity-100">×</button>
        </div>
    @endif
</div>

<script>
    // Los de éxito se van solos; los errores, al tocar la ×. Se deslizan a la derecha
    // y después se quitan: como flotan, no mueven nada de la página.
    document.querySelectorAll('[data-aviso-sesion]').forEach(aviso => {
        const cerrar = () => {
            aviso.classList.add('translate-x-full', 'opacity-0');
            setTimeout(() => aviso.remove(), 300);
        };

        if (aviso.dataset.avisoSesion === 'exito') {
            setTimeout(cerrar, 4000);
        }
        aviso.querySelector('[data-cerrar]')?.addEventListener('click', cerrar);
    });
</script>
