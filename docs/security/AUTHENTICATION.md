# Autenticación y autorización

## Modelo efectivo

La autenticación interactiva principal usa el guard `web` de Laravel con driver `session`. No es un login JWT ni una API Bearer. Sanctum está instalado para SPA stateful, pero el flujo principal conserva la cookie de sesión.

Archivos de referencia:
- `backend/routes/auth.php`
- `backend/app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `backend/app/Http/Requests/Auth/LoginRequest.php`
- `backend/config/auth.php` y `backend/config/sanctum.php`
- `backend/routes/channels.php`

## Login y logout

1. El cliente envía credenciales por POST a `/login`.
2. `LoginRequest::authenticate()` valida credenciales y aplica limitación de intentos.
3. Laravel regenera el identificador de sesión tras autenticar.
4. Se cargan roles y se guarda el rol principal en sesión.
5. La cookie de sesión permite solicitudes posteriores.
6. `POST /logout` cierra el guard, invalida la sesión y regenera el token CSRF.

## Cookies y CSRF

Configura `SESSION_DOMAIN`, `SESSION_SECURE_COOKIE`, `SESSION_SAME_SITE`, `SANCTUM_STATEFUL_DOMAINS` y CORS con orígenes explícitos. En producción exige HTTPS y cookies seguras. No uses comodines de origen con credenciales.

## Autorización

El proyecto combina roles/permisos Spatie, middleware y Policies. Las rutas administrativas requieren autorización específica; las operaciones financieras validan propiedad y estado dentro de la transacción. La UI no es un límite de seguridad: cada endpoint valida en servidor.

## WebSockets

Las reglas están en `backend/routes/channels.php`. Los canales privados verifican identidad, pertenencia y permisos; los canales públicos solo transportan información destinada a todos.

## Mantenimiento

- Mantener throttling en login, registro, reset y verificación.
- Añadir tests de acceso denegado para roles y recursos ajenos (IDOR).
- Revisar CORS, dominios stateful y flags de cookie al cambiar dominios o despliegue.
- No introducir tokens persistentes sin expiración, revocación y almacenamiento seguro.
