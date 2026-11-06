# Contribuir a PlayRKE

## Flujo

1. Actualiza `master` y crea una rama enfocada: `feat/`, `fix/`, `docs/` o `chore/`.
2. Lee [arquitectura](../architecture/OVERVIEW.md) y la guía del dominio afectado.
3. Identifica contratos HTTP, permisos, modelos, conexiones DB, jobs, eventos y workers implicados.
4. Añade pruebas de regresión y actualiza la documentación canónica.
5. Ejecuta validaciones locales relevantes y abre PR contra `master`.
6. Espera los checks requeridos de GitHub Actions; un job omitido o no ejecutado no equivale a validación.

## Entorno

La aplicación principal está en `backend/`. Desde la raíz instala el workspace pnpm; desde backend instala Composer:

```bash
pnpm install
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Configura MySQL, PostgreSQL Trivia y Redis según [Desarrollo](DEVELOPMENT.md). El frontend Nuxt de `frontend/` está aislado y fuera del workspace/CI principal; consulta [su guía](../frontends/NUXT4.md).

## Validaciones

Desde `backend/`:

```bash
php artisan test
./vendor/bin/pint --test
pnpm run build
pnpm run test
pnpm run typecheck
```

Consulta [CI](CI.md) para el alcance real. Para cambios de Docker, valida el stack afectado y el procedimiento de despliegue en staging.

## Cambios sensibles

- **Auth:** probar acceso permitido/denegado por rol, usuario y recurso (IDOR).
- **Wallet/pagos/premios:** probar transacciones, locks, idempotencia, beneficiario, reintentos y conciliación. No convertir estados ambiguos en pagados.
- **MySQL/PostgreSQL:** una transacción no cubre ambas conexiones; diseñar compensación/idempotencia.
- **Colas:** alinear nombres de cola, jobs, workers y Compose.
- **WebSockets:** autorizar canales y no publicar datos privados en canales públicos.
- **Migraciones:** no reescribir migraciones aplicadas; planificar cambios compatibles y rollback.
- **Secretos:** nunca subir `.env`, tokens, claves privadas, seeds o credenciales.

## Pull Request

Incluye problema, solución, alcance, riesgos, pruebas ejecutadas con resultado y pasos de staging cuando aplique. No fusionar con checks requeridos fallidos, pendientes o no ejecutados.
