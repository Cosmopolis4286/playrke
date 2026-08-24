# Changelog — Arquitectura de plataforma (Wallet / Game Engine / Eventos)

Bitácora de progreso de la iniciativa "preparar la plataforma para que Bingo y Trivia compartan infraestructura común (wallet, XP, eventos, matchmaking), antes de construir el futuro juego Transportistas vs Cazadores". El plan completo de arquitectura vive en `C:\Users\Eisker\.claude\plans\claro-te-preparo-un-crispy-seal.md` (fuera del repo, referencia local de la sesión que lo generó).

Roadmap por fases: **0.Estabilidad → 1.Wallet → 2.Game Engine → 3.Eventos → 4.XP/Rewards → 5.WebSocket → 6.Matchmaking → 7.Consolidación Bingo/Trivia → 8.Prototipo Cargas → 9.Roles avanzados → 10.Mercado**.

Con las fases 0-6 cerradas (ver abajo), el trabajo pasó de "preparar infraestructura" a "usar la infraestructura para crecer el negocio" — ver `progreso/estrategia-producto-plan.md` (bitácora separada, de producto/negocio, no de arquitectura técnica) y su análisis de referencia `progreso/estrategia-producto-analisis.md`.

---

## 2026-08-24 — Fase 0: Estabilidad

- **Bug `entry_fee`/`entry_fee_usd` en Trivia**: ya resuelto por el propio equipo (migración `2026_08_22_211001_add_legacy_entry_fee_columns_to_trivia_rooms_table.php`, endurecida con checks `hasColumn()`/`hasIndex()` por columna para tolerar reintentos parciales). `TriviaSummaryService` seguía documentando el bug como "fuera de alcance" — queda desactualizado, revisar si conviene actualizar ese comentario cuando se toque ese archivo de nuevo.
- **Regla de arquitectura MySQL↔Postgres documentada**: se agregó un bloque de documentación a nivel de clase en `backend/app/Services/WalletService.php` explicitando que `DB::transaction()` nunca es atómico entre la conexión default (MySQL) y `pgsql_trivia`, y fijando el patrón estándar (escribir en la base dueña → aplicar efecto con idempotency key determinística → dejar que `failed_jobs`/panel de Jobs sea la red de reintento → reconciliación de solo-alerta, nunca auto-corrección). Este patrón ya existía de facto en `recordPrize()`/`recordTriviaEntry()`; ahora está declarado como regla explícita para que cualquier juego futuro (incluyendo Cargas) lo siga sin tener que redescubrirlo.

**Pendiente de Fase 0**: ninguno más — se pasa a Fase 1 (Wallet: `WalletHold`, `getAvailableBalance()`, historial de wallet en ficha admin, outbox formalizado).

---

## 2026-08-24 — Fase 1: Wallet (en curso)

- **`WalletHold`** (reserva de saldo comprometido en una partida en curso, sin tocar `balance`/`bonus_balance` mientras está activa): modelo nuevo `app/Models/WalletHold.php` + migración `2026_08_24_120000_create_wallet_holds_table.php` (tabla `wallet_holds`, CHECK `amount > 0`, sin FK física a `game_sessions` todavía porque esa tabla no existe hasta Fase 2 — se agrega cuando exista). Estados: `HELD → CAPTURED | RELEASED`.
- **`Wallet::getAvailableBalance()` / `hasSufficientAvailableBalance()`**: saldo jugable (bono + retirable) menos holds activos. No reemplaza `getPlayableBalance()` — se usa para decidir si se puede *abrir* una nueva reserva, mientras que `getPlayableBalance()` sigue siendo correcto para operaciones que resuelven un hold ya existente sobre esa misma wallet.
- **`WalletService::recordHoldCapture()`** (nuevo método): aplica el débito real al capturar un hold, reutilizando el mismo patrón que `recordCardPurchase`/`recordTriviaEntry` (lock, `consumeBonusFirst`, `WalletMovement`, broadcast fuera de la transacción). Se agregó como método de `WalletService` (no reimplementado en `WalletReservationService`) para no duplicar la lógica de negocio de débito que ya vive ahí.
- **`WalletReservationService`** (nuevo, `app/Services/WalletReservationService.php`): orquesta `hold()`/`capture()`/`release()`. `capture()` delega el efecto de wallet a `WalletService::recordHoldCapture()` y solo gestiona el ciclo de vida del `WalletHold` en sí (evita que dos servicios reimplementen el mismo débito con reglas divergentes).
- **Validado contra la base de desarrollo real** (no hay entorno de test separado corriendo): flujo hold→release y hold→capture probados de punta a punta vía `artisan tinker` — saldo disponible se descuenta/restaura correctamente, `WalletMovement` se genera con `reference_id`/`metadata.wallet_hold_id` apuntando al hold, doble-resolución de un hold ya resuelto lanza `DomainException` como se espera. Datos de prueba limpiados de la BD tras validar.
- **Nota para quien use holds en un juego nuevo** (ej. futuro Cargas): `capture()` requiere un código de `MovementType` ya sembrado en el catálogo (`movement_types.code`, FK real) — un juego nuevo necesita su propio código de movimiento antes de poder capturar holds con un `type` propio, no hay un tipo genérico "HOLD_CAPTURE".

