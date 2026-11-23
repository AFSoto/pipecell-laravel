/**
 * Búsqueda en vivo en las listas del panel (Productos y Reparaciones).
 *
 * Se activa en cada <form data-busqueda-en-vivo> que tenga un campo "buscar":
 *   - Mientras se escribe, desde 3 letras (y al bajar de 3 o borrar todo), pide la misma
 *     página con la búsqueda nueva y reemplaza las partes marcadas con
 *     data-en-vivo="nombre" por las de la respuesta: la lista, la paginación, el
 *     contador, los enlaces que llevan la búsqueda... El campo no se reemplaza: no pierde
 *     el foco y el teclado del iPad no se cierra.
 *   - Enter (o el botón de buscar) busca enseguida, desde 2 letras: lo mínimo que acepta
 *     el servidor, para marcas como "LG". Cierra el teclado, como la tecla Buscar.
 *   - Los botones con nombre (como "Stock bajo") y los selects que llaman form.submit()
 *     siguen recargando la página como siempre.
 *
 * El servidor arma la página igual que en una carga normal (no hay una vista aparte para
 * esto): lo que se ve en vivo es lo mismo que se vería recargando.
 *
 * Al terminar avisa con el evento "busqueda-en-vivo:actualizada" en document, para que
 * cada página ponga al día lo suyo (ver productos/index y reparaciones/index).
 *
 * No manda X-Requested-With a propósito: así Laravel guarda la búsqueda como "página
 * anterior", y un formulario que vuelve con errores vuelve a la misma búsqueda.
 */

const MINIMO_EN_VIVO   = 3;   // letras para buscar mientras se escribe
const MINIMO_CON_ENTER = 2;   // con Enter: el mínimo del servidor (ProductoController, ReparacionController)
const ESPERA_MS        = 400; // desde la última tecla

document.querySelectorAll('form[data-busqueda-en-vivo]').forEach(iniciar);

function iniciar(form) {
    const campo = form.elements.namedItem('buscar');
    if (!(campo instanceof HTMLInputElement)) return;

    let temporizador = null;
    let pedido       = null; // AbortController del pedido en curso
    let urlPedida    = null; // la URL de ese pedido
    let urlVista     = urlDeBusqueda(form, campo.value.trim(), MINIMO_CON_ENTER); // lo que muestra la página

    campo.addEventListener('input', () => {
        clearTimeout(temporizador);
        temporizador = setTimeout(() => buscar(MINIMO_EN_VIVO), ESPERA_MS);
    });

    form.addEventListener('submit', evento => {
        if (evento.submitter?.name) return;

        evento.preventDefault();
        clearTimeout(temporizador);
        campo.blur();
        buscar(MINIMO_CON_ENTER);
    });

    async function buscar(minimo) {
        const url = urlDeBusqueda(form, campo.value.trim(), minimo);

        if (pedido && url === urlPedida) return; // ya se está pidiendo

        // Una respuesta vieja nunca se pinta: el pedido anterior se cancela
        pedido?.abort();
        pedido = null;

        if (url === urlVista) { // ya se ve eso
            marcarCargando(false);
            return;
        }

        const este = new AbortController();
        pedido    = este;
        urlPedida = url;
        marcarCargando(true);

        try {
            const respuesta = await fetch(url, { signal: este.signal, headers: { Accept: 'text/html' } });

            // Sesión vencida: el servidor manda al login. Recargando se ve el login como siempre.
            if (respuesta.status === 401 || (respuesta.redirected && new URL(respuesta.url).pathname === '/login')) {
                window.location.reload();
                return;
            }
            if (!respuesta.ok) throw new Error(`HTTP ${respuesta.status}`);

            const pagina = new DOMParser().parseFromString(await respuesta.text(), 'text/html');
            if (pedido !== este) return;

            reemplazarPartes(pagina);
            history.replaceState(history.state, '', url);
            urlVista = url;
            document.dispatchEvent(new CustomEvent('busqueda-en-vivo:actualizada', { detail: { url } }));
        } catch (error) {
            if (error.name === 'AbortError') return;

            // Sin conexión o error del servidor: la lista queda como estaba
            window.mostrarNotificacion?.('No se pudo buscar. Revisa la conexión e intenta de nuevo.', 'error');
        } finally {
            if (pedido === este) {
                pedido = null;
                marcarCargando(false);
            }
        }
    }
}

/**
 * La URL de la lista con los filtros del formulario y la búsqueda (si llega al mínimo).
 * Sin los campos vacíos ni "page": una búsqueda nueva empieza en la página 1.
 */
function urlDeBusqueda(form, texto, minimo) {
    const datos = new URLSearchParams();

    for (const [nombre, valor] of new FormData(form)) {
        if (nombre !== 'buscar' && nombre !== 'page' && typeof valor === 'string' && valor !== '') {
            datos.append(nombre, valor);
        }
    }

    if (texto.length >= minimo) datos.set('buscar', texto);

    const url  = new URL(form.action, window.location.href);
    url.search = datos.toString();

    return url.href;
}

/** Cambia cada parte data-en-vivo de la página por la de igual nombre en la respuesta. */
function reemplazarPartes(pagina) {
    document.querySelectorAll('[data-en-vivo]').forEach(parte => {
        const nueva = pagina.querySelector(`[data-en-vivo="${parte.dataset.enVivo}"]`);
        if (nueva) parte.replaceWith(document.importNode(nueva, true));
    });
}

/** Atenúa la lista mientras llega la respuesta. */
function marcarCargando(cargando) {
    document.querySelectorAll('[data-en-vivo="resultados"]').forEach(lista => {
        lista.classList.toggle('opacity-60', cargando);

        if (cargando) lista.setAttribute('aria-busy', 'true');
        else lista.removeAttribute('aria-busy');
    });
}
