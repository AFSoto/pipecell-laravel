/**
 * Tiempo real del panel (Pusher Channels + Laravel Echo).
 *
 * - Se conecta al canal privado "taller" si el build tiene la clave de Pusher
 *   (VITE_PUSHER_APP_KEY en el .env al correr npm run build). Sin clave no hace
 *   nada y la plataforma funciona como siempre: cada pantalla se actualiza al recargar.
 * - Cada aviso se reenvía como evento del navegador y cada página decide qué hacer:
 *     tiempo-real:reparacion       detail = { id, accion, estado, autor }
 *     tiempo-real:cajas            se creó una caja
 *     tiempo-real:ponerse-al-dia   al reconectar o al volver la pestaña o la tablet:
 *                                  se pudieron perder avisos, hay que pedir el estado actual
 * - Pinta el indicador "En vivo" del encabezado (#indicador-en-vivo).
 * - window.cabecerasTiempoReal() da el encabezado X-Socket-ID para los fetch: así el
 *   servidor no le reenvía a esta pantalla sus propios cambios (toOthers).
 *
 * Los avisos no traen datos de clientes (Pusher es un servicio externo): la página
 * los pide a la plataforma. Ver App\Support\TiempoReal.
 */
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const CLAVE   = import.meta.env.VITE_PUSHER_APP_KEY;
const CLUSTER = import.meta.env.VITE_PUSHER_APP_CLUSTER;
const CANAL   = 'taller';

// Tiempo con la pestaña oculta a partir del cual, al volver, se pide el estado actual
const MAXIMO_OCULTA_MS = 15000;

const ESTADOS_INDICADOR = {
    conectado: {
        texto:  'En vivo',
        color:  '#22c55e',
        titulo: 'Los cambios de otras personas aparecen solos.',
    },
    conectando: {
        texto:  'Conectando…',
        color:  '#d1d5db',
        titulo: 'Conectando con el tiempo real.',
    },
    desconectado: {
        texto:  'Sin conexión en vivo',
        color:  '#9ca3af',
        titulo: 'Sin conexión en vivo: los cambios de otras personas se verán al reconectar o al recargar.',
    },
    sinConfigurar: {
        texto:  'Sin conexión en vivo',
        color:  '#9ca3af',
        titulo: 'El tiempo real no está configurado: recarga la página para ver los cambios de otras personas.',
    },
};

function pintarIndicador(estado) {
    const indicador = document.getElementById('indicador-en-vivo');
    if (!indicador) return;

    const { texto, color, titulo } = ESTADOS_INDICADOR[estado];
    indicador.querySelector('[data-punto]').style.backgroundColor = color;
    indicador.querySelector('[data-texto]').textContent = texto;
    indicador.title = titulo;
    indicador.classList.remove('hidden');
}

function avisarPagina(nombre, detalle = {}) {
    window.dispatchEvent(new CustomEvent(nombre, { detail: detalle }));
}

window.cabecerasTiempoReal = () => {
    const socketId = window.Echo?.socketId?.();
    return socketId ? { 'X-Socket-ID': socketId } : {};
};

if (!CLAVE) {
    pintarIndicador('sinConfigurar');
} else {
    window.Pusher = Pusher;
    window.Echo   = new Echo({
        broadcaster: 'pusher',
        key:         CLAVE,
        cluster:     CLUSTER,
        forceTLS:    true,
    });

    // Indicador y reconexión: si ya había estado conectada, al volver hay que ponerse al día
    let yaSeConecto = false;

    window.Echo.connector.pusher.connection.bind('state_change', ({ current }) => {
        if (current === 'connected') {
            pintarIndicador('conectado');
            if (yaSeConecto) avisarPagina('tiempo-real:ponerse-al-dia');
            yaSeConecto = true;
        } else if (current === 'connecting' || current === 'initialized') {
            pintarIndicador('conectando');
        } else {
            pintarIndicador('desconectado');
        }
    });

    pintarIndicador('conectando');

    window.Echo.private(CANAL)
        .listen('.reparacion.cambio', datos => avisarPagina('tiempo-real:reparacion', datos))
        .listen('.cajas.cambio', () => avisarPagina('tiempo-real:cajas'));

    // Safari corta la conexión cuando la tablet se bloquea o la pestaña queda atrás.
    // Al volver después de un rato, se pide el estado actual aunque no haya reconexión visible.
    let ocultaDesde = null;

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            ocultaDesde = Date.now();
            return;
        }

        if (ocultaDesde && Date.now() - ocultaDesde > MAXIMO_OCULTA_MS) {
            avisarPagina('tiempo-real:ponerse-al-dia');
        }
        ocultaDesde = null;
    });
}
