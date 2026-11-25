// Buscador del campo «Display» en los modales de Reparaciones (ver README.md): el campo mismo es
// el buscador, se elige tocando una fila o con Enter, y la que no tiene stock no se elige.
// Cambia datos: pantallas.php agrega pantallas y una caja libre, y la prueba crea la reparación
// de «Diana Display», le cambia el display y desactiva uno. Por eso va al final.
import { join } from 'node:path';
import { esperar, nuevaPagina } from './lib.mjs';

export const titulo = 'Buscador de display';

const A10_CON_MARCO = ['Pantalla Samsung A10', 'Con marco'];
const A10_GENERICO  = ['Pantalla Samsung A10', 'Genérico · Sin marco'];
const LG            = ['Pantalla LG K40', 'Sin marco'];

/** Lo que muestra el campo con ese display elegido. */
const textoDe = ([nombre, detalle]) => `${nombre} · ${detalle}`;

const FILA_DIANA = 'tr.reparacion-row:has-text("Diana Display")';

export async function correr({ navegador, app, revisar, pantallas, capturas, motor }) {
    // Una caja libre, una «Pantalla Samsung A10» Genérico sin marco (la de la base es con
    // marco), una «Pantalla Motorola G20» sin stock y 60 «Pantalla Xiaomi Redmi Note»
    const caja = pantallas('preparar');
    const { contexto, pagina, errores } = await nuevaPagina(navegador, app);

    /** Lo que muestra el buscador de un modal ('nueva' o 'edit'). */
    const ver = (prefijo = 'nueva') => pagina.evaluate(p => {
        const campo = document.getElementById(`${p}-buscar-display`);
        const aviso = document.getElementById(`${p}-aviso-displays`);

        return {
            abierta:  !document.getElementById(`${p}-desplegable-display`).classList.contains('hidden'),
            enfocado: document.activeElement === campo,
            texto:    campo.value,
            guia:     campo.placeholder,
            id:       document.getElementById(`${p}-display_id`).value,
            aviso:    aviso.classList.contains('hidden') ? '' : aviso.textContent,
            filas:    [...document.querySelectorAll(`#${p}-lista-displays [role="option"]`)].map(fila => ({
                nombre:    fila.children[0].children[0].textContent,
                detalle:   fila.children[0].children[1]?.textContent ?? '',
                stock:     fila.children[1].textContent,
                bloqueada: fila.getAttribute('aria-disabled') === 'true',
                resaltada: fila.classList.contains('bg-blue-50'),
            })),
        };
    }, prefijo);

    /** Id y stock de un display según la página (los datos del buscador). */
    const display = ([nombre, detalle]) => pagina.evaluate(([nombre, detalle]) => {
        const encontrado = DISPLAYS.find(d => d.nombre === nombre && d.detalle === detalle);
        return encontrado ? { id: String(encontrado.id), stock: encontrado.stock } : null;
    }, [nombre, detalle]);

    /** Toca la fila de la lista que tiene ese texto. force: también la bloqueada (sin stock). */
    const tocarFila = (prefijo, texto, opciones = {}) =>
        pagina.tap(`#${prefijo}-lista-displays [role="option"]:has-text("${texto}")`, opciones);

    /** Lo que dice el display de la fila de Diana (null si no está en la página). */
    const displayDeDiana = () => pagina.evaluate(() => {
        const fila = [...document.querySelectorAll('tr.reparacion-row')].find(tr => tr.textContent.includes('Diana Display'));
        return fila ? fila.querySelector('[data-campo="display"] span').textContent.trim() : null;
    });

    const modalEditarCerrado = () => esperar(pagina, () => document.getElementById('modal-editar').classList.contains('hidden'), undefined, 8000);

    await pagina.goto(`${app}/admin/reparaciones`);
    const lg    = await display(LG);
    const total = await pagina.evaluate(() => DISPLAYS.length);

    // ── 1. Al marcar «¿Se usó display?», el cursor queda en el buscador con la lista abierta ──
    await pagina.click('button:has-text("Nueva reparación")');
    await pagina.tap('#nueva-usa_display');
    let v = await ver();
    revisar(v.enfocado && v.abierta, 'Al marcar «¿Se usó display?» el cursor queda en el buscador, con la lista abierta',
        JSON.stringify({ enfocado: v.enfocado, abierta: v.abierta }));
    revisar(total > 50 && v.filas.length === 50 && v.aviso === `Y ${total - 50} más: escribe la marca o el modelo.`,
        'Muestra 50 y dice cuántas más hay', `${total} displays · ${v.filas.length} filas · «${v.aviso}»`);
    if (capturas) await pagina.screenshot({ path: join(capturas, `display-lista-${motor}.png`) });

    // ── 2. Todas las palabras, en cualquier orden, sin importar mayúsculas ni tildes ──
    await pagina.keyboard.type('a10 samsung', { delay: 40 });
    v = await ver();
    revisar(JSON.stringify(v.filas.map(f => [f.nombre, f.detalle])) === JSON.stringify([A10_CON_MARCO, A10_GENERICO])
        && v.filas[0].resaltada,
        '«a10 samsung» deja las dos A10, cada una con su marco, y resalta la primera', JSON.stringify(v.filas));
    if (capturas) await pagina.screenshot({ path: join(capturas, `display-a10-${motor}.png`) });

    await pagina.fill('#nueva-buscar-display', 'A10 GENERICO');
    v = await ver();
    revisar(v.filas.length === 1 && v.filas[0].detalle === A10_GENERICO[1],
        '«A10 GENERICO» (sin tilde y en mayúsculas) encuentra la Genérico', JSON.stringify(v.filas));

    await pagina.fill('#nueva-buscar-display', 'zzz');
    v = await ver();
    revisar(v.filas.length === 0 && v.aviso === 'Ningún display coincide con «zzz».', 'Sin coincidencias lo dice', `«${v.aviso}»`);

    // ── 3. Tocar una fila la elige: el campo la muestra y la lista se cierra ──
    await pagina.fill('#nueva-buscar-display', 'a10');
    await tocarFila('nueva', 'Genérico');
    v = await ver();
    const generico = await display(A10_GENERICO);
    revisar(v.id === generico.id && v.texto === textoDe(A10_GENERICO) && !v.abierta && !v.enfocado,
        'Tocar una la elige: el campo la muestra, la lista se cierra y el campo suelta el foco (se va el teclado)', JSON.stringify(v));

    // ── 4. La que no tiene stock no se elige ──
    await pagina.tap('#nueva-buscar-display');
    v = await ver();
    revisar(v.abierta && v.texto === '' && v.guia === textoDe(A10_GENERICO),
        'Al volver al campo queda vacío para buscar otra, con la elegida de guía', JSON.stringify({ texto: v.texto, guia: v.guia }));

    await pagina.keyboard.type('motorola', { delay: 40 });
    v = await ver();
    revisar(v.filas.length === 1 && v.filas[0].bloqueada && v.filas[0].stock === 'sin stock' && !v.filas[0].resaltada,
        'La que no tiene stock sale «sin stock», bloqueada y sin resaltar', JSON.stringify(v.filas));

    await tocarFila('nueva', 'Motorola', { force: true });
    v = await ver();
    revisar(v.id === generico.id && v.abierta, 'Tocarla no la elige: sigue la que estaba', JSON.stringify({ id: v.id, abierta: v.abierta }));

    // ── 5. Enter elige la resaltada y nunca envía el formulario ──
    // Con el resto del formulario lleno, Enter lo enviaría de verdad: se cuenta cada envío (y se
    // frena, para seguir) hasta que la prueba lo guarda a propósito
    await pagina.fill('#modal-nueva input[name="nombre_cliente"]', 'Diana Display');
    await pagina.fill('#modal-nueva input[name="marca"]', 'Samsung');
    await pagina.selectOption('#modal-nueva select[name="caja_id"]', caja);
    await pagina.fill('#valor_total_visual', '150000');
    await pagina.evaluate(() => {
        window.enviosDelFormulario = 0;
        window.frenarEnvio = evento => { window.enviosDelFormulario++; evento.preventDefault(); };
        document.querySelector('#modal-nueva form').addEventListener('submit', window.frenarEnvio);
    });
    const envios = () => pagina.evaluate(() => window.enviosDelFormulario);

    await pagina.tap('#nueva-buscar-display');
    await pagina.keyboard.type('k40', { delay: 40 });
    await pagina.keyboard.press('Enter');
    v = await ver();
    revisar(v.id === lg.id && v.texto === textoDe(LG) && !v.abierta && await envios() === 0,
        'Enter elige la resaltada, sin enviar el formulario', `${JSON.stringify(v)} · ${await envios()} envío(s)`);

    await pagina.tap('#nueva-buscar-display');
    await pagina.keyboard.type('zzz', { delay: 40 });
    await pagina.keyboard.press('Enter');
    v = await ver();
    revisar(await envios() === 0 && v.id === lg.id && v.enfocado,
        'Enter sin ninguna que coincida no hace nada: no guarda la reparación',
        `${await envios()} envío(s) · ${JSON.stringify({ id: v.id, enfocado: v.enfocado })}`);

    await pagina.keyboard.press('Escape');
    v = await ver();
    revisar(!v.abierta && v.texto === '' && v.enfocado && v.id === lg.id,
        'Escape cierra la lista y borra lo escrito, sin quitar la elegida', JSON.stringify(v));

    // ── 6. Tocar fuera sin elegir deja la que estaba ──
    await pagina.tap('#nueva-buscar-display');
    await pagina.keyboard.type('xiaomi', { delay: 40 });
    await pagina.tap('#modal-nueva h3');
    v = await ver();
    revisar(v.id === lg.id && v.texto === textoDe(LG) && !v.abierta && !v.enfocado,
        'Tocar fuera sin elegir cierra la lista y deja la que estaba', JSON.stringify(v));

    // ── 7. Guardar: la reparación queda con ese display y el stock baja ──
    await pagina.evaluate(() => document.querySelector('#modal-nueva form').removeEventListener('submit', window.frenarEnvio));
    await Promise.all([pagina.waitForEvent('load'), pagina.click('#modal-nueva button[type="submit"]')]);

    revisar(await displayDeDiana() === 'Display: Pantalla LG K40 ×1', 'Al guardar, la reparación queda con ese display', await displayDeDiana());
    const lgDespues = await display(LG);
    revisar(lgDespues.stock === lg.stock - 1, 'Y el stock de esa pantalla baja en 1', `${lg.stock} → ${lgDespues.stock}`);

    // ── 8. Editar trae su display; cambiarlo y guardar pone al día la fila y los stocks ──
    const a10 = await display(A10_CON_MARCO);
    await pagina.click(`${FILA_DIANA} button[onclick="abrirModalEditar(this)"]`);
    v = await ver('edit');
    revisar(v.id === lg.id && v.texto === textoDe(LG), 'Editar trae el display de la reparación', JSON.stringify({ id: v.id, texto: v.texto }));

    await pagina.tap('#edit-buscar-display');
    await pagina.keyboard.type('samsung a10', { delay: 40 });
    await tocarFila('edit', 'Con marco');
    await pagina.click('#edit-btn-guardar');
    const guardo = await modalEditarCerrado();
    revisar(guardo && await displayDeDiana() === 'Display: Pantalla Samsung A10 ×1', 'Cambiarlo y guardar cambia la fila', await displayDeDiana());

    const [lgAhora, a10Ahora] = [await display(LG), await display(A10_CON_MARCO)];
    revisar(lgAhora.stock === lg.stock && a10Ahora.stock === a10.stock - 1,
        'Y los stocks del buscador se ponen al día sin recargar: vuelve la LG y baja la A10',
        `LG ${lgDespues.stock} → ${lgAhora.stock} · A10 ${a10.stock} → ${a10Ahora.stock}`);

    // ── 9. Un display desactivado sale «(inactivo)» al editar su reparación y se conserva ──
    pantallas('desactivar', 'Pantalla Samsung A10', '1');
    await pagina.reload();
    await pagina.click(`${FILA_DIANA} button[onclick="abrirModalEditar(this)"]`);
    v = await ver('edit');
    revisar(v.id === a10.id && v.texto === 'Pantalla Samsung A10 (inactivo)', 'Desactivado, al editar sale «(inactivo)»',
        JSON.stringify({ id: v.id, texto: v.texto }));

    await pagina.tap('#edit-buscar-display');
    v = await ver('edit');
    revisar(v.filas[0]?.nombre === 'Pantalla Samsung A10 (inactivo)' && !v.filas[0].bloqueada && v.filas[0].resaltada,
        'Y en la lista está primero y se puede elegir', JSON.stringify(v.filas[0]));

    await pagina.tap('#modal-editar h3');
    await pagina.click('#edit-btn-guardar');
    const conservo = await modalEditarCerrado();
    revisar(conservo && await displayDeDiana() === 'Display: Pantalla Samsung A10 ×1', 'Guardar lo conserva', await displayDeDiana());

    revisar(errores.length === 0, 'Sin errores de JavaScript', errores.join(' | '));
    await contexto.close();
}
