# PipeCell — Panel de Gestión para Talleres de Celulares

Sistema web completo para la administración de talleres de reparación de celulares. Cubre el ciclo completo del negocio: recepción de equipos, seguimiento de reparaciones, control de inventario, punto de venta y reportes financieros.

---

## Funcionalidades del sistema

### Reparaciones

- Registro de reparaciones con datos del cliente, equipo, falla y valor
- Asignación automática de caja física con protección contra condiciones de carrera (`lockForUpdate`)
- Flujo de estados: **En proceso → Arreglado → Entregado**
- Cambio de estado vía AJAX sin recarga de página
- Registro de abonos parciales con control de saldo pendiente
- Bloqueo: no se puede marcar como entregada una reparación con saldo pendiente
- Edición de datos desde modal inline
- Registro del **display usado** al crear o editar: se descuenta del inventario (1 por defecto, cantidad editable) y queda el movimiento de stock. No cambia el costo de "Repuestos": el valor total ya incluye repuestos y mano de obra
- **Aviso automático por WhatsApp** al cliente cuando la reparación pasa a "Arreglado", una sola vez, con indicador en la fila (enviado / entregado / leído / falló / no enviado) y botón para reintentar si falló
- **Tiempo real** (Pusher): lo que hace otra persona aparece solo en las demás pantallas (estado, cobros, datos, WhatsApp, contadores y cajas libres), y guardar una edición nunca pisa los cambios de otro sin avisar
- Alertas visuales para reparaciones con más de 8 y 15 días en proceso
- Filtros por estado, período (hoy / semana / mes / trimestre / año / personalizado) y búsqueda en tiempo real

### Cajas físicas

- Control de disponibilidad en tiempo real (grupos A, B, C con hasta 5 cajas cada uno)
- Una caja solo puede tener una reparación activa simultánea
- La disponibilidad se determina por relación de reparaciones activas, no por columna de estado

### Inventario de productos

- Categorías con activación/desactivación (no se puede desactivar una categoría con productos activos)
- Productos con código, descripción, precios de compra/venta, stock y stock mínimo
- Alertas de stock bajo (cuando `stock ≤ stock_minimo`)
- Múltiples imágenes por producto con imagen principal designada
- Los productos nunca se eliminan físicamente para preservar el historial de ventas
- Filtros por categoría, stock bajo y búsqueda

### Ventas (punto de venta)

- Registro de ventas con múltiples ítems (producto + cantidad + precio unitario)
- Descuento automático de stock al registrar una venta
- Cálculo de subtotales por ítem y total de la venta

### Dashboard financiero

- KPIs por período: ingresos totales, ganancia neta, reparaciones completadas, activas en curso
- Comparativa porcentual respecto al período anterior
- Gráfica de barras de ingresos mensuales con selector de año independiente
- Gráfica de dona con distribución de reparaciones por estado
- Gráfica de área con actividad diaria de los últimos 30 días
- Top 5 marcas más reparadas
- Estado de cajas en tiempo real agrupadas por grupo
- Resumen de cobros pendientes de reparaciones activas

### Autenticación y perfil

- Login con verificación de estado de cuenta (activo/inactivo)
- Recuperación de contraseña por correo electrónico (token de 60 minutos)
- Perfil de usuario: cambio de nombre, email y contraseña segura
- Visualización y cierre de otras sesiones activas
- Roles: **Administrador** y **Técnico**

---

## Tecnologías

| Capa | Tecnología |
| --- | --- |
| Backend | Laravel 12, PHP 8.2+ |
| Base de datos | MySQL 8.0+ |
| Frontend | Blade, Tailwind CSS 4, Vite 7 |
| Gráficas | Chart.js 4 |
| HTTP async | Axios |
| Autenticación | Laravel Auth + Password Broker |

---

## Requisitos previos

- PHP 8.2 o superior (con extensiones `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`)
- Composer 2
- Node.js 18 o superior
- MySQL 8.0 o superior
- Git

---

## Instalación local

### Opción rápida (un solo comando)

```bash
git clone https://github.com/AFSoto/pipecell-laravel.git
cd pipecell-laravel
composer setup
php artisan storage:link
```

`composer setup` ejecuta en orden: `composer install` → copia `.env` → genera clave → migra → `npm install` → `npm run build`.

### Opción paso a paso

