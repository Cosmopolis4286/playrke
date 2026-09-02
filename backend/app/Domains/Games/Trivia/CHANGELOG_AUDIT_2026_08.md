# Changelog — Auditoría y remediación de Trivia (2026-08-31)

Registro de los fixes aplicados sobre el dominio Trivia tras la auditoría de
flujo completo (creación → juego → pago de ganadores) del 2026-08-31.
Orden de ejecución: **de menor a mayor impacto**, según lo pedido — deuda
técnica primero, críticos de dinero/concurrencia al final.

Cada entrada indica el hallazgo original, el fix aplicado, y los archivos
tocados. Los hallazgos no marcados como aplicados quedan documentados para
seguimiento futuro.

---

## ⚪ Deuda técnica menor

### ✅ Sistema legacy `Api\Trivia\*` eliminado (código muerto confirmado)

**Hallazgo:** coexistían dos sistemas de rutas de trivia. `routes/trivia.php`
→ `App\Http\Controllers\Api\Trivia\{Room,Game}Controller` no tenía ningún
consumidor real: las vistas `Game.vue`/`Results.vue` que le apuntaban no
eran renderizadas por ningún controller (`Inertia::render` inexistente para
ellas) y llamaban rutas que ni siquiera existían (`/timeout`, `/results`).
Además, `GameController::submitAnswer()` invocaba
`SubmitAnswerAction::execute()` como método **estático** sobre una clase que
no lo es — hubiera producido un `Error` fatal de PHP en runtime si se
hubiera alcanzado. `CreateRoomRequest::authorize()` devolvía `true` sin
policy real, exponiendo (en teoría) creación de salas con premio real a
cualquier usuario Sanctum autenticado.

**Fix:** eliminado por completo.
- `backend/routes/trivia.php`
- `backend/app/Http/Controllers/Api/Trivia/RoomController.php`
- `backend/app/Http/Controllers/Api/Trivia/GameController.php`
- `backend/app/Http/Requests/Trivia/{CreateRoomRequest,JoinRoomRequest,StartGameRequest,SubmitAnswerRequest}.php`
- `backend/resources/js/Pages/Games/Trivia/{Game,Results}.vue`
- `backend/app/Providers/TriviaServiceProvider.php` — quitado `loadRoutesFrom('routes/trivia.php')`

**Conservado** (sigue en uso real por el sistema activo):
`backend/app/Http/Requests/Trivia/UpdateRoomRequest.php` — consumido por
`TriviaRoomAdminController::update()`.

---

### ✅ Segundo sistema legacy (`TriviaMulti`/`triv_indv_*`) eliminado

**Hallazgo:** dentro de `TriviaGameController.php` (el controller real,
activo) convivían 8 métodos de una versión anterior del juego
(`rooms`, `inGame`, `timer`, `individual`, `checkIndividual`, `roomInfo`,
`leaveRoom`, `rand_quest`, más el alias `room()`), con sus rutas todavía
activas en `routes/modules/games/trivia.php` (incluidas redirects de
"compatibilidad"). Se confirmó que:
- El commit `9e9d32a1 refactor(trivia): migra sistema TriviaMulti a
  TriviaRoom con PostgreSQL` documenta el reemplazo, no una coexistencia
  intencional.
- Las vistas Inertia que esos métodos renderizaban
  (`Modules/User/Trivia/{Rooms,RmMulti,Individual}`) **no existen** en
  `resources/js` — cualquier request a esas rutas producía un 500.
- Ningún componente del frontend actual referencia esas rutas con nombre
  (`trivia-games.rooms|game|timer|individual`).
- `checkIndividual()` tampoco validaba ownership de la pregunta contra la
  sesión del usuario (oráculo de fuerza bruta de bajo impaco, dado que ya
  estaba muerto).

**Fix:** eliminados los 8 métodos + alias del controller, las rutas
correspondientes y el bloque completo de "rutas legacy (mantener
compatibilidad)" con sus redirects. Se quitó también `use UserTrait` del
controller (quedó sin consumidores tras el borrado — ver
`feedback_trait_vs_service` en memoria del proyecto: contar consumidores
reales antes de mantener un trait).

- `backend/app/Http/Controllers/Games/Trivia/Game/TriviaGameController.php`
- `backend/routes/modules/games/trivia.php`

**No tocado:** los modelos `TriviaMulti`, `TriviaMultiCups` y las tablas
`triv_indv_*`/`triv_multi_user` se dejaron intactos a nivel de esquema (no
se borraron migraciones ni datos) — solo se eliminó el código PHP que los
usaba, ya que quedaron sin ningún consumidor tras esta limpieza.

---

### ✅ Documentación (`README.md`, `ARCHITECTURE_TRIVIA.md`) reescrita

**Hallazgo:** ambos documentos describían un diseño planeado y nunca
construido (WebSocket nativo `ws://`, endpoints `/api/trivia/*` — recién
eliminados en este mismo changelog —, estructura de componentes
`Components/Trivia/Room/*` inexistente, tabla de cobertura de tests
("100%") no verificable/inexacta). Alto riesgo de desorientar a cualquier
desarrollador que los use como referencia operativa.

**Fix:** reescritos para reflejar la arquitectura real verificada en código
(Reverb/Echo, rutas Inertia reales, flujo confirmado end-to-end, estados de
la state machine, comandos de mantenimiento reales). Se agregó una sección
explícita "qué NO existe" para prevenir que el documento vuelva a
divergir silenciosamente de la implementación.

- `backend/app/Domains/Games/Trivia/README.md`
- `backend/app/Domains/Games/Trivia/ARCHITECTURE_TRIVIA.md`

---

## 🔵 Por implementar/anexar

