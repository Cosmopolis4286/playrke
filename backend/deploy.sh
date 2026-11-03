#!/usr/bin/env bash

# Despliegue de producción de PlayRKE.
# Ejecutar desde cualquier directorio: ./deploy.sh
set -Eeuo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

ENV_FILE="$SCRIPT_DIR/.env.prod"
COMPOSE_FILE="$SCRIPT_DIR/docker-compose.prod.yml"

log() { printf '\n[PlayRKE] %s\n' "$*"; }
fail() { printf '\n[ERROR] %s\n' "$*" >&2; exit 1; }

[[ -f "$ENV_FILE" ]] || fail "Falta backend/.env.prod. Créalo y configura secretos reales antes de desplegar."
[[ -f "$COMPOSE_FILE" ]] || fail "No se encontró docker-compose.prod.yml."

command -v docker >/dev/null 2>&1 || fail "Docker no está instalado."
docker info >/dev/null 2>&1 || fail "El daemon de Docker no está disponible."
docker compose version >/dev/null 2>&1 || fail "Se requiere Docker Compose v2 (comando: docker compose)."

# Lee valores sin ejecutar ni hacer source del archivo .env.
# Así evitamos ejecutar accidentalmente contenido arbitrario del archivo.
env_value() {
    local key="$1"
    local line value
    line="$(grep -E "^[[:space:]]*${key}=" "$ENV_FILE" | tail -n 1 || true)"
    [[ -n "$line" ]] || return 1
    value="${line#*=}"
    value="${value%$'\r'}"
    value="${value#\"}"; value="${value%\"}"
    value="${value#\'}"; value="${value%\'}"
    [[ -n "$value" ]] || return 1
    [[ "$value" != *"CHANGE_ME"* && "$value" != *"yourdomain.com"* && "$value" != *"example.com"* ]] || return 1
    printf '%s' "$value"
}

require_env() {
    local key="$1"
    env_value "$key" >/dev/null || fail "La variable $key falta, está vacía o conserva un valor de ejemplo en .env.prod."
}

for key in APP_ENV APP_DEBUG APP_KEY APP_URL FRONTEND_URL APP_DOMAIN ACME_EMAIL \
    DB_ROOT_PASSWORD DB_DATABASE DB_USERNAME DB_PASSWORD REDIS_PASSWORD \
    REVERB_APP_ID REVERB_APP_KEY REVERB_APP_SECRET \
    TURNSTILE_SITE_KEY TURNSTILE_SECRET_KEY SESSION_SECURE_COOKIE LOG_LEVEL; do
    require_env "$key"
done

[[ "$(env_value APP_ENV)" == "production" ]] || fail "APP_ENV debe ser production."
[[ "$(env_value APP_DEBUG)" == "false" ]] || fail "APP_DEBUG debe ser false."
[[ "$(env_value SESSION_SECURE_COOKIE)" == "true" ]] || fail "SESSION_SECURE_COOKIE debe ser true."
[[ "$(env_value APP_KEY)" == base64:* ]] || fail "APP_KEY debe ser una clave Laravel con prefijo base64:."
[[ "$(env_value APP_URL)" == https://* ]] || fail "APP_URL debe usar HTTPS."
[[ "$(env_value FRONTEND_URL)" == https://* ]] || fail "FRONTEND_URL debe usar HTTPS."

# Evita que el archivo de secretos quede legible para otros usuarios del host.
chmod 600 "$ENV_FILE"

COMPOSE=(docker compose --env-file "$ENV_FILE" -f "$COMPOSE_FILE")

log "Validando la configuración efectiva de Docker Compose..."
"${COMPOSE[@]}" config --quiet

log "Construyendo la imagen de producción..."
"${COMPOSE[@]}" build php

log "Iniciando y esperando MySQL y Redis..."
"${COMPOSE[@]}" up -d --wait --wait-timeout 180 mysql redis

log "Ejecutando migraciones una sola vez antes de iniciar workers..."
"${COMPOSE[@]}" run --rm --no-deps --entrypoint php php artisan migrate --force --no-interaction

log "Iniciando la aplicación, Reverb, workers, scheduler y proxy..."
"${COMPOSE[@]}" up -d --build --remove-orphans --wait --wait-timeout 300

log "Generando cachés Laravel con el entorno de producción ya cargado..."
"${COMPOSE[@]}" exec -T php php artisan optimize
"${COMPOSE[@]}" exec -T php php artisan storage:link

log "Reiniciando procesos persistentes para que lean la configuración actual..."
"${COMPOSE[@]}" restart queue bingo-refunds scheduler reverb

log "Estado final de los servicios:"
"${COMPOSE[@]}" ps

log "Despliegue completado. Revisa los logs y realiza una prueba funcional antes de habilitar tráfico real."
