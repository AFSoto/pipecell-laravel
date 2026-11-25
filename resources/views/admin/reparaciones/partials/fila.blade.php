{{--
    Una fila de la tabla de reparaciones.

    Es la única plantilla de la fila: la usan la carga normal de la página y
    GET /admin/reparaciones/sincronizar (tiempo real y puesta al día al volver de
    estar sin conexión), así lo que llega en vivo es idéntico a lo que se ve al recargar.

    Parámetros:
      $rep  Reparacion con caja, abonos, repuestos.producto y ultimoWhatsapp cargados
--}}
@php
    $diasEnProceso = $rep->estado->value === 'en_proceso'
        ? (int) floor($rep->created_at->diffInDays(now()))
        : null;
    $filaClase = match(true) {
        $diasEnProceso >= 15 => 'hover:bg-red-50/80 bg-red-50/40',
        $diasEnProceso >= 8  => 'hover:bg-amber-50/80 bg-amber-50/40',
        default              => 'hover:bg-gray-50/50',
    };
    $display = $rep->repuestos->first();

    // Al pasar a «Arreglado», a qué celular se le avisaría por WhatsApp: con uno, la pantalla
    // pregunta antes si se avisa (modal-arreglado). Vacío: no se avisaría y pasa directo.
    // Misma regla que el envío (NotificacionService::celularParaAviso)
    $avisarA = app(\App\Services\NotificacionService::class)->celularParaAviso($rep);
@endphp
<tr class="transition reparacion-row {{ $filaClase }}"

    data-id="{{ $rep->id }}"
    data-estado="{{ $rep->estado->value }}"
    data-avisar-a="{{ $avisarA }}"
    data-whatsapp="{{ $rep->ultimoWhatsapp ? json_encode($rep->ultimoWhatsapp->paraVista()) : '' }}"
    data-edit="{{ json_encode($rep->datosEdicion()) }}">

                    <td class="py-3 px-4 text-gray-400 text-xs">#{{ $rep->id }}</td>

                    <td class="py-3 px-4 whitespace-nowrap">
    <p class="text-gray-700 text-xs">{{ $rep->created_at->format('d/m/Y') }}</p>
    <p class="text-gray-400 text-xs">{{ $rep->created_at->format('h:i A') }}</p>
    @if($diasEnProceso >= 15)
        <span class="inline-block mt-1 text-xs font-semibold px-2 py-0.5 rounded-lg bg-red-100 text-red-600">
            {{ $diasEnProceso }} días
        </span>
    @elseif($diasEnProceso >= 8)
        <span class="inline-block mt-1 text-xs font-semibold px-2 py-0.5 rounded-lg bg-amber-100 text-amber-600">
            {{ $diasEnProceso }} días
        </span>
    @endif
</td>

                    <td class="py-3 px-4" data-celda="cliente">
    <p class="font-medium text-gray-900" data-campo="nombre">{{ $rep->nombre_cliente }}</p>
    <p class="text-xs text-gray-400" data-campo="telefono">{{ $rep->telefono_cliente ?? 'Sin teléfono' }}</p>
    {{-- Indicador del aviso por WhatsApp: lo pinta pintarWhatsapp() con data-whatsapp --}}
    <div data-whatsapp></div>
</td>

                    <td class="py-3 px-4" data-celda="equipo">
    <p class="text-gray-700" data-campo="marca">{{ $rep->marca }}</p>
    <p class="text-xs text-gray-400" data-campo="modelo">{{ $rep->modelo ?? '—' }}</p>
    @php $textoDisplay = $display ? "Display: {$display->producto->nombre} ×{$display->cantidad}" : ''; @endphp
    <p data-campo="display" class="mt-1 {{ $display ? '' : 'hidden' }}">
        <span class="inline-block max-w-50 truncate align-bottom text-[11px] font-medium px-2 py-0.5 rounded-lg bg-blue-50 text-blue-700"
              title="{{ $textoDisplay }}">{{ $textoDisplay }}</span>
    </p>
</td>

                    {{-- Descripción de la falla recortada a 20 caracteres; el texto completo va en el tooltip --}}
                    <td class="py-3 px-4 text-xs whitespace-nowrap {{ $rep->descripcion_falla ? 'text-purple-600 font-medium' : 'text-gray-400' }}" data-celda="descripcion" title="{{ $rep->descripcion_falla }}">
    {{ $rep->descripcionCorta() ?? '—' }}
</td>

                    <td class="py-3 px-4">
                        <span class="bg-gray-100 text-gray-700 text-xs font-bold px-2.5 py-1 rounded-lg">
                            {{ $rep->caja->nombre_display }}
                        </span>
                    </td>

                    <td class="py-3 px-4 font-medium text-gray-900" data-celda="valor">
    ${{ number_format($rep->valor_total, 0, ',', '.') }}
</td>
                    <td class="py-3 px-4 text-gray-700" data-celda="abonado">
    ${{ number_format($rep->total_abonado, 0, ',', '.') }}
</td>

                    <td class="py-3 px-4" data-celda="saldo">
    @php $saldo = $rep->saldo_pendiente; @endphp
                        <span class="{{ $saldo > 0 ? 'text-red-600' : 'text-green-600' }} font-medium">
                            ${{ number_format($saldo, 0, ',', '.') }}
                        </span>
                    </td>

                    <td class="py-3 px-4">
                        <select onchange="cambiarEstado(this, {{ $rep->id }})"
                                class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach(App\Enums\EstadoReparacion::cases() as $estado)
                                <option value="{{ $estado->value }}" {{ $rep->estado === $estado ? 'selected' : '' }}>
                                    {{ $estado->label() }}
                                </option>
                            @endforeach
                        </select>
                    </td>

                    <td class="py-3 px-4">
                        <div class="flex items-center gap-2">
                            {{-- Botón editar --}}
                            <button onclick="abrirModalEditar(this)"
        class="text-gray-400 hover:text-blue-600 transition"
        title="Editar reparación">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            {{-- Botón registrar abono (solo visible si hay saldo pendiente).
                                 No lleva datos en el onclick: abrirModalAbono() los lee de la fila --}}
                            <button onclick="abrirModalAbono(this)"
                                    class="text-blue-600 hover:text-blue-800 transition {{ $rep->saldo_pendiente <= 0 ? 'hidden' : '' }}"
                                    data-btn="abono"
                                    title="Registrar abono">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </button>
                            {{-- Botón pagar saldo total (solo visible si hay saldo pendiente) --}}
                            <button onclick="pagarSaldoTotal({{ $rep->id }})"
                                    class="text-green-600 hover:text-green-800 transition {{ $rep->saldo_pendiente <= 0 ? 'hidden' : '' }}"
                                    data-btn="pagar-total"
                                    title="Pagar saldo total">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
