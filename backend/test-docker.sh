#!/bin/bash

# Orquestador ÚNICO y oficial para correr el test suite de PHPUnit dentro del
# contenedor Docker (playrke_php). Cualquier otra forma de invocar
# `php artisan test` (docker exec directo, tarea de IDE, etc.) sigue siendo
# segura gracias a tests/bootstrap.php (ver más abajo), pero este script es
# la vía recomendada: valida el contenedor, refresca phpunit.xml/.env.testing,
# y da mensajes claros si algo falla.
#
# ⚠ CAUSA DE INCIDENTES REALES (2026-08-18, 2026-08-24): docker-compose.dev.yml
# fija DB_DATABASE/DB_HOST/DB_USERNAME/etc. como variables de entorno reales
# del PROCESO del contenedor (vía env_file: .env.dev). Dotenv NO sobrescribe
# variables que ya existen en el entorno, así que .env.testing se "cargaba"
# pero cada valor era ignorado en silencio — los tests corrían contra la BD
# REAL de desarrollo ('playrke', no 'playrke_testing'). RefreshDatabase
# truncó esa BD real ambas veces: 0 usuarios, 0 roles, 0 permisos.
#
# DEFENSA EN PROFUNDIDAD (3 capas independientes, cualquiera basta sola):
#   1. tests/bootstrap.php — corre ANTES que cualquier clase de test se
#      instancie o que RefreshDatabase toque la BD. Fuerza .env.testing al
#      entorno del proceso PHP directamente (sin depender de shell), y
#      aborta si DB_DATABASE/DB_TRIVIA_DATABASE resueltos no contienen
#      'testing'. Esta es la capa que protege SIN IMPORTAR el invocador.
#   2. tests/TestCase.php::setUp() — guarda de nuevo justo antes de cada
#      test individual, verificando config() ya resuelto por Laravel
#      (mysql -> playrke_testing, pgsql_trivia -> trivia_testing).
#   3. Este script — verificación previa explícita + export de .env.testing
#      al shell antes de invocar PHP, como red adicional.
#
# Ver post-mortems completos en las notas de memoria del proyecto.

set -e

CONTAINER=playrke_php

if ! docker ps --format '{{.Names}}' | grep -q "^${CONTAINER}$"; then
    echo "El contenedor ${CONTAINER} no está corriendo. Levanta el stack con:"
    echo "  docker compose -f docker-compose.dev.yml --env-file .env.dev up -d"
    exit 1
fi

# phpunit.xml y tests/bootstrap.php no están en el bind mount del compose
# (solo monta app/, config/, database/, resources/, routes/, tests/, public/,
# artisan) — en realidad tests/ SÍ está montado, pero se copian igual para no
# depender de que el contenedor tenga la versión más reciente si el mount
# fallara o se corriera contra una imagen sin bind mount (p.ej. CI).
docker cp phpunit.xml "${CONTAINER}:/var/www/phpunit.xml"
docker cp .env.testing "${CONTAINER}:/var/www/.env.testing"
docker cp tests/bootstrap.php "${CONTAINER}:/var/www/tests/bootstrap.php"
docker cp tests/TestCase.php "${CONTAINER}:/var/www/tests/TestCase.php"

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
    php artisan test "$@"
' -- "$@"
