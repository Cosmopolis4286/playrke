# Arquitectura general

## Vista general

PlayRKE es una aplicación Laravel modular con una UI principal Inertia/Vue. El repositorio también contiene un frontend Nuxt 4 separado, actualmente fuera del workspace pnpm y del CI principal.

## Componentes

| Componente | Stack | Ubicación | Alcance actual |
|---|---|---|---|
| Backend y dominios | Laravel 11 / PHP | `backend/app` | Aplicación principal |
| UI principal | Inertia.js 2, Vue 3, TypeScript, Vite 7 | `backend/resources/js` | Incluida en build Laravel |
| UI alternativa | Nuxt 4, Vue 3, TypeScript, Tailwind, Pinia | `frontend/` | Aislada; no asumir integración productiva |
| Workspace JS | pnpm 10.25 | raíz + `backend/` | `pnpm-workspace.yaml` incluye solo backend |
| CI | GitHub Actions | `.github/workflows/ci.yml` | Pipeline mantenido |
| Producción | Docker Compose | `backend/docker-compose.prod.yml` | Stack productivo |
| Desarrollo Docker | Docker Compose | `docker-compose.dev.yml` | Entorno local |

La raíz `package.json` y `pnpm-workspace.yaml` incluyen únicamente `backend`. El Nuxt se instala desde `frontend/` con su propio gestor de paquetes. No describir ambos frontends como una única aplicación integrada ni asumir que el Nuxt se compila en CI.

## Persistencia y servicios

- **MySQL:** persistencia principal Laravel, usuarios, roles, Bingo, wallet y plataforma.
- **PostgreSQL (`pgsql_trivia`):** persistencia específica de Trivia.
- **Redis:** cache, colas, locks y estado efímero de juego.
- **Laravel Reverb / Echo:** broadcasting y suscripciones en tiempo real.
- **Queues / Scheduler:** procesamiento asíncrono y tareas programadas.

## Organización backend

- `app/Domains`: lógica y modelos de Bingo, Trivia, Wallet, Marketplace y Payments.
- `app/Http`: controladores, requests, middleware y recursos HTTP.
- `app/Models`: modelos compartidos/compatibilidad.
- `routes/web.php` y `routes/modules`: composición de rutas.
- `database`: migraciones, factories y seeders.
- `resources/js`: páginas, componentes, composables, tipos y utilidades Inertia/Vue.

## Dominios e invariantes

### Bingo
Salas, cartones, lotes/pagos, rondas, sorteos, patrones, participación, premios y reembolsos. La elegibilidad depende del lote y los pagos; el sorteo y la reclamación se aíslan por ronda. El beneficiario se resuelve desde participación/pago, no desde el vendedor que registró la venta.

### Trivia
Salas multiusuario, lobby, participantes, preguntas, respuestas, puntuaciones, equipos, power-ups y resultados. PostgreSQL persiste el dominio; Redis conserva estado en vivo; Reverb difunde eventos. No existe un microservicio ni un WebSocket independiente.

### Wallet, pagos y marketplace
La wallet y los movimientos financieros residen en MySQL. El código incluye reservas/holds, pagos, conciliación, inventario y escrow. La presencia de una interfaz o servicio no prueba que una integración externa esté habilitada para fondos reales.

## Seguridad y consistencia

1. La UI no sustituye la autorización del servidor.
2. Saldo, movimiento y estado financiero deben actualizarse atómicamente en su base propietaria.
3. Una transacción Laravel no hace atómicas dos conexiones distintas (MySQL y PostgreSQL).
4. Reintentos y webhooks requieren idempotencia y estados recuperables.
5. Eventos y Redis no sustituyen registros persistentes.
6. Un estado financiero ambiguo se concilia; nunca se fuerza a pagado.
7. Canales privados validan identidad, pertenencia y permisos; canales públicos no transportan datos privados.

## Fuentes de verdad

Para workspace: `package.json` y `pnpm-workspace.yaml`. Para CI: `.github/workflows/ci.yml`. Para despliegue: `backend/docker-compose.prod.yml` y `backend/deploy.sh`.
