# Plan: XP real y Logros funcionales (Bingo + Trivia)

> Estado del sistema antes de este plan: infraestructura completa (fórmula de nivel,
> servicios, 19 logros seedeados) pero **desconectada** — nadie dispara XP ni evalúa
> logros al jugar. Ver diagnóstico completo en la conversación de auditoría de `/profile`.

## Objetivo

Que un jugador gane XP real al jugar/ganar partidas de Bingo y Trivia, y que los
logros del catálogo se desbloqueen automáticamente cuando se cumplen sus condiciones,
sin tocar el flujo de dinero real (wallet/premios) que ya funciona y está probado.

## Principio de diseño

Seguir el patrón que el propio repo ya usa para "una sola pieza de lógica, dos juegos
la llaman explícitamente" — el mismo patrón de `GamePrizeContract` + `GamePrizeService`.
**No** depender de los eventos `ShouldBroadcastNow` existentes (`BingoGameEnded`,
`GameFinished` de Trivia): no hay `EventServiceProvider` en el proyecto y esos eventos
son broadcast-only por diseño. En vez de eso: un servicio nuevo `GameCompletionService`
que Bingo y Trivia llaman explícitamente en su punto de cierre de partida.

## Fases

Cada fase es un commit/lote independiente, verificable por separado.

### Fase 1 — Cimientos (sin tocar Bingo/Trivia todavía)
Bajo riesgo. Corrige lo que ya está roto en el sistema de logros y prepara los
contadores reales, sin enganchar aún ningún flujo de juego real.

1. Agregar a `User`: accessors calculados (no columnas nuevas) para `games_played`,
   `games_won`, `total_winnings`, alimentados por fuentes que **ya existen**:
   - `total_winnings` → `SUM(wallet_movements.amount) WHERE user_id=? AND type IN (PRIZE, BONUS_PRIZE)`
   - `games_played` / `games_won` → nueva tabla ligera `game_participations` (ver
     Fase 2) — no se puede derivar 100% de lo existente para ambos juegos a la vez
     (bingo tiene `bingo_room_users`, trivia tiene `trivia_participants` en otra DB).
     Se define el contrato aquí; se llena en Fase 2/3.
2. Corregir `AchievementService::calculateProgress()` para leer estos accessors
   reales en vez de propiedades inexistentes.
3. Registrar la ruta que falta: `POST /profile/achievements/check` (o interno, sin
   HTTP) para `AchievementController::checkAndUnlock`, protegida con
   `throttle` y pensada para ser llamada por el propio backend, no por el cliente.
4. Decisión sobre los 3 logros `custom` (`lucky_winner`, `early_bird`, `night_owl`):
   quedan **desactivados** (`is_active = false`) hasta implementarlos explícitamente,
   en vez de vivir en el catálogo como indesbloqueables silenciosos.
5. Crear `GameCompletionService` (esqueleto): recibe `(User $user, string $game,
   bool $won, int $xp, array $context)`, llama `$user->addXp()` +
   `AchievementService::checkAndUnlockAchievements()` dentro de una transacción,
   logueando cada otorgamiento (mismo patrón de auditoría que `logXpTransaction`).

### Fase 2 — Contador de partidas cross-game
1. Migración: tabla `game_participations` (`user_id`, `game` enum(`bingo`,`trivia`),
   `room_id`, `won` bool, `created_at`). Es el registro mínimo cross-game que hoy no
   existe unificado (bingo y trivia usan tablas/DBs distintas y con otro propósito).
2. Poblar esta tabla desde los puntos de enganche de Fase 3/4 (no requiere backfill
   histórico — arranca en 0 para todos, igual que hoy).
3. Actualizar accessors de `User` para leer de aquí.

### Fase 3 — Enganchar Bingo (dinero real — requiere aprobación explícita antes de tocar)
Punto de enganche confirmado: `BingoDrawService::claimCardWin()`
(`app/Domains/Games/Bingo/Services/Game/BingoDrawService.php:947`, justo después del
payout exitoso) para el XP/logro del ganador, y
`BingoDrawService::closeRoomIfFinalRound()` (línea 994-1044) para registrar
"partida jugada" de todos los participantes de la sala (ganen o no).

Reglas de XP propuestas (usando `UserLevelService::calculateXpReward()`, ya
tabulado pero sin usar): `game_played: 25`, `game_won: 100`.

**No se toca la lógica de pago de premios ni de `BingoDrawService` más allá de
agregar la llamada a `GameCompletionService` al final de los métodos ya
identificados — cero cambios al flujo de dinero.**

