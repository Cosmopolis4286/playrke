# Desarrollo local, instalación y pruebas

## Requisitos

- PHP 8.4 recomendado; Composer 2.
- Node.js 20.19 y pnpm 10.25.0 (versiones de CI).
- MySQL 8, PostgreSQL 16 y Redis 7 para el stack completo.
- Extensiones PHP requeridas por Composer y el proyecto, incluidas PDO MySQL/PostgreSQL, GMP y Redis según el dominio.

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

Configura `backend/.env` con credenciales locales. No reutilices secretos de producción ni subas el archivo.

## Bases de datos

Crea la base MySQL indicada por `DB_DATABASE` y la base PostgreSQL indicada por `DB_TRIVIA_DATABASE`. La conexión de Trivia se llama `pgsql_trivia`. Configura también Redis y comprueba la conectividad antes de ejecutar la suite.

```bash
cd backend
php artisan migrate
```

Las migraciones que usan Trivia pueden requerir PostgreSQL disponible. Ejecuta seeders solo cuando el entorno y los datos sean apropiados.

## Variables importantes

| Variable | Propósito |
|---|---|
| `APP_ENV`, `APP_KEY`, `APP_URL` | Runtime Laravel |
| `FRONTEND_URL`, `VITE_API_URL` | Origen y URL del cliente |
| `DB_*` | Base principal |
| `DB_TRIVIA_*` | Base Trivia PostgreSQL |
| `REDIS_*` | Cache/colas/locks |
| `SESSION_*`, `SANCTUM_STATEFUL_DOMAINS` | Cookies y dominios SPA |
| `REVERB_*`, `VITE_REVERB_*` | WebSockets |
| `TURNSTILE_*` | Cloudflare Turnstile |
| `PAYMENTS_TRON_*` | Integración USDT-TRC20 |

Consulta `backend/.env.example` para la lista completa. Nunca copies claves privadas/seed a variables públicas `VITE_*`.

## Procesos de desarrollo

Abre terminales separadas desde `backend/`:

```bash
php artisan serve
pnpm run dev
php artisan reverb:start
php artisan queue:work
```

Para probar jobs de colas nombradas, inicia workers que consuman esas colas o configura el driver sync solo si el test no pretende verificar ejecución asíncrona real.

## Calidad y pruebas

```bash
cd backend
php artisan test
./vendor/bin/pint --test
pnpm run build
pnpm run test
pnpm run typecheck
```

Scripts en `backend/package.json`: `dev`, `build`, `preview`, `typecheck`, `lint`, `format` y `test`. El script `lint` actual usa `--fix`; para validar sin modificar archivos, ejecuta ESLint directamente sin esa opción.

## Pruebas de integración

- PHPUnit requiere los servicios definidos por `backend/phpunit.xml` o variables equivalentes.
- CI crea MySQL, PostgreSQL y Redis y ejecuta la suite completa.
- Si Trivia se bloquea conectando a PostgreSQL, verifica disponibilidad, credenciales, esquema y timeout.
- Las pruebas de concurrencia deben ejecutarse contra el motor real cuando dependen de `lockForUpdate()`; SQLite no reproduce el mismo locking.
- No ejecutes pruebas destructivas contra bases compartidas o producción.

## Windows / PowerShell

Ejecuta comandos separados si la versión de PowerShell no soporta `&&`:

```powershell
Set-Location backend
php artisan test
pnpm run build
```

## Contribución

Trabaja en una rama descriptiva, mantén el cambio enfocado, añade pruebas de regresión y abre PR contra `master`. No marques una corrección como validada si el job correspondiente no se ejecutó. Revisa todos los checks antes de fusionar.