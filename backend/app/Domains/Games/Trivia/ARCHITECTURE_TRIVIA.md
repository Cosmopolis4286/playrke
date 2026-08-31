# Arquitectura de Trivia en Tiempo Real

## Resumen

Sistema de trivia multi-sala en tiempo real, implementado como dominio
desacoplado dentro de Laravel (`app/Domains/Games/Trivia/`). Usa Postgres
(`pgsql_trivia`) para persistencia, Redis para estado en vivo, y Laravel
Reverb/Echo para broadcasting — no hay WebSocket nativo ni microservicio
independiente.

> Documento actualizado el 2026-08-31 tras auditoría de flujo completo, para
> reflejar la implementación real en vez de un diseño planeado. Ver
> `CHANGELOG_AUDIT_2026_08.md` en este directorio para los hallazgos y fixes
> aplicados sobre este dominio.

---

## Estructura del Módulo

```
app/Domains/Games/Trivia/
├── Contracts/          # Interfaces (desacoplamiento)
│   ├── GameEngineInterface.php
│   ├── RoomServiceInterface.php
│   ├── ScoreServiceInterface.php
│   └── GameStateRepositoryInterface.php
├── DTOs/                # Objetos de transferencia
├── Events/              # Eventos de dominio (broadcasting vía Reverb)
├── Models/              # Eloquent (tablas trivia_*, conexión pgsql_trivia)
│   ├── TriviaRoom.php
│   ├── TriviaGame.php
│   ├── TriviaQuestion.php
│   ├── TriviaAnswer.php
│   ├── TriviaScore.php
│   └── TriviaTeam.php
├── Repositories/
│   └── RedisGameStateRepository.php
├── Services/
│   ├── GameEngineService.php
│   ├── RoomService.php
│   ├── ScoreService.php
│   └── PrizeDistributionService.php   # delega el pago real a GamePrizeService (compartido con Bingo)
└── Actions/             # Casos de uso
    ├── StartGameAction.php
    ├── SubmitAnswerAction.php
    ├── FinishGameAction.php
    └── JoinRoomAction.php
```

Los controllers HTTP viven fuera del dominio, en
`app/Http/Controllers/Games/Trivia/{Room,Game,Question,Report}` (admin y
jugador separados) y se enrutan desde
`routes/modules/games/trivia.php`.

---

## Flujo de Datos

### 1. Admin crea sala
```
POST /trivia-rooms  (TriviaRoomAdminController::store)
     ↓
RoomService::createRoom()
     ↓
TriviaRoom (Postgres) + RoomDTO (Redis)
```

### 2. Jugador se une (con cobro de entrada)
```
POST /trivia-game/room/{id}/join  (TriviaGameController::join)
     ↓
JoinRoomAction::execute()
     ↓
WalletService::recordTriviaEntry()  (DB::transaction + lockForUpdate)
     ↓
trivia_participants (Postgres) + PlayerJoined Event → Broadcast Reverb
```

### 3. Inicia juego
```
POST /trivia-game/room/{id}/start  (TriviaStartController)
     ↓
StartGameAction::execute()
     ↓
GameEngineService::startGame()
     ↓
GameStarted Event → Broadcast Reverb
     ↓
sendNextQuestion() automático → QuestionSent Event
```

### 4. Jugador responde
```
POST /trivia-game/game/{gameId}/answer  (TriviaAnswerController)
     ↓
SubmitAnswerAction::execute()
     ↓
Anti-cheat: tiempo de respuesta calculado SERVER-SIDE
(desde server_timestamp_ms persistido al enviar la pregunta,
nunca desde el timestamp del cliente)
     ↓
GameEngineService::submitAnswer()  → AnswerSubmitted + ScoreUpdated Events
```

### 5. Finaliza juego
```
Automático: última pregunta respondida, o inactividad
(AutoFinishStaleTriviaGamesCommand, cron cada minuto)
     ↓
GameEngineService::finishGame()
     ↓
Ranking reconstruido desde Postgres (no desde Redis)
     ↓
PrizeDistributionService::distribute() → GamePrizeService::payout()
     ↓
GameFinished Event → Broadcast resultados
```

---

## Estados en Redis

