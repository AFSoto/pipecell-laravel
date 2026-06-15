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
```

> El correo es necesario para la recuperación de contraseña.

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
│                           # ProductoImagen, Venta, VentaItem
└── Services/               # ReparacionService, CategoriaService, ProductoService

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
└── web.php                 # Todas las rutas (solo web, sin API)
```

---

## Despliegue en producción

```bash
# 1. Compilar assets
npm run build

# 2. Dependencias sin dev
composer install --no-dev --optimize-autoloader

# 3. Optimizaciones Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Enlace de almacenamiento
php artisan storage:link
```

### `.env` de producción

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.com

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=file
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

---

## Convención de ramas

| Prefijo | Uso |
| --- | --- |
| `feat/` | Nueva funcionalidad |
| `fix/` | Corrección de bugs |
| `hotfix/` | Corrección urgente en producción |
| `refactor/` | Mejoras internas sin cambiar comportamiento |