**Corrección al plan original — el outbox de Trivia ya existía**: al buscar el punto de integración para "formalizar" el outbox, se encontró que **ya está completamente construido y en producción**, con más madurez de la que el plan de arquitectura asumía:
- `App\Services\Games\GamePrizeService` + `GamePrizeContract` — FSM `waiting→processing→claimed` e idempotencia, usado tanto por Bingo (`BingoCardPrize`) como por Trivia (`TriviaParticipant` vía `PrizeDistributionService`). Es, en efecto, el `GameRewardService` que la Fase 2 de este plan proponía crear — ya existe con otro nombre.
- `PrizeDistributionService::payoutOne()` documenta explícitamente por qué no envuelve el pago en una transacción conjunta (motores de BD distintos) — la misma regla que se documentó en `WalletService` en Fase 0, ya aplicada aquí de antes.
- `RetryFailedTriviaPrizePayoutsCommand` / `RetryFailedBingoPrizePayoutsCommand` (`trivia:retry-failed-prize-payouts`, `bingo:retry-failed-prize-payouts`) — programados cada 5 minutos (`routes/console.php`), registran su ejecución vía `CommandService::logCommandExecution()` (visible en el panel Jobs/Scheduler ya existente). Es el mecanismo de reintento/reconciliación que el plan proponía construir.

No se creó ningún Job ni comando nuevo para esto — sería duplicar infraestructura ya validada en producción. **Acción tomada**: ninguna, más allá de documentar el hallazgo aquí y en `ESTADO_ECOSISTEMA.md`, para que la Fase 2 (Game Engine) diseñe `GameReward` como una capa que delegue en `GamePrizeService` en vez de reemplazarlo.

**Segunda corrección al plan original — el historial de wallet en ficha admin tampoco era un gap real**: la exploración inicial marcó esto como pendiente mirando solo `AdminPlayerContextService.php` (que es el listado paginado, no tiene ficha de detalle). Al buscar dónde vive realmente la "ficha" de un jugador se encontró que **ya existe y ya incluye historial de wallet**: `UserDetailModal.vue` + `useAdminUserDetail.ts` (compartidos con la ficha de `AdminUser`) ya renderizan balance, `recent_movements` con "Cargar más" paginado (`GET /admin/user/{userId}/wallet-history`), nivel/XP, logros y seguridad/anti-cheat. `Pages/Roles/Admin/Player/Index.vue` ya importa y usa ese mismo modal — confirmado en el código, no hace falta construir nada.

**Fase 1: CERRADA.** Las tres piezas planeadas (`WalletHold`, outbox de Trivia, historial de wallet en ficha admin) quedaron resueltas: la primera con código nuevo, las otras dos porque ya existían con más madurez de la que el plan original asumía. Dos de tres correcciones de este tipo en una sola fase es una señal a tener en cuenta para las fases siguientes: antes de construir algo en Fase 2+, buscar primero si ya existe.

---

## 2026-08-24 — Fase 2: Game Engine (rediseñada y cerrada)

**Hallazgo mayor antes de escribir código**: existe `progreso/xp-logros-plan.md` en la raíz del repo — una iniciativa previa, **ya completada** el 2026-07-21 (7 fases, con su propio changelog), que resolvió gran parte de lo que esta Fase 2 planeaba desde cero:
- `GameCompletionService` (`app/Services/Games/`) ya es el punto único donde Bingo y Trivia reportan resultado final de partida, ya otorga XP/logros, ya está enganchado en producción en `BingoDrawService::claimCardWin()`/`reportGamesPlayed()` y `GameEngineService::finishGame()` (Trivia).
- `game_participations` (tabla + modelo) ya es el registro cross-game de resultado — el `GameParticipant` que el plan original de Fase 2 proponía crear.
- `GamePrizeService`/`GamePrizeContract` (documentados en la corrección de Fase 1 arriba) ya es el `GameReward` que se planeaba crear.
- Sistema de XP/nivel global, logros, notificaciones de level-up en tiempo real, racha de login — todo ya construido y probado end-to-end.

