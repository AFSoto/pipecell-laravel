// Pruebas de navegador de la búsqueda en vivo (Productos y Reparaciones), del filtro de periodo,
// del aviso por WhatsApp y del buscador de display, en WebKit (el motor de Safari, el del iPad) y
// en Chromium, contra la app con una base SQLite temporal: nunca toca MySQL ni Meta. Ver README.md.
//
//   npm run e2e                          todas, en WebKit y Chromium
//   npm run e2e -- --motor webkit        un solo motor
//   npm run e2e -- --solo reparaciones   una sola prueba (productos, reparaciones, periodo, carga-lenta, whatsapp o display)
//   npm run e2e -- --capturas <carpeta>  además guarda capturas
//   npm run e2e:rendimiento              3.000 reparaciones: lo que pesa y tarda una búsqueda
import { spawn, execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, mkdtempSync, openSync, rmSync } from 'node:fs';
import { createServer } from 'node:http';
import { tmpdir } from 'node:os';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { chromium, webkit } from 'playwright-core';
import { crearRevisor, dormir } from './lib.mjs';
import * as productos from './productos.mjs';
import * as reparaciones from './reparaciones.mjs';
import * as periodo from './periodo.mjs';
import * as cargaLenta from './carga-lenta.mjs';
import * as rendimiento from './rendimiento.mjs';
import * as whatsapp from './whatsapp.mjs';
import * as display from './display.mjs';

const RAIZ    = resolve(dirname(fileURLToPath(import.meta.url)), '../..');
const MOTORES = { webkit, chromium };

function abortar(motivo) {
    console.error(`No se corrieron las pruebas: ${motivo}`);
    process.exit(1);
}

// ── Opciones ──
const args        = process.argv.slice(2);
const opcion      = nombre => { const i = args.indexOf(nombre); return i === -1 ? null : args[i + 1]; };
const esRendimiento = args.includes('--rendimiento');
const motores     = opcion('--motor') ? [opcion('--motor')] : (esRendimiento ? ['webkit'] : ['webkit', 'chromium']);
const capturas    = opcion('--capturas') ? resolve(opcion('--capturas')) : null;
// whatsapp y display van al final: whatsapp pasa reparaciones a «Arreglado», y display agrega
// pantallas, crea una reparación y cambia stock
const TODAS       = { productos, reparaciones, periodo, 'carga-lenta': cargaLenta, whatsapp, display };
const solo        = opcion('--solo');
const suites      = esRendimiento ? [rendimiento] : (solo ? [TODAS[solo]] : Object.values(TODAS));

if (motores.some(motor => !MOTORES[motor])) abortar('motor desconocido (webkit o chromium)');
if (solo && !TODAS[solo]) abortar(`--solo ${solo}: las pruebas son ${Object.keys(TODAS).join(', ')}`);

// ── Antes de empezar: que la app de prueba no termine usando otra cosa ──
if (existsSync(join(RAIZ, 'bootstrap/cache/config.php'))) {
    abortar('la config está cacheada: se ignoraría la base de prueba y usaría MySQL. Corre «php artisan config:clear».');
}
if (existsSync(join(RAIZ, 'public/hot'))) {
    abortar('existe public/hot: las páginas cargarían el JS de «npm run dev». Detenlo y corre «npm run build».');
}
if (!existsSync(join(RAIZ, 'public/build/manifest.json'))) {
    abortar('falta public/build: corre «php artisan view:clear» y «npm run build».');
}
if (capturas) mkdirSync(capturas, { recursive: true });

// La base, el log del servidor y los perfiles del navegador van aquí (se borra si todo sale bien)
const temporal = mkdtempSync(join(tmpdir(), 'pipecell-e2e-'));

/**
 * Un Meta falso para el aviso por WhatsApp: responde como la API de WhatsApp (con el id del
 * mensaje) y guarda cada pedido, así las pruebas saben si se envió y con qué. Nada sale a
 * Internet. Las reparaciones de la base no tienen WhatsApp: solo envía la prueba que lo activa.
 */
async function levantarMetaFalso() {
    const pedidos  = [];
    const servidor = createServer((pedido, respuesta) => {
        let cuerpo = '';
        pedido.on('data', parte => { cuerpo += parte; });
        pedido.on('end', () => {
            let datos = null;
            try { datos = JSON.parse(cuerpo); } catch { /* no era JSON */ }
            pedidos.push({ ruta: pedido.url, token: pedido.headers.authorization, datos });
            respuesta.writeHead(200, { 'Content-Type': 'application/json' });
            respuesta.end(JSON.stringify({ messaging_product: 'whatsapp', messages: [{ id: `wamid.e2e.${pedidos.length}` }] }));
        });
    });
    await new Promise(listo => servidor.listen(0, '127.0.0.1', listo));

    return { url: `http://127.0.0.1:${servidor.address().port}`, pedidos, cerrar: () => servidor.close() };
}