```bash
# 1. Clonar
git clone https://github.com/AFSoto/pipecell-laravel.git
cd pipecell-laravel

# 2. Dependencias
composer install
npm install

# 3. Entorno
cp .env.example .env
php artisan key:generate

# 4. Base de datos (edita .env con tus credenciales antes)
php artisan migrate
php artisan db:seed

# 5. Assets y almacenamiento
npm run build
php artisan storage:link
```

### Variables de entorno relevantes

```dotenv
APP_NAME=PipeCell
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost
APP_LOCALE=es
APP_TIMEZONE=America/Bogota

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pipecell
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=smtp
MAIL_HOST=smtp.ejemplo.com
MAIL_PORT=587
MAIL_USERNAME=usuario@ejemplo.com
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@ejemplo.com

# Aviso por WhatsApp (opcional: si están vacías no se envía nada)
WHATSAPP_TOKEN=
WHATSAPP_PHONE_NUMBER_ID=
WHATSAPP_API_VERSION=v26.0
WHATSAPP_PLANTILLA_LISTA=reparacion_lista
WHATSAPP_IDIOMA=es
# Webhook de entrega (opcional: sin ellas no se sabe si el aviso llegó)
WHATSAPP_VERIFY_TOKEN=
WHATSAPP_APP_SECRET=
```

> El correo es necesario para la recuperación de contraseña.

---

## Aviso por WhatsApp (Meta Cloud API)

Cuando una reparación pasa de **En proceso** a **Arreglado**, la plataforma envía al cliente una plantilla de WhatsApp y muestra el resultado en la fila. No se envía si el cliente no tiene teléfono, si está desmarcado "Tiene WhatsApp" o si el número no es un celular colombiano.

Cada reparación recibe el aviso **una sola vez**: aunque vuelva a "En proceso" y otra vez a "Arreglado", o dos personas pulsen el botón al mismo tiempo, no se repite. Solo se puede reintentar si el envío falló o si Meta avisó que no lo pudo entregar.

"Enviado" (✓) significa que Meta aceptó el mensaje. Con el webhook configurado, la fila pasa a **entregado** (✓✓) o **leído** (✓✓ en azul) al recargar la página, o a **falló** si el número no tiene WhatsApp.

**Costo:** desde el 1 de octubre de 2026 Meta cobra cada aviso de negocio (plantilla *Utility*), unos US$0,0008 por mensaje entregado en Colombia. Para desarrollar, Meta da un número de prueba gratis que envía a hasta 5 números verificados.

**Configuración:**