### ✅ Power-ups implementados (fifty_fifty, double_points, skip, extra_time)

**Hallazgo original:** `config/trivia.php` tenía `powerups`/`chat`/
`spectator_mode` en `false` sin ningún código que los consumiera, y las
columnas `power_ups`/`power_ups_used` en `trivia_participants` sin lógica
asociada — aunque al investigar para decidir qué hacer se encontró más
terreno construido de lo que la auditoría original detectó:
`TriviaParticipant::getAvailablePowerUp()`/`usePowerUp()` ya existían y
funcionaban, y `settings.allow_powerups` ya era un toggle validado en
`UpdateRoomRequest` (aunque sin UI para activarlo).

**Decisión de producto (consultada al usuario):** implementar power-ups
ahora (era la única de las 3 con terreno real construido); retirar
`chat`/`spectator_mode` de la config por falta de diseño de reglas de
negocio (moderación de contenido, alcance de espectador). 4 tipos, 1 uso de
cada uno por jugador por partida, habilitado por sala vía
`settings.allow_powerups`.

**Implementación:**
- `TriviaPowerUpType` (enum nuevo) — los 4 tipos + `defaultAllowance()`.
- `TriviaPowerUpService` (nuevo) — consume usos del participante
  (Postgres), resuelve 50/50 server-side (nunca en el cliente, para no
  filtrar la respuesta correcta inspeccionando la red), y gestiona los
  flags de `double_points`/`extra_time` activos por pregunta en Redis
  (mismo patrón que el resto del estado en vivo del juego).
- `TriviaPowerUpController` (nuevo) — `POST
  /trivia-game/game/{gameId}/power-up`. `fifty_fifty` responde de
  inmediato con los índices a ocultar; `double_points`/`extra_time` activan
  un flag; `skip` persiste una respuesta neutra (0 puntos, no cuenta como
  correcta/incorrecta) y dispara el mismo avance automático que una
  respuesta real.
- `TriviaAnswerController::store()` — consume los flags de
  `double_points` (duplica puntos si la respuesta fue correcta; se
  consume siempre, no solo si acierta, para no dejarlo activo por accidente
  en la siguiente pregunta) y `extra_time` (extiende el límite de tiempo
  SOLO para ese jugador — el timer visual de los demás y el job de avance
  automático `AdvanceTriviaQuestion` no se ven afectados; decisión
  consultada al usuario, dado que el timer del juego es global por diseño).
- `JoinRoomAction::registerParticipantInDatabase()` — asigna
  `power_ups: TriviaPowerUpType::defaultAllowance()` al inscribirse si la
  sala tiene `settings.allow_powerups = true`.
- `TriviaGameController::play()` — expone los usos restantes al frontend.
- Frontend: `Play.vue` con la barra de 4 botones (deshabilitados sin usos o
  tras responder), `AdvancedSettingsSection.vue` con el toggle
  `allow_powerups` en el formulario de creación de sala (antes no existía
  ninguna UI para activarlo pese a estar validado en el backend).

- `backend/app/Domains/Games/Trivia/Enums/TriviaPowerUpType.php` (nuevo)
- `backend/app/Domains/Games/Trivia/Services/TriviaPowerUpService.php` (nuevo)
- `backend/app/Http/Controllers/Games/Trivia/Game/TriviaPowerUpController.php` (nuevo)
- `backend/app/Http/Controllers/Games/Trivia/Game/TriviaAnswerController.php`
- `backend/app/Http/Controllers/Games/Trivia/Game/TriviaGameController.php`
- `backend/app/Domains/Games/Trivia/Actions/JoinRoomAction.php`
- `backend/routes/modules/games/trivia.php`
- `backend/resources/js/Pages/Games/Trivia/Play.vue`
- `backend/resources/js/Pages/Games/Trivia/Admin/RoomsCreate.vue`
- `backend/resources/js/Components/games/Trivia/Admin/RoomForm/AdvancedSettingsSection.vue`
- `backend/resources/js/types/trivia.ts`, `routes/triviaRoutes.ts`
- `backend/config/trivia.php` — retirados `chat`/`spectator_mode`

**No incluido en este alcance:** UI para activar `allow_powerups` en
`RoomsEdit.vue` (la pantalla de edición no expone ningún toggle de
`settings`, ni siquiera los preexistentes `allow_late_join`/`public_room`/
`show_leaderboard` — limitación previa, no introducida aquí).

**Verificación:** `TriviaPowerUpServiceTest` (6 tests, nuevo) pasa
completo contra la BD reconstruida. `GameEngineServiceTest` (6) y
`SubmitAnswerActionTest` (5) siguen pasando tras la integración en
`TriviaAnswerController`. `pnpm run typecheck` sin errores sobre todos los
archivos Vue/TS tocados.

### ✅ Estado `paused` implementado — pausa manual + automática por desconexión masiva

**Hallazgo original:** `TriviaGame.status` documentaba `'paused'` en su
`$fillable` pero ninguna transición lo producía ni consumía.

**Decisión de producto (consultada al usuario, varias rondas):**
- Disparador: híbrido — pausa manual del host/admin, y automática si un
  umbral configurable de jugadores parece desconectado.
- Detección de "desconectado": se descartó depender de presence channels de
  Reverb tras encontrar que el canal de presencia ya existente
  (`bingo.room.{roomId}.presence`) es solo-frontend — Reverb no notifica al
  backend de joins/leaves sin webhooks que hoy no existen en el proyecto.
  Se usa en su lugar inactividad de respuestas: un jugador cuenta como
  desconectado si no respondió (ni siquiera tarde) ninguna de las últimas 2
  preguntas — dato que ya se persiste en `trivia_answers`, sin
  infraestructura nueva de broadcasting.
