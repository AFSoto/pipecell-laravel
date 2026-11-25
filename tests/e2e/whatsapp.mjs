// Aviso por WhatsApp al pasar a «Arreglado» (ver README.md): el modal que pregunta si se avisa,
// con el celular del cliente, y la opción para no avisar (por ejemplo, el cliente está en el
// taller). Usa el Meta falso de correr.mjs, que guarda cada pedido. Cambia datos: «Cliente 07»,
// «Cliente 08» y «Cliente 09» quedan en «Arreglado»; por eso va al final.
import { join } from 'node:path';
import { esperar, nuevaPagina, textoDe } from './lib.mjs';

export const titulo = 'Aviso por WhatsApp';

const MODAL = '#modal-arreglado';

export async function correr({ navegador, app, revisar, cambiar, capturas, motor, meta }) {
    const { contexto, pagina, errores } = await nuevaPagina(navegador, app);

    // La base crea las reparaciones sin WhatsApp: a dos se les activa (celulares 310 555 0010 y
    // 0011). «Cliente 07» sigue sin WhatsApp (cambiar() devuelve el id)
    const conAviso     = cambiar('Cliente 08', 'tiene_whatsapp', '1');
    const otraConAviso = cambiar('Cliente 09', 'tiene_whatsapp', '1');
    const sinAviso     = cambiar('Cliente 07', 'tiene_whatsapp', '0');

    // Meta falso: los pedidos de esta prueba (el de correr.mjs sirve a los dos motores)
    const yaHabia = meta.pedidos.length;
    const pedidos = () => meta.pedidos.slice(yaHabia);

    const fila   = id => `tr[data-id="${id}"]`;
    const elegir = (id, estado) => pagina.selectOption(`${fila(id)} select`, estado);

    /** Lo que muestra el modal ahora. */
    const verModal = () => pagina.evaluate(s => ({
        abierto: !document.querySelector(s).classList.contains('hidden'),
        cliente: document.getElementById('arreglado-cliente').textContent.trim(),
        celular: document.getElementById('arreglado-telefono').textContent.trim(),
        marcada: document.getElementById('arreglado-avisar').checked,
    }), MODAL);

    /** Espera a que el modal quede abierto (true) o cerrado (false). true si llegó a tiempo. */
    const modal = abierto => esperar(pagina, ([s, abierto]) => document.querySelector(s).classList.contains('hidden') !== abierto,
        [MODAL, abierto], 4000);

    /** Lo que muestra la fila: el estado guardado, el select y el indicador de WhatsApp con su botón. */
    const verFila = id => pagina.evaluate(s => {
        const tr = document.querySelector(s);
        return tr && {
            estado: tr.dataset.estado,
            select: tr.querySelector('select').value,
            aviso:  tr.querySelector('[data-whatsapp]').textContent.replace(/\s+/g, ' ').trim(),
        };
    }, fila(id));

    /** Espera a que el indicador de WhatsApp de la fila diga ese texto. */
    const avisoDice = (id, texto) => esperar(pagina, ([s, texto]) =>
        document.querySelector(s)?.querySelector('[data-whatsapp]').textContent.includes(texto), [fila(id), texto], 8000);

    await pagina.goto(`${app}/admin/reparaciones?estado=todos`);
    revisar(await pagina.isVisible(fila(conAviso)) && await pagina.isVisible(fila(otraConAviso)) && await pagina.isVisible(fila(sinAviso)),
        'Las tres reparaciones de la prueba están en la página');

    // 1. Al elegir «Arreglado» en una con WhatsApp, pregunta: el celular y la opción marcada
    await elegir(conAviso, 'arreglado');
    const abrio = await modal(true);
    let m = await verModal();
    revisar(abrio && m.cliente === 'Cliente 08 · Samsung A10' && m.celular === '310 555 0010' && m.marcada,
        'Al pasar a «Arreglado» pregunta, con el celular y «Enviar mensaje por WhatsApp» marcado', JSON.stringify(m));
    if (capturas) await pagina.screenshot({ path: join(capturas, `whatsapp-modal-${motor}.png`) });

    // 2. Cancelar: el select vuelve a «En proceso», y no se guarda nada ni se le escribe
    await pagina.click('#arreglado-cancelar');
    await modal(false);
    let f = await verFila(conAviso);
    revisar(f.select === 'en_proceso' && f.estado === 'en_proceso', 'Cancelar deja el select en «En proceso»', JSON.stringify(f));
    await pagina.reload();
    f = await verFila(conAviso);
    revisar(f.estado === 'en_proceso' && pedidos().length === 0, 'Y al recargar sigue «En proceso», sin escribirle',
        `${JSON.stringify(f)} · ${pedidos().length} pedido(s) a Meta`);

    // 3. Quitar la opción y confirmar: queda «Arreglado» sin mensaje, con el botón para avisar después
    await elegir(conAviso, 'arreglado');
    await modal(true);
    await pagina.uncheck('#arreglado-avisar');
    await pagina.click('#arreglado-confirmar');
    const sinMensaje = await avisoDice(conAviso, 'no enviado');
    f = await verFila(conAviso);
    revisar(sinMensaje && f.estado === 'arreglado' && f.aviso.includes('Enviar WhatsApp') && !f.aviso.includes('Reintentar')
        && pedidos().length === 0,
        'Sin la opción: queda «Arreglado», «WhatsApp no enviado» y el botón «Enviar WhatsApp», sin escribirle',
        `${JSON.stringify(f)} · ${pedidos().length} pedido(s) a Meta`);
    revisar((await textoDe(pagina, '#contenedor-notificaciones')).includes('No se avisó por WhatsApp'),
        'El aviso flotante dice que no se avisó por WhatsApp');

    // 4. Con la opción marcada se le escribe una vez, con el nombre y el equipo
    await elegir(otraConAviso, 'arreglado');
    await modal(true);
    await pagina.click('#arreglado-confirmar');
    const envio   = await avisoDice(otraConAviso, '✓');
    const enviado = pedidos()[0]?.datos;
    revisar(envio && pedidos().length === 1 && enviado?.to === '573105550011' && enviado?.template?.name === 'reparacion_lista'
        && JSON.stringify(enviado?.template?.components?.[0]?.parameters?.map(p => p.text)) === '["Cliente 09","Samsung A10"]',
        'Con la opción marcada se le escribe una vez, con la plantilla y 2 datos (nombre y equipo)',
        JSON.stringify(pedidos().map(p => p.datos)));

    // 5. Sin WhatsApp no pregunta: pasa directo, como siempre
    await elegir(sinAviso, 'arreglado');
    const directo = await esperar(pagina, s => document.querySelector(s)?.dataset.estado === 'arreglado', fila(sinAviso), 8000);
    m = await verModal();
    revisar(directo && !m.abierto && pedidos().length === 1, 'Una reparación sin WhatsApp pasa a «Arreglado» sin preguntar',
        `modal ${m.abierto ? 'abierto' : 'cerrado'} · ${pedidos().length} pedido(s) a Meta`);

    // 6. La del paso 3: al final el cliente no vino, y se le avisa desde la fila
    await pagina.click(`${fila(conAviso)} [data-whatsapp] button`);
    const despues = await avisoDice(conAviso, '✓');
    revisar(despues && pedidos().length === 2 && pedidos()[1]?.datos?.to === '573105550010',
        '«Enviar WhatsApp» en la fila le avisa después', `${pedidos().length} pedido(s) a Meta`);

    revisar(errores.length === 0, 'Sin errores de JavaScript', errores.join(' | '));
    await contexto.close();
}