| Key | TTL | Contenido |
|-----|-----|-----------|
| `trivia:game:{id}` | 1h | GameStateDTO |
| `trivia:score:{game}:{player}` | 1h | ScoreDTO |
| `trivia:leaderboard:{game}` | 1h | Sorted set |
| `trivia:room:{id}` | 2h | RoomDTO |
| `trivia:player:{room}:{id}` | 2h | PlayerDTO |
| `trivia:question:{game}` | 1h | QuestionDTO (incluye `server_timestamp_ms`) |

Redis es la fuente de verdad **durante** la partida. Postgres solo persiste
al final de cada respuesta/partida — no hay journaling de cada evento.

---

## Canales de Broadcasting (Reverb)

```
trivia.room.{roomId}      // Eventos de sala (join/leave/start)
trivia.game.{gameId}      // Eventos de juego (pregunta, respuesta, score)
```

El frontend se conecta vía Laravel Echo
(`resources/js/composables/games/trivia/useTriviaRoomEcho.ts`), que expone
`connectionStatus` (`connected`/`disconnected`/`reconnecting`).

---

## Máquina de estados

### TriviaRoom.status
```
waiting → playing   (StartGameAction / cron de scheduled_at)
waiting → cancelled (CancelStaleTriviaRoomsCommand, +reembolso)
waiting → closed    (admin: destroy/forceClose, sin reembolso)
playing → closed    (finishGame automático, +distribute() de premios)
playing → closed    (admin: forceClose, sin reembolso — ver hallazgo crítico)
```

### TriviaGame.status
```
playing → finished  (normal)
playing → cancelled (GameEngineService::cancelGame(), sin reembolso)
```
No existe el estado `paused` pese a estar documentado en el `$fillable` del
modelo — ninguna transición lo produce ni lo consume.

### TriviaParticipant.prize_status (FSM de GamePrizeContract)
```
waiting → processing → claimed | cancelled | suspended
```

---

## Multi-Base de Datos

| Componente | Base de Datos | Motor |
|------------|---------------|-------|
| Core (users, wallets, currencies) | `playrke` (default) | MySQL |
| Trivia (rooms, games, scores) | conexión `pgsql_trivia` | PostgreSQL |

Sin FK física entre ambas — las referencias cruzadas (`host_id`,
`game_mode_id`, `entry_currency_id`) son "blandas": Eloquent resuelve con
una segunda query, no un JOIN real.

```php
// config/database.php
'pgsql_trivia' => [
    'driver'   => 'pgsql',
    'host'     => env('DB_TRIVIA_HOST', '127.0.0.1'),
    'port'     => env('DB_TRIVIA_PORT', '5432'),
    'database' => env('DB_TRIVIA_DATABASE', 'trivia'),
    'username' => env('DB_TRIVIA_USERNAME', 'postgres'),
    'password' => env('DB_TRIVIA_PASSWORD', ''),
],
```

---

## Comandos de mantenimiento

```bash
php artisan trivia:start-scheduled-rooms     # cron 1 min
php artisan trivia:auto-finish-stale-games   # cron 1 min
php artisan trivia:cancel-stale-rooms        # cron 5 min
php artisan trivia:retry-failed-prize-payouts # cron 5 min
```

(Nombres exactos: ver `app/Console/Commands/*Trivia*.php` y su registro en
`routes/console.php`.)

---

## Qué NO existe (para evitar asumirlo de versiones anteriores de este doc)

- No hay endpoints `/api/trivia/*` ni controllers `Api\Trivia\*` — se
  eliminaron el 2026-08-31 por ser código muerto (ver changelog).
- No hay WebSocket nativo (`ws://`) — es Reverb/Echo estándar de Laravel.
- No hay componentes `Components/Trivia/Room/*`, `Game/*`, `Score/*` — el
  frontend real vive como páginas planas en `resources/js/Pages/Games/Trivia/`
  (`Lobby.vue`, `WaitingRoom.vue`, `Play.vue`, `PlayResults.vue`).
- No hay plan de extracción a microservicio en curso — el dominio está
  desacoplado por buena práctica (Contracts + DTOs), no porque haya un
  roadmap de migración activo.
