// Lo que pesa y tarda una búsqueda de Reparaciones con 3.000 reparaciones de un año
// (preparar-base.php grande). Antes, «sam» en Todos / Este año traía 706 filas y 4 MB.
import { enfocar, escribir, esperar, nuevaPagina } from './lib.mjs';

export const titulo = 'Rendimiento con 3.000 reparaciones';

const LIMITE_KB = 600;

export async function correr({ navegador, app, revisar }) {
    const { contexto, pagina, errores } = await nuevaPagina(navegador, app);

    await pagina.goto(`${app}/admin/reparaciones?estado=todos&periodo=anio`);
    await pagina.evaluate(() => {
        window.medida = {};
        document.addEventListener('busqueda-en-vivo:actualizada', () => { window.medida.lista = performance.now(); }, { once: true });
    });

    await enfocar(pagina, '#input-buscar');
    await escribir(pagina, 'sam', 60);
    const ultimaTecla = await pagina.evaluate(() => performance.now());
    await esperar(pagina, () => window.medida.lista, null, 60000);

    const r = await pagina.evaluate(async ultimaTecla => ({
        ms:      Math.round(window.medida.lista - ultimaTecla),
        kb:      Math.round((await (await fetch(location.href, { headers: { Accept: 'text/html' } })).text()).length / 1024),
        filas:   document.querySelectorAll('tr.reparacion-row').length,
        total:   Number(document.querySelector('[data-en-vivo="indicador"] strong')?.textContent),
        paginas: document.querySelectorAll('[data-en-vivo="paginacion"] a').length,
    }), ultimaTecla);

    console.log(`   «sam» en Todos / Este año: ${r.total} encontradas, ${r.filas} en pantalla, ${r.kb} KB, ${r.ms} ms desde la última tecla (400 son la espera)`);
    revisar(r.filas > 0 && r.filas <= 25, 'Con búsqueda trae una página (hasta 25 filas)', `${r.filas} filas`);
    revisar(r.total > 25 && r.paginas > 0, 'El indicador dice el total y hay paginación', `total ${r.total}, enlaces ${r.paginas}`);
    revisar(r.kb < LIMITE_KB, `Cada búsqueda baja menos de ${LIMITE_KB} KB`, `${r.kb} KB`);

    // Ponerse al día (cada 30 min, al reconectar o al despertar la tablet) con esa lista
    const pedidos = await pagina.evaluate(() => {
        const vistos   = [];
        const original = window.fetch;
        window.fetch = (url, opciones) => {
            if (String(url).includes('/sincronizar')) vistos.push(String(url));
            return original(url, opciones);
        };
        window.dispatchEvent(new CustomEvent('tiempo-real:ponerse-al-dia'));
        window.fetch = original;
        return vistos.length;
    });
    revisar(pedidos === 1, 'Ponerse al día con esa lista hace un solo pedido a /sincronizar', `${pedidos} pedidos`);

    revisar(errores.length === 0, 'Sin errores de JavaScript', errores.join(' | '));
    await contexto.close();
}
