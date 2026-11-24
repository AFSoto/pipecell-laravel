// Filtro de periodo de Reparaciones en el navegador (ver README.md). Usa solo las 15
// reparaciones de hoy de la base de prueba, así que no depende de la fecha en que se corra.
// Los bordes exactos de cada periodo los prueba PHPUnit (PeriodoReparacionesTest). No cambia datos.
import { dormir, enfocar, escribir, esperar, esperarBusqueda, nuevaPagina } from './lib.mjs';

export const titulo = 'Periodo de Reparaciones';

const CAMPO = '#input-buscar';

/** Lo que muestra la lista ahora. */
const ver = pagina => pagina.evaluate(selectorCampo => ({
    filtros:   Object.fromEntries(new URLSearchParams(location.search)),
    periodo:   document.querySelector('#select-periodo')?.selectedOptions[0]?.textContent.trim(),
    indicador: document.querySelector('[data-en-vivo="indicador"]')?.textContent.replace(/\s+/g, ' ').trim(),
    lista:     document.querySelector('[data-en-vivo="resultados"]')?.textContent.replace(/\s+/g, ' ').trim(),
    filas:     document.querySelectorAll('tr.reparacion-row').length,
    campo:     document.querySelector(selectorCampo)?.value,
    desde:     document.querySelector('input[name=desde]')?.value,
    hasta:     document.querySelector('input[name=hasta]')?.value,
}), CAMPO);

/** Elige un periodo en el select: la página se recarga con él (el formulario se envía entero). */
const elegirPeriodo = (pagina, valor) => Promise.all([
    pagina.waitForURL(url => new URL(url).searchParams.get('periodo') === valor),
    pagina.selectOption('#select-periodo', valor),
]);

/** Una fecha a tantos días de hoy, como la pide el campo (AAAA-MM-DD) y como la dice la lista (DD/MM/AAAA). */
const aDias = dias => { const fecha = new Date(); fecha.setDate(fecha.getDate() + dias); return fecha; };
const dos   = n => String(n).padStart(2, '0');
const campo = fecha => `${fecha.getFullYear()}-${dos(fecha.getMonth() + 1)}-${dos(fecha.getDate())}`;
const lista = fecha => `${dos(fecha.getDate())}/${dos(fecha.getMonth() + 1)}/${fecha.getFullYear()}`;

const DESDE_HACE_6_MESES = '\\(del \\d{2}/\\d{2}/\\d{4} a hoy\\)';

