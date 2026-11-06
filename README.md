# PlayRKE

PlayRKE es una plataforma web de juegos en línea. Sus dominios de juego principales son **Bingo** y **Trivia**, con servicios compartidos de usuarios, roles, wallet, pagos/premios, marketplace y tiempo real.

> La documentación técnica mantenida está en [`docs/`](docs/README.md). La presencia de una funcionalidad en el repositorio no implica que esté habilitada para producción ni que esté autorizada para procesar dinero real. Consulta [preparación para lanzamiento](docs/RELEASE_READINESS.md).

## Arquitectura y frontend

El repositorio contiene dos implementaciones de interfaz que no deben confundirse:

| Parte | Tecnología | Estado en el repositorio |
|---|---|---|
| Backend y UI integrada | Laravel 11, Inertia.js 2, Vue 3, TypeScript, Vite 7 | Aplicación principal activa; UI en `backend/resources/js` |
| Frontend separado | Nuxt 4, Vue 3, TypeScript, Tailwind, Pinia | Código presente en `frontend/`, pero actualmente excluido del workspace pnpm y fuera del flujo principal documentado |
| Datos principales | MySQL | Usuarios, roles, Bingo, wallet y datos de plataforma |
| Datos Trivia | PostgreSQL (`pgsql_trivia`) | Persistencia específica del dominio Trivia |
| Estado efímero / colas | Redis | Cache, colas, locks y coordinación |
| Tiempo real | Laravel Reverb + Echo | Broadcasting y suscripciones |
| CI | GitHub Actions | `.github/workflows/ci.yml` |
| Producción | Docker Compose | Stack productivo en `backend/docker-compose.prod.yml` |

La raíz `package.json` y `pnpm-workspace.yaml` incluyen únicamente `backend` en el workspace pnpm. Por tanto, **`pnpm install` desde la raíz no instala ni compila el frontend Nuxt**. Este paquete se ejecuta de forma aislada desde `frontend/` (o mediante los scripts `dev:frontend` y `build:frontend` de la raíz); su integración con autenticación, CI y despliegue debe confirmarse antes de tratarlo como frontend productivo.

## Dominios funcionales

- **Bingo:** salas, cartones, rondas/sorteos, patrones, jugadores registrados y compradores externos, premios, reembolsos, historial y reportes.
- **Trivia:** salas multiusuario, lobby, participantes, preguntas, respuestas, puntuaciones, power-ups y resultados.
- **Wallet y pagos:** movimientos, recargas/retiros, pagos USDT-TRC20, trazabilidad y conciliación.
- **Marketplace:** inventario, escrow y flujos relacionados; algunas integraciones externas pueden permanecer en modo stub o sujetas a validación.
- **Plataforma:** usuarios, roles/permisos, notificaciones, parámetros, KYC y servicios compartidos.

## Estructura

```text
.
├── .github/workflows/ci.yml       # CI GitHub Actions
├── backend/
│   ├── app/Domains/               # Dominios de negocio
│   ├── app/Http/                  # HTTP, middleware y requests
│   ├── database/                  # Migraciones, factories y seeders
│   ├── resources/js/              # Vue + Inertia (UI principal)
│   ├── routes/                    # Rutas Laravel
│   ├── tests/                     # PHPUnit
│   ├── docker-compose.prod.yml    # Stack de producción
│   ├── Dockerfile.prod
│   └── README-DOCKER.md
├── frontend/                      # Aplicación Nuxt 4 separada
├── docs/                          # Documentación técnica mantenida
├── progreso/                      # Planes y bitácoras de producto/arquitectura
├── docker-compose.dev.yml         # Stack local de desarrollo
├── package.json
└── pnpm-workspace.yaml
```

## Requisitos

Para el backend y la UI principal:
- PHP 8.4 recomendado; Composer 2.
- Node.js 20.19 y pnpm 10.25 (versiones de CI).
- MySQL 8, PostgreSQL 16 y Redis 7 para el stack completo.
- Extensiones PHP exigidas por Composer y por los dominios habilitados, incluidas PDO MySQL/PostgreSQL, GMP y Redis.

Para trabajar con el frontend Nuxt separado, consulta `frontend/package.json` y su [README](frontend/README.md); sus dependencias no forman parte del workspace pnpm activo.

## Instalación local: backend principal

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

Configura `backend/.env` con credenciales locales. Crea la base MySQL indicada por `DB_DATABASE`, la base PostgreSQL indicada por `DB_TRIVIA_DATABASE` y configura Redis antes de migrar:

```bash
php artisan migrate
```

La conexión de Trivia se llama `pgsql_trivia` y usa variables `DB_TRIVIA_*`. Consulta [Desarrollo y pruebas](docs/DEVELOPMENT.md) para los pasos completos.

## Servicios de desarrollo

Desde `backend/`, en terminales separadas:

```bash
php artisan serve
pnpm run dev
php artisan reverb:start
php artisan queue:work
```

Los puertos se configuran en el entorno. El Compose local de la raíz es `docker-compose.dev.yml`.

## Pruebas y calidad

Desde `backend/`:

```bash
php artisan test
./vendor/bin/pint --test
pnpm run build
pnpm run test
pnpm run typecheck
```

La CI valida además traducciones de errores de dominio, lint de archivos modificados y construcción Docker condicional. Consulta [CI](docs/CI.md).

## Autenticación y seguridad

El login de la aplicación principal usa el guard web de Laravel y sesiones; no debe describirse como JWT. Las rutas de autenticación están en `backend/routes/auth.php`; la autorización combina middleware, roles/permisos y Policies. Consulta [Autenticación y autorización](docs/AUTHENTICATION.md).

No subas archivos `.env`, secretos, tokens, seeds ni claves privadas. Nunca expongas secretos mediante variables `VITE_*` o configuración pública de Nuxt.

## Despliegue

El stack productivo se encuentra exclusivamente en `backend/docker-compose.prod.yml` y se opera con `backend/deploy.sh`, usando `backend/.env.prod` fuera del control de versiones. Consulta [Despliegue Docker](backend/README-DOCKER.md). El archivo raíz `docker-compose.dev.yml` es para desarrollo, no para producción.

## Documentación

- [Índice de documentación](docs/README.md)
- [Arquitectura](docs/ARCHITECTURE.md)
- [Desarrollo, instalación y pruebas](docs/DEVELOPMENT.md)
- [Autenticación y autorización](docs/AUTHENTICATION.md)
- [Pagos, wallets y premios](docs/PAYMENTS_AND_PRIZES.md)
- [CI y validaciones](docs/CI.md)
- [Preparación para lanzamiento](docs/RELEASE_READINESS.md)
- [Contribución](docs/CONTRIBUTING.md)
- [Despliegue Docker](backend/README-DOCKER.md)
- [Dominio Bingo](backend/app/Domains/Games/Bingo/README.md)
- [Dominio Trivia](backend/app/Domains/Games/Trivia/README.md)

Los documentos de `progreso/` y auditorías de raíz conservan decisiones e historial de trabajo; no sustituyen la documentación técnica canónica ni necesariamente reflejan el estado actual de cada tarea.

## CI y colaboración

GitHub Actions es el proveedor CI mantenido. No reintroducir CircleCI ni duplicar pipelines sin una decisión explícita. Trabaja en ramas descriptivas y abre PR contra `master`. No fusionar con checks requeridos fallidos, pendientes o que no se ejecutaron.

## Licencia

Este README no declara una licencia de distribución. Consulta al propietario antes de reutilizar o redistribuir el código.