const meta = await levantarMetaFalso();

/**
 * El entorno de la app de prueba: base SQLite temporal, sesión en cookie y nada hacia afuera.
 * WhatsApp queda configurado, pero contra el Meta falso.
 */
const entornoPara = base => ({
    ...process.env,
    APP_ENV: 'local', APP_DEBUG: 'false', DB_CONNECTION: 'sqlite', DB_DATABASE: base,
    SESSION_DRIVER: 'cookie', CACHE_STORE: 'array', QUEUE_CONNECTION: 'sync', BROADCAST_CONNECTION: 'null',
    MAIL_MAILER: 'array', LOG_CHANNEL: 'stderr',
    WHATSAPP_TOKEN: 'e2e', WHATSAPP_PHONE_NUMBER_ID: 'e2e', WHATSAPP_API_URL: meta.url,
});

function prepararBase(base, tamano) {
    try {
        console.log(execFileSync('php', [join(RAIZ, 'tests/e2e/preparar-base.php'), tamano], { env: entornoPara(base), encoding: 'utf8' }).trim());
    } catch (error) {
        abortar(`no se pudo preparar la base: ${error.stderr || error.message}`);
    }
}

/** Levanta php -S desde public/ (su router usa la carpeta actual) y espera a que responda. */
async function levantarServidor(base, motor) {
    for (let intento = 0; intento < 3; intento++) {
        const puerto   = 8100 + Math.floor(Math.random() * 800);
        const log      = openSync(join(temporal, `servidor-${motor}.log`), 'a');
        const servidor = spawn('php', ['-S', `127.0.0.1:${puerto}`, '../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'],
            { cwd: join(RAIZ, 'public'), env: entornoPara(base), stdio: ['ignore', log, log] });

        for (let i = 0; i < 60 && servidor.exitCode === null; i++) {
            try {
                if ((await fetch(`http://127.0.0.1:${puerto}/login`)).ok) return { app: `http://127.0.0.1:${puerto}`, servidor };
            } catch { /* arrancando */ }
            await dormir(150);
        }
        servidor.kill();   // puerto ocupado u otro problema: otro intento
    }
    abortar(`el servidor de prueba no arrancó (ver ${temporal})`);
}

let fallos = 0;

for (const motor of motores) {
    const base = join(temporal, `base-${motor}.sqlite`);
    prepararBase(base, esRendimiento ? 'grande' : 'normal');

    const { app, servidor } = await levantarServidor(base, motor);
    const navegador         = await MOTORES[motor].launch({ headless: true });
    const revisor           = crearRevisor(motor);

    // «Otra persona» cambia una reparación en esta base (tests/e2e/cambiar.php). Devuelve el id
    const cambiar = (cliente, campo, valor) => execFileSync('php', [join(RAIZ, 'tests/e2e/cambiar.php'), cliente, campo, valor],
        { env: entornoPara(base), encoding: 'utf8' }).trim();

    // Las pantallas de la prueba del buscador de display (tests/e2e/pantallas.php)
    const pantallas = (...argumentos) => execFileSync('php', [join(RAIZ, 'tests/e2e/pantallas.php'), ...argumentos],
        { env: entornoPara(base), encoding: 'utf8' }).trim();

    try {
        for (const suite of suites) {
            console.log(`\n── ${suite.titulo} · ${motor} ──`);
            try {
                await suite.correr({ navegador, app, revisar: revisor.revisar, cambiar, pantallas, capturas, motor, meta });
            } catch (error) {
                revisor.revisar(false, `${suite.titulo}: la prueba se cortó`, error.message.split('\n')[0]);
            }
        }
    } finally {
        await navegador.close();
        servidor.kill();
    }

    fallos += revisor.fallos();
}

meta.cerrar();

if (fallos) {
    console.log(`\n${fallos} revisión(es) fallaron. El log del servidor quedó en ${temporal}`);
} else {
    await dormir(300);   // que php suelte la base
    try { rmSync(temporal, { recursive: true, force: true }); } catch { /* se borra con la carpeta temporal del sistema */ }
    console.log('\nTodo bien');
}
process.exit(fallos ? 1 : 0);
