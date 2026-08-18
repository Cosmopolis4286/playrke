#!/bin/bash

# Corre el test suite de PHPUnit dentro del contenedor Docker (playrke_php).
#
# ⚠ CAUSA DE UN INCIDENTE REAL (2026-08-18): una versión anterior de este
# script solo forzaba APP_ENV=testing vía `docker exec -e`. Eso NO alcanza:
# docker-compose.dev.yml ya fija DB_DATABASE/DB_HOST/DB_USERNAME/etc. como
# variables de entorno reales del PROCESO del contenedor (vía env_file:
# .env.dev). Dotenv NO sobrescribe variables que ya existen en el entorno,
# así que .env.testing se "cargaba" pero cada valor era ignorado en
# silencio — los tests corrían contra la BD REAL de desarrollo ('playrke',
# no 'playrke_testing'). RefreshDatabase truncó esa BD real: 0 usuarios,
# 0 roles, 0 permisos. Ver el incidente para el post-mortem completo.
#
# Fix: se exportan explícitamente TODAS las variables de .env.testing al
# entorno del proceso de test (vía `set -a; . .env.testing`) ANTES de que
# Laravel arranque, para que ganen sobre las heredadas del contenedor —
# no alcanza con pasar solo APP_ENV.
#
# Verificación de seguridad: el script aborta si, tras cargar .env.testing,
# la conexión de base de datos resuelta NO es 'playrke_testing' — nunca debe
# correr un test (y mucho menos RefreshDatabase) contra la BD real.

set -e

CONTAINER=playrke_php

if ! docker ps --format '{{.Names}}' | grep -q "^${CONTAINER}$"; then
    echo "El contenedor ${CONTAINER} no está corriendo. Levanta el stack con:"
    echo "  docker compose -f docker-compose.dev.yml --env-file .env.dev up -d"
    exit 1
fi

# phpunit.xml no está en el bind mount del compose (solo monta app/, config/,
# database/, resources/, routes/, tests/, public/, artisan) — se copia en
# cada corrida para no depender de que ya esté presente en la imagen.
docker cp phpunit.xml "${CONTAINER}:/var/www/phpunit.xml"
docker cp .env.testing "${CONTAINER}:/var/www/.env.testing"

# Verificación de seguridad ANTES de correr nada: confirma que, exportando
# .env.testing al entorno del proceso, la BD resuelta es la de test.
RESOLVED_DB=$(docker exec "${CONTAINER}" sh -c '
    set -a
    . /var/www/.env.testing
    set +a
    php artisan tinker --execute="echo config(\"database.connections.mysql.database\");"
' 2>/dev/null | tail -1)

if [ "$RESOLVED_DB" != "playrke_testing" ]; then
    echo "❌ ABORTADO: la base de datos resuelta es '${RESOLVED_DB}', se esperaba 'playrke_testing'."
    echo "   Correr los tests así arriesga truncar datos reales de desarrollo. No se ejecutó nada."
    exit 1
fi

echo "✔ Verificado: los tests correrán contra playrke_testing (no la BD de desarrollo)."

docker exec "${CONTAINER}" sh -c '
    set -a
    . /var/www/.env.testing
    set +a
    php artisan test '"$*"'
'
