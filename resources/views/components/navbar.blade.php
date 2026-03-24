{{--
    Componente: Navbar
    Barra de navegación fija con logo, links, botón CTA y menú móvil.
--}}
<nav class="fixed top-0 w-full z-50">
    <div class="bg-white/95 backdrop-blur-md border-b border-gray-100 shadow-sm">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">

            {{-- Logo --}}
            <a href="/" class="flex items-center gap-2.5">
                <div class="w-9 h-9 bg-blue-600 rounded-xl flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <span class="text-xl font-bold text-gray-900 tracking-tight">Pipe<span class="text-blue-600">Cell</span></span>
            </a>

            {{-- Links - desktop --}}
            <div class="hidden md:flex items-center gap-8">
                <a href="#servicios" class="text-sm text-gray-600 hover:text-blue-600 transition font-medium">Servicios</a>
                <a href="#tecnicos"  class="text-sm text-gray-600 hover:text-blue-600 transition font-medium">Equipo</a>
                <a href="#trabajos"  class="text-sm text-gray-600 hover:text-blue-600 transition font-medium">Trabajos</a>
                <a href="#contacto"  class="text-sm text-gray-600 hover:text-blue-600 transition font-medium">Contacto</a>
            </div>

            {{-- CTA + botón móvil --}}
            <div class="flex items-center gap-3">
                <a href="https://wa.me/57XXXXXXXXXX" target="_blank"
                   class="hidden md:inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm px-5 py-2.5 rounded-xl transition shadow-sm shadow-blue-600/20">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492l4.638-1.467A11.932 11.932 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.75c-2.146 0-4.142-.685-5.77-1.848l-.413-.268-2.75.87.908-2.684-.293-.435A9.709 9.709 0 012.25 12c0-5.385 4.365-9.75 9.75-9.75S21.75 6.615 21.75 12s-4.365 9.75-9.75 9.75z"/>
                    </svg>
                    Cotizar ahora
                </a>

                {{-- Hamburger --}}
                <button class="md:hidden p-2 rounded-xl hover:bg-gray-100 transition"
                        onclick="document.getElementById('mobile-menu').classList.toggle('hidden')">
                    <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Menú móvil --}}
        <div id="mobile-menu" class="hidden md:hidden border-t border-gray-100 bg-white px-6 pb-6">
            <div class="pt-4 space-y-1">
                <a href="#servicios" onclick="document.getElementById('mobile-menu').classList.add('hidden')"
                   class="block px-4 py-3 rounded-xl text-gray-700 hover:bg-gray-50 font-medium text-sm transition">Servicios</a>
                <a href="#tecnicos" onclick="document.getElementById('mobile-menu').classList.add('hidden')"
                   class="block px-4 py-3 rounded-xl text-gray-700 hover:bg-gray-50 font-medium text-sm transition">Equipo</a>
                <a href="#trabajos" onclick="document.getElementById('mobile-menu').classList.add('hidden')"
                   class="block px-4 py-3 rounded-xl text-gray-700 hover:bg-gray-50 font-medium text-sm transition">Trabajos</a>
                <a href="#contacto" onclick="document.getElementById('mobile-menu').classList.add('hidden')"
                   class="block px-4 py-3 rounded-xl text-gray-700 hover:bg-gray-50 font-medium text-sm transition">Contacto</a>
                <div class="pt-2">
                    <a href="https://wa.me/57XXXXXXXXXX" target="_blank"
                       class="flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-3 rounded-xl transition text-sm">
                        Cotizar por WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>
