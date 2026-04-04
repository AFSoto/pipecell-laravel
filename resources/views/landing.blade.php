{{--
    Landing page de PipeCell.
    Secciones: Hero · Stats · Servicios · Por qué nosotros · Equipo · Trabajos · Testimonios · Contacto · CTA
--}}
@extends('layouts.app')

@section('title', 'PipeCell - Reparación de Celulares Profesional')

@section('content')

{{-- ===================================================== --}}
{{-- HERO                                                   --}}
{{-- ===================================================== --}}
<section class="relative min-h-[92vh] bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 flex items-center overflow-hidden pt-20">

    {{-- Fondo decorativo --}}
    <div class="absolute inset-0" style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.04) 1px, transparent 0); background-size: 40px 40px;"></div>
    <div class="absolute top-1/3 right-0 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-0 left-1/4 w-72 h-72 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="relative max-w-7xl mx-auto px-6 py-20 lg:py-0 grid lg:grid-cols-2 gap-16 items-center w-full">

        {{-- Texto --}}
        <div>
            <div class="inline-flex items-center gap-2 bg-blue-500/20 text-blue-300 text-sm font-medium px-4 py-2 rounded-full mb-8 border border-blue-500/30">
                <span class="w-2 h-2 bg-blue-400 rounded-full animate-pulse"></span>
                Servicio técnico certificado
            </div>

            <h1 class="text-5xl md:text-6xl font-bold text-white leading-tight mb-6">
                Tu celular en<br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-400">manos expertas</span>
            </h1>

            <p class="text-lg text-slate-300 max-w-lg mb-10 leading-relaxed">
                Reparamos todo tipo de smartphones con repuestos de calidad, diagnóstico gratuito y garantía escrita en cada servicio.
            </p>

            <div class="flex flex-wrap gap-4 mb-12">
                <a href="https://wa.me/573113105244?text=Hola%2C%20quiero%20cotizar%20una%20reparaci%C3%B3n%20%F0%9F%93%B1" target="_blank"
                   class="inline-flex items-center gap-3 bg-green-500 hover:bg-green-400 text-white font-semibold px-8 py-4 rounded-xl transition-all duration-200 shadow-lg shadow-green-500/25">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492l4.638-1.467A11.932 11.932 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.75c-2.146 0-4.142-.685-5.77-1.848l-.413-.268-2.75.87.908-2.684-.293-.435A9.709 9.709 0 012.25 12c0-5.385 4.365-9.75 9.75-9.75S21.75 6.615 21.75 12s-4.365 9.75-9.75 9.75z"/>
                    </svg>
                    Cotizar por WhatsApp
                </a>
                <a href="#servicios"
                   class="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 text-white font-semibold px-8 py-4 rounded-xl transition-all duration-200 border border-white/20">
                    Ver servicios
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </a>
            </div>

            <div class="flex flex-wrap items-center gap-6">
                @foreach(['Diagnóstico gratuito', 'Garantía por escrito', 'Repuestos de calidad'] as $badge)
                <div class="flex items-center gap-2 text-slate-300 text-sm">
                    <svg class="w-5 h-5 text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    {{ $badge }}
                </div>
                @endforeach
            </div>
        </div>

        {{-- Imagen del local --}}
        <div class="relative hidden lg:block">
            <div class="relative rounded-3xl overflow-hidden shadow-2xl shadow-blue-900/50 border border-white/10">
                {{-- Reemplaza este src con la foto real de tu local --}}
                <img src="{{ asset('img/landing/foto-local.webp') }}"
                     alt="Local PipeCell" class="w-full h-[450px] object-cover">
                <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-6">
                    <div class="flex items-center gap-3 bg-white/10 backdrop-blur-sm rounded-2xl p-4 border border-white/20">
                        <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-white font-semibold text-sm">Diagnóstico sin costo</p>
                            <p class="text-white/70 text-xs">Revisamos tu equipo antes de cotizar</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Badge flotante: rating --}}
            <div class="absolute -top-5 -right-5 bg-white rounded-2xl shadow-xl p-4 border border-gray-100">
                <div class="flex items-center gap-1.5 mb-1">
                    @for ($i = 0; $i < 5; $i++)
                    <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    @endfor
                    <span class="text-sm font-bold text-gray-900 ml-1">5.0</span>
                </div>
                <p class="text-xs text-gray-500">+200 clientes felices</p>
            </div>
        </div>
    </div>

    {{-- Indicador scroll --}}
    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex flex-col items-center gap-1 text-white/30 animate-bounce">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </div>
