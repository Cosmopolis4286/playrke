# Guía de contribución

## Flujo de trabajo

1. Actualiza `master` y crea una rama enfocada: `feat/`, `fix/`, `docs/` o `chore/`.
2. Lee [Arquitectura](ARCHITECTURE.md) y las guías del dominio afectado.
3. Antes de cambiar código, identifica contratos HTTP, permisos, modelos, conexiones de base de datos, jobs, eventos y workers implicados.
4. Añade o actualiza pruebas de regresión.
5. Ejecuta las validaciones locales relevantes y abre un Pull Request contra `master`.
6. Espera a que todos los checks requeridos de GitHub Actions terminen correctamente; no declares validado un job omitido o que no se ejecutó.

## Entorno

La aplicación principal está en `backend/`. Instala dependencias JS desde la raíz con pnpm (workspace activo) y dependencias PHP desde backend:

```bash
pnpm install
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Configura MySQL, PostgreSQL Trivia y Redis según [Desarrollo](DEVELOPMENT.md). No uses credenciales productivas.

El frontend Nuxt de `frontend/` está presente pero excluido de `pnpm-workspace.yaml`; no asumir que forma parte del build/CI principal. Consulta su [README](../frontend/README.md) antes de modificarlo.

## Validaciones

Desde `backend/`:

```bash
php artisan test
./vendor/bin/pint --test
pnpm run build
pnpm run test
pnpm run typecheck
```

Consulta [CI](CI.md) para el alcance real de los jobs. Para cambios de Docker, valida el stack afectado y el procedimiento de despliegue en staging.

## Reglas para cambios sensibles

- **Autenticación/autorización:** probar acceso permitido y denegado por rol, usuario y recurso (IDOR). La UI no es un control de seguridad.
- **Wallet/pagos/premios:** transacciones, locks, idempotencia, beneficiario correcto, reintentos y conciliación. No convertir estados ambiguos en pagados.
- **MySQL y PostgreSQL:** una transacción Laravel no hace atómicas dos conexiones distintas; diseñar compensación/idempotencia.
- **Colas:** mantener alineados nombres de cola, jobs, workers y Compose.
- **WebSockets:** comprobar autorización de canales y no publicar datos privados en canales públicos.
- **Migraciones:** no reescribir migraciones ya aplicadas; planificar cambios compatibles y rollback.
- **Secretos:** nunca subir `.env`, tokens, claves privadas, seeds o credenciales.

## Pull Request

Incluye:
- problema y solución;
- alcance y archivos/dominos afectados;
- riesgos de seguridad/datos/operación;
- pruebas ejecutadas con resultado;
- pasos de validación en staging si hay cambios operativos;
- capturas cuando la UI cambie.

No fusionar PRs con checks requeridos fallidos, pendientes o no ejecutados.