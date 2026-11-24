// Filtro de periodo de Reparaciones en el navegador (ver README.md). Usa solo las 15
// reparaciones de hoy de la base de prueba, así que no depende de la fecha en que se corra.
// Los bordes exactos de cada periodo los prueba PHPUnit (PeriodoReparacionesTest). No cambia datos.
import { dormir, enfocar, escribir, esperar, esperarBusqueda, marcar, nuevaPagina, sinRecargar } from './lib.mjs';

export const titulo = 'Periodo de Reparaciones';

const CAMPO = '#input-buscar';

/** Lo que muestra la lista ahora. */
const ver = pagina => pagina.evaluate(selectorCampo => ({
    filtros:   Object.fromEntries(new URLSearchParams(location.search)),
    estado:    document.querySelector('#select-estado')?.value,
    periodo:   document.querySelector('#select-periodo')?.selectedOptions[0]?.textContent.trim(),
    indicador: document.querySelector('[data-en-vivo="indicador"]')?.textContent.replace(/\s+/g, ' ').trim(),
    lista:     document.querySelector('[data-en-vivo="resultados"]')?.textContent.replace(/\s+/g, ' ').trim(),
    filas:     document.querySelectorAll('tr.reparacion-row').length,
    campo:     document.querySelector(selectorCampo)?.value,
    desde:     document.querySelector('input[name=desde]')?.value,
    hasta:     document.querySelector('input[name=hasta]')?.value,
    limpiar:   !!document.querySelector('[data-en-vivo="limpiar-filtros"] a'),   // «Limpiar filtros»
}), CAMPO);

/** «Limpiar filtros» en el texto de un revisar(). */
const conLimpiar = v => `Limpiar filtros: ${v.limpiar ? 'sale' : 'no sale'}`;

/** Toca «Limpiar filtros» y espera la página de inicio (sin nada en la URL). true si llegó. */
const tocarLimpiar = pagina => Promise.all([
    pagina.waitForURL(url => new URL(url).search === '', { timeout: 8000 }),
    pagina.click('[data-en-vivo="limpiar-filtros"] a', { timeout: 5000 }),
]).then(() => true, () => false);

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

    // 1. El periodo predefinido, por el camino de siempre: se entra a Reparaciones («En proceso»,
    //    sin periodo) y se elige «Todos». El formulario manda lo que muestra el select de periodo
    //    (oculto en «En proceso»): «Últimos 6 meses». «Limpiar filtros» vuelve al inicio, así
    //    que en el inicio no sale y en «Todos» sí
    await pagina.goto(`${app}/admin/reparaciones`);
    v = await ver(pagina);
    revisar(v.estado === 'en_proceso' && !v.limpiar, 'En el inicio («En proceso») no sale «Limpiar filtros»',
        `${v.estado} · ${conLimpiar(v)}`);
    await Promise.all([
        pagina.waitForURL(url => new URL(url).searchParams.get('estado') === 'todos'),
        pagina.selectOption('#select-estado', 'todos'),
    ]);
    v = await ver(pagina);
    revisar(v.filtros.periodo === 'semestre' && v.periodo === 'Últimos 6 meses'
        && new RegExp(`^Mostrando todas las reparaciones de los últimos 6 meses ${DESDE_HACE_6_MESES}$`).test(v.indicador),
        'De «En proceso» a «Todos»: el periodo arranca en «Últimos 6 meses» y dice desde cuándo cuenta',
        `${JSON.stringify(v.filtros)} · ${v.periodo} · «${v.indicador}»`);
    revisar(v.filas === 10, 'Las 15 de hoy: 10 en la página 1', `${v.filas} filas`);
    revisar(v.limpiar, 'En «Todos» sí sale «Limpiar filtros», para volver al inicio', conLimpiar(v));

    // Se puede elegir otro periodo y volver
    await elegirPeriodo(pagina, 'mes');
    v = await ver(pagina);
    revisar(v.periodo === 'Este mes' && v.indicador === 'Mostrando todas las reparaciones de este mes',
        'Elegir «Este mes» recarga con esa opción', `${v.periodo} · «${v.indicador}»`);
    await elegirPeriodo(pagina, 'semestre');

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

    // 6. Las fechas que quedan de «Personalizado» no cuentan en «En proceso»: los campos ocultos
    //    se envían igual, pero ahí no se filtra por fecha y es la página de inicio
    await Promise.all([
        pagina.waitForURL(url => new URL(url).searchParams.get('estado') === 'en_proceso'),
        pagina.selectOption('#select-estado', 'en_proceso'),
    ]);
    v = await ver(pagina);
    revisar(v.filtros.desde === campo(haceTres) && v.filtros.hasta === campo(manana) && !v.limpiar,
        'De «Personalizado» a «En proceso»: las fechas quedan en la URL, pero no sale «Limpiar filtros»',
        `${JSON.stringify(v.filtros)} · ${conLimpiar(v)}`);

    // 7. «Limpiar filtros» vuelve al inicio («En proceso», sin nada en la URL), y ahí ya no sale
    await pagina.goto(`${app}/admin/reparaciones?estado=todos&periodo=mes`);
    const volvio = await tocarLimpiar(pagina);
    v = await ver(pagina);
    revisar(volvio && v.estado === 'en_proceso' && !v.limpiar,
        'Desde «Todos · Este mes», «Limpiar filtros» vuelve al inicio y ahí ya no sale',
        `${pagina.url().replace(app, '')} · ${v.estado} · ${conLimpiar(v)}`);

    // 8. En el inicio, una búsqueda en vivo lo hace salir sin recargar (la parte llega con la
    //    búsqueda), y tocarlo vuelve al inicio con el buscador vacío
    await marcar(pagina);
    await enfocar(pagina, CAMPO);
    await escribir(pagina, 'Cliente', 60);
    const busco8 = await esperarBusqueda(pagina, 'Cliente');
    v = await ver(pagina);
    revisar(busco8 && v.limpiar && await sinRecargar(pagina),
        'En el inicio, la búsqueda en vivo hace salir «Limpiar filtros» sin recargar', conLimpiar(v));
    const limpio = await tocarLimpiar(pagina);
    v = await ver(pagina);
    revisar(limpio && v.estado === 'en_proceso' && v.campo === '' && !v.limpiar,
        'Y tocarlo vuelve al inicio con el buscador vacío', `campo «${v.campo}» · ${v.estado} · ${conLimpiar(v)}`);

    revisar(errores.length === 0, 'Sin errores de JavaScript', errores.join(' | '));
    await contexto.close();
}
