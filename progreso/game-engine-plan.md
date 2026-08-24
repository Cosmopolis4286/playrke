# Plan: Game Engine — sesión de partida mínima para holds y juegos de duración variable

> Estado del sistema antes de este plan: `GameCompletionService` (resultado final) y
> `GamePrizeService` (pago de premio) ya cubren, en producción, el "qué pasó" de una
> partida de Bingo/Trivia. `WalletHold` (Fase 1 de la iniciativa de arquitectura
> Wallet/Game Engine/Eventos) ya resuelve "saldo reservado durante una partida en
> curso", pero su columna `game_session_id` es una referencia blanda a una tabla
> `game_sessions` que **todavía no existe**. Bingo y Trivia modelan su propio ciclo
> de vida de sala/partida de forma madura e independiente (`bingo_rooms.status_id`
> vía FK a `game_status`, `trivia_rooms.status`/`trivia_games.status` como strings
> en Postgres) — ninguno de los dos tiene un problema sin resolver ahí. Ver
> diagnóstico completo en `progreso/xp-logros-plan.md` y `CHANGELOG_ARQUITECTURA.md`
> (Fase 1: Wallet).

## Objetivo

Cerrar la única referencia colgante que dejó la Fase 1 de la iniciativa de
arquitectura (`WalletHold.game_session_id` sin tabla real detrás) con el mínimo de
infraestructura necesaria para que el futuro juego "Transportistas vs Cazadores"
(partida de duración variable, con estados intermedios que hoy ningún mecanismo
cubre) tenga dónde anclar sus holds y su resultado final, **sin construir una
máquina de estados genérica que Bingo y Trivia no necesitan y no van a adoptar**.

## Principio de diseño

Mismo principio que ya aplicó la Fase 1 de esta iniciativa y que ya aplicó
`xp-logros-plan.md`: antes de construir, verificar qué ya existe. Aquí el resultado
de esa verificación es que **la mayor parte de lo que el diseño original de Fase 2
proponía ya está resuelto** por `GameCompletionService`/`game_participations`
(resultado final) y `GamePrizeService` (pago de premio) — ninguno de los dos se
toca, ninguno se reemplaza.

Lo que sí falta es angosto: un ancla de ciclo de vida **durante** la partida, útil
solo para juegos con duración variable y resultado no instantáneo. Bingo y Trivia
resuelven un resultado en minutos y ya tienen su propio estado de sala — forzarlos a
adoptar una tabla genérica hoy no les da nada que no tengan, y sí les agrega una
migración de riesgo (dinero real) sin beneficio medible. Por eso: **`game_sessions`
se construye, pero Bingo/Trivia NO se migran en esta fase** — mismo patrón strangler
fig que `WalletHold` ya siguió (`game_session_id` nullable, acompañante opcional, no
reemplazo).

Igual que en `xp-logros-plan.md`: nada de `GameLifecycleEvent`/`EventServiceProvider`.
El futuro juego llama explícitamente a un servicio nuevo (`GameSessionService`) en
sus puntos de inicio/fin, mismo patrón que `GameCompletionService` y
`WalletReservationService`.

## Qué NO se construye (y por qué)