### Fase 4 — Enganchar Trivia (dinero real — requiere aprobación explícita antes de tocar)
Punto de enganche confirmado: `GameEngineService::finishGame()`
(`app/Domains/Games/Trivia/Services/GameEngineService.php`, tras línea 513, con
`$finalRanks`/`$winners`/`$prizeResults` ya calculados).

Mismo patrón: llamar `GameCompletionService` por cada participante final, sin tocar
`PrizeDistributionService` ni el motor de preguntas.

### Fase 5 — Racha de login
Actualmente no existe ninguna lógica de racha. Requiere: listener de
`Illuminate\Auth\Events\Login` que compare `last_login_at` contra "ayer" y actualice
un contador (columna nueva `login_streak_days` en `users`, o tabla aparte). Se deja
como fase separada porque es funcionalidad nueva sin infraestructura previa, y no es
bloqueante para que Bingo/Trivia otorguen XP.

### Fase 6 — Frontend
- `GameStatsTab.vue` ya consume `games_played`/`games_won`/`total_winnings` del
  backend (ver `ProfileController::buildStats()`) — una vez Fase 1-4 estén activas,
  solo hace falta que `buildStats()` deje de hardcodear esos campos en 0 y use los
  accessors reales de `User`. No requiere cambios adicionales en Vue.
- Notificación de "logro desbloqueado" / "subiste de nivel" en tiempo real: fuera de
  alcance de este plan (requeriría un canal broadcast nuevo); se deja como mejora
  futura.

## Fuera de alcance de este plan
- Cambiar cómo se pagan premios reales (wallet) — no se toca.
- Logros específicos por juego (hoy el catálogo es genérico/bingo-céntrico) — se
  puede añadir una columna `game` a `achievements` en una fase posterior si se
  decide tener logros exclusivos de Trivia.
- UI de administración para otorgar XP manualmente — hoy el endpoint existe sin UI;
  se deja fuera salvo que se pida explícitamente.

## Progreso

| Fase | Estado | Notas |
|---|---|---|
| Fase 1 — Cimientos | ✅ Hecho | |
| Fase 2 — Contador cross-game | ✅ Hecho | Tabla creada y migrada |
| Fase 3 — Enganche Bingo | ✅ Hecho | `BingoDrawService` |
| Fase 4 — Enganche Trivia | ✅ Hecho | `GameEngineService` |
| Fase 5 — Racha de login | ✅ Hecho | |
| Fase 6 — Frontend | ✅ Hecho | `buildStats()` ya expone todos los campos con datos reales |

## Changelog

- **2026-07-20**: plan creado. Iniciando Fase 1.
- **2026-07-20**: Fase 1 + Fase 2 implementadas y probadas end-to-end en local:
  - `User`: nuevos accessors `games_played_count`, `games_won_count`, `total_winnings`
    (agregados con `getXxxAttribute()`, sin columnas nuevas en `users`) y relación
    `walletMovements()`.
  - `game_participations` (tabla + modelo `GameParticipation`): registro mínimo
    cross-game de partidas jugadas/ganadas. Migrada correctamente.
  - `AchievementService`/`Achievement::calculateProgress()`: corregidos para leer
    los accessors reales en vez de propiedades inexistentes (`$user->games_played`
    ya no resolvía a `null` silenciosamente).
  - `GameCompletionService` (nuevo, `app/Services/Games/`): punto único donde un
    juego reporta `reportParticipation(User, game, won, xp, roomId)` — registra la
    participación, otorga XP vía `UserLevelService`, y evalúa logros vía
    `AchievementService`, con logging si la evaluación de logros falla (no revierte
    el XP ya otorgado).
  - Ruta de respaldo `POST /profile/achievements/check` enrutada (antes existía el
    controller pero no la ruta) con rate limit propio (`profile-achievements-check`,
    5/min). Rate limit también agregado a `add-xp` (`profile-add-xp`, 10/min,
    `max:10000` en el amount).
  - Los 3 logros `custom` (`lucky_winner`, `early_bird`, `night_owl`) desactivados
    (`is_active=false`) en seeder + migración de datos — antes eran indesbloqueables
    silenciosos porque `checkCustomRequirement()` no está implementado.
  - Bug preexistente encontrado y corregido en el camino: `Achievement::unlockForUser()`
    llamaba `$user->addXp($amount, $reason)` con 2 argumentos cuando
    `User::addXp()` solo acepta 1 — nunca se había ejecutado en producción con un
    logro real hasta esta prueba. Ahora usa `UserLevelService::addXpToUser()`
    (con el `reason` sí soportado y auditado).
  - Bug preexistente encontrado y corregido: `UserLevelService::logXpTransaction()`
    llamaba al helper `activity()` de `spatie/laravel-activitylog`, paquete que
    **no está instalado en este proyecto** — cualquier llamada a `addXpToUser()`
    con `reason` no vacío fallaba con `Error: Call to undefined function`. Nunca se
    había ejercitado porque nada llamaba a `addXp` con `reason` real hasta ahora.
    Reemplazado por `Log::info()` con la misma estructura de datos.
  - Colores de badge `gold`/`cyan` (usados por el seeder) agregados al mapa de
    `Achievement::getBadgeStyleAttribute()` — antes caían silenciosamente a gris.
  - `ProfileController::buildStats()`: `games_played`/`games_won`/`total_winnings`
    ya no están hardcodeados en 0, usan los accessors reales.
  - Probado end-to-end vía tinker: partida ganada → XP correcto, `first_game` +
    `first_win` desbloqueados automáticamente; partida perdida → solo `first_game`.
  - Suite `tests/Feature/Api/UserTest.php` corrida tras los cambios: los tests que
    ejercitan lo tocado en esta sesión (`user can change their password`,
    `user can get/update their preferences`, `user can get their stats`) pasan.
    El único fallo (`user cannot change password with wrong current password`) es
    preexistente a esta sesión (falla en `assertJsonValidationErrors` de un 422,
    no relacionado a XP/logros/preferencias) — no introducido por estos cambios.