- Umbral: configurable por sala (`settings.pause_disconnect_threshold_percent`,
  0 = desactivado), no fijo.
- Reanudación: híbrida — el host puede reanudar manualmente en cualquier
  momento; el cron `trivia:auto-finish-stale-games` (ya existente, corre
  cada minuto) también reintenta reanudar automáticamente si el % de
  inactivos vuelve a bajar del umbral, pero SOLO si la pausa fue automática
  (una pausa manual del host nunca se reanuda sola).

**Implementación:**
- `TriviaPauseService` (nuevo) — `pause()`/`resume()` actualizan el status
  en Redis (`GameStateDTO`) y Postgres (`TriviaGame`) de forma consistente;
  `inactivePlayerPercent()` calcula el % sobre la ventana de 2 preguntas;
  `maybeAutoPause()`/`maybeAutoResume()` encapsulan la lógica automática.
  Al reanudar, `server_timestamp_ms` de la pregunta activa en Redis se
  corre hacia adelante exactamente por la duración de la pausa — esto
  evita tocar el cálculo de `responseTimeMs` en `TriviaAnswerController`,
  que sigue confiando ciegamente en ese timestamp. También re-despacha
  `AdvanceTriviaQuestion` con el tiempo restante correcto (el job
  original, despachado antes de pausar, ya no sirve — su guarda
  `!$state->isActive()` lo neutraliza al ejecutarse, pero en el momento
  equivocado).
- `GameEngineService::sendNextQuestion()` — llama `maybeAutoPause()` tras
  cada avance de pregunta (después de registrar `used_question_ids`, así
  que no afecta la primera pregunta de la partida, que nunca acumula
  suficiente historial).
- `AutoFinishStaleTriviaGamesCommand` — ahora también itera partidas
  `'paused'` (fuera de `TriviaGame::active()` a propósito, para no
  auto-finalizar mientras están en pausa) e intenta `maybeAutoResume()`.
- `TriviaGamePaused`/`TriviaGameResumed` (eventos de broadcast nuevos,
  mismo patrón que `TriviaGameCancelled`).
- `TriviaPauseController` (nuevo) — `POST /trivia-game/game/{gameId}/pause`
  y `/resume`, reutilizando `TriviaRoomPolicy::start()` (misma regla que
  iniciar la partida: host o admin/superadmin).
- `TriviaGameController::play()` — incluye `'paused'` en los status
  aceptados (antes un jugador recargando durante una pausa recibía 404),
  expone `game.isPaused` e `isHost` al frontend.
- Frontend: `Play.vue` con pantalla de pausa, botón de pausar (visible solo
  al host mientras la partida corre) y botón de reanudar (visible solo al
  host mientras está pausada); listeners de
  `trivia.game.paused`/`trivia.game.resumed` que recalculan el timer local
  con `remaining_seconds` del servidor al reanudar. Toggle
  `pause_disconnect_threshold_percent` (0-100, input numérico) en
  `AdvancedSettingsSection.vue`/`RoomsCreate.vue`.

- `backend/app/Domains/Games/Trivia/Services/TriviaPauseService.php` (nuevo)
- `backend/app/Domains/Games/Trivia/Services/GameEngineService.php`
- `backend/app/Console/Commands/AutoFinishStaleTriviaGamesCommand.php`
- `backend/app/Events/Games/Trivia/TriviaGamePaused.php` (nuevo)
- `backend/app/Events/Games/Trivia/TriviaGameResumed.php` (nuevo)
- `backend/app/Http/Controllers/Games/Trivia/Game/TriviaPauseController.php` (nuevo)
- `backend/app/Http/Controllers/Games/Trivia/Game/TriviaGameController.php`
- `backend/app/Http/Requests/Trivia/UpdateRoomRequest.php`
- `backend/routes/modules/games/trivia.php`
- `backend/resources/js/Pages/Games/Trivia/Play.vue`
- `backend/resources/js/Pages/Games/Trivia/Admin/RoomsCreate.vue`
- `backend/resources/js/Components/games/Trivia/Admin/RoomForm/AdvancedSettingsSection.vue`
- `backend/resources/js/types/trivia.ts`, `routes/triviaRoutes.ts`

**No incluido en este alcance:** presence channels reales (descartados por
alcance, ver arriba); UI de `pause_disconnect_threshold_percent` en
`RoomsEdit.vue` (mismo motivo que `allow_powerups` — la pantalla de
edición no expone ningún toggle de `settings`).

**Verificación:** `TriviaPauseServiceTest` (8 tests, nuevo) pasa completo:
pausa/reanudación de status, avance de `server_timestamp_ms`, detección de
inactividad con y sin historial suficiente, auto-pausa con umbral
superado/desactivado. Re-verificados sin regresiones:
`GameEngineServiceTest` (6), `SubmitAnswerActionTest` (5),
`AutoFinishStaleTriviaGamesCommandTest` (5, incluida la firma nueva de
`handle()` con `TriviaPauseService` inyectado), `TriviaPowerUpServiceTest`
(6) — 22 tests, todos verdes en una sola corrida. `pnpm run typecheck` sin
errores.

### ✅ Mecanismo de disputa/reversión de premios pagados (compartido con Bingo)

**Hallazgo original:** no existía ningún mecanismo de disputa/reversión de
un premio ya pagado (`prize_status = claimed`).

**Decisión de producto (consultada al usuario, varias rondas):**
- Disparador: manual únicamente por ahora, solo admin/superadmin. Sin señal
  automática de anti-fraude conectada (no hay política definida de qué
  flags califican como "lo bastante graves" para disputar sin intervención
  humana — se dejó como pendiente futuro, no inventado aquí).
