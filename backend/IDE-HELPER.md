# Laravel IDE Helper

El proyecto usa Laravel IDE Helper para reducir falsos positivos del IDE y mejorar el autocompletado de Facades, modelos y helpers.

## Archivos generados

Los siguientes archivos se generan localmente y no deben versionarse:
- `_ide_helper.php`
- `_ide_helper_models.php`
- `.phpstorm.meta.php`

## Regenerar

Ejecuta desde `backend/`:

```bash
composer ide-helper
# o, por separado:
php artisan ide-helper:generate
php artisan ide-helper:meta
php artisan ide-helper:models --nowrite
```

Si usas el Compose local de la raíz, el servicio Laravel se llama `backend`:

```bash
docker compose -f docker-compose.dev.yml exec backend php artisan ide-helper:generate
```

Comprueba los nombres de servicios del Compose que estés usando antes de ejecutar comandos en contenedores. El stack productivo está en `backend/docker-compose.prod.yml` y tiene servicios distintos.

Regenera los helpers después de actualizar Composer, crear/modificar modelos o cambiar namespaces.

## IDE

Para VS Code/Intelephense, asegúrate de tener habilitados los stubs Laravel, PHP, PDO, MySQL y Redis. PhpStorm detecta `.phpstorm.meta.php` automáticamente.

Estos archivos son herramientas de desarrollo local; no forman parte del runtime ni deben generarse como paso obligatorio de CI.
