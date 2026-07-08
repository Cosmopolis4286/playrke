# Registro de cambios — PlayRKE Backend

---

## [Refactor] 2026-07-07 — Migración del dominio Bingo a `App\Domains\Games\Bingo\*`

### Contexto
El dominio Bingo (133 archivos PHP) está fragmentado a mitad de camino: solo los
Models (22 archivos + 1 ValueObject) viven en `App\Domains\Games\Bingo\Models\*`;
el resto (Services, Repositories, Contracts, DTOs, Events, Jobs, Policies,
Exceptions — 111 archivos) sigue disperso en las carpetas clásicas por tipo
(`app/Services/Games/Bingo`, `app/Repositories/Games/Bingo`, etc.). El dominio
Trivia ya demostró el patrón consolidado completo:
`App\Domains\Games\Trivia\{Actions,Contracts,DTOs,Events,Models,Repositories,Services}`,
con un `TriviaServiceProvider` propio y Controllers/Requests/Resources
deliberadamente fuera de `Domains`. Esta migración replica ese patrón para Bingo,
por fases, para reducir la deriva de namespaces que ya causó bugs reales
(`App\Domains\Games\Bingo\Models\Room\BingoRoom.php` y
`BingoCreatorSaleRepository.php` tenían imports rotos apuntando a namespaces de
Models que ya no existían).

### Plan completo (9 fases)
Ver `C:\Users\Eisker\.claude\plans\jolly-chasing-twilight.md` para el detalle
completo de cada fase, decisiones de diseño y comandos de verificación. Resumen:

| Fase | Contenido | Riesgo | Estado |
|------|-----------|--------|--------|
| 0 | CHANGELOG + fix de imports huérfanos preexistentes | mínimo | ✅ |
| 1 | Exceptions (3 archivos) → Domains | mínimo | ✅ |
| 2 | DTOs (15 archivos) → Domains | bajo | pendiente |
| 3 | Events (13 archivos) → Domains | medio (broadcasting) | pendiente |
| 4 | Jobs (1 archivo) → Domains | bajo | pendiente |
| 5 | Contracts (11 interfaces) → Domains | bajo | pendiente |
| 6 | Repositories (28 archivos) + `BingoServiceProvider` | alto (DI) | pendiente |
| 7 | Services (26 archivos) + completar Provider | alto (DI) | pendiente |
| 8 | Policies (2 archivos) + `AuthServiceProvider` | medio | pendiente |
| 9 | Cierre: limpiar carpetas vacías, `phpstan.neon`, tests completos | — | pendiente |

**Decisiones clave**: se crea `BingoServiceProvider` explícito (Fase 6) en vez de
extender el auto-registro por reflection de `RepositoryServiceProvider`, que hoy
escanea solo `app/Repositories/**` y dejaría de encontrar los repos de Bingo si se
movieran sin este cambio. Controllers/Requests/Resources no se migran (mismo
criterio que Trivia). La duplicación `*RepositoryInterface` vs `*RepositoryContract`
se preserva tal cual, documentada como deuda técnica a resolver aparte.

### Fase 0 — Fix de imports huérfanos (aislado del movimiento de carpetas)
Corregidos los `use` que apuntaban a namespaces de Models ya migrados:
- `App\Domains\Games\Bingo\Models\Room\BingoRoom.php`: imports `App\Models\Games\Bingo\*`
  corregidos a `App\Domains\Games\Bingo\Models\*` (8 imports); `cardPrizes()` ahora usa
  el import corto en vez de FQCN inline.
- `App\Repositories\Games\Bingo\Creators\BingoCreatorSaleRepository.php` y su contrato:
  namespace declarado no coincidía con su ruta real PSR-4, e importaban
  `App\Models\Bingo\Creators\BingoCreatorSale` (no existe) y
  `App\Models\Games\Bingo\Room\Config\BingoRoomConfig` (no existe). Confirmado sin
  ningún caller real en el proyecto — se corrigió el namespace/imports para dejarlo
  consistente, sin agregar binding en Provider (sigue sin usarse).

### Fase 1 — Exceptions → Domains
`app/Exceptions/Domain/Games/Bingo/**` movido a
`app/Domains/Games/Bingo/Exceptions/**`. Sin bindings de contenedor afectados.
Actualizados los `use` en `BingoRoomCrudService`, `BingoRoomAutomaticCrudService`
y `BingoPatternCrudService`.

---

## [Dev] 2026-04-20 — Infraestructura Docker para desarrollo

