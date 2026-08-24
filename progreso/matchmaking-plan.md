# Plan: Matchmaking mínimo sobre GameSession

> Estado del sistema antes de este plan: `game_sessions` (ver
> `progreso/game-engine-plan.md`) existía en su forma mínima — sin
> `entry_fee`/`prize_pool`/cupo, sin consumidor real. No existía ningún
> sistema de matchmaking en el proyecto (Bingo/Trivia resuelven "unirse a
> sala" con lógica simple propia, sin criterios de nivel/entrada/reputación).
> Tampoco existía ningún sistema de reputación de jugador.

## Objetivo

Dar a `GameSession` un matchmaking real (no un esqueleto) para que un futuro
juego de duración variable pueda ofrecer "sesiones disponibles" filtradas por
rango de entrada, moneda, nivel mínimo, cupo y reputación mínima, sin que
cada juego tenga que reimplementar ese filtrado.

## Principio de diseño

Mismo principio que las fases anteriores de esta iniciativa: reutilizar
señales que ya existen en el sistema en vez de inventar sistemas paralelos.
La reputación, en particular, se calculó **exclusivamente a partir de datos
que el proyecto ya registra** (`game_participations`, `anti_cheat_flags`,
`user_suspensions`) — no se le pidió al jugador ningún dato nuevo ni se creó
un sistema de puntuación arbitrario desconectado de su comportamiento real.

El matchmaking no decide reglas de juego (cuándo pasar de `WAITING` a
`RUNNING`, cuántos jugadores hacen falta para arrancar) — eso sigue siendo
responsabilidad de cada juego vía su propio `GameRulesEngine` (cuando exista
uno). `MatchmakingService` solo resuelve dos preguntas: "¿qué sesiones le
puedo ofrecer a este jugador?" y "¿puede entrar a esta sesión concreta?".

## Qué se construyó

### 1. `game_sessions` — columnas de matchmaking
Migración `2026_08_24_140000_add_matchmaking_columns_to_game_sessions_table.php`:
`entry_fee`, `currency_id` (FK a `currencies`), `prize_pool`, `max_participants`,
`min_level`. Columnas reales e indexables — el filtrado por rango ocurre en
SQL, no iterando `metadata` json en PHP.

Deliberadamente **no** se agregó `min_reputation` como columna: se decidió
que viviera en `metadata.min_reputation` porque ningún consumidor real pidió
todavía filtrar por rango de reputación en SQL (a diferencia de
`entry_fee`/`min_level`, que si tienen ese caso de uso desde el diseño
original del documento de visión del usuario). Si en el futuro hace falta
indexar por reputación mínima, se promueve a columna con la misma migración
de alteración que ya se usó dos veces en esta iniciativa.

### 2. `game_session_participants` — participación EN CURSO
Migración `2026_08_24_140001_create_game_session_participants_table.php` +
modelo `GameSessionParticipant`. Deliberadamente **distinta** de
`game_participations` (que sigue siendo, sin cambios, el registro de
RESULTADO FINAL que usa `GameCompletionService`): una sesión de duración
variable necesita saber cuántos jugadores la ocupan *ahora* para no
ofrecerla llena, algo que `game_participations` no puede responder porque
solo existe una fila ahí cuando la partida ya terminó.

`status` distingue `JOINED` (activo, ocupa cupo) de `LEFT` (salió antes del
cierre — ej. abandono). No se borra la fila al salir: conserva quién estuvo
en la sesión, útil si más adelante se quiere derivar una señal real de
"abandono" para reputación (hoy no existe esa señal, ver más abajo).

### 3. `user_reputations` + `ReputationService`
Migración `2026_08_24_140002_create_user_reputations_table.php` + modelo
`UserReputation` + `app/Services/Games/ReputationService.php`.

Score 0-100, persistido (no recalculado on-the-fly en cada query de
matchmaking — se deriva de agregados sobre `anti_cheat_flags`/
`game_participations`, caro de recalcular por request). Cálculo:

- Base 100.
- `-2` puntos por cada punto de severidad acumulada en `anti_cheat_flags`
  (todo el historial del usuario, no solo la ventana de 24h que usa
  `AntiCheatService` para auto-suspensión — para reputación interesa el
  patrón completo).
- `-40` puntos fijos si el usuario tiene una `UserSuspension` activa ahora
  mismo.
- Hasta `+10` puntos de bonus por volumen de partidas jugadas sin
  incidentes (`game_participations`, escala hasta 50 partidas).
- Clamp final a `[0, 100]`.

`getScore()` devuelve el valor cacheado (crea el registro en 100 si no
existe, sin recalcular). `recalculate()` fuerza el cálculo — **no se
enganchó automáticamente a ningún evento todavía** (ver "Fuera de alcance").

**Decisión explícita, consultada con el usuario**: no se construyó un
ledger de reputación con historial de cada cambio (como `wallet_movements`)
— un score simple recalculable es suficiente para esta primera versión, sin
la complejidad de auditar cada variación para algo que ningún juego usa
todavía.

**Limitación conocida**: no existe ninguna señal real de "abandono" hoy.
`game_participations` solo registra partidas que `GameCompletionService`
reportó al *cerrar* la partida — un jugador que se va a mitad de camino y
nunca se reporta como terminado no genera ninguna fila, ni positiva ni
negativa. `game_session_participants.status = LEFT` sí podría ser esa señal
en el futuro, pero conectarla a `ReputationService` no se hizo en esta fase
por no tener todavía un juego real generando esos datos.

### 4. `MatchmakingService`
`app/Services/Games/MatchmakingService.php`:

- `findCandidateSessions(string $game, User $user, array $criteria)`: filtra
  `GameSession` en `WAITING` por `min_entry_fee`/`max_entry_fee`/`currency_id`
  en SQL, luego filtra en memoria por elegibilidad real del jugador
  (`isEligible()`) — se traen `limit * 3` candidatas antes de filtrar para
  no quedarse cortos si varias se descartan por elegibilidad.
- `isEligible(GameSession, User)`: valida estado `WAITING`, `min_level`
  (contra `User::getCurrentLevel()`, ya existente), `metadata.min_reputation`
  (contra `ReputationService::getScore()`), cupo disponible
  (`GameSession::hasAvailableSlot()`), y que el jugador no esté ya
  participando activamente.
- `join()`/`leave()`: gestionan `GameSessionParticipant`. `join()` lanza
  `DomainException` si no es elegible (mismo patrón que
  `WalletReservationService`/`GameSessionService`). `leave()` marca `LEFT`
  sin borrar la fila.

## Fuera de alcance de este plan

- Conectar `ReputationService::recalculate()` a algún trigger automático
  (evento de fin de partida, cron periódico) — hoy se llama manualmente o
  bajo demanda. Se decide cuándo/cómo enganchar esto cuando haya un
  consumidor real que lo necesite recalculado con cierta frecuencia.
- Derivar una señal real de "abandono" desde `game_session_participants` y
  usarla en el cálculo de reputación.
- Criterios de matchmaking por región (mencionado en el documento de visión
  original del usuario) — no hay ningún concepto de región/latencia en el
  proyecto hoy, agregarlo sería sobre-arquitectura sin caso de uso.
- UI de administración para ver/ajustar manualmente la reputación de un
  usuario — no se pidió, no se construyó.
- Un endpoint HTTP para exponer matchmaking al frontend — este plan entrega
  el servicio de dominio; la ruta/controller se agrega cuando un juego real
  lo consuma.

## Progreso

| Pieza | Estado |
|---|---|
| Columnas de matchmaking en `game_sessions` | ✅ Hecho |
| `game_session_participants` (tabla + modelo) | ✅ Hecho |
| `user_reputations` + `ReputationService` | ✅ Hecho |
| `MatchmakingService` (`findCandidateSessions`/`isEligible`/`join`/`leave`) | ✅ Hecho |
| Validado end-to-end vía `artisan tinker` | ✅ Hecho |

## Changelog

- **2026-08-24**: plan creado e implementado en la misma sesión, a pedido
  explícito del usuario ("constrúyelo robusto y fuerte, todo lo necesario"),
  ampliando el alcance original de Fase 6 de la iniciativa de arquitectura
  (que por defecto habría quedado bloqueada por falta de consumidor real,
  mismo criterio aplicado a Fase 3/4 de `game-engine-plan.md`). Validado
  end-to-end vía `artisan tinker`: filtrado por rango de entrada, cupo
  lleno/liberado, doble-join bloqueado, filtrado por reputación mínima.
  Datos de prueba eliminados de la BD tras validar.