- **2026-07-20**: Fase 3 + Fase 4 implementadas (enganche real con Bingo y Trivia).
  - **Bingo** (`BingoDrawService`):
    - `claimCardWin()` (tras el `payout()` exitoso, línea ~953): otorga XP de
      `game_won` (vía `UserLevelService::calculateXpReward('game_won')`) y evalúa
      logros para el ganador, envuelto en `try/catch` que solo loguea — no puede
      revertir un premio ya pagado.
    - Nuevo método privado `reportGamesPlayed()`, llamado al final de
      `closeRoomIfFinalRound()`: recorre `BingoRoomUser` de la sala (filtrando por
      `isPlayer()` para excluir vendedores/administradores de sala), y para cada
      jugador que no ganó (`winner !== 'Y'`) registra `game_played` con su XP
      correspondiente. Cubre también el camino "sorteo sin ganador".
    - Constructor recibió `GameCompletionService` y `UserLevelService` nuevos
      (autowiring, sin bindings manuales que actualizar).
  - **Trivia** (`GameEngineService::finishGame()`):
    - Nuevo paso "7.5" (tras actualizar skill ratings, antes de construir el
      broadcast), método privado `reportGameCompletion($finalRanks, $roomId)`:
      itera `$finalRanks` (ya contiene `playerId`/`position` de todos los
      participantes), resuelve el `User`, y otorga `game_won` si `position === 1`
      (misma regla que ya usa `TriviaScore::is_winner`) o `game_played` en caso
      contrario. Envuelto en `try/catch` — no afecta los premios ya distribuidos
      en el paso 6.
    - Constructor recibió `GameCompletionService` y `UserLevelService` nuevos.
  - Verificado: el contenedor de Laravel resuelve ambos servicios con las nuevas
    dependencias sin errores (`app(BingoDrawService::class)` /
    `app(GameEngineService::class)` en tinker). Ningún test existente instancia
    estos servicios manualmente con `new` (todos usan el contenedor o mocks), y
    ningún test ejercita `finishGame()`/`claimCardWin()` directamente hoy — bajo
    riesgo de romper la suite existente.
  - Ambos puntos de enganche reutilizan los valores ya tabulados en
    `UserLevelService::calculateXpReward()` (`game_played: 25`, `game_won: 100`)
    en vez de introducir números nuevos.
  - **No se tocó** la lógica de cálculo de premios, `WalletService`,
    `GamePrizeService`, ni el motor de preguntas de Trivia — solo se agregó una
    llamada adicional al final de los métodos de cierre ya identificados.
  - Suite completa corrida: `tests/Feature/Api/BingoTest.php` +
    `tests/Unit/Domains/Bingo/*` + `tests/Unit/Domains/Trivia/*` →
    **58/58 tests pasan, 0 regresiones** (incluye `GameEngineServiceTest`, que
    instancia el servicio real vía contenedor con las nuevas dependencias).
    Nota de cobertura: ningún test existente ejercita `finishGame()` ni
    `claimCardWin()` directamente (los tests de `GameEngineServiceTest` cubren
    `startGame`/`sendNextQuestion`/`submitAnswer`, no el cierre de partida) — la
    validación del código nuevo en sí se hizo manualmente vía tinker
    (`GameCompletionService::reportParticipation()`, ver entrada de Fase 1/2) más
    la verificación de que el contenedor resuelve ambos servicios sin error de DI.
    Sería recomendable añadir un test de integración para `finishGame()` y
    `claimCardWin()` que cubra el otorgamiento de XP/logros en una sesión futura.

