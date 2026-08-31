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

## 🔵 Por implementar/anexar — pendiente

- Feature flags `powerups`/`chat`/`spectator_mode` en `config/trivia.php`
  están en `false` sin ningún código que los consuma; columnas
  `power_ups`/`power_ups_used` en `trivia_participants` sin lógica asociada.
  No se tocó: es una decisión de producto (¿se implementa o se retira el
  esquema?), no un bug.
- Estado `paused` de `TriviaGame` documentado en `$fillable` pero nunca
  producido ni consumido por ninguna transición. No se implementó una
  función de pausa (por ejemplo, ante desconexión masiva) porque no hay
  especificación de comportamiento esperado — requiere decisión de producto.
- No existe mecanismo de disputa/reversión de un premio ya pagado
  (`prize_status = claimed`). Requiere decisión de producto sobre el
  proceso de disputa, no solo código.

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

## 🔴 Crítico — pendiente

- `canStart()` compara `status === 'active'` pero las partidas se crean con
  `'playing'` — la guarda nunca dispara, permite doble inicio de partida.
- Falta lock en `StartGameAction` contra inicio concurrente.
- `forceClose()`/`destroy()` del admin no reembolsan entry fee ya cobrado.
- Guard idempotente faltante en `JoinRoomAction` contra doble cobro.
- Manejo de doble respuesta no implementado en `SubmitAnswerAction`.
- Empates en el ranking final no se detectan como empates reales.

*(Esta sección se actualiza a medida que avanza la remediación en esta misma sesión.)*