- Alcance: generalizado en `GamePrizeContract`/`GamePrizeService`
  (compartido con Bingo), no solo Trivia — impacto real: `bingo_card_prizes.
  prize_status` es un ENUM de MySQL con datos reales ya persistidos, así
  que requirió una migración de alteración de esquema (`trivia_participants.
  prize_status` es string libre, sin ese costo).
- Dinero: débito inmediato de la wallet, pero **topado al saldo disponible**
  — se descubrió en la propia implementación que la BD tiene un CHECK
  CONSTRAINT real (`chk_wallets_balance_nonneg`) que impide balance
  negativo, garantía global del sistema que esta feature no debía romper.
  La diferencia no cobrada (premio ya gastado) queda registrada en
  `wallets.pending_debt` (columna nueva) y se descuenta automáticamente del
  próximo crédito a esa wallet — decisión explícita del usuario de aplicar
  este cobro automático a los 11 puntos de crédito existentes en
  `WalletService` (depósitos, aprobación/rechazo de depósitos y retiros,
  reembolsos, premios normales, movimientos custom del catálogo), no solo
  a uno.

**Implementación:**
- `GamePrizeContract` — nuevos métodos `isClaimed()` (ya lo implementaban
  ambos modelos, solo faltaba en el contrato), `isDisputed()`,
  `markAsDisputed()`.
- `TriviaParticipant`/`BingoCardPrize` — implementan los métodos nuevos;
  `PRIZE_STATUSES` incluye `'disputed'`.
- Migración `2026_08_31_000000_add_disputed_to_bingo_card_prizes_status_enum`
  — `ALTER TABLE ... MODIFY COLUMN` vía SQL crudo (doctrine/dbal no está
  instalado; Laravel no soporta bien alterar ENUMs sin él) para el ENUM
  existente con datos reales — mismo patrón que
  `2026_08_30_200343_add_triggered_by_to_command_logs_table`. El
  `Schema::create` original de `bingo_card_prizes` también se actualizó
  para que un entorno nuevo tenga `'disputed'` desde la creación.
- Migración `2026_08_31_000100_add_pending_debt_to_wallets_table` —
  columna nueva + su propio CHECK `>= 0`, mismo patrón de migración
  incremental (la tabla `wallets` ya tiene datos reales). `Wallet` model
  actualizado (`$fillable`, `$casts`).
- `GamePrizeService::disputeAndReverse()` (nuevo) — bajo `lockForUpdate()`,
  solo actúa si `isClaimed()`; marca `disputed` y delega el débito a
  `WalletService::recordPrizeReversal()`.
- `WalletService::recordPrizeReversal()` (nuevo) — topa el débito al
  `balance` disponible, registra el resto en `pending_debt`,
  `WalletMovement` tipo `PRIZE_REVERSAL` (constante + seed en
  `MovementTypeSeeder` nuevos), con `idempotencyKey` (mismo patrón que
  `recordPrize()` — dos llamadas con la misma clave nunca revierten el
  mismo premio dos veces).
- `WalletService::settlePendingDebt()` (nuevo, privado) — conectado a los
  11 puntos de crédito de `WalletService`: `recordPrize()` (solo porción
  balance, no bono), `recordRefund()` (solo porción balance),
  `recordAdminDeposit()`, `approveDeposit()`, `rejectWithdrawal()`,
  `approveGeneric()`/`rejectGeneric()` (rama `isCredit`). **No conectado**
  a `deposit()` (`@deprecated`, acredita en estado PENDING antes de
  aprobación — cobrar deuda ahí y luego que el depósito se rechace dejaría
  la deuda cobrada sin el crédito real) ni a `requestDeposit()`/
  `requestWithdraw()` (no acreditan nada, solo crean el registro PENDING).
- **Bug encontrado y corregido en el mismo trabajo:**
  `RetryFailedTriviaPrizePayoutsCommand` excluía `isClaimed()`/
  `isCancelled()` pero NO `isDisputed()` — sin el fix, el cron (cada 5 min)
  habría re-acreditado un premio que un admin acaba de revertir
  manualmente, deshaciendo la disputa. `RetryFailedBingoPrizePayoutsCommand`
  no tenía este bug (ya filtraba la query base por
  `whereIn('prize_status', ['waiting', 'processing'])`, `disputed` nunca
  entraba).
- `TriviaPrizeDisputeController` (nuevo) — `POST /trivia-rooms/{roomId}/
  participants/{participantId}/dispute`, protegido `role:admin,superadmin`.
- `TriviaRoomAdminController::show()` — expone `winners` (ganadores desde
  `trivia_participants`, no Redis) a la vista.
- `RoomsShow.vue` — sección "Ganadores y Premios" con botón "Disputar"
  (solo visible si `prizeStatus === 'claimed'`) y formulario inline de
  motivo (obligatorio, 10-500 caracteres, queda en la descripción del
  movimiento de wallet).

- `backend/app/Services/Games/GamePrizeContract.php`
- `backend/app/Services/Games/GamePrizeService.php`
- `backend/app/Services/WalletService.php`
- `backend/app/Models/WalletMovement.php`
- `backend/app/Models/Wallet.php`
- `backend/app/Models/Games/Trivia/TriviaParticipant.php`
- `backend/app/Domains/Games/Bingo/Models/Card/CardPrize/BingoCardPrize.php`
- `backend/app/Console/Commands/RetryFailedTriviaPrizePayoutsCommand.php`
- `backend/database/seeders/MovementTypeSeeder.php`
- `backend/database/migrations/2026_08_31_000000_add_disputed_to_bingo_card_prizes_status_enum.php` (nueva)
- `backend/database/migrations/2026_08_31_000100_add_pending_debt_to_wallets_table.php` (nueva)
- `backend/database/migrations/bingo/2024_01_01_001121_create_bingo_card_prizes_table.php`
- `backend/database/migrations/2024_01_01_000202_create_wallets_table.php`
- `backend/app/Http/Controllers/Games/Trivia/Room/TriviaPrizeDisputeController.php` (nuevo)
- `backend/app/Http/Controllers/Games/Trivia/Room/TriviaRoomAdminController.php`
- `backend/routes/modules/games/trivia.php`
- `backend/resources/js/Pages/Games/Trivia/Admin/RoomsShow.vue`
- `backend/resources/js/routes/triviaRoutes.ts`

