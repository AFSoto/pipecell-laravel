# Pruebas de navegador (búsqueda en vivo, periodo, aviso por WhatsApp y buscador de display)

Prueban la búsqueda en vivo de Productos y Reparaciones (`resources/js/busqueda-en-vivo.js`), el filtro de periodo de Reparaciones, el modal del aviso por WhatsApp y el buscador de display de los modales de Reparaciones en un navegador de verdad:

- **WebKit**, el motor de Safari, que es el que usa el iPad.
- **Chromium**, el motor de Edge y Chrome.

PHPUnit revisa el HTML. Estas pruebas revisan lo que pasa en la pantalla: que la lista cambie sola, que el teclado no se cierre, el tiempo real, la red lenta o sin conexión, y la sesión vencida.

## Correrlas

```bash
php artisan view:clear
npm run build                          # prueban el JS compilado de public/build
npm run e2e                            # todas, en WebKit y Chromium (unos 2 minutos)
npm run e2e -- --motor webkit          # un solo motor
npm run e2e -- --solo reparaciones     # una sola prueba (productos, reparaciones, periodo, carga-lenta, whatsapp o display)
npm run e2e -- --capturas C:\capturas  # además guarda capturas
npm run e2e:rendimiento                # 3.000 reparaciones: lo que pesa y tarda una búsqueda
```

## Cómo funcionan

`correr.mjs` hace todo de punta a punta:

1. Crea una base SQLite en la carpeta temporal del sistema, con `preparar-base.php`, y un usuario de prueba.
2. Levanta un **Meta falso** en otro puerto: responde como la API de WhatsApp y guarda cada pedido, así las pruebas saben si se envió un aviso y con qué datos.
3. Levanta la app con `php -S` y un entorno de prueba: esa base, sesión en cookie, sin Pusher ni correo, y WhatsApp configurado contra el Meta falso (`WHATSAPP_API_URL`). Nada sale a Internet.
4. Corre las pruebas en cada motor, con una base nueva para cada uno.
5. Apaga todo.

Si algo falla, la carpeta temporal queda con el log del servidor.

**Nunca tocan MySQL:**

- `correr.mjs` no arranca si la config está cacheada (se ignoraría la base de prueba), si existe `public/hot` o si falta `public/build`.
- `preparar-base.php`, `cambiar.php` y `pantallas.php` no corren si la base no es la SQLite temporal (`seguridad.php`).

**Las pruebas:**

| Archivo | Qué prueba |
| --- | --- |
| `productos.mjs` | Desde 3 letras; Enter desde 2 sin activar «Stock bajo»; red lenta (se cancela el pedido viejo); «Ajustar stock», «Stock bajo», «Editar → Guardar» y «Desactivar» con la búsqueda; la vista con dos pestañas; `aria-live`; sin conexión |
| `reparaciones.mjs` | La ×; WhatsApp en las filas nuevas; varias palabras y teléfono sin espacios; «Cargar sus cambios» con el modal abierto; un aviso de tiempo real mientras viene la búsqueda; `aria-live`; sesión vencida |
| `periodo.mjs` | Al pasar de «En proceso» a «Todos» el periodo arranca en «Últimos 6 meses» (el predefinido); elegir otro y volver, y la página 2; con búsqueda el indicador nombra el periodo y, sin resultados, sugiere otro; una letra y cambiar el periodo no cuenta como búsqueda; «Personalizado» con las fechas al revés se invierte. «Limpiar filtros» vuelve al inicio y solo sale fuera de él: no en «En proceso» (tampoco con las fechas que quedan de «Personalizado»), sí en «Todos» y con una búsqueda en vivo. Usa solo las reparaciones de hoy: no depende de la fecha |
| `carga-lenta.mjs` | Lo que se escribe antes de que cargue el JS se busca solo al cargar |
| `whatsapp.mjs` | Al pasar a «Arreglado» una reparación con WhatsApp sale el modal, con el celular y la opción marcada; «Cancelar» no guarda nada; sin la opción queda «Arreglado», sin escribirle y con «Enviar WhatsApp»; con la opción se le escribe una vez, con la plantilla y 2 datos; sin WhatsApp pasa directo; «Enviar WhatsApp» avisa después. Va al final porque cambia estados |
| `display.mjs` | El campo «Display» de los modales es el buscador: al marcar «¿Se usó display?» queda el cursor ahí y se abre la lista (50 y «Y N más»); todas las palabras en cualquier orden, sin importar mayúsculas ni tildes; las dos «Pantalla Samsung A10» se distinguen por el marco; tocar una la elige y suelta el foco; la sin stock no se elige; Enter elige la resaltada y nunca envía el formulario (se cuenta cada envío, con el resto del formulario lleno); Escape y tocar fuera dejan la elegida; guardar descuenta el stock; Editar trae su display, cambiarlo pone al día la fila y los stocks sin recargar, y uno desactivado sale «(inactivo)» y se conserva. Va al final porque agrega pantallas, crea una reparación y cambia stock |
| `rendimiento.mjs` | Con 3.000 reparaciones: una página de hasta 25 filas, menos de 600 KB, y ponerse al día en un solo pedido |

`cambiar.php` simula a «otra persona» que cambia una reparación, y la prueba simula el aviso de Pusher. `pantallas.php` agrega lo que usa la prueba del buscador de display (una caja libre, una pantalla con el mismo nombre que otra, una sin stock y 60 más) y desactiva una.

## Requisitos

- PHP y Node, como para el resto del proyecto.
- `playwright-core`, que está fijo en `1.63.0` en `package.json`. Necesita sus navegadores (WebKit 2359 y Chromium 1243): en este PC ya están; en otro, `npx playwright-core install webkit chromium`.

SQLite compara las tildes y MySQL no: en estas pruebas se busca «bater», no «bateria».