**Decisión**: en vez de proceder con el diseño original (`GameSession`/`GameParticipant`/`GameReward` completos, migración de Bingo/Trivia), se pausó, se lanzó un agente de planificación con este contexto completo, y se produjo un plan reducido: `progreso/game-engine-plan.md` (mismo formato que `xp-logros-plan.md`, para que ambos documentos de planificación del proyecto sean consistentes entre sí).

**Alcance final de Fase 2** (ver `progreso/game-engine-plan.md` para el detalle completo): el único gap real era que `WalletHold.game_session_id` (creado en Fase 1) es una referencia blanda sin tabla detrás. Se construyó únicamente:
- Migración `game_sessions` (mínima: `id`, `game`, `status` enum propio `WAITING/RUNNING/FINISHED/CANCELLED` — no reutiliza el catálogo `game_status` de Bingo, que está acoplado a su UI —, `initiator_user_id`, `started_at`/`finished_at`, `metadata` json). Sin `entry_fee`/`prize_pool`, sin tabla de participantes — se agregan solo cuando exista un juego real que los necesite (Fase 3 de ese plan, bloqueada a propósito).
- FK física `wallet_holds.game_session_id → game_sessions.id` (`nullOnDelete`), cerrando la nota pendiente de Fase 1.
- Modelo `GameSession` + `GameSessionService` (`start/run/finish/cancel`, sin caller todavía — mismo criterio que Fase 1 de `xp-logros-plan.md`, cimientos antes que enganche).
- **Bingo y Trivia NO se tocan ni se migran** — decisión explícita, documentada como "Fase 4: Descartada" en `game-engine-plan.md`.

**Bug encontrado y corregido en el camino**: la migración de `wallet_holds` (Fase 1) había declarado `game_session_id`/`captured_movement_id` como `string(32)`, pero los IDs snowflake reales del proyecto son `unsignedBigInteger` — la FK habría fallado por mismatch de tipo. Corregido antes de comitear (rollback + re-migrate en dev, sin pérdida de datos).

**Validado end-to-end vía tinker**: ciclo `start→run→finish`, transición inválida lanza `DomainException`, relación `WalletHold ↔ GameSession` funciona en ambas direcciones, `nullOnDelete()` se comporta como se documentó.

**Fase 2: CERRADA**, con alcance muy por debajo del plan original — la lección de Fase 1 ("buscar antes de construir") se confirmó de nuevo, esta vez a mayor escala.

---

## 2026-08-24 — Fase 3: Eventos (cerrada, sin código de infraestructura nuevo)

**Antes de proponer un `GameLifecycleEvent`/tabla `game_events`** (el diseño original de esta fase), se investigó si ya existía algo equivalente — mismo criterio que Fase 2. Resultado: **ya existen cuatro sistemas cross-juego maduros y en producción**, todos con el mismo patrón de diseño (servicio único, llamado explícitamente por cada juego en su punto de interés, sin bus de eventos ni `EventServiceProvider`):

1. **`GameCompletionService`** (Fase 2/xp-logros-plan.md) — resultado final de partida + XP + logros.
2. **`GamePrizeService`/`GamePrizeContract`** (documentado en Fase 1) — pago de premios con FSM e idempotencia.
3. **`WinEventService`/`WinEvent`/`WinEventBroadcasted`** (`app/Domains/Platform/WinEvent/`, no se conocía antes de esta fase) — fuente única para el feed de ganancias en tiempo real de toda la plataforma (tabla `platform_win_events`, canal público `platform.win-events`), con `game_slug` ya en el payload. Bingo y Trivia ya reportan aquí (`PrizeDistributionService::announceTopWinner()`, visto en Fase 1).
4. **`AntiCheatService`/`AntiCheatFlag`** (`app/Services/Games/AntiCheatService.php`, no se conocía antes de esta fase) — señales sospechosas cross-juego con severidad acumulada y auto-suspensión, agregando por usuario sin importar el juego a propósito.

También existe `App\Enums\Games\Common\GameSlug` (`BINGO`/`TRIVIA`), el identificador games-agnostic estándar que ya usan `AntiCheatService` y `GamePrizeService`.

**Conclusión**: no hay ningún log genérico de "todo evento de ciclo de vida" (join/leave/started sin premio ni flag) — pero tampoco evidencia de que algún consumidor lo necesite hoy. Construirlo ahora sería la sobre-arquitectura que el usuario pidió evitar explícitamente. **No se creó ninguna infraestructura nueva de eventos.**

