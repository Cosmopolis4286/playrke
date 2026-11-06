# Sistema de Trivia — PlayRKE

Sistema de trivia multi-sala en tiempo real, con arquitectura multi-base de datos
(MySQL + PostgreSQL) y sincronización de estado vía Redis + Laravel Reverb/Echo.

> Este documento describe la implementación real verificada en código (auditoría
> 2026-08-31). No es una plantilla de diseño — refleja lo que existe hoy.

---

## Arquitectura

```
┌─────────────────────────────────────────────────────────┐
│           FRONTEND (Vue 3 + Inertia + TS)                │
│  Lobby.vue · WaitingRoom.vue · Play.vue · PlayResults.vue │
└─────────────────────────────────────────────────────────┘
                           │
              Inertia (HTTP) + Laravel Echo (Reverb)
                           │
┌─────────────────────────────────────────────────────────┐
│                   LARAVEL BACKEND                        │
│  RoomService · GameEngineService · ScoreService ·         │
│  PrizeDistributionService (comparte motor con Bingo)      │
└─────────────────────────────────────────────────────────┘
                           │
              ┌────────────┴────────────┐
    ┌─────────▼─────────┐    ┌──────────▼─────────┐
    │      MySQL         │    │     PostgreSQL      │
    │ (users, wallets,   │    │ pgsql_trivia:        │
    │  currencies)       │    │ rooms/games/scores)  │
    └────────────────────┘    └──────────────────────┘
              │                         │
              └────────────┬────────────┘
                   ┌────────▼────────┐
                   │      Redis       │
                   │ (estado en vivo) │
                   └──────────────────┘
```

No hay WebSocket nativo ni microservicio: el tiempo real se maneja con
Laravel Reverb (broadcasting estándar de Laravel) consumido por Echo en el
frontend. El estado en vivo de una partida vive en Redis; PostgreSQL solo
persiste al final de cada respuesta/partida.

---

## Base de Datos

Conexión `pgsql_trivia`, registrada en `config/database.php`. Las tablas
usan ULID como PK (no Snowflake — no aplica el cast `id => string` que sí
usan otros dominios del proyecto).

| Tabla | Descripción |
|-------|-------------|
| `trivia_rooms` | Salas de juego (config, entry fee, premio) |
| `trivia_games` | Partidas concretas dentro de una sala |
| `trivia_participants` | Inscripción de jugadores + estado de premio |
| `trivia_questions` | Banco de preguntas global (no por sala) |
| `trivia_answers` | Respuestas de jugadores (unique game+player+question) |
| `trivia_scores` | Puntuaciones finales por partida |
| `trivia_teams` | Equipos (modos TEAMS/DUEL) |

Las migraciones viven en `database/migrations/trivia/` y se registran
manualmente vía `TriviaServiceProvider::boot()` con `loadMigrationsFrom()`
— `artisan migrate` no las descubre solo.

---

## Flujo real (creación → pago de ganadores)

1. **Crear sala** (admin): `TriviaRoomAdminController::store()` →
   `RoomService::createRoom()`. Config incluye entry fee multidivisa,
   modelo de premio (`guaranteed`|`pool`), rake, `scheduled_at`.
2. **Publicar/programar**: `scheduled_at` + `StartScheduledTriviaRoomsCommand`
   (cron cada minuto) o inicio manual por el host.
3. **Inscripción**: `TriviaGameController::join()` → `JoinRoomAction` cobra
   la entrada vía `WalletService` (con lock) e inserta en
   `trivia_participants`.
4. **Juego en vivo**: `GameEngineService` orquesta preguntas/respuestas.
   Redis es la fuente de verdad en tiempo real; el tiempo de respuesta se
   calcula server-side (`SubmitAnswerAction::calculateResponseTime()`),
   nunca confiando en el timestamp del cliente.
5. **Cierre**: automático al agotar preguntas o por inactividad
   (`AutoFinishStaleTriviaGamesCommand`). El ranking final se reconstruye
   desde PostgreSQL, no desde Redis.
6. **Pago de premios**: `PrizeDistributionService::distribute()` calcula
   montos y delega cada pago a `GamePrizeService` (compartido con Bingo),
   con FSM de estado (`waiting→processing→claimed`) e idempotencyKey.
   Reintentos automáticos vía `RetryFailedTriviaPrizePayoutsCommand`.

---

## Comandos de mantenimiento (cron)

| Comando | Frecuencia | Función |
|---------|-----------|---------|
| `StartScheduledTriviaRoomsCommand` | 1 min | Inicia salas con `scheduled_at` vencido |
| `AutoFinishStaleTriviaGamesCommand` | 1 min | Cierra partidas inactivas |
| `CancelStaleTriviaRoomsCommand` | 5 min | Cancela y reembolsa salas abandonadas |
| `RetryFailedTriviaPrizePayoutsCommand` | 5 min | Reintenta pagos de premio fallidos |

---

## Rutas activas

Definidas en `routes/modules/games/trivia.php` (incluido desde `routes/web.php`).
No existe un sistema de API REST independiente — todo el flujo de jugador
usa Inertia; solo el envío de respuesta (`TriviaAnswerController`) es un
endpoint JSON puro consumido por el propio frontend Inertia.

---

## Tests

```bash
php artisan test tests/Unit/Domains/Trivia
php artisan test --filter=RoomServiceTest
```

Cobertura real (auditoría 2026-08-31): `RoomService` tiene una suite sólida
(22 tests). `GameEngineService`/`ScoreService`/`SubmitAnswerAction` tienen
cobertura parcial. **No hay tests** para `JoinRoomAction` (cobro real),
`PrizeDistributionService` (cálculo/reparto de premios), `StartGameAction`,
ni para ningún escenario de concurrencia — ver
`../history/TRIVIA_AUDIT_2026_08.md` en el archivo histórico enlazado para el detalle de
hallazgos y qué falta blindar antes de confiar en este flujo bajo carga.

---

## Historial de esta documentación

Este README reemplazó una versión anterior que describía un diseño nunca
construido (WebSocket nativo, endpoints `/api/trivia/*`, componentes
`Components/Trivia/Room/*` inexistentes). Ese sistema paralelo (`Api\Trivia\*`,
`routes/trivia.php`, `Game.vue`/`Results.vue`) fue código muerto — nada lo
renderizaba y llamaba rutas inexistentes — y se eliminó el 2026-08-31.