### Nuevo: `.env.dev`
- Archivo de variables de entorno exclusivo para el entorno de desarrollo Docker.
- Separado del `.env` general para no mezclar configuraciones de Laragon/local con las del compose.
- Cambios respecto al `.env` base:
  - `APP_URL` apunta a `http://playrke.localhost` (dominio Traefik).
  - `SESSION_DRIVER` y `CACHE_STORE` en `redis` (no `database`), aprovechando el contenedor Redis.
  - `QUEUE_CONNECTION=redis`.
  - `MAIL_MAILER=smtp` hacia Mailpit (`mailpit:1025`) para capturar correos en local.
  - `REVERB_HOST=ws.playrke.localhost`, `REVERB_PORT=6001` (proxy Traefik).
  - `DB_ROOT_PASSWORD` incluida para el healthcheck de MySQL.
  - `REDIS_CLIENT=phpredis` (extensión nativa, más rápida que predis).

### Modificado: `docker-compose.dev.yml`
- **Nginx eliminado** — reemplazado por Traefik en todos los entornos.
- **`env_file: .env.dev`** añadido a todos los servicios Laravel (php, reverb, queue, scheduler).  
  Variables hardcodeadas en `environment:` eliminadas; ahora todo viene del archivo.
- **Traefik**:
  - Entrypoint `ws` en puerto `6001` para WebSocket (antes Reverb y Traefik compartían el 8080, causando conflicto).
  - Panel de administración expuesto en `localhost:8888`.
  - `log.level=WARN` para reducir ruido en consola.
- **MySQL**:
  - Variables mapeadas desde `.env.dev` vía `${DB_*}`.
  - Flags de rendimiento para dev: `--skip-log-bin`, `--innodb-flush-log-at-trx-commit=2`, `--innodb-flush-method=O_DIRECT` (escrituras ~3× más rápidas).
  - `healthcheck` con `start_period=30s` para evitar arranques prematuros.
- **Redis**:
  - `--appendonly no` + `--save ""`: modo puramente en memoria (no necesita persistencia en dev, arranca al instante).
  - `--maxmemory 256mb` + `--maxmemory-policy allkeys-lru`.
  - Healthcheck cada 5 s.
- **PHP-FPM**:
  - `extra_hosts: host.docker.internal:host-gateway` para que Xdebug alcance el IDE en el host Windows.
  - Volúmenes `vendor_cache` y `bootstrap_cache` nombrados para aislar dependencias del bind mount.
- **Reverb**:
  - Traefik enruta `ws.playrke.localhost:6001` → contenedor `reverb:8080` (sin conflicto).
  - Flag `--debug` para logs de conexión WebSocket en consola.
- **Queue**:
  - `--sleep=1` (antes 3 s) para respuesta más ágil en dev.
  - `restart: unless-stopped` para que recargue código automáticamente tras cambios.
- **Mailpit** añadido: captura SMTP en `mailpit:1025`, interfaz web en `localhost:8025`.
- **Volúmenes** nuevos: `vendor_cache`, `bootstrap_cache`, `redis_data`.

### Modificado: `Dockerfile` — stage `development`
- **OPcache reconfigurado para dev**:
  - `validate_timestamps=1` + `revalidate_freq=0`: PHP detecta cambios de archivo en cada request sin reiniciar el contenedor.
  - Archivo separado `opcache-dev.ini` (no pisa la config de producción).
- **Xdebug instalado**:
  - Modo `debug,coverage`.
  - Cliente apunta a `host.docker.internal:9003` (puerto estándar VS Code / PhpStorm).
  - `log_level=0` para no contaminar los logs de PHP-FPM.
- **PHP-FPM pool reducido**: `max_children=10` (antes 50); `start_servers=2`. Sin sentido mantener 50 workers en dev.
- **`composer install` con deps de desarrollo** (`--dev` incluido): antes usaba el stage `dependencies` que instala `--no-dev`, rompiendo herramientas como PHPUnit, Laravel Telescope, etc.
- **CMD cambiado a `php-fpm`**: el stage development arrancaba `php artisan serve` en lugar de FPM, lo que era incompatible con Traefik (que espera FPM en el puerto 9000).

---

### Cómo arrancar el entorno de desarrollo

```bash
cd backend
docker compose -f docker-compose.dev.yml --env-file .env.dev up -d --build
```

### Accesos locales

| Servicio         | URL                                   |
|------------------|---------------------------------------|
| Aplicación       | http://playrke.localhost              |
| WebSocket Reverb | ws://ws.playrke.localhost:6001        |
| Panel Traefik    | http://localhost:8888                 |
| Mailpit (correos)| http://localhost:8025                 |
| MySQL            | localhost:3306                        |
| Redis            | localhost:6379                        |
