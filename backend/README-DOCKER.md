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
- Docker Compose v2.20 o superior (comando docker compose, no el binario antiguo docker-compose).
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
| DB_CONNECTION, DB_HOST, DB_PORT | mysql, mysql, 3306 |
| DB_ROOT_PASSWORD, DB_PASSWORD | Contraseñas únicas y robustas |
| REDIS_HOST | redis |
| REDIS_PASSWORD | Secreto robusto |
| CACHE_STORE, QUEUE_CONNECTION, SESSION_DRIVER | redis |
| REVERB_HOST, REVERB_PORT, REVERB_SCHEME | reverb, 8080, http (comunicación interna) |
| REVERB_APP_ID, REVERB_APP_KEY, REVERB_APP_SECRET | Valores únicos y secretos |
| VITE_API_URL | URL HTTPS de la API, por ejemplo https://dominio/api |
| VITE_REVERB_APP_KEY | Debe coincidir con REVERB_APP_KEY |
| VITE_REVERB_HOST | Host público WebSocket, por ejemplo ws.dominio |
| VITE_REVERB_PORT, VITE_REVERB_SCHEME | 443 y https |
| VITE_TURNSTILE_SITE_KEY | Debe coincidir con TURNSTILE_SITE_KEY |
| SESSION_SECURE_COOKIE | true |
| TURNSTILE_SITE_KEY, TURNSTILE_SECRET_KEY | Credenciales reales de Cloudflare Turnstile |
| LOG_LEVEL | Nivel adecuado para producción, por ejemplo error |

Genera secretos fuera del repositorio. Las contraseñas de base de datos/Redis y el secreto Reverb deben tener al menos 32 caracteres. Por ejemplo:

~~~bash
printf 'APP_KEY=base64:%s\n' "$(openssl rand -base64 32 | tr -d '\n')"
openssl rand -hex 32 # contraseña DB/Redis
openssl rand -hex 16 # identificador o clave Reverb
openssl rand -hex 32 # secreto Reverb
~~~

Configura además CACHE_STORE=redis, QUEUE_CONNECTION=redis, SESSION_DRIVER=redis y FILESYSTEM_DISK=public. VITE_API_URL debe apuntar a la API pública HTTPS; VITE_REVERB_HOST debe ser el host público ws.dominio, mientras REVERB_HOST permanece en reverb para la comunicación interna. VITE_REVERB_APP_KEY debe coincidir con REVERB_APP_KEY y VITE_TURNSTILE_SITE_KEY con TURNSTILE_SITE_KEY. No reutilices valores del entorno local ni guardes .env.prod en Git.

## 2. Validar y desplegar

El script valida variables críticas, comprueba la configuración de Compose, construye los assets públicos del frontend con los valores VITE_* de producción, espera a MySQL/Redis, ejecuta las migraciones una sola vez y luego inicia el stack.

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
docker compose --env-file .env.prod -f docker-compose.prod.yml run --rm --no-deps --entrypoint sh php -c 'php artisan package:discover --ansi && php artisan migrate --force --no-interaction'
docker compose --env-file .env.prod -f docker-compose.prod.yml up -d --build --remove-orphans --wait --wait-timeout 300
for service in php reverb queue bingo-refunds scheduler; do docker compose --env-file .env.prod -f docker-compose.prod.yml exec -T "$service" php artisan optimize; done
docker compose --env-file .env.prod -f docker-compose.prod.yml exec -T php php artisan storage:link
docker compose --env-file .env.prod -f docker-compose.prod.yml restart queue bingo-refunds scheduler reverb
~~~

Las migraciones se ejecutan explícitamente una sola vez antes de iniciar los workers. El entrypoint solo genera el manifiesto de paquetes con el entorno ya cargado; no ejecuta migraciones, evitando carreras entre PHP-FPM, workers, Reverb y scheduler. Las cachés se generan en cada contenedor porque sus sistemas de archivos no son compartidos.

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
