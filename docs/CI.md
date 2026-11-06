# Integración continua

## Proveedor

**GitHub Actions es el único proveedor CI mantenido** en `.github/workflows/ci.yml`. No restaurar CircleCI ni crear un pipeline paralelo sin decisión explícita.

## Eventos

Pull Requests hacia `master`, pushes a `master` y ejecución manual. El workflow usa permisos mínimos de lectura y concurrencia por workflow/ref.

## Jobs

### Backend PHP / PHPUnit

- MySQL 8, PostgreSQL 16 y Redis 7.
- PHP 8.4 con GMP, PDO MySQL, PDO PostgreSQL y Redis.
- Composer install y comprobación de requisitos de plataforma.
- Preparación de `.env.testing`.
- PHPUnit completo.
- Verificación de traducciones de errores de dominio.
- Pint sobre PHP modificado.

### Frontend build / tests / typecheck

- Node 20.19 y pnpm 10.25.
- Instalación con lockfile congelado.
- Build Vite, Vitest y type-check Vue/TypeScript.
- ESLint sobre archivos frontend modificados.

### Docker Compose / build de producción

Detecta cambios en el stack productivo. Si corresponde, valida sintaxis de `deploy.sh`, crea un entorno ficticio sin secretos reales, valida Compose y construye imágenes PHP-FPM/Nginx.

## Validación local

```bash
cd backend
composer install
php artisan test
./vendor/bin/pint --test
pnpm run build
pnpm run test
pnpm run typecheck
```

El entorno local debe disponer de extensiones y servicios requeridos. Un resultado local parcial no sustituye los checks del PR.

## Aceptación

- No fusionar con checks requeridos fallidos, pendientes o no ejecutados.
- Si un job se omite por detección de cambios, comprobar que sea esperado.
- Corregir la causa raíz; no eliminar tests ni reducir cobertura para conseguir verde.
- Si se modifica una cola, ruta, servicio, variable o target Docker, actualizar pruebas y documentación.
- La CI construye imágenes; no despliega ni sustituye el smoke test en staging.