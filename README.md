# Ventia

Sistema POS moderno, modular y multiempresa para tiendas de abarrotes, farmacias, pastelerías, ferreterías, tiendas de ropa, papelerías, purificadoras y comercio minorista en general.

Ventia no está limitado a un giro específico: catálogos, unidades, impuestos, variantes, lotes y configuración por empresa permiten adaptarlo a distintos tipos de negocio sin tocar código.

## Stack tecnológico

- **Backend**: Laravel 13, PHP 8.4
- **Frontend**: Vue 3 + TypeScript, Inertia.js 3
- **UI**: Tailwind CSS 4, shadcn-vue (reka-ui), Lucide icons
- **Auth**: Laravel Fortify
- **Autorización**: Spatie Laravel Permission (roles y permisos granulares) + Policies
- **Rutas tipadas**: Laravel Wayfinder
- **Base de datos**: MySQL / MariaDB
- **Pruebas**: Pest
- **Calidad de código**: Larastan (PHPStan nivel 7), Pint, ESLint, Prettier

## Arquitectura

- Multiempresa por `company_id` con aislamiento vía global scope (`App\Models\Scopes\CompanyScope` + trait `BelongsToCompany`), reforzado con Policies y validaciones explícitas en Form Requests (nunca se confía solo en el scope).
- Acceso por sucursal mediante el pivote `branch_user` y el scope `accessibleBy()`.
- Controllers delgados; lógica de negocio en `Actions` (casos de uso puntuales) y `Services` (cálculo/consulta reutilizable).
- Inventario basado en movimientos append-only (`inventory_movements`) con una proyección de saldo (`inventory_balances`); nunca se edita el stock directamente.
- Cambios de precio y costo siempre pasan por Actions transaccionales que registran historial (`product_price_histories`); nunca se sobrescriben con un `update()` genérico.

## Requisitos

- PHP 8.4 con extensiones `pdo_mysql`, `mbstring`, `bcmath`, `intl`, `gd`, `zip`
- Composer 2
- Node.js 22+ y npm
- MySQL 8 o MariaDB 10.6+ corriendo localmente

## Instalación

```bash
git clone https://github.com/JesusA2004/Ventia.git
cd Ventia

composer install
npm install

cp .env.example .env
php artisan key:generate
```

## Configuración de MySQL

Crea la base de datos y ajusta `.env` (por defecto ya apunta a MySQL):

```sql
CREATE DATABASE ventia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ventia
DB_USERNAME=root
DB_PASSWORD=
```

> El motor de tablas se fuerza a InnoDB desde `config/database.php` (necesario para foreign keys e índices únicos en `utf8mb4`), independientemente de la configuración del servidor.

## Migraciones y seeders

```bash
php artisan migrate
php artisan db:seed
```

O ambos en un paso, reiniciando la base de datos:

```bash
php artisan migrate:fresh --seed
```

Esto crea roles y permisos, una empresa demo con sucursal/almacén/caja, catálogo de ejemplo (categorías, marcas, unidades, impuestos, listas de precios), productos de muestra (simple, a granel, con variantes, con lote y caducidad) e inventario inicial.

### Credenciales demo

| Rol | Correo | Contraseña |
|---|---|---|
| Superadministrador | `superadmin@ventia.test` | `password` |
| Administrador de empresa | `admin@ventia-demo.test` | `password` |
| Cajero | `cajero@ventia-demo.test` | `password` |

## Desarrollo

```bash
composer dev
```

Levanta en paralelo `php artisan serve`, `php artisan queue:listen` y `npm run dev` (Vite).

Comandos individuales:

```bash
php artisan serve       # servidor Laravel
npm run dev              # Vite con HMR
php artisan queue:listen # worker de colas
```

Tras cambios en rutas/controllers, si necesitas regenerar las definiciones tipadas de Wayfinder manualmente:

```bash
php artisan wayfinder:generate --with-form
```

## Pruebas y calidad

```bash
composer ci:check   # lint (Pint), format (Prettier), types (Larastan + vue-tsc), Pest
php artisan test     # solo la suite de Pest
php vendor/bin/pint   # formatear PHP
npm run lint          # ESLint
npm run format        # Prettier
npm run types:check   # vue-tsc
npm run build          # build de producción (Vite)
```

## Estructura del proyecto

```
app/
  Actions/            Casos de uso transaccionales (precios, inventario, etc.)
  Enums/               Estados y tipos
  Http/Controllers/    Controllers delgados, agrupados por dominio (Catalog, Inventory, Settings)
  Http/Requests/       Form Requests con validación de aislamiento multiempresa
  Http/Resources/      API Resources
  Models/               Eloquent, con traits BelongsToCompany / BelongsToBranch
  Policies/             Autorización por modelo
  Services/             Lógica de cálculo/consulta reutilizable

resources/js/
  components/           ui/ (shadcn-vue) + componentes reutilizables por dominio
  composables/           usePermissions, etc.
  layouts/                AppLayout, AuthLayout
  pages/                  Vistas Inertia por dominio
  types/                  Tipos TypeScript compartidos
```

## Estado del proyecto

- **Fase 1** (completa): fundamentos, autenticación, multiempresa, sucursales, almacenes, cajas, usuarios, roles y permisos.
- **Fase 2** (completa): catálogo de productos, variantes, códigos de barras, precios e historial, inventario (movimientos, kardex, ajustes, transferencias, conteos, lotes y caducidades).
- Próximas fases: POS y caja, descuentos y promociones, compras y proveedores, reportes.
