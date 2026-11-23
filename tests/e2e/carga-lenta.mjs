// Se escribe en el buscador antes de que corra el JS (red lenta en el iPad): al cargar, lo
// escrito se busca solo. Antes la lista quedaba sin filtrar y Enter no hacía nada.
import { dormir, esperarBusqueda, USUARIO } from './lib.mjs';

export const titulo = 'JS que llega tarde';

// El JS de la app tal como lo pide la página (sin "?"): la copia con ?tarde=1 no se toca
const esElJsDeLaApp = url => /^\/build\/assets\/app-[^/]*\.js$/.test(url.pathname) && url.search === '';

export async function correr({ navegador, app, revisar }) {
    // Contexto propio en el que el JS de la app llega vacío desde el principio: si se
    // demorara, el navegador podría usar la copia de la caché y no habría demora
    const contexto = await navegador.newContext({ viewport: { width: 1180, height: 820 }, hasTouch: true });
    await contexto.route(/pusher/, ruta => ruta.abort());
    await contexto.route(esElJsDeLaApp, ruta => ruta.fulfill({ contentType: 'text/javascript', body: '' }));

    const pagina  = await contexto.newPage();
    const errores = [];
    pagina.on('pageerror', error => { if (!/pusher/i.test(error.message)) errores.push(error.message); });

    // El login no necesita JS
    await pagina.goto(`${app}/login`);
    await pagina.fill('input[name=email]', USUARIO.email);
    await pagina.fill('input[name=password]', USUARIO.clave);
    await Promise.all([pagina.waitForURL(/\/admin/), pagina.press('input[name=password]', 'Enter')]);

    for (const [lista, campo, texto] of [
        ['productos', '#form-filtros input[name=buscar]', 'cargador'],
        ['reparaciones', '#input-buscar', 'Cliente'],
    ]) {
        await pagina.goto(`${app}/admin/${lista}`);
        await pagina.click(campo);
        await pagina.keyboard.type(texto);           // todavía sin JS: nadie lo oye
        await dormir(800);
        const sinBuscar = !new URL(pagina.url()).searchParams.has('buscar');

        // Ahora llega el JS de verdad
        const src = await pagina.evaluate(() => document.querySelector('script[type="module"][src*="/build/assets/app-"]').src);
        await pagina.addScriptTag({ url: `${src}?tarde=1`, type: 'module' });

        revisar(sinBuscar && await esperarBusqueda(pagina, texto, 8000),
            `${lista}: lo escrito antes de que cargue el JS se busca solo al cargar`, pagina.url());
    }

    revisar(errores.length === 0, 'Sin errores de JavaScript', errores.join(' | '));
    await contexto.close();
}
