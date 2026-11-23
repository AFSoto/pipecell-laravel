// Lo común de las pruebas de navegador (ver README.md).

export const USUARIO = { email: 'e2e@pruebas.test', clave: 'clave-e2e' };

// Tamaño iPad, apaisado, con pantalla táctil
const PANTALLA = { viewport: { width: 1180, height: 820 }, hasTouch: true };

export const dormir = ms => new Promise(listo => setTimeout(listo, ms));

/** Un revisor por motor: imprime ✓ / ✗ y cuenta los fallos. */
export function crearRevisor(motor) {
    let fallos = 0;

    return {
        revisar(ok, texto, detalle = '') {
            console.log(`${ok ? '✓' : '✗'} [${motor}] ${texto}${ok || detalle === '' ? '' : `  →  ${detalle}`}`);
            if (!ok) fallos++;
        },
        fallos: () => fallos,
    };
}

/**
 * Pestaña nueva con la sesión iniciada. Pusher se bloquea (el servidor de prueba no
 * transmite) y sus errores no cuentan; los demás errores de JavaScript quedan en errores.
 */
export async function nuevaPagina(navegador, app) {
    const contexto = await navegador.newContext(PANTALLA);
    await contexto.route(/pusher/, ruta => ruta.abort());

    const pagina  = await contexto.newPage();
    const errores = [];
    pagina.on('pageerror', error => { if (!/pusher/i.test(error.message)) errores.push(error.message); });

    await pagina.goto(`${app}/login`);
    await pagina.fill('input[name=email]', USUARIO.email);
    await pagina.fill('input[name=password]', USUARIO.clave);
    await Promise.all([pagina.waitForURL(/\/admin/), pagina.press('input[name=password]', 'Enter')]);

    return { contexto, pagina, errores };
}

/** Espera hasta que la función sea verdadera en la página. true si llegó a tiempo. */
export const esperar = (pagina, funcion, argumento, ms = 6000) =>
    pagina.waitForFunction(funcion, argumento, { timeout: ms }).then(() => true, () => false);

export const busquedaEnUrl = pagina => pagina.evaluate(() => new URL(location.href).searchParams.get('buscar'));

/** Espera a que la búsqueda en vivo termine con ese texto (null: sin búsqueda). */
export const esperarBusqueda = (pagina, texto, ms = 8000) => esperar(pagina, texto =>
    new URL(location.href).searchParams.get('buscar') === texto
        && !document.querySelector('[data-en-vivo="resultados"][aria-busy]'), texto, ms);

export const textoDe = (pagina, selector) => pagina.evaluate(s => document.querySelector(s)?.textContent ?? '', selector);

/** Pone el cursor al final del campo, como al tocarlo. */
export async function enfocar(pagina, selector) {
    await pagina.focus(selector);
    await pagina.evaluate(s => { const campo = document.querySelector(s); campo.setSelectionRange(campo.value.length, campo.value.length); }, selector);
}

export const escribir = (pagina, texto, pausa = 120) => pagina.keyboard.type(texto, { delay: pausa });

export async function borrar(pagina, veces) {
    for (let i = 0; i < veces; i++) {
        await pagina.keyboard.press('Backspace');
        await dormir(80);
    }
}

/** Marca la página: si recarga, la marca se pierde. */
export const marcar      = pagina => pagina.evaluate(() => { window.sinRecargar = true; });
export const sinRecargar = pagina => pagina.evaluate(() => window.sinRecargar === true).catch(() => false);

/**
 * Anota los pedidos de la búsqueda en vivo (GET de la lista, no los de tiempo real) y si se
 * cancelaron: window.pedidosBusqueda = [{ buscar, cancelado }].
 */
export async function contarPedidos(pagina) {
    await pagina.evaluate(() => {
        if (window.pedidosBusqueda) return;
        window.pedidosBusqueda = [];

        const original = window.fetch;
        window.fetch = (url, opciones = {}) => {
            const direccion = new URL(String(url), location.href);

            if (/^\/admin\/(productos|reparaciones)$/.test(direccion.pathname)) {
                const pedido = { buscar: direccion.searchParams.get('buscar'), cancelado: false };
                window.pedidosBusqueda.push(pedido);
                opciones.signal?.addEventListener('abort', () => { pedido.cancelado = true; });
            }

            return original(url, opciones);
        };
    });
}

export const pedidos = pagina => pagina.evaluate(() => window.pedidosBusqueda);

/**
 * Hace que las respuestas de la búsqueda en vivo lleguen tarde (red lenta): el servidor la
 * arma enseguida y el navegador la recibe ms después. Devuelve la función que lo quita.
 */
export async function demorarBusquedas(pagina, ms) {
    const patron = /\/admin\/(productos|reparaciones)\?.*buscar=/;

    await pagina.route(patron, async ruta => {
        try {
            const respuesta = await ruta.fetch();
            await dormir(ms);
            await ruta.fulfill({ response: respuesta });
        } catch { /* la página ya lo canceló */ }
    });

    return () => pagina.unroute(patron);
}

/** Simula un aviso de Pusher sobre una reparación (lo que dispara resources/js/tiempo-real.js). */
export const avisoTiempoReal = (pagina, id, accion, estado = null) => pagina.evaluate(([id, accion, estado]) =>
    window.dispatchEvent(new CustomEvent('tiempo-real:reparacion', { detail: { id, accion, estado, autor: 'Otra persona' } })),
    [Number(id), accion, estado]);