</section>

{{-- ===================================================== --}}
{{-- STATS                                                  --}}
{{-- ===================================================== --}}
<section class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-6">
        <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-y md:divide-y-0 divide-gray-100">
            @foreach([
                ['+1000', 'Reparaciones realizadas'],
                ['98%',  'Clientes satisfechos'],
                ['1 mes', 'Garantía por reparación'],
                ['24h',  'Tiempo promedio de entrega'],
            ] as [$num, $label])
            <div class="py-8 px-6 text-center">
                <p class="text-3xl font-bold text-blue-600 mb-1">{{ $num }}</p>
                <p class="text-sm text-gray-500">{{ $label }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ===================================================== --}}
{{-- SERVICIOS                                              --}}
{{-- ===================================================== --}}
<section id="servicios" class="py-24 px-6 bg-gray-50">
    <div class="max-w-7xl mx-auto">

        <div class="text-center mb-16">
            <span class="text-blue-600 text-sm font-semibold uppercase tracking-wider">Lo que hacemos</span>
            <h2 class="text-4xl font-bold text-gray-900 mt-2 mb-4">Nuestros servicios</h2>
            <p class="text-gray-500 max-w-xl mx-auto text-lg">Soluciones completas para cualquier problema con tu smartphone</p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">

            {{-- Pantalla --}}
            <div class="bg-white rounded-2xl p-8 border border-gray-100 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-50/80 transition-all duration-300 group">
                <div class="w-14 h-14 bg-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-3">Cambio de pantalla</h3>
                <p class="text-gray-500 text-sm leading-relaxed mb-4">
                    Reemplazo de pantallas LCD, OLED y AMOLED para todas las marcas con repuestos originales y genéricos de alta calidad.
                </p>
                <span class="text-blue-600 text-sm font-semibold">Samsung · iPhone · Xiaomi · Motorola</span>
            </div>

            {{-- Batería --}}
            <div class="bg-white rounded-2xl p-8 border border-gray-100 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-50/80 transition-all duration-300 group">
                <div class="w-14 h-14 bg-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-3">Cambio de batería</h3>
                <p class="text-gray-500 text-sm leading-relaxed mb-4">
                    Restaura la duración de tu batería al 100%. Celdas de alta capacidad con garantía incluida en todos los modelos.
                </p>
                <span class="text-blue-600 text-sm font-semibold">Todas las marcas</span>
            </div>

            {{-- Software --}}
            <div class="bg-white rounded-2xl p-8 border border-gray-100 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-50/80 transition-all duration-300 group">
                <div class="w-14 h-14 bg-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-3">Reparación de software</h3>
                <p class="text-gray-500 text-sm leading-relaxed mb-4">
                    Formateo, actualización del sistema, eliminación de virus, desbloqueo y recuperación de datos importantes.
                </p>
                <span class="text-blue-600 text-sm font-semibold">Android · iOS</span>
            </div>

            {{-- Cámara --}}
            <div class="bg-white rounded-2xl p-8 border border-gray-100 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-50/80 transition-all duration-300 group">
                <div class="w-14 h-14 bg-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-3">Reparación de cámara</h3>
                <p class="text-gray-500 text-sm leading-relaxed mb-4">
                    Cambio de módulo de cámara frontal y trasera. Solución a fotos borrosas, sin enfoque o error de cámara.
                </p>
                <span class="text-blue-600 text-sm font-semibold">Frontal · Trasera</span>
            </div>

            {{-- Pin de carga --}}
            <div class="bg-white rounded-2xl p-8 border border-gray-100 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-50/80 transition-all duration-300 group">
                <div class="w-14 h-14 bg-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2zm0-11h.01M12 7h.01M17 7h.01"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-3">Pin y puerto de carga</h3>
                <p class="text-gray-500 text-sm leading-relaxed mb-4">
                    Reparación o cambio del conector de carga con soldadura de microtecnología y equipos especializados.
                </p>
                <span class="text-blue-600 text-sm font-semibold">USB-C · Lightning · Micro-USB</span>
            </div>

            {{-- Accesorios --}}
            <div class="bg-white rounded-2xl p-8 border border-gray-100 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-50/80 transition-all duration-300 group">
                <div class="w-14 h-14 bg-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-3">Venta de accesorios</h3>
                <p class="text-gray-500 text-sm leading-relaxed mb-4">
                    Estuches, vidrios templados, cargadores, cables y audífonos para proteger y sacarle el máximo a tu equipo.
                </p>
                <span class="text-blue-600 text-sm font-semibold">Ver catálogo</span>
            </div>

        </div>
    </div>
