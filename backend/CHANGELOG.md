# Registro de cambios — PlayRKE Backend

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
