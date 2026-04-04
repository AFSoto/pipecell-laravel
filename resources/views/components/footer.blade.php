{{--
    Componente: Footer
    Pie de página completo con columnas de servicios, contacto y redes.
--}}
<footer class="bg-slate-900 text-white">
    <div class="max-w-7xl mx-auto px-6 py-16">

        {{-- Grid principal --}}
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-10 mb-12">

            {{-- Columna 1: Marca --}}
            <div class="lg:col-span-2">
                <a href="/" class="inline-flex items-center gap-2.5 mb-5">
                    <div class="w-9 h-9 bg-blue-600 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <span class="text-xl font-bold">Pipe<span class="text-blue-400">Cell</span></span>
                </a>
                <p class="text-slate-400 text-sm leading-relaxed max-w-xs mb-6">
                    Servicio técnico especializado en reparación de smartphones. Diagnóstico gratuito, repuestos de calidad y garantía por escrito en cada reparación.
                </p>
                <div class="flex gap-3">
                    <a href="#" class="w-9 h-9 bg-slate-800 hover:bg-blue-600 rounded-xl flex items-center justify-center transition">
                        {{-- Instagram --}}
                        <svg class="w-4 h-4 text-slate-400 hover:text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                        </svg>
                    </a>
                    <a href="#" class="w-9 h-9 bg-slate-800 hover:bg-blue-600 rounded-xl flex items-center justify-center transition">
                        {{-- Facebook --}}
                        <svg class="w-4 h-4 text-slate-400 hover:text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </a>
                    <a href="https://wa.me/573113105244?text=Hola%2C%20quiero%20cotizar%20una%20reparaci%C3%B3n%20%F0%9F%93%B1" target="_blank" class="w-9 h-9 bg-slate-800 hover:bg-green-600 rounded-xl flex items-center justify-center transition">
                        <svg class="w-4 h-4 text-slate-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492l4.638-1.467A11.932 11.932 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.75c-2.146 0-4.142-.685-5.77-1.848l-.413-.268-2.75.87.908-2.684-.293-.435A9.709 9.709 0 012.25 12c0-5.385 4.365-9.75 9.75-9.75S21.75 6.615 21.75 12s-4.365 9.75-9.75 9.75z"/>
                        </svg>
                    </a>
                </div>
            </div>

            {{-- Columna 2: Servicios --}}
            <div>
                <h4 class="font-semibold text-white mb-5 text-sm uppercase tracking-wider">Servicios</h4>
                <ul class="space-y-3">
                    <li><a href="#servicios" class="text-slate-400 hover:text-white text-sm transition">Cambio de pantalla</a></li>
                    <li><a href="#servicios" class="text-slate-400 hover:text-white text-sm transition">Cambio de batería</a></li>
                    <li><a href="#servicios" class="text-slate-400 hover:text-white text-sm transition">Reparación de software</a></li>
                    <li><a href="#servicios" class="text-slate-400 hover:text-white text-sm transition">Reparación de cámara</a></li>
                    <li><a href="#servicios" class="text-slate-400 hover:text-white text-sm transition">Pin de carga</a></li>
                    <li><a href="#servicios" class="text-slate-400 hover:text-white text-sm transition">Venta de accesorios</a></li>
                </ul>
            </div>

            {{-- Columna 3: Contacto --}}
            <div>
                <h4 class="font-semibold text-white mb-5 text-sm uppercase tracking-wider">Contacto</h4>
                <ul class="space-y-4">
                    <li class="flex items-start gap-3">
                        <svg class="w-4 h-4 text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <span class="text-slate-400 text-sm">+57 311 310 5244</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-4 h-4 text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <a href="https://maps.app.goo.gl/a8CLPCiMUcQJdqjq8" target="_blank" class="text-slate-400 hover:text-white text-sm transition">Ver en Google Maps</a>
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="w-4 h-4 text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="text-slate-400 text-sm">
                            <p>Lun – Vie: 8am – 7pm</p>
                            <p>Sábado: 9am – 5pm</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Bottom bar --}}
        <div class="border-t border-slate-800 pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
            <p class="text-slate-500 text-sm">&copy; {{ date('Y') }} PipeCell &mdash; Todos los derechos reservados</p>
            <div class="flex flex-wrap justify-center gap-x-5 gap-y-1 text-slate-500 text-xs">
                <span>Diagnóstico gratuito</span>
                <span class="hidden sm:inline">&middot;</span>
                <span>Garantía por escrito</span>
                <span class="hidden sm:inline">&middot;</span>
                <span>Repuestos de calidad</span>
            </div>
        </div>
    </div>
</footer>
