PipeCell — Panel de Gestión de Reparaciones
Sistema de administración para talleres de reparación de celulares. Permite gestionar el flujo completo de reparaciones, desde la recepción del equipo hasta la entrega al cliente, incluyendo control de cajas físicas, registro de abonos y seguimiento de estados.

Tecnologías

PHP 8.2+
Laravel 11
MySQL
Tailwind CSS
Vite
Chart.js 4.4.1


Requisitos previos
Antes de instalar el proyecto asegúrate de tener instalado:

PHP 8.2 o superior
Composer
Node.js 18 o superior
MySQL 8.0 o superior
Git


Instalación local
1. Clonar el repositorio
bashgit clone https://github.com/tu-usuario/pipecell.git
cd pipecell
2. Instalar dependencias PHP
bashcomposer install
3. Instalar dependencias JavaScript
bashnpm install
4. Configurar el archivo de entorno
bashcp .env.example .env
Edita el .env con tus datos locales:
dotenvAPP_NAME=PipeCell
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pipecell
DB_USERNAME=root
DB_PASSWORD=
5. Generar la clave de la aplicación
bashphp artisan key:generate
6. Crear la base de datos
Crea una base de datos llamada pipecell en tu gestor MySQL, luego ejecuta las migraciones:
bashphp artisan migrate
7. Poblar la base de datos con datos iniciales
bashphp artisan db:seed
Esto crea:

Usuario administrador por defecto
Cajas físicas iniciales (grupos A, B, C con 5 cajas cada uno)

8. Compilar los assets
bashnpm run dev
```

---

## Credenciales por defecto

| Campo | Valor |
|---|---|
| Email | admin@pipecell.com |
| Contraseña | password |

> Cambia estas credenciales inmediatamente después del primer inicio de sesión.

---

## Estructura del proyecto
```
pipecell/
├── app/
│   ├── Enums/              # EstadoReparacion, EstadoCaja, RolUsuario
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/      # DashboardController, ReparacionController
│   │   │   ├── Auth/       # LoginController
│   │   ├── Requests/       # StoreReparacionRequest, UpdateReparacionRequest
│   ├── Models/             # Reparacion, Caja, Abono, User
│   ├── Services/           # ReparacionService
├── database/
│   ├── migrations/
│   ├── seeders/
├── resources/
│   ├── views/
│   │   ├── admin/          # dashboard, reparaciones
│   │   ├── auth/           # login
│   │   ├── layouts/        # admin.blade.php
├── routes/
│   └── web.php

Funcionalidades principales
Reparaciones

Registro de reparaciones con datos del cliente y equipo
Asignación automática de caja física
Estados: En proceso → Arreglado → Entregado
Cambio de estado via AJAX sin recargar la página
Edición de datos desde modal inline
Registro de abonos parciales con control de saldo
Alertas visuales para reparaciones con más de 8 y 15 días en proceso
Filtros por estado, periodo y búsqueda en tiempo real

Cajas

Control de disponibilidad en tiempo real
Validación de concurrencia con lockForUpdate() para evitar condiciones de carrera
Una caja solo puede tener una reparación activa simultánea

Dashboard

Métricas KPI: ingresos, ganancia, completadas y reparaciones en proceso
Gráfica de barras de ingresos mensuales con selector de año independiente (2026–2029)
Gráfica de dona con distribución por estado del periodo seleccionado
Gráfica de área con actividad diaria de los últimos 30 días
Top 5 marcas más reparadas con escala dinámica
Estado de cajas en tiempo real
Cobros pendientes de reparaciones activas

Autenticación

Login con validación de estado de cuenta
Middleware de redirección según rol
Perfil de usuario con cambio de contraseña seguro


Despliegue en producción
1. Compilar assets para producción
bashnpm run build
2. Instalar dependencias sin paquetes de desarrollo
bashcomposer install --no-dev --optimize-autoloader
3. Configurar el .env de producción
dotenvAPP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nombre_bd
DB_USERNAME=usuario_bd
DB_PASSWORD=contraseña_bd
4. Subir al servidor
Sube todos los archivos excepto node_modules y .git. Si no tienes acceso SSH, sube el proyecto comprimido y extráelo desde el administrador de archivos del hosting.
5. Apuntar el dominio a la carpeta /public
En cPanel configura el documento raíz del dominio hacia la carpeta public del proyecto, o agrega esto en el .htaccess de public_html:
apacheRewriteEngine On
RewriteRule ^(.*)$ public/$1 [L]

Convención de ramas
PrefijoUsofeat/Nueva funcionalidadfix/Corrección de bugshotfix/Corrección urgente en producciónrefactor/Mejoras de código sin cambiar funcionalidad
