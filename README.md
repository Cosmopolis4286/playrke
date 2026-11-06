# PlayRKE

Plataforma web de juegos y gestión de partidas que reúne **Bingo** y **Trivia**, con administración de salas, participantes, pagos, premios y operaciones relacionadas.

> **Estado:** proyecto en desarrollo. La existencia de código, rutas o pruebas no equivale por sí sola a una certificación de disponibilidad para producción. Antes de habilitar dinero real deben completarse las validaciones funcionales, financieras, operativas y legales descritas en la documentación.

## Stack actual

| Área | Tecnología |
|---|---|
| Backend y aplicación web | PHP 8.2+ / Laravel 11 |
| UI | Vue 3 + Inertia.js 2 + TypeScript |
| Assets | Vite 7 |
| Base de datos principal | MySQL |
| Persistencia de Trivia | PostgreSQL, conexión Laravel `pgsql_trivia` |
| Cache, colas y datos efímeros | Redis |
| Tiempo real | Laravel Reverb + Laravel Echo |
| Pruebas backend | PHPUnit 11 |
| Pruebas frontend | Vitest |
| Calidad PHP | Laravel Pint |
| CI | GitHub Actions |
| Runtime de producción | Docker, PHP-FPM, Nginx y Traefik |

El frontend activo vive dentro de `backend/resources/js`. No hay un workspace frontend Nuxt independiente en el workspace pnpm actual: `pnpm-workspace.yaml` incluye únicamente `backend`.

## Funcionalidades principales

- **Bingo:** administración de salas, cartones, rondas, sorteos, patrones, historial, estadísticas y eventos en tiempo real.
- **Trivia:** salas multijugador, preguntas, respuestas, puntuaciones, power-ups, leaderboard y premios.
- **Cuentas y permisos:** sesiones Laravel, roles y permisos, verificación de correo y recuperación de contraseña.
- **Operaciones financieras:** wallets, pagos, retiros, comisiones, reembolsos y conciliación.
- **Compradores externos de Bingo:** registro de ventas y titularidad separada del vendedor; los premios externos que requieren verificación no deben confundirse con premios abonables automáticamente.
- **Pagos USDT-TRC20:** módulo de pagos con proveedor blockchain configurable. El proveedor mock no está permitido en producción.

Consulta [Arquitectura](docs/arquitectura.md), [Flujos y límites de seguridad](docs/seguridad-y-flujos-financieros.md) y [Estado y pendientes](docs/estado-y-pendientes.md) para conocer los límites de cada módulo.

## Estructura del repositorio

```text
playrke/
├── .github/workflows/ci.yml   # Pipeline principal GitHub Actions
├── backend/
│   ├── app/                   # Dominios, servicios, modelos y controladores
│   ├── bootstrap/             # Arranque y middleware Laravel
│   ├── config/                # Configuración
│   ├── database/              # Migraciones, seeders y factories
│   ├── docker/                # Configuración Nginx
│   ├── resources/js/          # Vue, Inertia y TypeScript
│   ├── routes/                # Rutas web, auth y módulos de dominio
│   ├── tests/                 # PHPUnit y Vitest
│   ├── Dockerfile.prod
│   ├── docker-compose.prod.yml
│   ├── deploy.sh
│   ├── composer.json
│   └── package.json
├── docs/                      # Documentación vigente del proyecto
├── package.json               # Scripts y configuración raíz
├── pnpm-lock.yaml
└── pnpm-workspace.yaml
```

## Requisitos locales

- PHP 8.4 recomendado (Composer declara compatibilidad desde PHP 8.2).
- Composer 2.
- Extensiones PHP requeridas por Composer y por los dominios: entre ellas `gmp`, `pdo_mysql`, `pdo_pgsql`, `redis` y las extensiones indicadas por `composer check-platform-reqs`.
- Node.js 20.19 y pnpm 10.25 (versiones usadas por CI).
- MySQL 8, PostgreSQL 16 y Redis 7 para reproducir el entorno de CI y los dominios que dependen de cada servicio.

## Puesta en marcha local

Desde la raíz del repositorio:

```bash
pnpm install --frozen-lockfile
composer install --working-dir=backend
```

Configura el backend:

```bash
cp backend/.env.example backend/.env
php backend/artisan key:generate
```