</section>

{{-- ===================================================== --}}
{{-- POR QUÉ NOSOTROS                                       --}}
{{-- ===================================================== --}}
<section class="py-24 px-6 bg-white">
    <div class="max-w-7xl mx-auto grid lg:grid-cols-2 gap-16 items-center">

        {{-- Imagen del técnico trabajando --}}
        <div class="relative">
            <div class="rounded-3xl overflow-hidden shadow-xl">
                {{-- Reemplaza este src con una foto real de tu taller --}}
                <img src="{{ asset('img/landing/puesto-reparacion.webp') }}"
                     alt="Técnico trabajando en PipeCell" class="w-full h-[520px] object-cover object-bottom">
            </div>
            <div class="absolute -bottom-6 -right-6 bg-blue-600 text-white rounded-2xl p-6 shadow-xl">
                <p class="text-4xl font-bold">3+</p>
                <p class="text-blue-200 text-sm mt-1">Años de experiencia</p>
            </div>
        </div>

        {{-- Texto --}}
        <div>
            <span class="text-blue-600 text-sm font-semibold uppercase tracking-wider">Por qué elegirnos</span>
            <h2 class="text-4xl font-bold text-gray-900 mt-2 mb-6">Expertos en cada detalle</h2>
            <p class="text-gray-500 text-lg mb-10 leading-relaxed">
                No somos un servicio técnico más. Trabajamos con pasión por los dispositivos móviles y un compromiso real con cada cliente.
            </p>

            <div class="space-y-7">
                @foreach([
                    ['shield-check', 'Diagnóstico gratuito y sin compromiso',
                     'Revisamos tu equipo sin costo. Si no te convence el precio, sin ningún problema.'],
                    ['badge-check',  'Garantía por escrito de 3 meses',
                     'Respaldamos cada reparación con garantía real y documentada.'],
                    ['clock',        'Entrega en el mismo día',
                     'La mayoría de reparaciones se entregan en menos de 24 horas.'],
                    ['cube',         'Repuestos de calidad certificada',
                     'Usamos piezas originales o de alta calidad, nunca materiales de baja gama.'],
                ] as [$icon, $title, $desc])
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center flex-shrink-0 mt-1">
                        @if($icon === 'shield-check')
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        @elseif($icon === 'badge-check')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        @elseif($icon === 'clock')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        @else
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        @endif
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 mb-1">{{ $title }}</h3>
                        <p class="text-gray-500 text-sm">{{ $desc }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ===================================================== --}}
{{-- NUESTRO EQUIPO                                         --}}
{{-- ===================================================== --}}
<section id="tecnicos" class="py-24 px-6 bg-gray-50">
    <div class="max-w-7xl mx-auto">

        <div class="text-center mb-16">
            <span class="text-blue-600 text-sm font-semibold uppercase tracking-wider">Nuestro equipo</span>
            <h2 class="text-4xl font-bold text-gray-900 mt-2 mb-4">Los expertos detrás de cada reparación</h2>
            <p class="text-gray-500 max-w-xl mx-auto text-lg">Técnicos certificados con años de experiencia en reparación de smartphones</p>
        </div>

        <div class="grid md:grid-cols-2 gap-8 max-w-5xl mx-auto">

            {{-- Técnico 1 --}}
            <div class="bg-white rounded-3xl overflow-hidden border border-gray-100 hover:shadow-xl hover:border-blue-100 transition-all duration-300 group">
                <div class="relative h-64 overflow-hidden bg-blue-50">
                    {{-- Reemplaza este src con la foto real del técnico --}}
                    <img src="https://placehold.co/440x300/dbeafe/1e40af?text=📷+Foto+Técnico+1"
                         alt="Técnico 1" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    <div class="absolute top-4 right-4 bg-blue-600 text-white text-xs font-semibold px-3 py-1 rounded-full shadow">
                        Técnico Senior
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-1">Nombre Técnico 1</h3>
                    <p class="text-blue-600 text-sm font-medium mb-3">Especialista en pantallas y hardware</p>
                    <p class="text-gray-500 text-sm leading-relaxed">
                        Más de 5 años reparando dispositivos Android e iOS. Especializado en cambio de pantallas y reparación de placa madre.
                    </p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span class="bg-blue-50 text-blue-700 text-xs px-3 py-1 rounded-full font-medium">Pantallas</span>
                        <span class="bg-blue-50 text-blue-700 text-xs px-3 py-1 rounded-full font-medium">Hardware</span>
                        <span class="bg-blue-50 text-blue-700 text-xs px-3 py-1 rounded-full font-medium">Samsung</span>
                    </div>
                </div>
            </div>

            {{-- Técnico 2 --}}
            <div class="bg-white rounded-3xl overflow-hidden border border-gray-100 hover:shadow-xl hover:border-blue-100 transition-all duration-300 group">
                <div class="relative h-64 overflow-hidden bg-blue-50">
                    {{-- Reemplaza este src con la foto real del técnico --}}
                    <img src="https://placehold.co/440x300/dbeafe/1e40af?text=📷+Foto+Técnico+2"
                         alt="Técnico 2" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    <div class="absolute top-4 right-4 bg-blue-600 text-white text-xs font-semibold px-3 py-1 rounded-full shadow">
                        Técnico Senior
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-1">Nombre Técnico 2</h3>
                    <p class="text-blue-600 text-sm font-medium mb-3">Especialista en software e iOS</p>
                    <p class="text-gray-500 text-sm leading-relaxed">
                        Experto en sistemas operativos iOS y Android. Recuperación de datos, desbloqueo y restauración de equipos dañados.
                    </p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span class="bg-blue-50 text-blue-700 text-xs px-3 py-1 rounded-full font-medium">Software</span>
                        <span class="bg-blue-50 text-blue-700 text-xs px-3 py-1 rounded-full font-medium">iOS</span>
                        <span class="bg-blue-50 text-blue-700 text-xs px-3 py-1 rounded-full font-medium">Datos</span>
                    </div>
                </div>
            </div>

            

        </div>
    </div>