- **2026-07-20**: Fase 5 implementada (racha de login) — desvío del diseño original
  del plan: en vez de un listener de `Illuminate\Auth\Events\Login` (el proyecto no
  tiene `EventServiceProvider`, habría que crear esa infraestructura desde cero),
  se optó por un servicio explícito llamado directamente desde el único controlador
  de login real, replicando el patrón de `GameCompletionService`.
  - Migración: columnas nuevas `login_streak_days` y `best_login_streak_days` en
    `users` (default 0). No existía ninguna columna de racha previamente.
  - Hallazgo importante durante la investigación: **`last_login_at`/`last_login_ip`
    nunca se actualizaban en ningún login real** — `UserRepository::updateLastLogin()`
    existía pero no tenía ningún caller en todo el código. `ProfileController` y
    los dashboards ya leían ese campo para mostrar "último acceso", pero siempre
    mostraba `NULL`/fallback. Corregido como parte de este mismo cambio.
  - `LoginStreakService` (nuevo, `app/Services/User/`): `registerLogin(User, ?ip)`.
    Reglas: último login hoy (UTC) → no toca la racha; último login ayer →
    incrementa; cualquier otro caso (o primer login) → resetea a 1. Actualiza
    `best_login_streak_days` como máximo histórico. Evalúa logros al final con
    `try/catch` — un fallo no bloquea el login.
  - Enganchado en `AuthenticatedSessionController::store()` (único punto de login
    real de la app — Fortify está instalado pero solo gestiona Sanctum/passwords,
    no procesa el login de la UI), envuelto en `try/catch` propio: un fallo en la
    racha nunca debe impedir que el usuario entre.
  - `AchievementService`/`Achievement::calculateProgress()`: caso `login_streak`
    corregido para leer `login_streak_days` real — antes siempre `false`/`0`.
  - `ProfileController::buildStats()`: `current_streak`/`best_streak` ya no están
    hardcodeados en 0.
  - **Decisión de zona horaria**: el server corre en UTC (`config/app.php` /
    `.env`), y la racha se calcula con `Carbon::now()` en UTC — un usuario en otra
    zona horaria podría ver su "día" cambiar en un momento distinto al que percibe
    localmente. No hay infraestructura de timezone por usuario en el proyecto hoy;
    aceptado como limitación conocida, no bloqueante.
  - Probado end-to-end vía tinker, los 4 escenarios: primer login (streak=1),
    mismo día repetido (sin cambio), día consecutivo (incrementa y actualiza
    récord), salto de varios días (resetea a 1, conserva el récord). Confirmado
    también que el logro `streak_7` se desbloquea automáticamente al alcanzar 7
    días de racha.
  - Tests `tests/Feature/Api/AuthTest.php` corridos tras el cambio (BD de test
    recreada limpia para descartar contaminación entre corridas): **6/8 pasan**,
    incluyendo `user can login with email` y `user can login with username` —
    los dos únicos tests que ejercitan `POST /login` real, la ruta donde se
    enganchó `LoginStreakService`. Los 2 fallos restantes son preexistentes y no
    relacionados: `user_can_register` falla por un método inexistente en
    `RegisteredUserController::getReferredBy` (no tocado en esta sesión) y
    `login_fails_with_incorrect_credentials` falla por el mismo patrón de bug
    preexistente de validación 422 ya documentado en sesiones anteriores (no es
    la ruta de login exitoso). `tests/Feature/Auth/AuthenticationTest.php` se
    descartó de la comparación: sus 7 tests apuntan a rutas `/api/auth/login`,
    `/api/user`, `/api/auth/logout` que **no existen** en `routes/api.php`
    (mismo hallazgo de "api.php casi completamente comentado" de la auditoría
    original) — es un archivo de test obsoleto que prueba un contrato de API
    que ya no está montado, ajeno por completo a esta sesión.
