# PlayRKE

Plataforma web de juegos en línea centrada en **Bingo** y **Trivia**, con servicios compartidos de usuarios, roles, wallet, pagos/premios, marketplace y tiempo real.

> La documentación técnica mantenida está centralizada en [`docs/`](docs/README.md). Que exista código o una integración configurada no significa que esté habilitada para producción ni autorizada para procesar dinero real.

## Stack y estado

| Área | Tecnología | Ubicación / estado |
|---|---|---|
| Backend y UI principal | Laravel 11, Inertia.js 2, Vue 3, TypeScript, Vite 7 | `backend/` — aplicación principal |
| Frontend alternativo | Nuxt 4, Vue 3, TypeScript, Tailwind, Pinia | `frontend/` — aislado; no forma parte del workspace ni CI principal |
| Persistencia | MySQL + PostgreSQL (`pgsql_trivia`) | MySQL para core; PostgreSQL para Trivia |
| Estado efímero y colas | Redis | Cache, locks, colas y estado de juego |
| Tiempo real | Laravel Reverb + Echo | Broadcasting |
| CI | GitHub Actions | `.github/workflows/ci.yml` |
| Producción | Docker Compose | `backend/docker-compose.prod.yml` |

El workspace pnpm incluye únicamente `backend` (ver `pnpm-workspace.yaml`). El frontend Nuxt se instala y ejecuta de forma aislada desde `frontend/`; actualmente no está incluido en el pipeline principal.

## Inicio rápido

Requisitos del stack principal: PHP 8.4, Composer 2, Node.js 20.19, pnpm 10.25, MySQL 8, PostgreSQL 16 y Redis 7.

```bash
git clone https://github.com/Cosmopolis4286/playrke.git
cd playrke
pnpm install
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Configura `backend/.env` con bases MySQL y PostgreSQL (`DB_TRIVIA_*`) y Redis. Después:

```bash
php artisan migrate
php artisan serve
pnpm run dev
php artisan reverb:start
php artisan queue:work
```

## Pruebas principales

Desde `backend/`:

```bash
php artisan test
./vendor/bin/pint --test
pnpm run build
pnpm run test
pnpm run typecheck
```

## Documentación

- [Índice y mapa documental](docs/README.md)
- [Arquitectura](docs/architecture/OVERVIEW.md)
- [Desarrollo e instalación](docs/guides/DEVELOPMENT.md)
- [Autenticación y autorización](docs/security/AUTHENTICATION.md)
- [Pagos, wallets y premios](docs/payments/PAYMENTS_AND_PRIZES.md)
- [CI](docs/guides/CI.md)
- [Preparación para lanzamiento](docs/guides/RELEASE_READINESS.md)
- [Despliegue Docker](docs/operations/DEPLOYMENT_DOCKER.md)

## Seguridad y operación

No subas archivos `.env`, tokens, semillas, claves privadas ni credenciales. La autenticación principal usa sesiones web de Laravel; no asumir JWT. No procesar dinero real hasta completar los controles técnicos, operativos, antifraude y legales descritos en [preparación para lanzamiento](docs/guides/RELEASE_READINESS.md).

GitHub Actions es el único CI mantenido. Trabaja en ramas enfocadas y abre PR contra `master`; no fusionar con checks requeridos fallidos, pendientes o no ejecutados.

## Licencia

Este README no declara una licencia de distribución. Consulta al propietario antes de reutilizar o redistribuir el código.
