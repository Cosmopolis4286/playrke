# Rutas Laravel

## Punto de entrada

`backend/routes/web.php` es el índice de las rutas web de la aplicación. Carga las rutas de autenticación y los módulos de plataforma/juegos. Los archivos concretos versionados son la fuente de verdad: no asumir que un ejemplo o un mapa antiguo coincide con las rutas registradas actualmente.

## Organización actual

- `web.php`: índice principal de rutas web.
- `auth.php`: registro, login, logout, recuperación y verificación de cuenta.
- `channels.php`: autorización de canales de broadcasting.
- `console.php`: comandos y programación de tareas.
- `modules/`: agrupación de rutas por área funcional.
- `modules/games/bingo.php`: rutas del dominio Bingo.
- `modules/games/trivia.php`: rutas del dominio Trivia.
- `api.php` y `api/`: rutas HTTP adicionales; comprobar su uso real antes de asumir que todo el producto es una API REST versionada.

## Autenticación y permisos

La aplicación principal usa el guard web de Laravel con sesiones. Los grupos de rutas aplican middleware de autenticación, verificación, roles/permisos y Policies según el recurso y la operación. No asumir que Sanctum implica que el login devuelve JWT/Bearer token.

La autorización de los canales WebSocket está en `channels.php` y debe revisarse junto con los controladores/Policies. Los canales públicos no deben emitir datos personales o financieros.

## Cómo inspeccionar rutas registradas

Desde `backend/`:

```bash
php artisan route:list
php artisan route:list --path=bingo
php artisan route:list --path=trivia
php artisan route:list --path=login
```

Usa `php artisan route:list --help` para filtrar por middleware, método o nombre según la versión instalada.

## Regla de mantenimiento

Al añadir, mover o retirar rutas:
1. actualizar el archivo de módulo y el índice que lo carga;
2. comprobar middleware y Policy de cada endpoint;
3. actualizar clientes Inertia/API, tests y documentación;
4. verificar nombres/URLs consumidos por jobs, eventos y frontend;
5. ejecutar las pruebas y revisar el route:list en CI/staging.

Este README reemplaza el mapa antiguo que enumeraba módulos y helpers no necesariamente presentes en la versión actual.