</section>

{{-- ===================================================== --}}
{{-- TRABAJOS REALIZADOS                                    --}}
{{-- ===================================================== --}}
<section id="trabajos" class="py-24 px-6 bg-white">
    <div class="max-w-7xl mx-auto">

        <div class="text-center mb-16">
            <span class="text-blue-600 text-sm font-semibold uppercase tracking-wider">Portafolio</span>
            <h2 class="text-4xl font-bold text-gray-900 mt-2 mb-4">Trabajos realizados</h2>
            <p class="text-gray-500 max-w-xl mx-auto text-lg">Algunos de los dispositivos que hemos devuelto a la vida</p>
        </div>

        <div class="grid md:grid-cols-2 gap-6">

            {{-- Trabajo 1 --}}
            <div class="group rounded-3xl overflow-hidden border border-gray-100 hover:shadow-xl transition-all duration-300">
                <div class="relative h-56 overflow-hidden">
                    {{-- Reemplaza este src con foto real del trabajo --}}
                    <img src="https://placehold.co/700x400/eff6ff/1d4ed8?text=📷+Samsung+Galaxy+S23+-+Pantalla"
                         alt="Samsung Galaxy S23" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                    <span class="absolute bottom-4 left-4 bg-emerald-500 text-white text-xs font-semibold px-3 py-1 rounded-full">Entregado</span>
                </div>
                <div class="p-6 bg-white">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Samsung</span>
                        <span class="w-1 h-1 bg-gray-300 rounded-full"></span>
                        <span class="text-xs text-gray-400">Reparación de pantalla</span>
                    </div>
                    <h3 class="font-bold text-gray-900 text-lg">Cambio de pantalla Galaxy S23</h3>
                    <p class="text-sm text-gray-500 mt-2">Pantalla AMOLED original, reparación completada en 2 horas sin pérdida de datos.</p>
                </div>
            </div>

            {{-- Trabajo 2 --}}
            <div class="group rounded-3xl overflow-hidden border border-gray-100 hover:shadow-xl transition-all duration-300">
                <div class="relative h-56 overflow-hidden">
                    {{-- Reemplaza este src con foto real del trabajo --}}
                    <img src="https://placehold.co/700x400/f0fdf4/15803d?text=📷+iPhone+14+Pro+-+Batería"
                         alt="iPhone 14 Pro" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                    <span class="absolute bottom-4 left-4 bg-emerald-500 text-white text-xs font-semibold px-3 py-1 rounded-full">Entregado</span>
                </div>
                <div class="p-6 bg-white">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">iPhone</span>
                        <span class="w-1 h-1 bg-gray-300 rounded-full"></span>
                        <span class="text-xs text-gray-400">Cambio de batería</span>
                    </div>
                    <h3 class="font-bold text-gray-900 text-lg">Cambio de batería iPhone 14 Pro</h3>
                    <p class="text-sm text-gray-500 mt-2">Batería certificada Apple. Duración restaurada al 100% con 6 meses de garantía.</p>
                </div>
            </div>

            {{-- Trabajo 3 --}}
            <div class="group rounded-3xl overflow-hidden border border-gray-100 hover:shadow-xl transition-all duration-300">
                <div class="relative h-56 overflow-hidden">
                    {{-- Reemplaza este src con foto real del trabajo --}}
                    <img src="https://placehold.co/700x400/fff7ed/c2410c?text=📷+Redmi+Note+12+-+Pin+Carga"
                         alt="Redmi Note 12" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                    <span class="absolute bottom-4 left-4 bg-emerald-500 text-white text-xs font-semibold px-3 py-1 rounded-full">Entregado</span>
                </div>
                <div class="p-6 bg-white">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Xiaomi</span>
                        <span class="w-1 h-1 bg-gray-300 rounded-full"></span>
                        <span class="text-xs text-gray-400">Pin de carga</span>
                    </div>
                    <h3 class="font-bold text-gray-900 text-lg">Reparación pin de carga Redmi Note 12</h3>
                    <p class="text-sm text-gray-500 mt-2">Cambio de conector USB-C mediante soldadura de precisión. Listo en 3 horas.</p>
                </div>
            </div>

            {{-- Trabajo 4 --}}
            <div class="group rounded-3xl overflow-hidden border border-gray-100 hover:shadow-xl transition-all duration-300">
                <div class="relative h-56 overflow-hidden">
                    {{-- Reemplaza este src con foto real del trabajo --}}
                    <img src="https://placehold.co/700x400/fdf4ff/7e22ce?text=📷+Moto+G54+-+Software"
                         alt="Moto G54" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                    <span class="absolute bottom-4 left-4 bg-emerald-500 text-white text-xs font-semibold px-3 py-1 rounded-full">Entregado</span>
                </div>
                <div class="p-6 bg-white">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Motorola</span>
                        <span class="w-1 h-1 bg-gray-300 rounded-full"></span>
                        <span class="text-xs text-gray-400">Software</span>
                    </div>
                    <h3 class="font-bold text-gray-900 text-lg">Reparación de software Moto G54</h3>
                    <p class="text-sm text-gray-500 mt-2">Restauración completa del sistema Android, eliminación de malware y recuperación de datos.</p>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ===================================================== --}}
{{-- TESTIMONIOS                                            --}}
{{-- ===================================================== --}}
<section class="py-24 px-6 bg-gray-50">
    <div class="max-w-7xl mx-auto">

        <div class="text-center mb-16">
            <span class="text-blue-600 text-sm font-semibold uppercase tracking-wider">Testimonios</span>
            <h2 class="text-4xl font-bold text-gray-900 mt-2 mb-4">Lo que dicen nuestros clientes</h2>
        </div>

        <div class="grid md:grid-cols-3 gap-6">

            @foreach([
                ['JM', 'Juan Martínez',    'blue',
                 '"Excelente servicio. Me repararon la pantalla del Samsung en menos de 2 horas y a muy buen precio. Definitivamente volvería."'],
                ['LR', 'Laura Rodríguez',  'pink',
                 '"Le cambiaron la batería a mi iPhone y quedó como nuevo. El diagnóstico fue gratis y el precio justo. Muy recomendados."'],
                ['CG', 'Carlos García',    'green',
                 '"Mi Redmi no cargaba y pensé que tocaba comprarlo nuevo. En PipeCell lo repararon el mismo día a un precio excelente."'],
            ] as [$initials, $name, $color, $review])
            <div class="bg-white rounded-2xl p-8 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex gap-1 mb-4">
                    @for ($i = 0; $i < 5; $i++)
                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    @endfor
                </div>
                <p class="text-gray-600 text-sm leading-relaxed mb-6 italic">{{ $review }}</p>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-{{ $color }}-100 rounded-full flex items-center justify-center text-{{ $color }}-600 font-bold text-sm">
                        {{ $initials }}
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 text-sm">{{ $name }}</p>
                        <p class="text-gray-400 text-xs">Cliente verificado</p>
                    </div>
                </div>
            </div>
            @endforeach

        </div>
    </div>