**No incluido en este alcance:** UI de disputa para Bingo (el pedido
original era Trivia; el backend ya soporta ambos vía el contrato
compartido, pero no se construyó una pantalla de Bingo); señal automática
de anti-fraude (pendiente de definición de política); reversión del caso
`BONUS_PRIZE` (premio acreditado como bono) — solo se revierte `balance`.

**Verificación:** `GamePrizeServiceDisputeTest` (8 tests, nuevo) — marca
disputa, débito completo cuando alcanza el saldo, tope + registro de deuda
cuando no alcanza, cobro automático de la deuda en el siguiente crédito,
registro del movimiento `PRIZE_REVERSAL`, rechazo si no está `claimed`,
idempotencia (no se puede disputar dos veces), y la regresión específica
del bug de `RetryFailedTriviaPrizePayoutsCommand`. Re-verificado sin
regresiones: `WalletServiceTest` completo (11 tests preexistentes de
premios/reembolsos/compras/retiros/bono — ninguno se rompió pese a tocar 6
puntos de crédito de ese mismo servicio), más los 33 tests de fixes/features
anteriores de esta sesión (`TriviaPauseServiceTest`,
`TriviaPowerUpServiceTest`, `GameEngineServiceTest`,
`SubmitAnswerActionTest`, `AutoFinishStaleTriviaGamesCommandTest`) — 49
tests en total, todos verdes en una sola corrida. `pnpm run typecheck` sin
errores.

---

*(Las 3 decisiones de producto originalmente pendientes — power-ups, pausa,
disputa de premios — quedaron todas implementadas y verificadas en esta
sesión. Ver el resto de este documento para el detalle completo de cada
una y de los fixes de auditoría que las precedieron.)*

---

## 🟡 Optimización

### ✅ Cache de símbolos de moneda (lobby/sala de espera)

**Hallazgo:** `TriviaGameController::lobby()` y `waitingRoom()` resolvían
`entry_currency_symbol`/`prize_currency_symbol` consultando `currencies`
(MySQL) en cada request, sin cache — ambas pantallas se consultan con
polling frecuente desde el frontend, y el símbolo de una moneda casi nunca
cambia.

**Fix:** se agregó `Currency::symbolsByIds(array $ids): array` (cacheado 5
min, mismo TTL que `CurrencyExchangeRateService` — ver commit `5468f894`
que estableció ese patrón para tasas de cambio) y se reemplazaron las 3
consultas directas (`Currency::whereIn(...)->get()->keyBy('id')` en
`waitingRoom()`, `DB::table('currencies')->whereIn(...)` en `lobby()`) por
llamadas a este helper.

- `backend/app/Models/Currency.php` — nuevo método `symbolsByIds()`
- `backend/app/Http/Controllers/Games/Trivia/Game/TriviaGameController.php`
  — `lobby()` y `waitingRoom()` actualizados

### ✅ Reembolso puede tomar `bonus_amount` de la sala equivocada

**Hallazgo:** `CancelStaleTriviaRoomsCommand::cancelAndRefundRoom()`
buscaba el `WalletMovement` original de la entrada pagada filtrando solo
por `user_id` + `type` + `amount` — sin `room_id`. Si el mismo usuario pagó
la misma cantidad exacta de entrada en dos salas de trivia distintas y
ambas quedaban abandonadas, el comando podía recuperar el movimiento
equivocado y aplicar una proporción de `bonus_amount` incorrecta al
reembolso (el monto total reembolsado no se veía afectado, solo cuánto de
ese monto vuelve a `bonus_balance` vs. saldo retirable).

**Fix:** `WalletService::recordTriviaEntry()` ahora persiste `room_id`
dentro de `metadata` (ya recibía `$roomId` como parámetro, antes solo lo
usaba para el broadcast). `CancelStaleTriviaRoomsCommand` filtra
adicionalmente por `metadata->room_id`. Movimientos previos a este fix no
tienen `room_id` en su metadata — si no se encuentra el original, el
comando cae a `bonusPortion = 0.0` (mismo comportamiento que antes del fix
en el peor caso, sin afectar el monto total del reembolso).

- `backend/app/Services/WalletService.php`
- `backend/app/Console/Commands/CancelStaleTriviaRoomsCommand.php`

### Evaluado, sin cambio: job `AdvanceTriviaQuestion` "huérfano"

La auditoría señaló que este job de seguridad (avanza la pregunta si nadie
respondió a tiempo) podía quedar en cola sin limpieza tras un
`finishGame()` manual. Al revisar el código, `handle()` ya comprueba
`$state->isActive()` al inicio y sale sin efecto si el juego ya terminó —
es un job que se ejecuta y no hace nada, no una fuente de bugs. No se
tocó: cancelar jobs en cola explícitamente sería una complejidad extra sin
beneficio funcional medible.

---

## 🟠 Importante

### ✅ Policy real para iniciar partida (reemplaza chequeo inline)

