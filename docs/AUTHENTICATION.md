# Autenticación y autorización

## Modelo efectivo

La autenticación interactiva principal usa el guard `web` de Laravel con driver `session`. No debe describirse como JWT ni como API basada en Bearer tokens. Sanctum está instalado y configurado para SPA stateful, pero el login efectivo usa la sesión web.

Archivos clave:
- `backend/routes/auth.php`
- `backend/app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `backend/app/Http/Requests/Auth/LoginRequest.php`
- `backend/config/auth.php`
- `backend/config/sanctum.php`
- `backend/routes/channels.php`

## Login

1. El usuario visita la ruta de login.
2. El cliente envía credenciales por POST a `/login`.
3. La ruta aplica `guest` y `throttle:20,1`; el request añade su limitador por login/IP.
4. `LoginRequest::authenticate()` valida credenciales.
5. Laravel regenera el identificador de sesión para mitigar session fixation.
6. Se cargan roles y se guarda el prefijo del rol principal en sesión como `userRole`.
7. Se registra la racha de login dentro de un bloque que contiene excepciones para no invalidar el acceso.
8. El backend devuelve JSON de éxito y el navegador conserva la cookie de sesión.

## Logout

`POST /logout` requiere autenticación. El controlador cierra el guard web, invalida la sesión y regenera el token CSRF.

## Cookies y CSRF

El cliente debe enviar cookies de sesión y token CSRF en solicitudes mutables. Configura:
- `SESSION_DOMAIN`, `SESSION_SECURE_COOKIE`, `SESSION_SAME_SITE`
- `SANCTUM_STATEFUL_DOMAINS` con host:puerto sin protocolo cuando corresponda
- CORS con orígenes explícitos y soporte de credenciales
- HTTPS en producción

No uses comodines de origen con credenciales. La cookie de producción debe ser segura.

## Registro, recuperación y verificación

Las rutas guest incluyen registro, login, recuperación y restablecimiento de contraseña con throttling. Las rutas autenticadas incluyen verificación de email, reenvío, confirmación/actualización de contraseña y logout. Las acciones de juego que mueven dinero deben exigir verificación de email cuando la ruta lo establece.

## Autorización

El proyecto combina roles/permisos (Spatie), middleware `role`, `multiPermission` y Policies.

- El permiso para jugar no equivale al permiso para administrar salas.
- Rutas administrativas aplican autorización específica y Policies sobre el recurso.
- Trivia limita gestión de salas/preguntas a administración y participación a jugadores verificados, con excepciones explícitas para host/admin.
- Acciones financieras verifican propiedad y vuelven a validar el estado dentro de la transacción.
- La UI no es un límite de seguridad: cada endpoint valida en servidor.

## WebSockets

Las reglas están en `backend/routes/channels.php`. Canales privados comparan al usuario autenticado con el identificador o exigen roles; el presence channel de Bingo comprueba además la Policy `viewGame`. Los canales públicos solo pueden emitir datos destinados a todos.

## Mantenimiento

- No introducir tokens persistentes sin expiración, revocación y almacenamiento seguro.
- Mantener throttling en login, registro, reset y verificación.
- Añadir tests de acceso denegado para roles y recursos ajenos (IDOR).
- Revisar CORS, dominios stateful y flags de cookie al cambiar dominios/despliegue.