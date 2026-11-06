# Desarrollo local

## Requisitos

- PHP 8.4 y Composer 2.
- Node.js 20.19 y pnpm 10.25.
- MySQL 8, PostgreSQL 16 y Redis 7 para el stack completo.

## Instalación

Desde la raíz:

```bash
git clone https://github.com/Cosmopolis4286/playrke.git
cd playrke
pnpm install
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Configura `backend/.env` con credenciales locales. Crea la base MySQL indicada por `DB_DATABASE`, PostgreSQL indicada por `DB_TRIVIA_DATABASE` y configura Redis. La conexión de Trivia se llama `pgsql_trivia`.

```bash
php artisan migrate
```

No ejecutes seeders ni pruebas destructivas contra bases compartidas o producción.

## Procesos locales

Desde `backend/`, en terminales separadas:

```bash
php artisan serve
pnpm run dev
php artisan reverb:start
php artisan queue:work
```

Para el stack Docker local utiliza el Compose de la raíz: `docker compose -f docker-compose.dev.yml up -d --build`.

## Variables relevantes

| Grupo | Uso |
|---|---|
| `APP_*` | Runtime Laravel |
| `DB_*` | MySQL principal |
| `DB_TRIVIA_*` | PostgreSQL Trivia |
| `REDIS_*` | Cache, colas y locks |
| `SESSION_*`, `SANCTUM_STATEFUL_DOMAINS` | Sesión y dominios SPA |
| `REVERB_*`, `VITE_REVERB_*` | Tiempo real |
| `TURNSTILE_*` | Cloudflare Turnstile |
| `PAYMENTS_TRON_*` | Integración USDT-TRC20 |

La fuente completa es `backend/.env.example`. Nunca expongas claves privadas mediante variables `VITE_*`.

## Pruebas y calidad

Desde `backend/`:

```bash
php artisan test
./vendor/bin/pint --test
pnpm run build
pnpm run test
pnpm run typecheck
```

La suite de integración requiere los servicios declarados por `backend/phpunit.xml` o variables equivalentes. Las pruebas de concurrencia que dependen de `lockForUpdate()` deben usar el motor real; SQLite no reproduce el mismo comportamiento.

## Windows / PowerShell

Si tu versión de PowerShell no admite `&&`, ejecuta comandos separados:

```powershell
Set-Location backend
php artisan test
pnpm run build
```

## Frontend Nuxt

El paquete Nuxt está fuera del workspace activo y se ejecuta por separado desde `frontend/`. No confundir sus comandos con el build Inertia/Vite del backend.