- **No se crea `GameParticipant` ni `GameReward` genéricos.** `game_participations`
  ya es el registro cross-game de resultado final (Fase 2 de `xp-logros-plan.md`,
  en producción) y `GamePrizeService`/`GamePrizeContract` ya es el pagador de
  premios cross-game (confirmado en `CHANGELOG_ARQUITECTURA.md`, Fase 1: "es, en
  efecto, el `GameReward` que la Fase 2 proponía crear — ya existe con otro
  nombre"). Construir estos dos de nuevo sería duplicar infraestructura probada.
- **No se migra Bingo ni Trivia a `game_sessions`.** Sus máquinas de estado
  (`bingo_rooms.status_id`, `trivia_rooms.status`/`trivia_games.status`) son
  maduras, están en producción con dinero real, y no tienen ningún problema que
  `game_sessions` resuelva mejor. Migrarlos "porque el plan original lo decía" es
  el tipo de sobre-arquitectura que este plan busca evitar explícitamente.
- **No se generaliza `game_status` (el catálogo que hoy usa Bingo) como estado de
  `game_sessions`.** Es tentador reusarlo, pero está acoplado a la UI de admin de
  Bingo (`room-status-chip`, popovers de auditoría) y a un catálogo con estados que
  no todos aplican a una sesión genérica (`REFUND`, `OFFLINE` son bingo-céntricos).
  `game_sessions.status` usa su propio enum corto, igual que hizo `WalletHold.status`
  con `HELD/CAPTURED/RELEASED` en vez de reusar `game_status`.
- **No se toca `WalletReservationService` ni `WalletHold`** más allá de agregar la
  FK física diferida que su propia migración ya anticipó.

## Fases

Cada fase es un commit/lote independiente, verificable por separado.

### Fase 1 — `game_sessions` mínima (sin tocar Bingo/Trivia)
Bajo riesgo. No engancha ningún flujo de dinero real todavía; solo crea la tabla y
cierra la referencia blanda que dejó `WalletHold`.

1. Migración `game_sessions`:
   - `id` (snowflake, mismo patrón que el resto del proyecto).
   - `game` (string corto, mismo formato que `game_participations.game` —
     `'cargas'`, sin enum de catálogo nuevo; se amplía el `string(20)` si hiciera
     falta más adelante).
   - `status` (string corto propio: `WAITING`, `RUNNING`, `FINISHED`,
     `CANCELLED` — no FK a `game_status`, ver justificación arriba).
   - `initiator_user_id` (nullable, FK a `users`) — quién inició la sesión, sin
     asumir todavía cuántos participantes tendrá una partida de Cargas (eso se
     decide cuando ese juego se diseñe en detalle; una `game_sessions` con
     `participants` hoy sería adivinar un dominio que no existe aún).
   - `entry_fee`/`prize_pool` **no se agregan en esta fase** — ver Fase 3.
     Agregarlos ahora sin un consumidor real sería la misma sobre-arquitectura que
     este plan evita en el resto de la propuesta.
   - `started_at`, `finished_at` (nullable).
   - `metadata` (json nullable) — para lo que un juego futuro necesite sin forzar
     una migración por cada campo específico (mismo patrón que `WalletHold.description`
     resolvió con un campo simple en vez de anticipar columnas).
   - `timestamps`.
2. Migración de alteración: `Schema::table('wallet_holds', ...)` agrega la FK física
   `game_session_id → game_sessions.id` (nullable, `nullOnDelete()` — un hold no
   debe desaparecer si la sesión se borra, solo perder la referencia), cerrando la
   nota pendiente que la propia migración de `wallet_holds` dejó escrita
   (`"se agrega la FK física en la migración que cree game_sessions"`).
3. Modelo `GameSession` (`app/Models/GameSession.php`): `SnowflakeId`, relación
   `holds(): HasMany` (`WalletHold`), scopes `active()`/`forGame()`. Sin lógica de
   negocio — la máquina de estados vive en el servicio, no en el modelo, siguiendo
   el mismo criterio que `WalletHold` (modelo delgado, `WalletReservationService`
   con la lógica).
4. `GameSessionService` (`app/Services/Games/GameSessionService.php`), esqueleto:
   `start(string $game, ?User $initiator, array $metadata = []): GameSession`,
   `finish(GameSession $session): GameSession`, `cancel(GameSession $session): GameSession`.
   Transiciones válidas únicamente `WAITING/RUNNING → FINISHED|CANCELLED` (fuera de
   esos casos, `DomainException`, mismo patrón que `WalletReservationService::capture()`/
   `release()` ya usa para holds ya resueltos).
5. **No se llama desde ningún lado todavía** — no hay juego real que lo use en esta
   fase, exactamente igual que Fase 1 de `xp-logros-plan.md` no enganchó Bingo/Trivia
   hasta tener los cimientos probados.

### Fase 2 — Puente opcional hacia `game_participations` (sin tocar Bingo/Trivia)
1. Agregar columna nullable `game_session_id` a `game_participations` (mismo patrón
   strangler fig: acompañante opcional, sin FK obligatoria, sin migrar los datos
   existentes de Bingo/Trivia que seguirán con `game_session_id = null`).
2. `GameSessionService::finish()` puede recibir opcionalmente los resultados por
   jugador y delegar en `GameCompletionService::reportParticipation()` por cada uno
   (pasando el `game_session_id` recién disponible) — **no reimplementa** el
   otorgamiento de XP/logros, solo lo invoca con el dato adicional. Si `finish()` se
   llama sin resultados (ej. sesión cancelada sin XP que otorgar), no llama a
   `GameCompletionService` en absoluto.
3. Documentar explícitamente en el modelo `GameParticipation` que `game_session_id`
   es opcional y hoy solo lo puebla un eventual juego que adopte `GameSession` —
   Bingo/Trivia lo dejan en null indefinidamente salvo decisión futura explícita.

### Fase 3 — Adopción por un juego real (requiere que exista un consumidor real antes de avanzar)
**No se implementa especulativamente.** Esta fase solo se activa cuando el diseño
concreto de "Transportistas vs Cazadores" (o cualquier otro juego de duración
variable) esté aprobado y se sepa qué necesita realmente:
- `entry_fee`/`prize_pool` en `game_sessions` (si ese juego los necesita
  normalizados; si el juego calcula esto por su cuenta como hace Bingo/Trivia hoy,
  no hace falta agregarlos nunca).
- Un modelo de participantes de sesión (`GameSessionParticipant` o similar) —
  **solo si** un jugador puede unirse/salir de una sesión en curso de forma
  independiente del resultado final (a diferencia de Bingo/Trivia donde la sala ya
  resuelve esto). No se diseña su esquema en este documento por ser especulativo
  sin el detalle real del juego.
- Enganche real de `WalletReservationService::hold()`/`capture()`/`release()` con
  `game_session_id` apuntando a una fila real de `game_sessions` (hoy el hold puede
  crearse con `game_session_id = null`, válido, pero sin trazabilidad de sesión).

### Fase 4 — Bingo/Trivia adoptan `GameSession` (NO planificada, solo si aparece una razón concreta)
Explícitamente fuera de alcance salvo que en el futuro se identifique un problema
real que `bingo_rooms`/`trivia_rooms` no resuelvan hoy (ninguno detectado durante
este análisis). Si se activa alguna vez: **requiere aprobación explícita antes de
tocar** — mismo criterio que Fase 3/4 de `xp-logros-plan.md`, porque toca las
tablas de sala de dos juegos con dinero real en producción. Hasta entonces, esta
fase no existe más que como nota de que se consideró y se descartó a propósito.

## Fuera de alcance de este plan

- Reemplazar o envolver `GameCompletionService`/`game_participations` — siguen
  siendo la fuente de verdad de "qué pasó al final de una partida".
- Reemplazar `GamePrizeService`/`GamePrizeContract` — sigue siendo el pagador de
  premios cross-game.
- Cualquier `GameLifecycleEvent`/bus de eventos con listeners automáticos — el
  proyecto no tiene `EventServiceProvider` y este plan no lo introduce.
- Migrar `bingo_rooms`/`trivia_rooms`/`trivia_games` a `game_sessions` — ver Fase 4.
- Diseñar el detalle de "Transportistas vs Cazadores" (roles, reglas, mecánica de
  intercepción) — eso es un plan de dominio de juego aparte, no de infraestructura.
- Generalizar `game_status` como catálogo de `game_sessions.status`.
- Agregar `entry_fee`/`prize_pool` a `game_sessions` sin un consumidor real (ver
  Fase 3) — evitar campos especulativos sin caso de uso.

## Nota para cuando exista un consumidor real (WebSocket)

La infraestructura de canales ya es completamente genérica y no requiere ningún
cambio de código para soportar un juego nuevo: `Broadcast::channel()` (Laravel) y
`useEchoChannel.ts` (frontend, `resources/js/composables/realtime/`) aceptan
cualquier nombre de canal como parámetro — no hay nada hardcodeado a Bingo/Trivia
que bloquee agregar canales de un juego futuro.

Lo único que no existe es una *convención* de nombres. Cuando el juego que consuma
`GameSession` (Fase 3) se diseñe, se sugiere seguir el mismo estilo que ya usan
Bingo/Trivia (`{juego}.{recurso}.{id}`, ver `routes/channels.php`) aplicado a la
sesión genérica, por ejemplo:

```
game.{gameSlug}.session.{sessionId}            (según el juego, público o privado)
game.{gameSlug}.session.{sessionId}.presence   (si el juego necesita presence)
player.{userId}.sessions                        (privado — eventos de TODAS las
                                                  sesiones activas del jugador,
                                                  cualquier juego)
```

Esto es solo una sugerencia de naming a aplicar cuando haga falta — no se crea
ningún canal ni código nuevo hoy, sería especular sobre un consumidor que no existe.

## Progreso

| Fase | Estado | Notas |
|---|---|---|
| Fase 1 — `game_sessions` mínima | ✅ Hecho | |
| Fase 2 — Puente opcional a `game_participations` | Pendiente | |
| Fase 3 — Adopción por un juego real | Parcial | `entry_fee`/`prize_pool`/`max_participants`/`min_level` se agregaron antes de tiempo, a pedido explícito del usuario, como consumidor de `MatchmakingService` (ver `progreso/matchmaking-plan.md`) — no por la adopción de un juego de dominio real como se planeaba originalmente aquí. Participantes de sesión también se construyeron (`game_session_participants`), pero como pieza de matchmaking, no de reglas de juego. |
| Fase 4 — Bingo/Trivia adoptan `GameSession` | Descartada | Sin problema real que lo justifique hoy; requiere aprobación explícita si se reconsidera |

## Changelog

- **2026-08-24**: plan creado, reemplazando el diseño original de Fase 2 ("Game
  Engine" con `GameSession`/`GameParticipant`/`GameReward` desde cero) de la
  iniciativa de arquitectura Wallet/Game Engine/Eventos. Verificado contra código
  real que `GameParticipant` y `GameReward` ya existen con otro nombre y más
  madurez (`game_participations`/`GameCompletionService` y
  `GamePrizeService`/`GamePrizeContract`, respectivamente — ver
  `progreso/xp-logros-plan.md` y `CHANGELOG_ARQUITECTURA.md`, Fase 1). El único gap
  real confirmado es la referencia blanda `WalletHold.game_session_id` sin tabla
  detrás, y la ausencia de un ancla de ciclo de vida "durante la partida" para
  juegos de duración variable que Bingo/Trivia no necesitan (cada uno ya resuelve
  su propio ciclo de vida con `bingo_rooms.status_id`/`trivia_rooms.status`).
  Decisión: construir `game_sessions` mínima ahora (cierra la referencia colgante),
  no migrar Bingo/Trivia (sin problema real que resolver ahí), y no construir
  participantes/premios de sesión ni `entry_fee`/`prize_pool` hasta que exista un
  consumidor real (Fase 3, bloqueada a propósito).

- **2026-08-24**: Fase 1 implementada y probada end-to-end.
  - Migración `game_sessions` (`id` snowflake, `game`, `status` enum propio
    `WAITING/RUNNING/FINISHED/CANCELLED`, `initiator_user_id` nullable FK a
    `users`, `started_at`/`finished_at`, `metadata` json).
  - Migración de alteración: FK física `wallet_holds.game_session_id →
    game_sessions.id` (`nullOnDelete`), cerrando la nota pendiente de la
    migración de `wallet_holds`.
  - **Corrección de tipo encontrada en el camino**: la migración original de
    `wallet_holds` (Fase 1 de la iniciativa Wallet) declaró `game_session_id` y
    `captured_movement_id` como `string(32)`, pero los IDs snowflake reales del
    proyecto son `unsignedBigInteger` (`snowflake()`/`foreignSnowflake()` en
    `dive-be/laravel-snowflake`) — la FK habría fallado por mismatch de tipo.
    Corregido a `unsignedBigInteger` en ambas columnas antes de que la
    migración se compartiera (no estaba comiteada todavía). Rollback + re-migrate
    aplicado en dev sin pérdida de datos (la tabla estaba vacía tras la limpieza
    de pruebas de la Fase 1 de Wallet).
  - Modelo `GameSession` (`app/Models/GameSession.php`) + `WalletHold::gameSession()`
    (relación inversa).
  - `GameSessionService` (`app/Services/Games/GameSessionService.php`):
    `start()`/`run()`/`finish()`/`cancel()`, transiciones validadas con
    `DomainException` en estado inválido — mismo patrón que
    `WalletReservationService`. Sin ningún caller todavía, a propósito.
  - Validado vía `artisan tinker`: ciclo `start→run→finish`, doble-`finish()`
    lanza excepción, `WalletHold` con `game_session_id` real funciona en ambas
    direcciones de la relación, y `nullOnDelete()` se comporta como se
    documentó (borrar la sesión no borra el hold, solo limpia la referencia).
    Datos de prueba eliminados de la BD tras validar.