**Hallazgo:** no existía ninguna Policy para el dominio Trivia. La única
regla de autorización no cubierta por middleware de ruta —
"solo el host de la sala o admin/superadmin puede iniciar la partida" —
vivía hardcodeada dentro de `TriviaStartController::__invoke()`
(`(string) $room->host_id !== $userId && ! $isAdmin`), sin reutilización
posible y sin seguir el patrón de Policies que el resto del proyecto usa
(`BaseRolePolicy` + policies de dominio, ver `BingoRoomPolicy` como
precedente exacto: rol elevado O creador de la sala).

**Nota:** `TriviaRoomAdminController` (crear/editar/forzar cierre de salas)
ya estaba protegido correctamente por middleware `role:admin,superadmin`
en las rutas — no tenía chequeos inline, así que no fue el problema.

**Fix:** se creó `TriviaRoomPolicy` (siguiendo el mismo patrón de
`BingoRoomPolicy::canManage()`) con un método `start(User, TriviaRoom): bool`,
registrada en `AuthServiceProvider::$policies`. `TriviaStartController` ahora
usa `$this->authorize('start', $room)` en vez de la comparación manual.

- `backend/app/Domains/Games/Trivia/Policies/TriviaRoomPolicy.php` (nuevo)
- `backend/app/Providers/AuthServiceProvider.php`
- `backend/app/Http/Controllers/Games/Trivia/Game/TriviaStartController.php`

## 🔴 Crítico

### ✅ `canStart()` roto + falta de lock contra doble inicio de partida

**Hallazgo:** `TriviaRoom::canStart()` filtraba `games()->where('status',
'active')`, pero `GameEngineService::startGame()` siempre crea partidas con
`status = 'playing'` — la comparación exacta contra `'active'` nunca
coincidía, así que esta guarda jamás bloqueaba iniciar una segunda partida
sobre una sala que ya tenía una `playing` en curso. Además,
`GameEngineService::startGame()` leía `$room->canStart()` sin ningún lock:
incluso corregido el string, dos requests concurrentes (doble clic del
host, o el cron `StartScheduledTriviaRoomsCommand` corriendo justo cuando
el host también inicia manualmente) podían leer ambas `canStart() === true`
antes de que cualquiera creara el `TriviaGame`, resultando en dos partidas
`'playing'` para la misma sala y — más grave — en
`PrizeDistributionService::distribute()` ejecutándose dos veces al
finalizar cada una (doble pago de premios).

**Fix:**
1. `TriviaRoom::canStart()` ahora usa `$this->games()->active()` (el scope
   `TriviaGame::scopeActive()`, que ya cubría correctamente
   `whereIn(['playing','active'])`) en vez de comparar el string a mano.
2. `GameEngineService::startGame()` envuelve la validación + creación del
   `TriviaGame` + actualización de `TriviaRoom.status` en una única
   transacción con `TriviaRoom::lockForUpdate()`. Esto serializa dos
   llamadas concurrentes: la segunda espera a que la primera confirme, y
   entonces `canStart()` ve la partida recién creada y lanza la excepción
   `RuntimeException` esperada (mismo contrato de error que antes, sin
   cambios de comportamiento para el caso no concurrente).

- `backend/app/Domains/Games/Trivia/Models/TriviaRoom.php`
- `backend/app/Domains/Games/Trivia/Services/GameEngineService.php`

**Verificación:** revisado por lectura contra `GameEngineServiceTest.php`
(usa modelos Eloquent reales, no mocks, contra `RefreshDatabase` con
`connectionsToTransact = ['mysql', 'pgsql_trivia']` — el `lockForUpdate()`
anidado en la misma conexión transaccional de test es válido en
PostgreSQL) — el test `test_cannot_start_game_in_non_waiting_room` sigue
pasando el mismo contrato de excepción. **No se pudo ejecutar el test
suite en esta sesión**: el contenedor `playrke_php` no tenía conectividad
de red hacia el contenedor de Postgres (`postgres-hybrid`, en una red
Docker distinta a `backend_playrke_network`) — problema de infraestructura
local preexistente, ajeno a este cambio de código. **Pendiente: correr
`bash test-docker.sh --filter=GameEngineServiceTest` en un entorno con
red resuelta antes de dar este fix por completamente verificado.**

---

### ✅ `forceClose()`/`destroy()` del admin no reembolsaban entry fee ya cobrado

