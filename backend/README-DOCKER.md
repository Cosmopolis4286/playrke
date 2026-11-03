# Despliegue de producción con Docker

Esta guía corresponde exclusivamente al stack de producción ubicado en backend/docker-compose.prod.yml.

> **Importante:** el archivo docker-compose.prod.yml de la raíz del monorepo es un entorno de desarrollo y no debe utilizarse para desplegar producción. El stack de producción está dentro de backend/.

## Arquitectura

- **Traefik:** entrada HTTPS y certificados Let's Encrypt.
- **Nginx:** único servicio HTTP expuesto a Traefik; sirve los recursos públicos y reenvía PHP por FastCGI.
- **PHP-FPM:** ejecuta Laravel.
- **MySQL y Redis:** persistencia, sesiones, caché y colas.
- **Reverb:** WebSockets.
- **Workers:** colas generales/Bingo y reembolsos de Bingo en procesos separados.
- **Scheduler:** ejecuta las tareas programadas de Laravel.

Traefik no debe enviar tráfico HTTP directamente a PHP-FPM: PHP-FPM habla FastCGI, no HTTP. El servicio Nginx es el puente HTTP/FastCGI.

## Requisitos

- Docker Engine.
- Docker Compose v2 (comando docker compose, no el binario antiguo docker-compose).
- Dominio apuntando al servidor y puertos TCP 80/443 disponibles.
- Memoria, almacenamiento y copias de seguridad dimensionados para producción.

## 1. Preparar el entorno

Desde el directorio backend/:

~~~bash
cp .env.example .env.prod
~~~

Edita .env.prod. Como mínimo, establece valores reales para:

| Variable | Requisito |
|---|---|
| APP_ENV | production |
| APP_DEBUG | false |
| APP_KEY | Clave Laravel con prefijo base64: |
| APP_URL | URL HTTPS del dominio |
| FRONTEND_URL | URL HTTPS pública |
| APP_DOMAIN | Dominio sin protocolo |
| ACME_EMAIL | Correo válido para Let's Encrypt |
| DB_HOST | mysql |
| DB_ROOT_PASSWORD, DB_PASSWORD | Contraseñas únicas y robustas |
| REDIS_HOST | redis |
| REDIS_PASSWORD | Secreto robusto |
| REVERB_APP_ID, REVERB_APP_KEY, REVERB_APP_SECRET | Valores únicos y secretos |
| SESSION_SECURE_COOKIE | true |
| TURNSTILE_SITE_KEY, TURNSTILE_SECRET_KEY | Credenciales reales de Cloudflare Turnstile |
| LOG_LEVEL | Nivel adecuado para producción, por ejemplo error |

Genera secretos fuera del repositorio. Por ejemplo:

~~~bash
printf 'APP_KEY=base64:%s\n' "$(openssl rand -base64 32 | tr -d '\n')"
openssl rand -hex 32 # contraseña DB/Redis
openssl rand -hex 16 # identificador o clave Reverb
openssl rand -hex 32 # secreto Reverb
~~~

Configura además las variables del resto de integraciones que utilice tu entorno. No reutilices valores del entorno local ni guardes .env.prod en Git.

## 2. Validar y desplegar

El script valida variables críticas, comprueba la configuración de Compose, espera a MySQL/Redis, ejecuta las migraciones una sola vez y luego inicia el stack:

~~~bash
chmod +x deploy.sh
./deploy.sh
~~~

También puede ejecutarse desde otro directorio usando la ruta al script.

El script requiere que .env.prod exista previamente. No genera claves ni contraseñas de ejemplo. Si la validación falla, corrige el archivo antes de continuar.

## 3. Despliegue manual

Si necesitas controlar cada paso:

~~~bash
docker compose --env-file .env.prod -f docker-compose.prod.yml config --quiet
docker compose --env-file .env.prod -f docker-compose.prod.yml build php nginx
docker compose --env-file .env.prod -f docker-compose.prod.yml up -d --wait --wait-timeout 180 mysql redis
docker compose --env-file .env.prod -f docker-compose.prod.yml run --rm --no-deps --entrypoint php php artisan migrate --force --no-interaction
docker compose --env-file .env.prod -f docker-compose.prod.yml up -d --build --remove-orphans --wait --wait-timeout 300
docker compose --env-file .env.prod -f docker-compose.prod.yml exec -T php php artisan optimize
docker compose --env-file .env.prod -f docker-compose.prod.yml exec -T php php artisan storage:link
docker compose --env-file .env.prod -f docker-compose.prod.yml restart queue bingo-refunds scheduler reverb
~~~

Las migraciones se ejecutan explícitamente antes de iniciar los workers. No se ejecutan desde el entrypoint de cada contenedor, para evitar carreras entre PHP-FPM, workers, Reverb y scheduler.

## 4. Verificación posterior

~~~bash
docker compose --env-file .env.prod -f docker-compose.prod.yml ps
docker compose --env-file .env.prod -f docker-compose.prod.yml logs --tail=200 php nginx queue bingo-refunds scheduler reverb
~~~

Antes de habilitar tráfico real, verifica:

- HTTPS y renovación de certificados.
- Acceso a la aplicación a través de Nginx/Traefik.
- Conexión a MySQL y Redis.
- Conexión WebSocket de Reverb.
- Procesamiento de colas generales y bingo-refunds.
- Ejecución del scheduler.
- Lectura/escritura de archivos públicos.
- Flujos de compra, sorteo, premio y reembolso en staging.

## Operación

Ver logs:

~~~bash
docker compose --env-file .env.prod -f docker-compose.prod.yml logs -f
~~~

Reiniciar workers después de desplegar código:

~~~bash
docker compose --env-file .env.prod -f docker-compose.prod.yml restart queue bingo-refunds scheduler reverb
~~~

Detener el stack:

~~~bash
docker compose --env-file .env.prod -f docker-compose.prod.yml down
~~~

No utilices down -v en producción: eliminaría volúmenes persistentes. Mantén copias de seguridad verificadas de MySQL y de los archivos públicos antes de cambios de esquema o despliegues importantes.