**Único cambio real de esta fase**: se agregó `GameSlug::CARGAS = 'cargas'` (+ su `label()`) al enum ya existente — preparación de bajo riesgo para que el futuro juego tenga un identificador consistente con el resto de servicios cross-juego desde el día en que se empiece a diseñar, sin crear ninguna tabla ni fila de catálogo nueva (el juego en sí sigue sin diseñarse).

**Fase 3: CERRADA.**

---

## 2026-08-24 — Fase 5: WebSocket (cerrada, sin código nuevo)

Investigado antes de proponer cambios: la infraestructura de canales ya es completamente genérica. `useEchoChannel.ts` (`resources/js/composables/realtime/`, frontend) acepta cualquier nombre de canal/evento como parámetro — no hay nada hardcodeado a Bingo/Trivia. Del lado backend, `Broadcast::channel()` (Laravel) tiene el mismo comportamiento. No hace falta ningún código nuevo para que un juego futuro tenga sus propios canales.

Lo único que no existía era una *convención* de nombres para cuando `GameSession` (Fase 2) tenga un consumidor real — se documentó como sugerencia en `progreso/game-engine-plan.md` (sección "Nota para cuando exista un consumidor real"), sin crear ningún canal ni código, para no especular sobre un juego que todavía no está diseñado.

**Fase 5: CERRADA.**

---

## 2026-08-24 — Fase 6: Matchmaking (construido robusto, a pedido explícito)

A diferencia de las fases anteriores, esta sí era un gap real sin trabajo previo: no existía ningún matchmaking en el proyecto (Bingo/Trivia resuelven "unirse a sala" con lógica simple propia). Por defecto habría quedado bloqueada por falta de consumidor real (mismo criterio que Fase 3/4 de `progreso/game-engine-plan.md`), pero el usuario pidió explícitamente construirlo "robusto y fuerte, todo lo necesario". Documentado en detalle en `progreso/matchmaking-plan.md` (nuevo). Resumen:

- **`game_sessions`** ganó columnas reales indexables para matchmaking: `entry_fee`, `currency_id`, `prize_pool`, `max_participants`, `min_level` (migración de alteración — la migración original de `game_sessions` ya estaba commiteada por otra sesión, no se editó directamente).
- **`game_session_participants`** (tabla + modelo nuevo): participación EN CURSO en una sesión, `JOINED`/`LEFT` — deliberadamente distinta de `game_participations` (que sigue siendo el registro de resultado final ya usado por Bingo/Trivia). Necesaria porque matchmaking debe saber el cupo ocupado *ahora*, algo que `game_participations` no puede responder.
- **`user_reputations` + `ReputationService`** (nuevo): score 0-100 derivado **exclusivamente de señales que ya existen** — severidad acumulada de `anti_cheat_flags`, suspensión activa, volumen de partidas limpias en `game_participations`. Decisión consultada con el usuario: score simple cacheado, sin ledger de historial de cada cambio (se consideró y se descartó por complejidad sin caso de uso todavía).
- **`MatchmakingService`** (nuevo): `findCandidateSessions()` (filtra en SQL por rango de entrada/moneda, luego por elegibilidad real del jugador), `isEligible()` (nivel, reputación mínima vía `metadata`, cupo, no duplicar participación), `join()`/`leave()`.

**Corrección durante la implementación**: se detectó trabajo concurrente de otra sesión en el mismo repositorio (commits `feat(wallet/holds)`, `feat(games/game-session)`, `docs: bitácora...` ya en el historial sin que esta sesión los hubiera creado) — confirmado con el usuario como esperado, no se tomó ninguna acción correctiva más allá de verificar que el working tree local seguía consistente antes de seguir escribiendo migraciones.

**Validado end-to-end vía `artisan tinker`**: filtrado por rango de entrada, cupo lleno bloquea nuevos joins, cupo se libera al salir, doble-join lanza excepción, filtrado por reputación mínima funciona. Datos de prueba eliminados de la BD tras validar.

**Fase 6: CERRADA.**

Con esto, todas las fases del roadmap original (0 a 6) quedan cerradas — restan solo el prototipo de Cargas y los roles avanzados, explícitamente fuera de alcance de esta preparación de infraestructura desde el inicio.

---

## Cómo usar este archivo

Cada entrada nueva va arriba (orden cronológico descendente), agrupada por fecha y fase. Registrar solo lo que cambió realmente en el código — no repetir el diseño ya descrito en el plan de arquitectura, solo qué se implementó, qué se descartó, y por qué si difiere de lo planeado.
