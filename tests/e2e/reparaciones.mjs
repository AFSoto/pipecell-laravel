// Búsqueda en vivo de Reparaciones (ver README.md). Cambia datos: «Cliente 10» y «Cliente 12».
import { join } from 'node:path';
import {
    avisoTiempoReal, borrar, contarPedidos, demorarBusquedas, dormir, enfocar, escribir, esperar,
    esperarBusqueda, marcar, nuevaPagina, sinRecargar, textoDe,
} from './lib.mjs';

export const titulo = 'Reparaciones';

const CAMPO = '#input-buscar';

export async function correr({ navegador, app, revisar, cambiar, capturas, motor }) {
    const { contexto, pagina, errores } = await nuevaPagina(navegador, app);
    const ir       = async ruta => { await pagina.goto(app + ruta); await contarPedidos(pagina); };
    const filas    = () => pagina.locator('tr.reparacion-row').count();
    const estadoDe = id => pagina.evaluate(id => document.querySelector(`tr[data-id="${id}"]`)?.dataset.estado ?? null, id);

    // ── Buscar y limpiar con la × ──
    await ir('/admin/reparaciones?estado=todos');
    const filasIniciales = await filas();
    await enfocar(pagina, CAMPO);
    await escribir(pagina, 'Laura');
    revisar(await esperarBusqueda(pagina, 'Laura'), 'Buscar «Laura» actualiza la URL');
    revisar(await filas() === 1 && (await textoDe(pagina, 'tr.reparacion-row')).includes('Laura Gómez'), 'Queda solo la de Laura', `filas: ${await filas()}`);
    revisar(await pagina.isVisible('[data-en-vivo="limpiar-busqueda"] button'), 'Aparece la × para limpiar la búsqueda');
    revisar((await textoDe(pagina, '[data-en-vivo="indicador"]')).includes('1 resultado'), 'El indicador dice «1 resultado»');
    revisar(await pagina.evaluate(() => document.querySelector('[data-en-vivo="paginacion"]').children.length === 0),
        'Con un solo resultado no hay paginación');

    await marcar(pagina);
    await pagina.click('[data-en-vivo="limpiar-busqueda"] button');
    revisar(await esperar(pagina, cuantas => new URL(location.href).searchParams.get('buscar') === null
        && document.querySelectorAll('tr.reparacion-row').length === cuantas, filasIniciales),
        'La × limpia y vuelve la lista', `filas: ${await filas()}`);
    revisar(await sinRecargar(pagina), 'Y no recargó la página');
    revisar(!(await pagina.$('[data-en-vivo="limpiar-busqueda"] button')), 'Y la × se va');
    revisar((await textoDe(pagina, '[data-en-vivo="paginacion"]')).trim() !== '', 'Y vuelve la paginación');
    revisar(await pagina.evaluate(s => document.activeElement === document.querySelector(s), CAMPO),
        'Y el foco queda en el buscador, para escribir la próxima');

    await escribir(pagina, 'zzz');
    revisar(await esperar(pagina, () => document.querySelector('[data-en-vivo="resultados"]').textContent.includes('No se encontraron reparaciones con esa búsqueda')),
        'Sin resultados dice «No se encontraron reparaciones con esa búsqueda»');

    await borrar(pagina, 3);
    await escribir(pagina, 'Pedro');
    revisar(await esperarBusqueda(pagina, 'Pedro'), 'Buscar «Pedro»');
    revisar(await pagina.evaluate(() => [...document.querySelectorAll('tr.reparacion-row button')].some(b => b.textContent.includes('Enviar WhatsApp'))),
        'Las filas traídas por la búsqueda tienen su WhatsApp pintado («Enviar WhatsApp»)');
    if (capturas) await pagina.screenshot({ path: join(capturas, `reparaciones-pedro-${motor}.png`) });

    // ── Varias palabras y teléfono ──
    const clientes = async texto => {
        await ir('/admin/reparaciones?estado=todos');
        await enfocar(pagina, CAMPO);
        await escribir(pagina, texto, 40);
        await esperarBusqueda(pagina, texto);
        return pagina.evaluate(() => [...document.querySelectorAll('tr.reparacion-row [data-campo="nombre"]')].map(c => c.textContent.trim()));
    };
    const samsungA10 = await clientes('Samsung A10');
    revisar(samsungA10.length === 14 && !samsungA10.includes('Pedro Ruiz'), '«Samsung A10» encuentra las 14 (marca y modelo)', JSON.stringify(samsungA10));
    revisar(JSON.stringify(await clientes('Ruiz Pedro')) === '["Pedro Ruiz"]', '«Ruiz Pedro» encuentra a Pedro Ruiz (en cualquier orden)');
    revisar(JSON.stringify(await clientes('Xiaomi Redmi')) === '["Pedro Ruiz"]', '«Xiaomi Redmi» encuentra el Xiaomi');
    revisar(JSON.stringify(await clientes('3105550001')) === '["Pedro Ruiz"]', 'El teléfono sin espacios encuentra «310 555 0001»');

    // ── Editar abierto cuando llega la búsqueda: «Cargar sus cambios» y «Guardar» ──
    await ir('/admin/reparaciones');
    await pagina.fill(CAMPO, 'Cliente 12');   // la búsqueda sale en 400 ms...
    await pagina.click('tr.reparacion-row:has-text("Cliente 12") button[onclick="abrirModalEditar(this)"]'); // ...y antes se toca Editar
    await esperarBusqueda(pagina, 'Cliente 12');

    const id12 = cambiar('Cliente 12', 'descripcion_falla', 'Cambio de otra persona');
    await avisoTiempoReal(pagina, id12, 'editar');
    revisar(await pagina.waitForSelector('#edit-aviso-cambio:not(.hidden)', { timeout: 5000 }).then(() => true, () => false),
        'Con el modal abierto, sale el aviso «Otra persona acaba de cambiar esta reparación»');

    await pagina.click('#edit-aviso-cambio button[onclick="cargarCambiosEdicion()"]');
    revisar(await esperar(pagina, () => document.getElementById('edit-descripcion_falla').value === 'Cambio de otra persona'),
        '«Cargar sus cambios» trae lo que cambió la otra persona', await pagina.inputValue('#edit-descripcion_falla'));

    const guardado = pagina.waitForResponse(r => /\/admin\/reparaciones\/\d+$/.test(new URL(r.url()).pathname) && r.request().method() === 'POST');
    await pagina.click('#edit-btn-guardar');
    const respuesta = (await guardado).status();
    revisar(respuesta === 200, 'Y «Guardar» funciona: no choca con el cambio que ya cargó', `el servidor respondió ${respuesta}`);
    revisar(await esperar(pagina, () => document.getElementById('modal-editar').classList.contains('hidden')), 'Y el modal se cierra');

    // ── Un aviso de tiempo real que llega mientras viene la búsqueda no se pierde ──
    await ir('/admin/reparaciones?estado=todos&periodo=anio&buscar=Cliente');   // la fila ya se ve
    const quitarDemora = await demorarBusquedas(pagina, 4000);                  // la búsqueda se arma ya y llega tarde
    await enfocar(pagina, CAMPO);
    await escribir(pagina, ' 10', 60);
    await esperar(pagina, () => !!document.querySelector('[data-en-vivo="resultados"][aria-busy]'), null, 5000);
    await dormir(400);

    const id10 = cambiar('Cliente 10', 'estado', 'arreglado');
    await avisoTiempoReal(pagina, id10, 'estado', 'arreglado');
    const pintada = await esperar(pagina, id => document.querySelector(`tr[data-id="${id}"]`)?.dataset.estado === 'arreglado', id10, 3000);
    await esperarBusqueda(pagina, 'Cliente 10', 12000);
    await esperar(pagina, id => document.querySelector(`tr[data-id="${id}"]`)?.dataset.estado === 'arreglado', id10, 3000);
    const alFinal = await estadoDe(id10);
    revisar(pintada && alFinal === 'arreglado', 'Un cambio que llega por tiempo real mientras viene la búsqueda no se pierde',
        `con el aviso: ${pintada ? 'arreglado' : 'no se pintó'}; al llegar la búsqueda: ${alFinal}`);
    await quitarDemora();

    // ── La zona que anuncia los resultados (aria-live) no se reemplaza ──
    await ir('/admin/reparaciones');
    await pagina.evaluate(() => { window.zonaAnuncio = document.querySelector('[data-en-vivo="indicador"]').closest('[aria-live]'); });
    await enfocar(pagina, CAMPO);
    await escribir(pagina, 'Ana');
    await esperarBusqueda(pagina, 'Ana');
    revisar(await pagina.evaluate(() => !!window.zonaAnuncio && document.contains(window.zonaAnuncio)
        && window.zonaAnuncio.textContent.includes('Ana')), 'La zona aria-live sigue siendo la misma y dice lo buscado');

    // ── Sesión vencida: recarga y el servidor manda al login ──
    await contexto.clearCookies();
    await borrar(pagina, 3);
    await escribir(pagina, 'Laura');
    revisar(await pagina.waitForURL(/\/login/, { timeout: 8000 }).then(() => true, () => false),
        'Con la sesión vencida, recarga y queda en el login', pagina.url());

    revisar(errores.length === 0, 'Sin errores de JavaScript', errores.join(' | '));
    await contexto.close();
}