</section>

{{-- ===================================================== --}}
{{-- CONTACTO                                               --}}
{{-- ===================================================== --}}
<section id="contacto" class="py-24 px-6 bg-white">
    <div class="max-w-7xl mx-auto grid lg:grid-cols-2 gap-16 items-start">

        {{-- Info --}}
        <div>
            <span class="text-blue-600 text-sm font-semibold uppercase tracking-wider">Contáctanos</span>
            <h2 class="text-4xl font-bold text-gray-900 mt-2 mb-4">¿Listo para reparar tu equipo?</h2>
            <p class="text-gray-500 text-lg mb-10 leading-relaxed">
                Escríbenos, llámanos o visítanos. Estamos listos para ayudarte con tu dispositivo.
            </p>

            <div class="space-y-4 mb-8">

                {{-- WhatsApp --}}
                <a href="https://wa.me/573113105244?text=Hola%2C%20quiero%20cotizar%20una%20reparaci%C3%B3n%20%F0%9F%93%B1" target="_blank"
                   class="flex items-center gap-4 p-5 rounded-2xl border border-gray-100 hover:border-green-200 hover:bg-green-50/50 transition-all duration-300 group">
                    <div class="w-12 h-12 bg-green-100 text-green-600 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492l4.638-1.467A11.932 11.932 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.75c-2.146 0-4.142-.685-5.77-1.848l-.413-.268-2.75.87.908-2.684-.293-.435A9.709 9.709 0 012.25 12c0-5.385 4.365-9.75 9.75-9.75S21.75 6.615 21.75 12s-4.365 9.75-9.75 9.75z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-900">WhatsApp</p>
                        <p class="text-sm text-gray-500">+57 311 310 5244 &middot; Respuesta rápida</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                {{-- Teléfono --}}
                <a href="tel:+573113105244"
                   class="flex items-center gap-4 p-5 rounded-2xl border border-gray-100 hover:border-blue-200 hover:bg-blue-50/50 transition-all duration-300 group">
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-900">Llámanos</p>
                        <p class="text-sm text-gray-500">+57 311 310 5244  &middot; Atención directa</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                {{-- Ubicación --}}
                <a href="https://maps.app.goo.gl/a8CLPCiMUcQJdqjq8" target="_blank" class="flex items-center gap-4 p-5 rounded-2xl border border-gray-100 hover:border-purple-200 hover:bg-purple-50/50 transition-all duration-300 group">
                    <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">Visítanos</p>
                        <p class="text-sm text-gray-500">Ver en Google Maps</p>
                    </div>
                </a>
            </div>

            {{-- Horario --}}
            <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100">
                <h4 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Horario de atención
                </h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Lunes – Viernes</span>
                        <span class="font-semibold text-gray-900">8:00 am – 7:00 pm</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Sábado</span>
                        <span class="font-semibold text-gray-900">9:00 am – 5:00 pm</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Domingo</span>
                        <span class="text-gray-400">Cerrado</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Imagen del local --}}
        <div class="relative hidden lg:block">
            <div class="rounded-3xl overflow-hidden shadow-xl">
                {{-- Reemplaza este src con una foto real del local/fachada --}}
                <img src="{{ asset('img/landing/fachada-local.webp') }}"
                     alt="Local PipeCell" class="w-full h-[600px] object-cover">
            </div>
            <div class="absolute -bottom-6 -left-6 bg-blue-600 text-white rounded-2xl p-6 shadow-xl">
                <p class="text-3xl font-bold">+1000</p>
                <p class="text-blue-200 text-sm mt-1">Reparaciones exitosas</p>
            </div>
        </div>

    </div>