En Windows PowerShell, usa:

```powershell
Copy-Item backend/.env.example backend/.env
php backend/artisan key:generate
```

Crea las bases de datos y usuarios de MySQL y PostgreSQL. Configura en `backend/.env` las variables `DB_*` para MySQL y `DB_TRIVIA_*` para PostgreSQL. La conexión PostgreSQL de Trivia se llama `pgsql_trivia` en Laravel. Configura Redis según el entorno local. No reutilices credenciales de producción.

Ejecuta las migraciones y levanta los procesos en terminales separadas:

```bash
cd backend
php artisan migrate
php artisan serve
```

```bash
# Desde la raíz: assets Vite
pnpm --dir backend run dev
```

```bash
cd backend
php artisan reverb:start
```

```bash
cd backend
php artisan queue:work
```

Para tareas programadas en desarrollo:

```bash
cd backend
php artisan schedule:work
```

Los comandos de instalación, variables, bases de datos, pruebas y resolución de problemas están ampliados en [Desarrollo local](docs/desarrollo-local.md).

## Comprobaciones y pruebas

```bash
# Suite PHP
cd backend
php artisan test

# Traducciones de errores de dominio
php artisan check:domain-error-translations

# Frontend: tests, build y tipado (desde la raíz)
pnpm --dir backend run test
pnpm --dir backend run build
pnpm --dir backend run typecheck
```

Para ejecutar Pint localmente:

```bash
cd backend
./vendor/bin/pint
```

En Windows puede usarse `vendor/bin/pint` desde `backend`. Consulta [Desarrollo local](docs/desarrollo-local.md) para los comandos completos. El pipeline oficial y sus servicios están descritos en [CI y despliegue](docs/ci-y-despliegue.md).

## CI y despliegue

GitHub Actions es el proveedor de CI del repositorio. El workflow principal está en `.github/workflows/ci.yml` y contiene validaciones de backend, frontend y construcción Docker de producción condicionada a cambios del stack de despliegue.

El despliegue productivo se define en `backend/docker-compose.prod.yml` y se ejecuta mediante `backend/deploy.sh`. La guía vigente es [backend/README-DOCKER.md](backend/README-DOCKER.md). No uses archivos Compose de desarrollo como sustituto del stack productivo.

## Seguridad y dinero real

- El login web usa el guard de sesión `web`; la aplicación regenera la sesión tras autenticar y la invalida al cerrar sesión.
- Las rutas de registro, login y recuperación tienen límites de frecuencia.
- Las acciones sensibles deben aplicar autorización por rol, permiso y recurso; estar autenticado no concede acceso global.
- Las operaciones de wallet y premios requieren transacciones, bloqueos, idempotencia y trazabilidad.
- Los premios de compradores externos pueden quedar retenidos para verificación/conciliación; no deben acreditarse al vendedor por defecto.
- No publiques claves, tokens, semillas privadas, XPUB de producción ni archivos `.env`.
- Los cambios financieros no deben desplegarse ni habilitarse para usuarios reales sin pruebas de regresión y conciliación.

Lee [Seguridad y flujos financieros](docs/seguridad-y-flujos-financieros.md) y [SECURITY.md](SECURITY.md).

## Documentación

- [Arquitectura y módulos](docs/arquitectura.md)
- [Desarrollo local y pruebas](docs/desarrollo-local.md)
- [CI y despliegue](docs/ci-y-despliegue.md)
- [Seguridad y flujos financieros](docs/seguridad-y-flujos-financieros.md)
- [Estado y pendientes de v1](docs/estado-y-pendientes.md)
- [Guía para contribuir](CONTRIBUTING.md)
- [Reporte responsable de vulnerabilidades](SECURITY.md)
- [Despliegue Docker](backend/README-DOCKER.md)

## Contribuir

Lee [CONTRIBUTING.md](CONTRIBUTING.md) antes de abrir una rama o Pull Request. Mantén cada cambio acotado al dominio correspondiente, añade pruebas de regresión y no mezcles refactors masivos con cambios financieros o de seguridad.

## Licencia

El repositorio no declara una licencia de distribución de código abierto en su README actual. Consulta al propietario del proyecto antes de reutilizar o redistribuir el código.
