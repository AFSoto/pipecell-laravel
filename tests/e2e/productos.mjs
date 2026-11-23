// Búsqueda en vivo de Productos (ver README.md).
import { join } from 'node:path';
import {
    borrar, busquedaEnUrl, contarPedidos, demorarBusquedas, dormir, enfocar, escribir, esperar,
    esperarBusqueda, marcar, nuevaPagina, pedidos, sinRecargar, textoDe,
} from './lib.mjs';

export const titulo = 'Productos';

const CAMPO = '#form-filtros input[name=buscar]';

export async function correr({ navegador, app, revisar, capturas, motor }) {
    const { contexto, pagina, errores } = await nuevaPagina(navegador, app);
    const ir         = async ruta => { await pagina.goto(app + ruta); await contarPedidos(pagina); };
    const resultados = () => textoDe(pagina, '[data-en-vivo="resultados"]');
    const total      = async () => (await textoDe(pagina, '[data-en-vivo="total"]')).trim();
    const valorDe    = selector => pagina.evaluate(s => document.querySelector(s)?.value ?? null, selector);
    const enfocado   = () => pagina.evaluate(s => document.activeElement === document.querySelector(s), CAMPO);

    // ── Escribir: desde 3 letras, un pedido, sin perder el foco ──
    await ir('/admin/productos?vista=tarjetas');
    const totalInicial = await total();
    await marcar(pagina);
    await enfocar(pagina, CAMPO);

    await escribir(pagina, 'ba');
    await dormir(800);
    revisar((await pedidos(pagina)).length === 0, 'Con 2 letras no pide nada');

    await escribir(pagina, 't');
    revisar(await esperarBusqueda(pagina, 'bat'), 'Con «bat» la URL queda con buscar=bat');
    revisar((await pedidos(pagina)).length === 1, 'Un solo pedido', JSON.stringify(await pedidos(pagina)));
    revisar(await enfocado(), 'El foco sigue en el buscador (el teclado del iPad no se cierra)');
    const conBat = await resultados();
    revisar(conBat.includes('Batería iPhone 14') && !conBat.includes('Cargador 20W'), 'La lista muestra solo lo que coincide');
    revisar(await total() === '3', 'El número junto al título se actualiza', `${totalInicial} → ${await total()}`);
    revisar((await textoDe(pagina, '[data-en-vivo="indicador"]')).includes('"bat"'), 'El indicador dice lo buscado');
    revisar((await pagina.getAttribute('[data-en-vivo="vista"] a[aria-label="Ver como lista"]', 'href')).includes('buscar=bat'),
        'El enlace «Ver como lista» lleva la búsqueda');
    revisar((await valorDe('#form-editar-producto input[name=volver]')).includes('buscar=bat'), '«volver» del modal de editar lleva la búsqueda');
    revisar((await valorDe('[data-en-vivo="resultados"] form input[name=volver]')).includes('buscar=bat'), '«Desactivar» vuelve a la búsqueda nueva');

    // ── Red lenta: el pedido viejo se cancela y solo se pinta el último ──
    const quitarDemora = await demorarBusquedas(pagina, 2500);
    await escribir(pagina, 'e');                 // «bate»: sale un pedido, que tarda
    await dormir(600);
    await escribir(pagina, 'r deji', 60);        // «bater deji»: sale otro y cancela el anterior
    revisar(await esperarBusqueda(pagina, 'bater deji', 15000), 'Con la red lenta termina en la última búsqueda');
    const lista = (await pedidos(pagina)).slice(1).map(p => [p.buscar, p.cancelado]);
    revisar(JSON.stringify(lista) === JSON.stringify([['bate', true], ['bater deji', false]]),
        'El pedido viejo se canceló (nunca se pinta una respuesta vieja)', JSON.stringify(lista));
    await quitarDemora();
    const deji = await resultados();
    revisar(deji.includes('Deji') && !deji.includes('Ampsentrix'), 'Muestra solo las baterías Deji');

    // ── Menos de 3 letras: lista completa ──
    await borrar(pagina, 'bater deji'.length - 2);
    revisar(await esperar(pagina, total => new URL(location.href).searchParams.get('buscar') === null
        && document.querySelector('[data-en-vivo="total"]').textContent.trim() === total, totalInicial),
        'Con menos de 3 letras vuelve la lista completa');

    // ── «LG» + Enter: busca enseguida desde 2 letras, sin activar «Stock bajo» ──
    await borrar(pagina, 2);
    await escribir(pagina, 'LG');
    await pagina.keyboard.press('Enter');
    revisar(await esperarBusqueda(pagina, 'LG', 3000), '«LG» con Enter busca');
    revisar(!new URL(pagina.url()).searchParams.has('stock_bajo'), 'Enter no activa «Stock bajo»', pagina.url());
    revisar((await resultados()).includes('Pantalla LG K40'), 'Y trae la pantalla LG');
    revisar(!(await enfocado()), 'Enter cierra el teclado (el buscador pierde el foco)');
    revisar(await sinRecargar(pagina), 'Todas esas búsquedas, sin recargar la página');

    // ── «Ajustar stock» de un producto que no estaba al cargar la página ──
    await pagina.click('[data-en-vivo="resultados"] [onclick^="abrirAjusteStock("]');
    const badge = (await textoDe(pagina, '#ajuste-stock-actual-badge')).trim();
    revisar(badge === '3 uds.', '«Ajustar stock» de un producto traído por la búsqueda muestra su stock real', badge);
    if (capturas) await pagina.screenshot({ path: join(capturas, `productos-lg-${motor}.png`) });
    await pagina.evaluate(() => cerrarAjusteStock());

    // ── «Stock bajo» recarga con la búsqueda; después la búsqueda en vivo conserva el filtro ──
    await pagina.fill(CAMPO, '');
    await enfocar(pagina, CAMPO);
    await escribir(pagina, 'bater');
    await esperarBusqueda(pagina, 'bater');
    await Promise.all([pagina.waitForURL(/stock_bajo=1/), pagina.click('#form-filtros button[name=stock_bajo]')]);
    await contarPedidos(pagina);
    revisar(await busquedaEnUrl(pagina) === 'bater', '«Stock bajo» recarga con la búsqueda incluida');
    await enfocar(pagina, CAMPO);
    await escribir(pagina, ' iphone');
    revisar(await esperar(pagina, () => {
        const query = new URL(location.href).searchParams;
        return query.get('buscar') === 'bater iphone' && query.get('stock_bajo') === '1';
    }), 'Con «Stock bajo» activo, la búsqueda en vivo no lo pierde');
    revisar((await resultados()).includes('Ampsentrix'), 'Muestra la batería con stock bajo');

    // ── Editar → Guardar vuelve a la búsqueda ──
    await pagina.click('[data-en-vivo="resultados"] [onclick^="abrirEditarProducto("]');
    await Promise.all([pagina.waitForEvent('load'), pagina.click('#form-editar-producto button[type=submit]')]);
    await contarPedidos(pagina);
    revisar(await busquedaEnUrl(pagina) === 'bater iphone', 'Editar → Guardar vuelve a la misma búsqueda', pagina.url());
    revisar(await pagina.inputValue(CAMPO) === 'bater iphone', 'Y el buscador sigue con lo escrito');

    // ── Con «Stock bajo» activo, Enter busca en vivo y lo conserva ──
    await ir('/admin/productos?stock_bajo=1');
    await marcar(pagina);
    await enfocar(pagina, CAMPO);
    await escribir(pagina, 'LG');
    await pagina.keyboard.press('Enter');
    revisar(await esperarBusqueda(pagina, 'LG', 3000) && new URL(pagina.url()).searchParams.get('stock_bajo') === '1'
        && await sinRecargar(pagina), 'Con «Stock bajo» activo, Enter busca en vivo y lo conserva', pagina.url());

    // ── Otra pestaña elige «tarjetas»: buscar no cambia la vista de esta ──
    await ir('/admin/productos?vista=lista');
    const otra = await contexto.newPage();
    await otra.goto(`${app}/admin/productos?vista=tarjetas`);
    await otra.close();
    await enfocar(pagina, CAMPO);
    await escribir(pagina, 'cargador');
    await esperarBusqueda(pagina, 'cargador');
    const vista = await pagina.evaluate(() => document.querySelector('[data-en-vivo="resultados"] [data-vista]')?.dataset.vista);
    revisar(vista === 'lista', 'Si otra pestaña elige «tarjetas», buscar no cambia la vista de esta (lista)', `se dibujó «${vista}»`);

    // ── La zona que anuncia los resultados (aria-live) no se reemplaza ──
    await ir('/admin/productos');
    await pagina.evaluate(() => { window.zonaAnuncio = document.querySelector('[data-en-vivo="indicador"]').closest('[aria-live]'); });
    await enfocar(pagina, CAMPO);
    await escribir(pagina, 'cable');
    await esperarBusqueda(pagina, 'cable');
    revisar(await pagina.evaluate(() => !!window.zonaAnuncio && document.contains(window.zonaAnuncio)
        && window.zonaAnuncio.textContent.includes('"cable"')), 'La zona aria-live sigue siendo la misma y dice lo buscado');

    // ── Sin conexión: avisa y la lista queda como estaba ──
    await ir('/admin/productos?vista=tarjetas');
    await enfocar(pagina, CAMPO);
    const antes = await pagina.evaluate(() => document.querySelector('[data-en-vivo="resultados"]').innerHTML.length);
    await contexto.setOffline(true);
    await escribir(pagina, 'cargador');
    revisar(await esperar(pagina, () => document.getElementById('contenedor-notificaciones')?.textContent.includes('No se pudo buscar')),
        'Sin conexión avisa «No se pudo buscar»');
    revisar(await pagina.evaluate(() => document.querySelector('[data-en-vivo="resultados"]').innerHTML.length) === antes,
        'Y la lista queda como estaba');
    await contexto.setOffline(false);

    revisar(errores.length === 0, 'Sin errores de JavaScript', errores.join(' | '));
    await contexto.close();
}
