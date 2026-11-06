# Estado del ecosistema PlayRKE

> Documento de contexto técnico fechado y sujeto a cambios. La fuente de verdad es el código/configuración de `master`; para guías mantenidas consulta [../README.md](../README.md). No usar este resumen como evidencia de que una función está habilitada en producción.

## Aplicaciones

- **Backend principal:** Laravel 11 / PHP, en `backend/`.
- **UI principal activa:** Inertia.js 2 + Vue 3 + TypeScript, en `backend/resources/js`.
- **Frontend separado:** Nuxt 4 en `frontend/`. El código existe, pero `pnpm-workspace.yaml` excluye ese paquete; el foco del flujo de trabajo actual está en Inertia.
- **Workspace:** la raíz `package.json` declara npm workspaces para backend/frontend, mientras que pnpm incluye solo backend. Esta diferencia debe resolverse antes de integrar Nuxt a CI/build.
- **CI:** GitHub Actions, `.github/workflows/ci.yml`.
- **Producción:** `backend/docker-compose.prod.yml`; desarrollo local: `docker-compose.dev.yml`.

## Persistencia y tiempo real

- MySQL: base principal de Laravel, usuarios, roles, Bingo, wallet y plataforma.
- PostgreSQL: conexión `pgsql_trivia` para datos del dominio Trivia.
- Redis: cache, colas, locks y estado efímero de juego.
- Laravel Reverb + Echo: broadcasting y eventos en tiempo real.

Las transacciones Laravel no hacen atómicas las operaciones entre MySQL y PostgreSQL. Los efectos cruzados deben diseñarse con idempotencia, estados recuperables y reconciliación.

## Dominios

- **Bingo:** salas, cartones, rondas/sorteos, patrones, ventas/participación, premios y reembolsos.
- **Trivia:** salas, participantes, preguntas, respuestas, puntuaciones, equipos, resultados y premios.
- **Wallet/Payments:** ledger de movimientos, holds/reservas, pagos y procesos de conciliación.
- **Marketplace:** inventario, escrow y adaptadores de cripto; verificar el proveedor concreto antes de considerar una integración productiva.

## Referencias

- [Alcance funcional Bingo/Trivia y comercio](../product/ALCANCE_JUEGOS_Y_COMERCIO.md)
- [Changelog de arquitectura](../history/CHANGELOG_ARQUITECTURA.md)
- [Auditoría de Marketplace](../payments/MARKETPLACE_ARCHITECTURE_AUDIT.md)
- [Diseño USDT-TRC20](../payments/USDT_TRC20_DESIGN.md)
- [Plan de estrategia de producto](../product/ESTRATEGIA_PLAN.md)
- [Guías técnicas](../README.md)

## Advertencia sobre planes

Los archivos de `progreso/` son bitácoras/planes de trabajo con estados que pueden haber cambiado tras su última actualización. Antes de reabrir una fase, inspecciona el código, las migraciones y los tests actuales; no interpretes una casilla histórica como estado actual sin verificarla.