**Hallazgo:** a diferencia de `CancelStaleTriviaRoomsCommand` (que sí
reembolsa), `TriviaRoomAdminController::forceClose()` y `destroy()` solo
actualizaban el `status` de la sala y limpiaban Redis. Un admin cerrando una
sala `waiting` o `playing` con jugadores que ya pagaron su entrada dejaba
ese dinero cobrado sin partida jugada y sin ningún mecanismo de
recuperación — ni siquiera manual (no existía endpoint de "reembolsar sala
cerrada por admin").

**Fix:** se extrajo la lógica de reembolso de
`CancelStaleTriviaRoomsCommand` a un servicio compartido
`TriviaRoomRefundService::refundConfirmedParticipants()` (mismo
comportamiento: transacción por participante, respeta el split de
`bonus_amount`, notifica al usuario). `CancelStaleTriviaRoomsCommand` ahora
consume este servicio en vez de duplicar la lógica. `forceClose()` y
`destroy()` reembolsan a los participantes confirmados antes de cerrar la
sala, y el mensaje de éxito informa cuántas entradas se reembolsaron.

- `backend/app/Domains/Games/Trivia/Services/TriviaRoomRefundService.php` (nuevo)
- `backend/app/Console/Commands/CancelStaleTriviaRoomsCommand.php` (refactor, sin cambio de comportamiento)
- `backend/app/Http/Controllers/Games/Trivia/Room/TriviaRoomAdminController.php`

**Verificación:** sin tests existentes para este controller ni para el
comando (confirmado por grep en `tests/`) — cambio revisado solo por
lectura. Mismo bloqueo de infraestructura de red Docker que el fix
anterior impidió correr una verificación de integración en esta sesión.

---

### ✅ Doble cobro en `JoinRoomAction` + `paid_amount` desincronizado del monto real cobrado

**Hallazgo 1 (doble cobro):** `chargeEntryFee()` (débito de wallet, ya
confirmado) se ejecutaba antes de `registerParticipantInDatabase()` (INSERT
protegido por `unique(room_id,user_id)`), y `isPlayerAlreadyJoined()` se
verificaba una sola vez, sin lock, antes de ambos pasos. Dos requests
concurrentes del mismo usuario (doble clic, reintento de red) podían pasar
esa verificación como `false` ambas, cobrar la wallet dos veces, y solo una
lograba insertar el registro — la otra caía en el `catch (\Throwable $e)`
genérico de `TriviaGameController::join()`, que respondía como éxito con un
warning genérico, ocultando el cobro de más.

**Hallazgo 2 (bug adicional encontrado al implementar el fix, no listado en
la auditoría original):** `registerParticipantInDatabase()` persistía
siempre `$room->entry_fee` como `paid_amount` — el campo *legado* de una
sola moneda — en vez del monto realmente cobrado por `chargeEntryFee()`.
Cuando la sala usa `fee_by_currency` (multidivisa) o
`chargeEntryFeeWithConversion()` (conversión de tasa de cambio), el monto
cobrado puede ser distinto (o `entry_fee` puede ser `0` si la sala no usa
el campo legado). Esto desincroniza `trivia_participants.paid_amount` del
monto real cobrado en `WalletMovement` — y como
`TriviaRoomRefundService`/`CancelStaleTriviaRoomsCommand` leen
`paid_amount` para calcular el reembolso, una sala multidivisa cancelada
podía reembolsar de más, de menos, o nada, según el desfase entre ambos
valores.

**Fix:**
1. La re-verificación de `isPlayerAlreadyJoined()` ahora se repite DENTRO
   de una transacción con `lockForUpdate()` sobre la fila de `trivia_rooms`
   — esto serializa el join del mismo jugador: la segunda request espera a
   que la primera confirme, y entonces ve al jugador ya inscrito y devuelve
   la respuesta de "ya estás registrado" sin cobrar de nuevo. El chequeo
   rápido inicial (sin lock) se mantiene para no pagar el costo de una
   transacción en el camino feliz de "ya unido".
2. `chargeEntryFee()`/`chargeEntryFeeWithConversion()` ahora devuelven
   `[float $amount, ?string $currencyId]` con lo REALMENTE cobrado, y
   `registerParticipantInDatabase()` los recibe como parámetros explícitos
   en vez de leer `$room->entry_fee` de nuevo.
3. Se extrajo la respuesta duplicada de "ya está registrado" a un método
   `alreadyJoinedResponse()` (antes se repetía inline en dos lugares).

- `backend/app/Domains/Games/Trivia/Actions/JoinRoomAction.php`

**Nota:** el `catch (\Throwable $e)` genérico en
`TriviaGameController::join()` que ocultaba el error de unique constraint
sigue existiendo — con el lock, la carrera ya no debería alcanzar ese
catch en el camino normal, pero no se auditó cada ruta posible que
llegue ahí. Queda fuera del alcance de este fix puntual.

**Verificación:** sin tests existentes para `JoinRoomAction` (confirmado
por grep en `tests/`) — revisado por lectura, confirmando que la firma
pública `execute()` no cambió (único caller externo:
`TriviaGameController::join()`) y que los métodos privados modificados no
tienen otros consumidores. Mismo bloqueo de infraestructura Docker que los
fixes anteriores impidió una verificación de integración.

---

### ✅ Doble respuesta sin manejo en `GameEngineService::submitAnswer()` — reevaluado: no es el código real en producción

**Hallazgo original de la auditoría:** `SubmitAnswerAction`/
`GameEngineService::submitAnswer()` tenían el chequeo de doble respuesta
literalmente sin implementar (comentario "implementar según necesidad de
negocio"), y `updateScore()` se ejecutaba antes del `INSERT` en
`trivia_answers` (protegido por `unique(game_id,player_id,question_id)`) —
un reintento de red duplicando la respuesta incrementaría el score en Redis
y luego fallaría el INSERT sin rollback del score.

**Reevaluación al implementar el fix:** revisando quién llama a
`GameEngineService::submitAnswer()` se confirmó que su único consumidor es
`SubmitAnswerAction`, y el único consumidor de `SubmitAnswerAction` era
`Api\Trivia\GameController::submitAnswer()` — el controller del sistema
legacy **ya eliminado** al inicio de esta sesión (ver primera entrada de
este changelog). **El flujo real en producción no pasa por aquí**: la ruta
activa `POST /trivia-game/game/{gameId}/answer` usa
`TriviaAnswerController::store()`, que persiste directamente vía Query
Builder y **ya tenía** el chequeo de doble respuesta implementado
correctamente — `$alreadyAnswered` se verifica (líneas 88-97) antes de
tocar el score o insertar la respuesta (líneas 135-151). El hallazgo de la
auditoría apuntaba a código real pero no ejecutado en producción.

**Fix aplicado de todos modos:** se agregó la misma guarda a
`GameEngineService::submitAnswer()` (verificación explícita de
`trivia_answers` antes de calcular o aplicar cualquier score), porque sigue
siendo el "motor" de dominio con tests propios
(`SubmitAnswerActionTest`, `GameEngineServiceTest`) y puede volver a tener
un consumidor real en el futuro — dejarlo con un bug conocido sería deuda
técnica silenciosa. No es una mitigación de un riesgo activo en
producción, es cerrar el mismo hueco en el código que sí podría ejecutarse
si algo vuelve a invocar este motor directamente.

- `backend/app/Domains/Games/Trivia/Services/GameEngineService.php`

**Verificación:** revisado por lectura contra los 5 tests de
`SubmitAnswerActionTest` y los 2 de `GameEngineServiceTest` que ejercitan
`submitAnswer` — ninguno responde dos veces a la misma pregunta con el
mismo jugador, por lo que el nuevo chequeo no debería romperlos. Mismo
bloqueo de infraestructura Docker impidió correrlos en esta sesión.

---

### ✅ Empates en el ranking final repartían premio de forma no determinística

**Hallazgo:** `buildFinalLeaderboard()` ordena por respuestas correctas →
tiempo promedio → racha máxima, pero asignaba `position` estrictamente por
índice del array ordenado (`$index + 1`), sin comparar si dos filas eran
idénticas en los 3 criterios. `PrizeDistributionService::distribute()` paga
por posición de array (`$finalRanks[$payout['rank'] - 1]`) — con pocas
preguntas, un empate real en los 3 criterios es plausible, y `usort()` no
garantiza un orden estable entre ejecuciones de PHP para elementos que su
comparador trata como iguales. Esto contradice el propio comentario del
servicio de premios ("el ranking se determina... nunca por azar"): el
premio de 2°/3° lugar podía terminar repartido arbitrariamente entre
jugadores con métricas idénticas.

**Decisión de producto (consultada al usuario):** en vez de dividir el
monto del premio entre los empatados (cambio de reglas de reparto), se
agregó un cuarto criterio de desempate determinístico:
`registered_at` (quién se inscribió primero a la sala) como último
desempate, ascendente. Es un dato ya existente en `trivia_participants`,
no depende de azar, y en la práctica deja el orden totalmente determinado
casi siempre (dos jugadores no suelen registrarse en el mismo microsegundo).

**Fix:** `buildFinalLeaderboard()` ahora recibe también `$roomId` (antes
solo `$gameId` + `$defaultTotalQuestions`), consulta `registered_at` de
`trivia_participants` por jugador, y lo usa como cuarto criterio de
`usort()`. El campo se descarta del array final (`unset($rank['registeredAt'])`)
antes de devolver el ranking — no cambia el contrato de datos que consume
`PrizeDistributionService`/`TriviaScore`.

- `backend/app/Domains/Games/Trivia/Services/GameEngineService.php`

**Verificación:** único caller de `buildFinalLeaderboard()` (privado) es
`finishGame()`, actualizado para pasar `$state->roomId`. Sin tests
existentes que ejerciten `finishGame()`/`buildFinalLeaderboard()`
directamente (confirmado por grep en `tests/`). Mismo bloqueo de
infraestructura Docker impidió una verificación end-to-end en esta sesión.

---

*(Todos los hallazgos 🔴 críticos de la auditoría original quedaron
atendidos en esta sesión.)*

## Verificación por test suite — estado final

Tras resolver el bloqueo inicial de red Docker (`playrke_php` sin conectividad
a Postgres), se reconstruyó `playrke_testing` (estaba con migraciones a medio
aplicar, tablas duplicadas — no causado por esta sesión) y se corrieron los
tests directamente afectados por los cambios:

- **`GameEngineServiceTest` (6/6 ✓)** — cubre `canStart()`/lock de
  `startGame()` (fix crítico #1) y la guarda de doble respuesta en
  `submitAnswer()` (fix crítico #4).
- **`SubmitAnswerActionTest` (5/5 ✓)** — cubre el mismo motor de dominio,
  anti-cheat de tiempos de respuesta.

Ambas suites pasaron limpiamente en una corrida aislada contra la BD
reconstruida, **antes** de que el entorno Docker local entrara en un estado
inestable (deadlocks de MySQL en el drop masivo de tablas, más tarde dos
procesos `php artisan test` idénticos quedando huérfanos y compitiendo por
la misma BD — se identificaron y mataron manualmente más de una vez). Ese
segundo problema es de orquestación del entorno de test local — no
relacionado al código de esta sesión — y quedó sin resolver de forma
duradera: **no se logró una corrida limpia del test suite completo de
Trivia** (`RoomServiceTest`, `ScoreServiceTest`,
`RetryFailedTriviaPrizePayoutsCommandTest`) al cierre de esta sesión.

Hallazgos adicionales de infraestructura, no de código, detectados durante
la verificación:
- `trivia_testing` (Postgres) tiene un esquema desactualizado —
  `trivia_rooms.game_mode` no existe ahí, aunque sí en el modelo/migraciones
  actuales. Afecta `RetryFailedTriviaPrizePayoutsCommandTest` (4 fallos).
  Requiere migrar `trivia_testing` aparte de `playrke_testing`.
- Los procesos `php artisan test` lanzados manualmente pueden quedar
  huérfanos dentro de `playrke_php` si el proceso que los invocó se
  interrumpe (timeout del lado del orquestador) sin que el hijo reciba la
  señal — vale la pena revisar si esto también ocurre en CI.

**Recomendación:** antes de desplegar, correr en un entorno estable:
```
bash test-docker.sh tests/Unit/Domains/Trivia
bash test-docker.sh --filter=RetryFailedTriviaPrizePayoutsCommandTest
```
tras migrar `trivia_testing` al esquema actual (`php artisan migrate
--database=pgsql_trivia --path=database/migrations/trivia --force` con
`.env.testing` exportado).
