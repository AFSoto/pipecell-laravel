/**
 * Tiempo real del panel (Pusher Channels + Laravel Echo).
 *
 * - Solo se conecta en las páginas que escuchan los avisos: las que declaran
 *   @section('tiempo-real') en su vista (el layout pone <meta name="tiempo-real">).
 *   Por ahora, Reparaciones. Las demás no abren conexión ni muestran el indicador.
 * - Necesita la clave de Pusher en el build (VITE_PUSHER_APP_KEY en el .env al correr
 *   npm run build). Sin clave no se conecta y la plataforma funciona como siempre.
 * - Cada aviso se reenvía como evento del navegador y la página decide qué hacer:
 *     tiempo-real:reparacion       detail = { id, accion, estado, autor }
 *     tiempo-real:cajas            se creó una caja
 *     tiempo-real:ponerse-al-dia   el canal se suscribió (la primera vez y después de cada
 *                                  reconexión) o la pestaña volvió: se pudieron perder
 *                                  avisos, hay que pedir el estado actual
 *     tiempo-real:sesion-vencida   el servidor no autorizó el canal (la página también lo
 *                                  dispara si /sincronizar responde 401 o 419)
 * - Pinta el indicador del encabezado (#indicador-en-vivo). "En vivo" sale solo cuando el
 *   canal quedó suscrito: con el socket conectado todavía no llegan avisos.
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

const PAGINA_EN_VIVO = document.querySelector('meta[name="tiempo-real"]') !== null;

// Tiempo con la pestaña oculta a partir del cual, al volver, se pide el estado actual
const MAXIMO_OCULTA_MS = 15000;

// Respuestas de /broadcasting/auth que significan "sin sesión" (o usuario sin acceso)
const ESTADOS_SIN_SESION = [401, 403, 419];

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
    sinSesion: {
        texto:  'Recarga la página',
        color:  '#f59e0b',
        titulo: 'Tu sesión venció (o tu usuario ya no tiene acceso): recarga la página para volver a ver los cambios de los demás.',
    },
};

// Con la sesión vencida el indicador se queda en ámbar hasta recargar: aunque el socket
// vuelva a conectar, sin sesión la pantalla ya no puede pedir las filas
let sesionVencida = false;

function pintarIndicador(estado) {
    const indicador = document.getElementById('indicador-en-vivo');
    if (!indicador || (sesionVencida && estado !== 'sinSesion')) return;

    const { texto, color, titulo } = ESTADOS_INDICADOR[estado];
    indicador.querySelector('[data-punto]').style.backgroundColor = color;
    indicador.querySelector('[data-texto]').textContent = texto;
    indicador.title = titulo;
    indicador.classList.remove('hidden');
    indicador.classList.add('flex');
}

function avisarPagina(nombre, detalle = {}) {
    window.dispatchEvent(new CustomEvent(nombre, { detail: detalle }));
}

window.cabecerasTiempoReal = () => {
    const socketId = window.Echo?.socketId?.();
    return socketId ? { 'X-Socket-ID': socketId } : {};
};

// Lo dispara este archivo si el canal no se autoriza, o la página si /sincronizar
// responde sin sesión. La página muestra el aviso; aquí se pinta el indicador.
window.addEventListener('tiempo-real:sesion-vencida', () => {
    sesionVencida = true;
    pintarIndicador('sinSesion');
});

if (PAGINA_EN_VIVO && !CLAVE) {
    pintarIndicador('sinConfigurar');
} else if (PAGINA_EN_VIVO) {
    conectar();
}

function conectar() {
    window.Pusher = Pusher;
    window.Echo   = new Echo({
        broadcaster: 'pusher',
        key:         CLAVE,
        cluster:     CLUSTER,
        forceTLS:    true,
    });

    // Pusher vuelve a suscribir el canal solo después de cada reconexión
    let suscrito = false;

    window.Echo.connector.pusher.connection.bind('state_change', ({ current }) => {
        if (current === 'connected') {
            // Conectado no basta: los avisos llegan cuando el canal termina de suscribirse
            pintarIndicador(suscrito ? 'conectado' : 'conectando');
            return;
        }

        suscrito = false;
        pintarIndicador(current === 'connecting' || current === 'initialized' ? 'conectando' : 'desconectado');
    });

    pintarIndicador('conectando');

    window.Echo.private(CANAL)
        // Suscrito, la primera vez y después de cada reconexión: desde ahora llegan los
        // avisos. Se pide el estado actual por lo que pudo pasar antes, incluido el rato
        // entre que el servidor armó la página y este momento.
        .subscribed(() => {
            suscrito = true;
            pintarIndicador('conectado');
            avisarPagina('tiempo-real:ponerse-al-dia');
        })
        // El servidor no autorizó el canal: casi siempre es la sesión vencida
        .error(error => {
            suscrito = false;

            if (ESTADOS_SIN_SESION.includes(error?.status)) {
                avisarPagina('tiempo-real:sesion-vencida');
            } else {
                pintarIndicador('desconectado');
            }
        })
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
