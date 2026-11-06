# PlayRKE

Plataforma web de juegos en línea con dos dominios principales: **Bingo** y **Trivia**. El backend concentra autenticación, autorización, reglas de negocio, operaciones financieras y tiempo real; la interfaz web está construida con Laravel, Inertia y Vue.

> **Estado:** documentación de la rama principal actualizada el 6 de noviembre de 2026. La presencia de código y pruebas no equivale por sí sola a autorización para operar con dinero real. Consulta [Estado y límites de lanzamiento](docs/RELEASE_READINESS.md).

## Stack actual

| Área | Tecnología |
|---|---|
| Backend | PHP 8.4 en CI/producción, Laravel 11 |
| UI | Inertia.js 2, Vue 3, TypeScript |
| Build y frontend tests | Vite 7, pnpm 10, Vitest |
| Persistencia principal | MySQL 8 |
| Persistencia Trivia | PostgreSQL (`pgsql_trivia`) |
| Cache, colas y presencia | Redis |
| Tiempo real | Laravel Reverb + Laravel Echo |
| Autenticación | Guard web de Laravel, sesiones y protección CSRF |
| Autorización | Spatie Laravel Permission, middleware y Policies |
| Producción | Docker Compose, PHP-FPM, Nginx y Traefik |
| CI | GitHub Actions (`.github/workflows/ci.yml`) |

**Nota sobre el workspace:** el repositorio mantiene un paquete pnpm en `backend/`; el workspace declara actualmente solo ese paquete. No asumir que existe un frontend Nuxt separado: el código activo de interfaz está en `backend/resources/js`.

## Dominios funcionales

- **Bingo:** administración y operación de salas, cartones, rondas/sorteos, patrones, jugadores registrados y compradores externos, premios, reembolsos, historial y reportes.
- **Trivia:** salas multiusuario, lobby, participación, respuestas, puntuaciones, power-ups, preguntas, resultados y reportes. Su persistencia específica usa PostgreSQL y Redis.
- **Wallet y pagos:** movimientos, recargas/retiros, compras, pagos USDT-TRC20 y trazabilidad/conciliación.
- **Plataforma:** usuarios, roles/permisos, notificaciones, parámetros, KYC, marketplace y servicios comunes.

## Estructura

```text
.
├── .github/workflows/ci.yml   # CI principal (GitHub Actions)
├── backend/
│   ├── app/Domains/            # Dominios de negocio
│   ├── app/Http/               # Controladores, middleware y requests
│   ├── app/Models/
│   ├── app/Providers/
│   ├── bootstrap/              # Arranque y middleware Laravel
│   ├── config/                 # Incluye conexiones MySQL/PostgreSQL
│   ├── database/               # Migraciones, factories y seeders
│   ├── resources/js/           # Vue, Inertia y TypeScript
│   ├── routes/                 # Índice web y módulos por dominio
│   ├── tests/                  # PHPUnit
│   ├── docker-compose.prod.yml # Stack de producción
│   ├── Dockerfile.prod
│   ├── deploy.sh
│   ├── composer.json
│   └── package.json
├── docs/                       # Documentación vigente
├── package.json
└── pnpm-workspace.yaml
```

## Requisitos

- PHP 8.4 recomendado (PHP `^8.2` declarado por Composer), Composer 2.
- Extensiones PHP requeridas por Composer y el proyecto; entre ellas PDO MySQL/PostgreSQL y GMP/Redis en los entornos que ejecutan esos dominios.
- Node.js 20.19 y pnpm 10.25 (alineados con CI).
- MySQL 8, PostgreSQL 16 y Redis 7 para el stack completo y las pruebas de integración.

## Instalación local

Clona el repositorio, instala dependencias y configura `backend/.env` con bases de datos y servicios locales:

```bash
git clone https://github.com/Cosmopolis4286/playrke.git
cd playrke
pnpm install
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Crea previamente la base MySQL indicada por `DB_DATABASE` y la base PostgreSQL indicada por `DB_TRIVIA_DATABASE`. La conexión de Trivia se llama `pgsql_trivia` y usa variables `DB_TRIVIA_*`. Consulta [Desarrollo y pruebas](docs/DEVELOPMENT.md) para la preparación completa.

## Servicios de desarrollo

Desde `backend/`, en terminales separadas:

```bash
php artisan serve
pnpm run dev
php artisan reverb:start
php artisan queue:work
```

Los puertos se configuran en el entorno. No asumas que los puertos de documentación histórica siguen vigentes.

## Pruebas y calidad

```bash
cd backend
php artisan test
./vendor/bin/pint --test
pnpm run build
pnpm run test
pnpm run typecheck
```

La CI también valida traducciones de errores de dominio, lint de PHP/frontend modificado y construcción Docker condicional. Consulta [CI](docs/CI.md).

## Autenticación y autorización

El login usa el guard web de Laravel y sesiones; no es un flujo JWT ni una API Bearer-token de Sanctum. Las rutas de autenticación se definen en `backend/routes/auth.php`; las rutas de negocio se cargan desde `backend/routes/web.php`. La autorización combina roles/permisos y Policies. Consulta [Autenticación y autorización](docs/AUTHENTICATION.md).

## Pagos, premios y operación financiera

Los flujos financieros requieren idempotencia, transacciones, locks, auditoría y conciliación. Los premios de compradores externos pueden quedar retenidos para revisión manual: no se debe asumir que un premio retenido o disputado fue pagado. Consulta [Pagos y premios](docs/PAYMENTS_AND_PRIZES.md) y [Estado de lanzamiento](docs/RELEASE_READINESS.md).

## Despliegue

El stack productivo está en `backend/docker-compose.prod.yml` y se opera con `backend/deploy.sh`, usando `backend/.env.prod` fuera del control de versiones. No uses el Compose de desarrollo como stack productivo. Consulta [Despliegue Docker](backend/README-DOCKER.md), valida backups y prueba primero en staging.

## Documentación

- [Índice](docs/README.md)
- [Arquitectura](docs/ARCHITECTURE.md)
- [Desarrollo, instalación y pruebas](docs/DEVELOPMENT.md)
- [Autenticación y autorización](docs/AUTHENTICATION.md)
- [Pagos, wallets y premios](docs/PAYMENTS_AND_PRIZES.md)
- [CI y validaciones](docs/CI.md)
- [Preparación para lanzamiento](docs/RELEASE_READINESS.md)
- [Despliegue Docker](backend/README-DOCKER.md)

## CI y colaboración

GitHub Actions es el proveedor CI vigente. No reintroducir CircleCI ni duplicar pipelines sin decisión explícita. Los cambios deben llegar mediante Pull Request contra `master` y pasar los checks requeridos. No marcar una corrección como validada si el job correspondiente no se ejecutó.

## Seguridad

No subas archivos `.env`, `.env.prod`, claves privadas, semillas, tokens ni credenciales. Nunca expongas secretos en variables `VITE_*`. Antes de procesar fondos reales, completa controles operativos, legales, antifraude y de conciliación.

## Licencia

Este README no declara una licencia de distribución. Consulta al propietario antes de reutilizar o redistribuir el código.