</section>

{{-- ===================================================== --}}
{{-- BANNER CTA                                             --}}
{{-- ===================================================== --}}
<section class="py-20 px-6 bg-gradient-to-r from-blue-600 to-blue-800">
    <div class="max-w-4xl mx-auto text-center">
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">
            ¿Tu celular tiene un problema?
        </h2>
        <p class="text-blue-100 text-lg mb-8 max-w-2xl mx-auto leading-relaxed">
            Escríbenos ahora y te damos un diagnóstico sin costo. La reparación puede ser más fácil y económica de lo que crees.
        </p>
        <a href="https://wa.me/573113105244?text=Hola%2C%20quiero%20cotizar%20una%20reparaci%C3%B3n%20%F0%9F%93%B1" target="_blank"
           class="inline-flex items-center gap-3 bg-white text-blue-700 font-bold px-10 py-4 rounded-xl hover:bg-blue-50 transition-all duration-200 shadow-xl text-lg">
            <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492l4.638-1.467A11.932 11.932 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.75c-2.146 0-4.142-.685-5.77-1.848l-.413-.268-2.75.87.908-2.684-.293-.435A9.709 9.709 0 012.25 12c0-5.385 4.365-9.75 9.75-9.75S21.75 6.615 21.75 12s-4.365 9.75-9.75 9.75z"/>
            </svg>
            Escribir por WhatsApp
        </a>
    </div>
</section>

{{-- Botón flotante de WhatsApp --}}
<a href="https://wa.me/573113105244?text=Hola%2C%20quiero%20cotizar%20una%20reparaci%C3%B3n%20%F0%9F%93%B1"
   target="_blank"
   class="fixed bottom-6 right-6 z-50 bg-green-500 hover:bg-green-400 text-white p-4 rounded-full shadow-lg shadow-green-500/40 transition-all duration-200 hover:scale-105">
    <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347zM12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492l4.638-1.467A11.932 11.932 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.75c-2.146 0-4.142-.685-5.77-1.848l-.413-.268-2.75.87.908-2.684-.293-.435A9.709 9.709 0 012.25 12c0-5.385 4.365-9.75 9.75-9.75S21.75 6.615 21.75 12s-4.365 9.75-9.75 9.75z"/>
    </svg>
</a>

@endsection
