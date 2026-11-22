{{--
    Layout del panel de administración.

    Sidebar colapsable: modo expandido (16rem) y modo mini (5rem, solo íconos).
    La preferencia se guarda en localStorage y se aplica en el <head>, antes de dibujar
    (clase sidebar-mini del <html>); los anchos y márgenes salen del <style>.
    En pantallas de menos de 1024 px el sidebar se abre encima, como menú.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Solo las páginas que escuchan los avisos en tiempo real (@section('tiempo-real')):
         resources/js/tiempo-real.js se conecta a Pusher si encuentra esta meta --}}
    @hasSection('tiempo-real')
        <meta name="tiempo-real" content="1">
    @endif
    <title>@yield('title', 'Panel') — PipeCell</title>
    <x-favicon />

    {{--
        Sidebar mini: se decide aquí, antes de dibujar la página, para que salga ya
        cerrado. Si se aplicara al final de la página, se vería abrirse y cerrarse en
        cada cambio de sección. La preferencia la guarda toggleCollapse().
    --}}
    <script>
        try {
            if (localStorage.getItem('sidebar-collapsed') === 'true') {
                document.documentElement.classList.add('sidebar-mini');
            }
        } catch (e) {
            // Sin acceso al almacenamiento: el sidebar queda abierto
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{--
        Ancho del sidebar y margen del contenido. Salen de aquí y no de JS: la página
        se dibuja desde el principio como debe quedar.
        - Menos de 1024 px: el sidebar es un menú que se abre encima, siempre completo,
          y el contenido va sin margen.
        - Desde 1024 px: el sidebar queda fijo, abierto (16rem) o mini (5rem, solo íconos).
        Como depende del ancho de la pantalla, al girar el iPad se acomoda solo.
    --}}
    <style>
        /* Reserva el espacio de la barra de scroll aunque la página no tenga scroll:
           así nada se corre de lado al pasar de una página a otra (en el PC) */
        html { scrollbar-gutter: stable; }

        #sidebar { width: 16rem; }

        @media (min-width: 1024px) {
            #main-content { margin-left: 16rem; transition: margin-left .3s; }

            .sidebar-mini #sidebar { width: 5rem; }
            .sidebar-mini #main-content { margin-left: 5rem; }
            .sidebar-mini #sidebar .sidebar-text { opacity: 0; width: 0; overflow: hidden; }
            .sidebar-mini #collapse-icon { transform: rotate(180deg); }

            /* Encabezado: el logo arriba y la flecha debajo (lado a lado no caben) */
            .sidebar-mini #sidebar-cabecera { flex-direction: column; justify-content: center; }
            .sidebar-mini #sidebar-cabecera .sidebar-text { display: none; }
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50">

    <div class="flex min-h-screen">

        {{-- ============================== --}}
        {{-- SIDEBAR                         --}}
        {{-- ============================== --}}
        <aside id="sidebar"
               class="fixed inset-y-0 left-0 z-40 bg-[#0f172a] transition-all duration-300 transform -translate-x-full lg:translate-x-0 overflow-hidden">

            <div class="flex flex-col h-full">

                {{-- Logo + botón colapsar (en modo mini, uno debajo del otro: ver el <style>) --}}
                <div id="sidebar-cabecera" class="px-4 py-5 border-b border-white/10 flex items-center justify-between gap-2 min-h-[4.5rem]">
                    {{-- Logo de tamaño fijo: no se corre al cargar ni se deforma en modo mini --}}
                    <div class="flex items-center gap-3 overflow-hidden">
                        <img src="{{ asset('img/logo-sidebar.png') }}" alt="PipeCell" width="40" height="40"
                             class="w-10 h-10 max-w-none shrink-0">
                        <div class="sidebar-text whitespace-nowrap">
                            <p class="text-white font-bold text-sm">PipeCell</p>
                            <p class="text-gray-500 text-xs">Panel de control</p>
                        </div>
                    </div>

                    {{--
                        Botón para colapsar/expandir el sidebar.
                        La flecha rota 180° cuando está colapsado.
                        Solo visible en desktop (hidden en móvil).
                    --}}
                    <button onclick="toggleCollapse()"
                            class="hidden lg:flex w-8 h-8 items-center justify-center rounded-lg hover:bg-white/10 transition flex-shrink-0">
                        <svg id="collapse-icon" class="w-4 h-4 text-gray-400 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                </div>

                {{-- Navegación --}}
                <nav class="flex-1 px-3 py-6 space-y-1 overflow-y-auto">

                    {{-- Dashboard --}}
                    <a href="{{ route('admin.dashboard') }}"
                       class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                              {{ request()->routeIs('admin.dashboard') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}"
                       title="Dashboard">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span class="sidebar-text whitespace-nowrap">Dashboard</span>
                    </a>

                    {{-- Reparaciones --}}
                    <a href="{{ route('admin.reparaciones.index') }}"
                       class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                              {{ request()->routeIs('admin.reparaciones.*') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}"
                       title="Reparaciones">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.42 15.17l-5.1-5.1a2.5 2.5 0 010-3.54l.71-.7a2.5 2.5 0 013.54 0l5.1 5.1m-4.25 4.24l4.24-4.24m0 0l5.1 5.1a2.5 2.5 0 010 3.54l-.7.7a2.5 2.5 0 01-3.55 0l-5.1-5.1"/>
                        </svg>
                        <span class="sidebar-text whitespace-nowrap">Reparaciones</span>
                    </a>

                    {{-- Separador de sección Inventario --}}
                    <div class="sidebar-text pt-3 pb-1 px-3">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Inventario</span>
                    </div>

                    {{-- Categorías --}}
                    <a href="{{ route('admin.categorias.index') }}"
                       class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                              {{ request()->routeIs('admin.categorias.*') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}"
                       title="Categorías">
                        {{-- Ícono de etiquetas (tags) --}}
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.99 1.99 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        <span class="sidebar-text whitespace-nowrap">Categorías</span>
                    </a>

                    {{-- Productos --}}
                    <a href="{{ route('admin.productos.index') }}"
                       class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                              {{ request()->routeIs('admin.productos.*') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}"
                       title="Productos">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <span class="sidebar-text whitespace-nowrap">Productos</span>
                    </a>

                    {{-- Separador de sección Sistema --}}
                    <div class="sidebar-text pt-3 pb-1 px-3">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Sistema</span>
                    </div>

                    {{-- Configuración --}}
                    <a href="{{ route('admin.settings.index') }}"
                       class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                              {{ request()->routeIs('admin.settings.*') ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}"
                       title="Configuración">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="sidebar-text whitespace-nowrap">Configuración</span>
                    </a>

                </nav>

                {{-- Perfil y logout --}}
                <div class="px-3 py-4 border-t border-white/10">

                    <a href="{{ route('admin.perfil') }}"
                       class="flex items-center gap-3 px-3 py-2 mb-1 rounded-xl hover:bg-white/5 transition group">
                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white text-xs font-bold flex-shrink-0 group-hover:bg-blue-500 transition">
                            {{ strtoupper(substr(auth()->user()->nombre, 0, 2)) }}
                        </div>
                        <div class="sidebar-text flex-1 min-w-0 whitespace-nowrap">
                            <p class="text-white text-sm font-medium truncate">{{ auth()->user()->nombre }}</p>
                            <p class="text-gray-500 text-xs truncate">{{ auth()->user()->role->label() }}</p>
                        </div>
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="sidebar-link w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-400 hover:bg-red-500/10 hover:text-red-400 transition"
                                title="Cerrar sesión">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            <span class="sidebar-text whitespace-nowrap">Cerrar sesión</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Overlay móvil --}}
        <div id="sidebar-overlay"
             class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden"
             onclick="toggleMobileSidebar()">
        </div>

        {{-- ============================== --}}
        {{-- CONTENIDO PRINCIPAL             --}}
        {{-- ============================== --}}
        {{-- min-w-0: sin esto el contenido crece hasta el ancho de la tabla más ancha y la
             página entera se sale por la derecha; con esto la tabla se desliza dentro de su tarjeta --}}
        <div id="main-content" class="flex-1 min-w-0">

            {{-- Header --}}
            <header class="sticky top-0 z-20 bg-white/80 backdrop-blur-md border-b border-gray-100">
                <div class="flex items-center justify-between px-6 py-4">

                    <button onclick="toggleMobileSidebar()"
                            class="lg:hidden p-2 rounded-xl hover:bg-gray-100 transition">
                        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <h1 class="text-lg font-semibold text-gray-900">
                        @yield('page-title', 'Dashboard')
                    </h1>

                    <div class="flex items-center gap-4">
                        {{-- Tiempo real: lo pinta resources/js/tiempo-real.js (verde "En vivo",
                             gris sin conexión, ámbar si la sesión venció). Solo en las páginas
                             que escuchan los avisos: en las demás diría "En vivo" sin serlo --}}
                        @hasSection('tiempo-real')
                            <span id="indicador-en-vivo" role="status" aria-live="polite"
                                  class="hidden items-center gap-1.5 text-xs font-medium text-gray-500 select-none">
                                <span data-punto class="w-2 h-2 rounded-full"></span>
                                <span data-texto></span>
                            </span>
                        @endif

                        <div class="hidden sm:flex items-center gap-2 text-sm text-gray-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            {{ now()->translatedFormat('l, d M Y') }}
                        </div>
                    </div>
                </div>
            </header>

            <main class="p-6">
                @yield('content')
            </main>

        </div>

    </div>

    {{--
        Script del sidebar. El ancho y los márgenes los pone el <style> del head
        según la clase sidebar-mini del <html>; aquí solo se cambia esa clase.
        - toggleCollapse(): en pantallas grandes, abre o deja en mini el sidebar y
          guarda la preferencia (el head la aplica al cargar la siguiente página).
        - toggleMobileSidebar(): abre o cierra el menú en pantallas chicas.
    --}}
    <script>
        function toggleCollapse() {
            const mini = document.documentElement.classList.toggle('sidebar-mini');
            try {
                localStorage.setItem('sidebar-collapsed', mini ? 'true' : 'false');
            } catch (e) {
                // Sin acceso al almacenamiento: funciona, pero no se recuerda
            }
        }

        function toggleMobileSidebar() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
            document.getElementById('sidebar-overlay').classList.toggle('hidden');
        }
    </script>

    {{-- Mensajes de guardado y error: flotan arriba a la derecha --}}
    <x-avisos-sesion />

    @yield('scripts')

</body>
</html>
