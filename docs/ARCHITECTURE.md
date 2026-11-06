# Arquitectura

## Vista general

PlayRKE es un monorepo con un backend Laravel modular, una UI Inertia/Vue integrada y un frontend Nuxt separado. La implementación principal documentada/activa está en `backend/`; el código Nuxt existe en `frontend/`, pero el workspace pnpm actual lo excluye.

## Aplicaciones y herramientas

| Componente | Stack | Ubicación | Estado |
|---|---|---|---|
| Backend HTTP y dominios | Laravel 11 / PHP | `backend/app` | Aplicación principal |
| UI integrada | Inertia.js 2, Vue 3, TypeScript, Vite 7 | `backend/resources/js` | Incluida en build Laravel |
| UI separada | Nuxt 4, Vue 3, TypeScript, Tailwind, Pinia | `frontend/` | Presente; no incluida en workspace pnpm activo |
| Workspace JS | pnpm 10.25 | raíz + `backend/` | `pnpm-workspace.yaml` incluye solo backend |
| CI | GitHub Actions | `.github/workflows/ci.yml` | Pipeline mantenido |
| Producción | Docker Compose | `backend/docker-compose.prod.yml` | Stack de producción |
| Desarrollo Docker | Docker Compose | `docker-compose.dev.yml` | Stack local |

La raíz `package.json` declara `backend` y `frontend` en `workspaces`, pero pnpm usa `pnpm-workspace.yaml`, que actualmente contiene solo `backend`. No asumir que los comandos pnpm de raíz ejecutan el Nuxt. Antes de activar esa aplicación, definir explícitamente instalación, CI, variables, autenticación, integración API y despliegue.

## Servicios y datos

- **MySQL:** persistencia principal de Laravel, usuarios, roles, Bingo, wallet y datos de plataforma.
- **PostgreSQL (`pgsql_trivia`):** persistencia específica de Trivia.
- **Redis:** cache, colas, locks y estado efímero de juego.
- **Laravel Reverb / Echo:** broadcasting y suscripciones en tiempo real.
- **Laravel queues/scheduler:** procesamiento asíncrono y tareas programadas.

## Organización backend

- `app/Domains`: lógica y modelos de dominios (Bingo, Trivia, Wallet, Marketplace, Payments).
- `app/Http`: controladores, requests, middleware y recursos HTTP.
- `app/Models`: modelos compartidos y compatibilidad de entidades.
- `routes/web.php`: índice de rutas web; carga archivos de autenticación y módulos.
- `routes/modules`: rutas organizadas por dominio.
- `database`: migraciones, factories y seeders.
- `resources/js`: páginas, componentes, composables, tipos y utilidades Inertia/Vue.

## Dominios

### Bingo

Salas, cartones, lotes/pagos, rondas, sorteos, patrones, participación, premios y reembolsos. La elegibilidad del cartón debe considerar estado del lote y pagos asociados. El sorteo y la reclamación deben delimitarse por ronda. Los compradores externos se resuelven desde su participación/pago, no desde el vendedor que registró la operación.

### Trivia

Salas multiusuario, lobby, participantes, preguntas, respuestas, puntuaciones, equipos, power-ups y resultados. La conexión de persistencia del dominio es `pgsql_trivia`; Redis mantiene estado efímero y Reverb difunde eventos. No existe un microservicio ni un servidor WebSocket independiente: se usa broadcasting estándar de Laravel.

### Wallet, pagos y marketplace

La wallet y movimientos financieros están en MySQL. El código incluye reservas/holds, pagos, conciliación, inventario y escrow. La integración USDT-TRC20 debe revisarse según el proveedor activo: la existencia de interfaces/servicios no implica que el adaptador blockchain esté listo para fondos reales. Consulta [Pagos y premios](PAYMENTS_AND_PRIZES.md) y los documentos de diseño enlazados en el índice.

## Tiempo real y canales

Los canales se declaran en `backend/routes/channels.php`. Hay canales públicos, privados y de presencia. Los canales públicos solo deben transportar información destinada a todos los clientes. Los privados deben verificar identidad, pertenencia y permisos; no confiar solo en que el usuario esté autenticado.

## Colas de producción

El Compose de producción define workers separados para colas generales/Bingo, reembolsos y scheduler. La cola exacta que procesa cada job debe mantenerse alineada entre `onQueue()`, configuración y servicios Compose. Antes de renombrar una cola, actualizar el worker, las pruebas y el runbook.

## Invariantes de arquitectura

1. La UI no sustituye la autorización del servidor.
2. Saldo, movimiento y estado financiero deben actualizarse de forma atómica dentro de la base propietaria.
3. Una transacción Laravel no hace atómicas dos conexiones distintas (MySQL y PostgreSQL).
4. Reintentos y webhooks necesitan claves de idempotencia y estados recuperables.
5. Eventos y Redis no sustituyen los registros persistentes.
6. Un estado financiero ambiguo se concilia; no se fuerza a pagado.
7. No deducir el beneficiario financiero desde el actor que registró la venta.

## Fuentes de verdad

La configuración y el código versionados prevalecen sobre los documentos. Para frontend, comparar `package.json`, `pnpm-workspace.yaml`, `backend/package.json` y `frontend/package.json`. Para despliegue, usar `backend/docker-compose.prod.yml` y `backend/deploy.sh`, no el Compose local de raíz.
