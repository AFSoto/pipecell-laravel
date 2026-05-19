{{--
    Layout del panel de administración.

    Sidebar colapsable: modo expandido (w-64) y modo mini (w-20, solo íconos).
    El estado se guarda en localStorage para que persista entre recargas.
    En móvil el sidebar se abre como overlay.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') — PipeCell</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{--
        Estilos para la transición del sidebar.
        Definimos las variables de ancho para expandido y colapsado.
        transition-all hace que el cambio sea suave.
    --}}
    <style>
    .sidebar-expanded { width: 16rem; }
    .sidebar-collapsed { width: 5rem; }
    .content-expanded { margin-left: 16rem; }
    .content-collapsed { margin-left: 5rem; }
    /* Evita flash del sidebar al recargar */
    #main-content { transition: none; }
</style>
<script>
    // Aplica el margin correcto al contenido antes de que el navegador pinte
    document.addEventListener('DOMContentLoaded', function() {
        if (window.innerWidth >= 1024) {
            const collapsed = localStorage.getItem('sidebar-collapsed') === 'true';
            const mainContent = document.getElementById('main-content');
            if (collapsed) {
                mainContent.classList.remove('content-expanded');
                mainContent.classList.add('content-collapsed');
            } else {
                mainContent.classList.remove('content-collapsed');
                mainContent.classList.add('content-expanded');
            }
            // Activar transiciones después de aplicar el estado inicial
            setTimeout(() => {
                mainContent.style.transition = 'margin-left 0.3s';
            }, 50);
        }
    });
</script>
</head>
<body class="min-h-screen bg-gray-50">

    <div class="flex min-h-screen">

        {{-- ============================== --}}
        {{-- SIDEBAR                         --}}
        {{-- ============================== --}}
        <aside id="sidebar"
               class="fixed inset-y-0 left-0 z-40 bg-[#0f172a] transition-all duration-300 transform -translate-x-full lg:translate-x-0 sidebar-expanded overflow-hidden">

            <div class="flex flex-col h-full">

                {{-- Logo + botón colapsar --}}
                <div class="px-4 py-5 border-b border-white/10 flex items-center justify-between min-h-[4.5rem]">
                    {{-- Logo expandido --}}
                    <div class="sidebar-content flex items-center gap-3 overflow-hidden">
                        <img src="{{ asset('img/logo.png') }}" alt="PipeCell" class="h-10 flex-shrink-0">
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

                    {{-- Cajas --}}
                    <a href="#"
                       class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition text-gray-400 hover:bg-white/5 hover:text-white"
                       title="Cajas">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <span class="sidebar-text whitespace-nowrap">Cajas</span>
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
                        {{-- Ícono de caja/paquete --}}
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <span class="sidebar-text whitespace-nowrap">Productos</span>
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
        <div id="main-content" class="flex-1 transition-all duration-300 lg:content-expanded">

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

                    <div class="hidden sm:flex items-center gap-2 text-sm text-gray-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        {{ now()->translatedFormat('l, d M Y') }}
                    </div>
                </div>
            </header>

            <main class="p-6">
                @yield('content')
            </main>

        </div>

    </div>

    {{--
        Script del sidebar colapsable.
        - toggleCollapse(): expande/colapsa en desktop (guarda estado en localStorage)
        - toggleMobileSidebar(): abre/cierra en móvil como overlay
        - applySidebarState(): aplica el estado guardado al cargar la página
    --}}
    <script>
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        const collapseIcon = document.getElementById('collapse-icon');
        const sidebarTexts = document.querySelectorAll('.sidebar-text');

        // Aplica el estado guardado al cargar
        function applySidebarState() {
            const collapsed = localStorage.getItem('sidebar-collapsed') === 'true';
            if (collapsed) {
                collapseSidebar();
            }
        }

        // Colapsa el sidebar (solo íconos)
        function collapseSidebar() {
            sidebar.classList.remove('sidebar-expanded');
            sidebar.classList.add('sidebar-collapsed');
            mainContent.classList.remove('content-expanded');
            mainContent.classList.add('content-collapsed');
            collapseIcon.style.transform = 'rotate(180deg)';
            sidebarTexts.forEach(el => {
                el.style.opacity = '0';
                el.style.width = '0';
                el.style.overflow = 'hidden';
            });
        }

        // Expande el sidebar (íconos + texto)
        function expandSidebar() {
            sidebar.classList.remove('sidebar-collapsed');
            sidebar.classList.add('sidebar-expanded');
            mainContent.classList.remove('content-collapsed');
            mainContent.classList.add('content-expanded');
            collapseIcon.style.transform = 'rotate(0deg)';
            sidebarTexts.forEach(el => {
                el.style.opacity = '1';
                el.style.width = 'auto';
                el.style.overflow = 'visible';
            });
        }

        // Toggle desktop: colapsar/expandir
        function toggleCollapse() {
            const isCollapsed = sidebar.classList.contains('sidebar-collapsed');
            if (isCollapsed) {
                expandSidebar();
                localStorage.setItem('sidebar-collapsed', 'false');
            } else {
                collapseSidebar();
                localStorage.setItem('sidebar-collapsed', 'true');
            }
        }

        // Toggle móvil: abrir/cerrar como overlay
        function toggleMobileSidebar() {
    const isHidden = sidebar.classList.contains('-translate-x-full');
    sidebar.classList.toggle('-translate-x-full');
    document.getElementById('sidebar-overlay').classList.toggle('hidden');

    if (isHidden) {
        // Al abrir en móvil: expandir sidebar pero sin tocar el margin del contenido
        sidebar.classList.remove('sidebar-collapsed');
        sidebar.classList.add('sidebar-expanded');
        sidebarTexts.forEach(el => {
            el.style.opacity = '1';
            el.style.width = 'auto';
            el.style.overflow = 'visible';
        });
    }
    // Al cerrar: no hacemos nada con el contenido, el overlay desaparece solo
}

        // Aplicar estado al cargar (solo en desktop)
        if (window.innerWidth >= 1024) {
            applySidebarState();
        }
    </script>

    @yield('scripts')

</body>
</html>
