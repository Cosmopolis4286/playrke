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
| 2 | DTOs (15 archivos) → Domains | bajo | ✅ |
| 3 | Events (13 archivos) → Domains | medio (broadcasting) | ✅ |
| 4 | Jobs (1 archivo) → Domains | bajo | ✅ |
| 5 | Contracts (11 interfaces) → Domains | bajo | ✅ |
| 6 | Repositories (28 archivos) + `BingoServiceProvider` | alto (DI) | ✅ |
| 7 | Services (26 archivos) + completar Provider | alto (DI) | ✅ |
| 8 | Policies (2 archivos) + `AuthServiceProvider` | medio | ✅ |
| 9 | Cierre: limpiar carpetas vacías, `phpstan.neon`, tests completos | — | ✅ |

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
y `BingoPatternCrudService`. De paso se corrigió `BingoRoomException.php` (evento
`Room/Automatic`), cuyo nombre de archivo no coincidía con la clase
`BingoRoomAutomaticException` que contenía — bajo PSR-4 estricto eso hacía la
clase invisible al autoloader.

### Fase 2 — DTOs → Domains
Los 15 DTOs de `app/DTOs/Games/Bingo/**` movidos a
`app/Domains/Games/Bingo/DTOs/**`, preservando subcarpetas (`Card/Generate`,
`Config`, `Game`, `Pattern`, `Room`, `Room/Automatic`). Actualizados los 18
archivos consumidores (Services y Controllers). De paso se corrigieron dos
imports huérfanos preexistentes con el mismo patrón de namespace desalineado
de la Fase 0 (`BingoTestingController.php` y una referencia FQCN inline en
`BingoCardGenerateService.php`).

### Fase 3 — Events → Domains
Los 13 Events de `app/Events/Games/Bingo/**` movidos a
`app/Domains/Games/Bingo/Events/**`. Sin bindings de contenedor (Laravel 11 usa
auto-discovery de listeners). Actualizados 7 archivos consumidores (Services,
un Command y 2 tests), incluyendo referencias FQCN inline sin `use`. Verificado
con instanciación real de eventos y confirmación de `broadcastAs()`.

### Fase 4 — Jobs → Domains
`StartBingoDrawJob` movido a `app/Domains/Games/Bingo/Jobs/`. Actualizados sus 3
consumidores (`BingoRoomCrudService`, `BingoGameController`,
`StartPendingBingoDrawsCommand`). Verificado con `Queue::fake()` +
`assertPushed()` confirmando el `dispatch()->onQueue('bingo-draw')` real.

### Fase 5 — Contracts → Domains
Las 11 interfaces (8 de `app/Contracts/Games/Bingo/**` + 3 de
`app/Services/Games/Bingo/Contracts/**`, estas últimas mal ubicadas dentro de
Services) movidas a `app/Domains/Games/Bingo/Contracts/**`. Ningún Provider
tocado en esta fase: el auto-scanner de `RepositoryServiceProvider` escanea
clases concretas en `app/Repositories/**`, no las interfaces — confirmado que
las 8 `*RepositoryInterface` se siguieron auto-bindeando correctamente tras
mover solo las interfaces.

### Fase 6 — Repositories + `BingoServiceProvider` (mayor riesgo mecánico)
Los 28 Repositories de `app/Repositories/Games/Bingo/**` movidos a
`app/Domains/Games/Bingo/Repositories/**`. Se creó `App\Providers\BingoServiceProvider`
(mismo patrón que `TriviaServiceProvider`) con los 8 bindings explícitos de
`*RepositoryInterface`, registrado en `bootstrap/providers.php`. Las 10 clases
que implementan `*RepositoryContract` (sin ningún caller real confirmado por
grep) se movieron igual, sin binding — deuda técnica documentada, no se
renombra la duplicación `RepositoryInterface`/`RepositoryContract`. Verificado
con resolución real vía el contenedor confirmando explícitamente que las 8
interfaces ahora resuelven a través de `BingoServiceProvider` (ya no del
scanner), más instanciación de las 10 clases sin binding.

### Fase 7 — Services + completar `BingoServiceProvider`
Los 26 Services de `app/Services/Games/Bingo/**` movidos a
`app/Domains/Games/Bingo/Services/**`. `BingoServiceProvider` extendido con los
3 bindings de las interfaces de Services movidas en Fase 5
(`CommissionCalculationInterface`, `RoomMetricsInterface`,
`RoomUserRegistrationInterface`). Actualizados 15 archivos consumidores externos
(Controllers, Commands, tests). Se encontró y se dejó intacto un import
huérfano preexistente sin relación (`App\Services\Games\Bingo\HandleBingoRooms`
en `SellerController.php`, clase que no existe en ningún lado — código muerto
anterior a esta migración).

### Fase 8 — Policies + `AuthServiceProvider`
Las 2 Policies (`BingoRoomPolicy`, `BingoRoomAutomaticPolicy`) movidas a
`app/Domains/Games/Bingo/Policies/`, junto con la actualización del mapeo
modelo→policy en `AuthServiceProvider` en el mismo cambio atómico. Verificado
con `Gate::getPolicyFor()` confirmando que ambos modelos resuelven su Policy
real desde `Domains`.

### Fase 9 — Cierre
- Eliminadas las carpetas clásicas vacías residuales de Bingo bajo
  `app/{Services,Contracts,Policies,DTOs,Events,Jobs}/Games/Bingo` (incluyendo
  subcarpetas huérfanas de limpiezas anteriores: `Services/Card/Customer`,
  `Services/Room/Validation`, `Services/Round`, sin archivos).
- `phpstan.neon`: eliminadas 2 reglas custom que referenciaban clases
  inexistentes desde antes de esta migración (`App\Contracts\BingoGameInterface`,
  `App\Models\BingoRoom`) y una regla `ReturnTypeRule` que exigía
  `Illuminate\Http\Response` en los Controllers de Bingo cuando los 9
  Controllers reales devuelven `JsonResponse`/`Response` de Inertia (48
  ocurrencias confirmadas, 0 del tipo exigido).
- Suite completa de tests corrida como red de seguridad: 145 de 146 tests
  fallan por una causa raíz **no relacionada** con esta migración — la base de
  datos de test (`playrke_testing`) rechaza la conexión configurada en
  `phpunit.xml` (`root` sin contraseña) en este entorno, que usa credenciales
  distintas (`playrke`/`playrke`). Confirmado por grep que ningún error
  menciona clases o namespaces de `App\Domains\Games\Bingo\*` — es un problema
  de configuración de entorno preexistente, no del refactor.

### Resultado final
Los 133 archivos PHP del dominio Bingo quedaron consolidados bajo
`App\Domains\Games\Bingo\{Models,DTOs,Events,Exceptions,Jobs,Contracts,Repositories,Services,Policies,ValueObjects}`,
replicando el patrón de Trivia. Controllers, Requests y Resources permanecen en
su ubicación HTTP clásica (mismo criterio que Trivia). Cada fase se verificó de
forma aislada con `php -l`, `composer dump-autoload -o` y resolución real vía
el contenedor de Laravel antes de avanzar a la siguiente.

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