1. Crear un portafolio en [business.facebook.com](https://business.facebook.com) y una app tipo *Business* en [developers.facebook.com](https://developers.facebook.com) con el producto WhatsApp.
2. Registrar el número que enviará los avisos y agregar un método de pago en WhatsApp Manager.
3. Crear la plantilla `reparacion_lista` (categoría **Utility**) y esperar la aprobación:

   ```text
   Hola {{1}}, tu {{2}} ya está listo para recoger en PipeCell. Saldo pendiente: {{3}}. ¡Te esperamos!
   ```

   `{{1}}` = nombre del cliente, `{{2}}` = marca y modelo, `{{3}}` = saldo pendiente (ej. `$50.000`).
   - **Idioma:** "Spanish" (código `es`). Si eliges otro, como "Spanish (MEX)", pon su código en `WHATSAPP_IDIOMA` (ej. `es_MX`).
   - **Tipo de variable:** número (`{{1}}`, `{{2}}`, `{{3}}`), no con nombre. La plataforma envía los datos en ese orden.
4. Crear un *System User* con un token **permanente** (permiso `whatsapp_business_messaging`) y poner el token y el *Phone Number ID* en el `.env`. El token temporal de la página "API Setup" vence en 24 horas.
5. Webhook de entrega (opcional, recomendado):
   1. Inventar una clave larga y ponerla en `WHATSAPP_VERIFY_TOKEN`; copiar el *App Secret* (Configuración de la app → Básica) en `WHATSAPP_APP_SECRET`. Correr `php artisan config:cache`.
   2. En la app de Meta → WhatsApp → Configuración → Webhook: URL de devolución `https://tudominio.com/webhooks/whatsapp` y como token de verificación la misma clave de `WHATSAPP_VERIFY_TOKEN`.
   3. Suscribirse al campo `messages`.

   El webhook solo funciona en el servidor (Meta necesita una URL pública con HTTPS). Rechaza toda llamada que no venga firmada con el App Secret.

Si algo de la configuración falla, la fila muestra "falló" con el motivo al pasar el cursor por encima.

---

## Tiempo real (Pusher)

Con 2 o 3 personas trabajando a la vez (mostrador y tablet), lo que hace una aparece solo en las otras pantallas, sin recargar. Usa **Pusher Channels**, que mantiene las conexiones en vivo con los navegadores; por eso funciona en hosting compartido, sin VPS ni worker de colas.

**Qué se actualiza solo en la lista de reparaciones:**

- La fila de una reparación que otra persona cambió: estado, valores, abonos, datos, display e indicador de WhatsApp. Se resalta 2 segundos y, al pasar el cursor, dice quién la cambió. Si ya no corresponde al filtro que estás viendo, se va a los 3 segundos.
- Los contadores por estado y las cajas libres del formulario de nueva reparación. Si se ocupa la caja que tenías elegida, se avisa debajo del campo antes de guardar.
- Avisos flotantes solo cuando hay algo que hacer: "Lista: …" cuando otra persona marca Arreglado, y "No le llegó el WhatsApp… Llámalo", que se queda hasta cerrarlo.
- Modales abiertos: si otra persona cambia la reparación que estás editando, aparece "Cargar sus cambios" y nunca se tocan los campos que estás escribiendo. Si cobra mientras tienes abierto el abono, el saldo se pone al día.

Las reparaciones nuevas no se insertan solas en la lista: aparecen al recargar.

**Siempre, aunque Pusher no esté configurado:** al guardar una edición se envían los datos con que se abrió el modal. Si otra persona cambió alguno de esos campos, no se guarda y se pide cargar sus cambios (un abono o un cambio de estado no cuentan).

**Privacidad:** por Pusher solo pasa qué reparación cambió, qué pasó y el primer nombre de quien lo hizo. Los datos del cliente se piden a la plataforma (`GET /admin/reparaciones/sincronizar`).

**Indicador:** en el encabezado de Reparaciones. Las demás páginas todavía no se actualizan solas, así que no lo muestran. Puede verse de tres formas:

- verde "En vivo", solo cuando el canal ya quedó suscrito;
- gris "Sin conexión en vivo";
- ámbar "Recarga la página", si la sesión venció.

Al suscribirse, al reconectar o cuando la tablet vuelve del reposo, la pantalla se pone al día sola.

**Sesión:** mientras Reparaciones esté abierta, cada 30 minutos se pone al día lo visible. Ese pedido mantiene viva la sesión (vence a las 2 horas sin actividad), así que la tablet no se cierra sola. Si el iPad estuvo bloqueado más de 2 horas, al volver sale un aviso para recargar.

**Cambios propios:** después de guardar, cobrar, cambiar el estado o enviar el WhatsApp, la pantalla le pide la fila al servidor y descarta las respuestas viejas. Así lo que se ve siempre termina igual a lo guardado, aunque otra persona haya tocado la misma reparación en ese momento.

**Configuración:**

1. Crear una app de **Channels** en [dashboard.pusher.com](https://dashboard.pusher.com) (plan Sandbox, gratis: 100 conexiones simultáneas y 200.000 mensajes al día) con el cluster **mt1** (US East, Virginia). Desde Colombia es el que responde más rápido: el tráfico normalmente sale por Miami, así que São Paulo (sa1) queda más lejos en la práctica. El cluster no se puede cambiar después de crear la app.
2. Llenar el bloque de Pusher del `.env` con sus *App Keys* y cambiar `BROADCAST_CONNECTION` a `pusher`:

   ```dotenv
   BROADCAST_CONNECTION=pusher
   PUSHER_APP_ID=
   PUSHER_APP_KEY=
   PUSHER_APP_SECRET=
   PUSHER_APP_CLUSTER=mt1
   VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
   VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
   ```

3. Correr `npm run build` con esas variables en el `.env` local: las `VITE_` quedan dentro del JS compilado. La key es pública; el secret nunca llega al navegador.
4. En el servidor, poner las mismas variables en su `.env` y correr `php artisan config:cache`.

Mientras no esté configurado, deja `BROADCAST_CONNECTION=null`: con `log`, cada cambio escribe una línea en `laravel.log`. Si Pusher falla o tarda más de 3 segundos, lo que se estaba guardando se guarda igual y el error queda en `laravel.log`.

---

## Desarrollo

```bash
composer dev   # Levanta servidor, worker de colas, log tail y Vite HMR en paralelo
```

O por separado:

```bash
php artisan serve    # Servidor PHP en http://localhost:8000
npm run dev          # Vite HMR en http://localhost:5173
php artisan queue:work  # Worker de colas (necesario para envío de correos)
```

---

## Credenciales por defecto

| Campo | Valor |
| --- | --- |
| Email | `admin@pipecell.com` |
| Contraseña | `password` |

> Cambia estas credenciales inmediatamente en **Perfil** tras el primer inicio de sesión.

El seeder también crea:

- 15 cajas físicas (grupos A, B, C × 5 unidades)
- 8 categorías de productos (Accesorios de Carga, Audio y Sonido, Protección y Estética, Repuestos - Pantallas, Repuestos - Baterías, Componentes Internos, Almacenamiento, Herramientas Técnicas)

---

## Estructura del proyecto

```text
app/
├── Enums/                  # EstadoReparacion, EstadoCaja, RolUsuario
├── Http/
│   ├── Controllers/
│   │   ├── Admin/          # Dashboard, Reparacion, Caja, Categoria, Producto, Perfil
│   │   └── Auth/           # Login, PasswordReset
│   └── Requests/           # Validaciones para cada recurso
├── Models/                 # Reparacion, Caja, Abono, User, Categoria, Producto,
│                           # ProductoImagen, Venta, VentaItem, ReparacionRepuesto, Notificacion
└── Services/               # ReparacionService, CategoriaService, ProductoService,
                            # NotificacionService, WhatsAppService

database/
├── migrations/             # 10 migraciones
└── seeders/                # UserSeeder, CajaSeeder, CategoriaSeeder

resources/
├── js/                     # app.js + bootstrap.js (Axios global)
├── css/                    # Tailwind CSS 4
└── views/
    ├── admin/              # dashboard, reparaciones, categorias, productos, perfil
    ├── auth/               # login, olvide-contrasena, restablecer-contrasena
    ├── emails/             # reset-password
    └── layouts/            # app.blade.php, admin.blade.php

routes/
├── web.php                 # Rutas del panel (solo web, sin API)
├── channels.php            # Canal privado "taller" del tiempo real (Pusher)
└── webhooks.php            # Webhook de WhatsApp (sin sesión ni CSRF)
```

---

## Despliegue en producción

```bash
# 1. Compilar assets
npm run build

# 2. Dependencias sin dev
composer install --no-dev --optimize-autoloader

# 3. Migraciones pendientes (cuando la versión trae migraciones nuevas)
#    No corras db:seed en producción: crea los usuarios por defecto
php artisan migrate --force

# 4. Optimizaciones Laravel (vuelve a correrlas si cambiaste el .env)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Enlace de almacenamiento
php artisan storage:link
```

> Al subir archivos al servidor no reemplaces su `.env` ni la carpeta `storage/` (ahí están las fotos de productos subidas en producción).

### `.env` de producción

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.com

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=file

# null hasta configurar Pusher; después, pusher (ver "Tiempo real")
BROADCAST_CONNECTION=null
```

### Apuntar el dominio a `/public`

En cPanel, configura el documento raíz del dominio hacia la carpeta `public/` del proyecto. Si usas `.htaccess` en `public_html/`:

```apache
RewriteEngine On
RewriteRule ^(.*)$ public/$1 [L]
```

---

## Tests

```bash
composer test                              # Limpia caché y corre PHPUnit
php artisan test --filter=NombreTest       # Test específico
```

Los tests usan SQLite en memoria y nunca llaman a Meta (`Http::fake()`). Requieren las dependencias de desarrollo (`composer install`); si después vas a subir `vendor/` al servidor, vuelve a correr `composer install --no-dev --optimize-autoloader`.

---

## Convención de ramas

| Prefijo | Uso |
| --- | --- |
| `feat/` | Nueva funcionalidad |
| `fix/` | Corrección de bugs |
| `hotfix/` | Corrección urgente en producción |
| `refactor/` | Mejoras internas sin cambiar comportamiento |