export async function correr({ navegador, app, revisar }) {
    const { contexto, pagina, errores } = await nuevaPagina(navegador, app);
    let v;

    // 1. «Últimos 6 meses» desde el select, y la página 2
    await pagina.goto(`${app}/admin/reparaciones?estado=todos`);
    await elegirPeriodo(pagina, 'semestre');
    v = await ver(pagina);
    revisar(v.periodo === 'Últimos 6 meses'
        && new RegExp(`^Mostrando todas las reparaciones de los últimos 6 meses ${DESDE_HACE_6_MESES}$`).test(v.indicador),
        'Elegir «Últimos 6 meses» recarga con esa opción y dice desde cuándo cuenta', `${v.periodo} · «${v.indicador}»`);
    revisar(v.filas === 10, 'Las 15 de hoy: 10 en la página 1', `${v.filas} filas`);

    // Laravel pone dos enlaces a la página 2: el de teléfono (oculto en el iPad) y el numerado
    await Promise.all([pagina.waitForURL(/page=2/), pagina.locator('a[href*="page=2"]:visible').first().click()]);
    v = await ver(pagina);
    revisar(v.filtros.estado === 'todos' && v.filtros.periodo === 'semestre' && v.filas === 5,
        'La página 2 sigue en Todos / Últimos 6 meses', `${JSON.stringify(v.filtros)} · ${v.filas} filas`);

    // 2. Con búsqueda, el indicador dice entre cuáles buscó, y la URL conserva estado y periodo
    await pagina.goto(`${app}/admin/reparaciones?estado=todos&periodo=semestre`);
    await enfocar(pagina, CAMPO);
    await escribir(pagina, 'Cliente', 60);
    const busco = await esperarBusqueda(pagina, 'Cliente');
    v = await ver(pagina);
    revisar(busco && v.filtros.estado === 'todos' && v.filtros.periodo === 'semestre',
        'La búsqueda en vivo conserva Todos / Últimos 6 meses en la URL', JSON.stringify(v.filtros));
    revisar(new RegExp(`^12 resultados para "Cliente" entre todas las reparaciones de los últimos 6 meses ${DESDE_HACE_6_MESES}$`).test(v.indicador),
        'Con búsqueda, el indicador nombra el periodo («entre todas las reparaciones de…»)', v.indicador);

    // 3. Sin resultados: la búsqueda respeta el periodo, así que sugiere probar otro
    await pagina.goto(`${app}/admin/reparaciones?estado=todos&periodo=semestre`);
    await enfocar(pagina, CAMPO);
    await escribir(pagina, 'zzzz', 60);
    await esperarBusqueda(pagina, 'zzzz');
    v = await ver(pagina);
    revisar(v.lista.includes('No se encontraron reparaciones con esa búsqueda') && v.lista.includes('o con otro periodo'),
        'Sin resultados sugiere probar con otro periodo', v.lista.slice(0, 160));

    // 4. Una letra escrita y cambiar el periodo: el formulario va con buscar=s, pero una letra
    //    no es búsqueda. Antes decía «15 resultados para "s"» con la lista sin filtrar
    await pagina.goto(`${app}/admin/reparaciones?estado=todos&periodo=semestre`);
    await enfocar(pagina, CAMPO);
    await escribir(pagina, 's');
    await dormir(700);                                // con una letra la búsqueda en vivo no sale
    await elegirPeriodo(pagina, 'trimestre');
    v = await ver(pagina);
    revisar(v.filtros.buscar === 's' && v.campo === 's' && v.filas === 10
        && /^Mostrando todas las reparaciones de los últimos 3 meses/.test(v.indicador),
        'Una letra y cambiar el periodo: la lista sin filtrar y el indicador sin «resultados para»',
        `campo «${v.campo}» · ${v.filas} filas · «${v.indicador}»`);

    // 5. «Personalizado» con las fechas al revés, escritas a mano: se invierten
    const manana   = aDias(1);
    const haceTres = aDias(-3);
    await pagina.goto(`${app}/admin/reparaciones?estado=todos`);
    await pagina.selectOption('#select-periodo', 'personalizado');   // muestra las fechas, no recarga
    await pagina.fill('input[name=desde]', campo(manana));
    await pagina.fill('input[name=hasta]', campo(haceTres));
    await pagina.press(CAMPO, 'Enter');                              // aplica las fechas
    const aplico = await esperar(pagina, desde => new URL(location.href).searchParams.get('desde') === desde
        && !document.querySelector('[data-en-vivo="resultados"][aria-busy]'), campo(manana), 8000);
    v = await ver(pagina);
    const rango = `del ${lista(haceTres)} al ${lista(manana)}`;
    revisar(aplico && v.filas === 10 && v.indicador === `Mostrando todas las reparaciones ${rango}`,
        '«Personalizado» con las fechas al revés: se invierten y salen las de hoy', `${v.filas} filas · «${v.indicador}»`);

    await pagina.reload();
    v = await ver(pagina);
    revisar(v.periodo === 'Personalizado' && v.desde === campo(haceTres) && v.hasta === campo(manana),
        'Al recargar, los campos muestran las fechas en orden', `${v.periodo} · desde ${v.desde} · hasta ${v.hasta}`);

    revisar(errores.length === 0, 'Sin errores de JavaScript', errores.join(' | '));
    await contexto.close();
}
