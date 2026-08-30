# Plan: Estrategia de producto — adquisición, retención y economía

> Estado del sistema antes de este plan: Bingo y Trivia maduros en producción, junto con
> toda la infraestructura compartida construida en la iniciativa de arquitectura previa
> (`progreso/game-engine-plan.md`, `progreso/matchmaking-plan.md`, `progreso/xp-logros-plan.md`).
> Diagnóstico completo en `progreso/estrategia-producto-analisis.md` (documento de
> referencia, no se actualiza fase a fase — este archivo sí).

## Objetivo

Convertir la infraestructura ya construida en crecimiento medible: adquisición,
conversión y retención de jugadores, y visibilidad económica del negocio — sin tocar
mecánica de juego de Bingo/Trivia y sin construir infraestructura nueva de plataforma
que ya exista.

## Principio de diseño

Regla explícita del usuario, gobierna toda decisión de este plan: **si una tarea no
mejora Bingo/Trivia, no mejora las métricas del negocio, y tampoco es una dependencia
real del juego futuro (Transportistas vs Cazadores), no se desarrolla todavía.**

Etiquetas de prioridad por fase:
- 🔴 **Ahora** — necesario para Bingo/Trivia o para medir el negocio.
- 🟡 **Después** — mejora que aumenta retención/economía, no bloquea nada actual.
- 🟢 **Futuro** — exclusivamente preparación para Transportistas/Cazadores.

Mismo criterio que las iniciativas anteriores: antes de construir, verificar qué ya
existe. Este plan ya parte de una auditoría (`estrategia-producto-analisis.md`) que
confirmó que dashboard económico, lobby unificado, misiones y ranking de vendedores
**no existen** — y que el sistema de referidos existe pero está roto/desconectado.

## Fases

Cada fase es un commit/lote independiente, verificable por separado. Orden = prioridad
real (ver sección Q del análisis), no orden de aparición en el documento original del
usuario.

### Fase 1 — Referidos: arreglar y reconectar 🔴 ✅ Hecho
`ReferralController.php` usaba una clase inexistente (`App\Models\Users`, con "s") y
renderizaba vistas Blade en un stack 100% Inertia/Vue — código huérfano, y la ruta
`referral.dashboard` apuntaba a un método `dashboard` que ni siquiera existía en el
controller (producía 500 si alguien la visitaba). `referral_code`/`referred_by_user_id`
(tabla `users`) y el evento de XP `referral_signup` (`UserLevelService::calculateXpReward()`,
500 XP) ya existían — y `referral_code` ya se generaba automáticamente en TODOS los
flujos de creación de usuario (registro público, admin, customer, external player,
treasurer). El eslabón perdido real: `RegisteredUserController::store()` (el único
registro público activo) nunca leía ni guardaba `referred_by_user_id` — el código
existía pero nunca se usaba para vincular referente-referido.

También se encontró un segundo archivo huérfano en el mismo hallazgo:
`resources/js/Services/userService.ts` completo (incluyendo su `getReferrals()`)
apunta a rutas `/users/me/*` que no existen en el backend, y no está importado por
ningún componente Vue real — se dejó intacto (no forma parte del alcance de esta
fase, es un patrón de API REST abandonado ajeno al stack Inertia actual; no bloquea
nada de lo implementado aquí).

**Implementado**:
1. `RegisteredUserController::store()`: lee el código de referido desde la cookie
   `referral` (o `referral_code` del form), resuelve el usuario referente vía
   `UserRepository::findByReferralCode()` (ya existente), guarda
   `referred_by_user_id`, y otorga 500 XP al referente fuera de la transacción de
   registro (try/catch, no revierte el registro si el otorgamiento de XP falla —
   mismo criterio que `GameCompletionService`).
2. `ReferralController` reescrito: `summary()` (JSON, requiere sesión, para el tab de
   perfil) y `link()` (sin auth — un visitante nuevo debe poder abrir el enlace de
   invitación antes de registrarse; setea la cookie `referral` por 15 días y
   redirige a `/register`).
3. Rutas corregidas: `GET /profile/referrals` (`referral.summary`) y
   `GET /referrals/r/{referralCode}` (`referral.link`) — la ruta rota
   `referral.dashboard` (apuntaba a un método inexistente) se eliminó.
4. Frontend: `ReferralsTab.vue` nuevo (enlace copiable + lista de referidos),
   integrado en el patrón de tabs ya existente del perfil (`ProfileTabs.vue`,
   `useProfile.ts` con carga lazy al abrir el tab, mismo patrón que
   `stats`/`security`).

**Validado**: `php artisan route:list` confirma las rutas nuevas cargan sin error;
`vue-tsc --noEmit` sin errores en todo el frontend; flujo completo
(`findByReferralCode` → `addXpToUser` → generación de link) probado end-to-end vía
`artisan tinker` contra un usuario real de dev, con el XP de prueba revertido tras
validar.

### Fase 2 — Dashboard económico consolidado 🔴 ✅ Hecho
Hoy `RkeSummaryService`/`TriviaSummaryService` reportan aislados por juego. No existía
ninguna vista que cruce dinero ingresado/premiado/comisiones por juego+moneda+comercializadora.

**Implementado**:
1. `EconomySummaryService` (`app/Services/Roles/SuperAdmin/Dashboard/`, mismo namespace
   que `RkeSummaryService`/`TriviaSummaryService`), cacheado 90s (mismo TTL que el resto
   de agregados del dashboard). No reemplaza a ninguno de los dos servicios existentes —
   agrega sobre datos ya existentes:
   - Bingo: `bingo_creator_sales` (ya desglosa `sale_amount`/comisiones por moneda).
   - Trivia: `wallet_movements` tipo `TRIVIA_ENTRY`/`PRIZE`/`BONUS_PRIZE` — se usó
     `wallet_movements` (MySQL) en vez de `trivia_rooms` (Postgres) porque el dinero real
     siempre pasa por la wallet del jugador, y cruzar conexiones en una sola query no es
     viable (misma regla documentada en `WalletService`, Fase 0 de arquitectura).
   - Por comercializadora: solo Bingo (jerarquía comercial de vendedores es exclusiva de
     Bingo, confirmado en `ALCANCE_JUEGOS_Y_COMERCIO.md`). Resuelto en PHP —
     `creator_user_id` agrupado en SQL primero, luego `Seller::resolveCommerce()`
     (ya existente, contempla reporte directo o vía Subsidiary) por creador único, no
     por venta individual.
   - **Limitación documentada explícitamente en el código, no oculta**: la comisión de
     Trivia no es desglosable desde `wallet_movements` (no hay forma de filtrar premios
     por juego con los datos disponibles) — se reporta `commission: null` para Trivia en
     vez de fingir una precisión que los datos no tienen.
2. Endpoint `GET /superadmin/economy-summary` (`SuperAdminController::economySummary()`,
   mismo patrón `response()->json($service->getSummary())` que el resto).
3. Frontend: `useEconomySummary.ts` + `EconomySummarySection.vue` (KPIs totales +
   desglose por juego/moneda/comercializadora), integrado en
   `Pages/Roles/SuperAdmin/Dashboard/Index.vue` antes de `RkeCommissionsSection` (el
   resumen consolidado precede a los desgloses específicos ya existentes).

**Validado**: `vue-tsc --noEmit` sin errores; `php artisan route:list` confirma la ruta;
probado end-to-end vía `artisan tinker` con un `WalletMovement` sintético
(`TRIVIA_ENTRY`) — agregación por juego y por moneda correctas, dato limpiado tras
validar. La parte de `BingoCreatorSale` no se pudo probar con datos reales (0 salas de
Bingo en la BD de dev, y la tabla tiene FK real a `room_id` que impide insertar una fila
sintética sin antes crear una sala completa) — se validó por revisión de código, reutiliza
el mismo patrón `SUM()`/`groupBy` que `RkeSummaryService` ya usa en producción.

### Fase 3 — Métrica de jugadores activos/recurrentes 🔴 ✅ Hecho
Hoy solo existía conteo total de entidades, no "activos en ventana de tiempo".

**Corrección de diseño encontrada durante la implementación**: el plan original
proponía "jugadores recurrentes por vendedor" sobre `BingoCreatorSale`, pero esa
tabla **no registra al comprador individual** — es un agregado por sala/creador
(`sale_amount`, comisiones), sin `buyer_user_id`. La fuente correcta es
`bingo_card_payments`, que sí tiene `seller_user_id` + (`user_id` o
`external_player_id`) + `payment_status` por cada pago individual.

**Implementado**: `PlayerActivitySummaryService`
(`app/Services/Roles/SuperAdmin/Dashboard/`, mismo patrón cacheado 90s):
- `activePlayers()`: jugadores distintos con al menos una fila en
  `game_participations` (cross-juego) en ventana diaria/semanal/mensual.
- `recurringBySeller()`: por vendedor, total de compradores distintos vs
  compradores con 2+ pagos `APPROVED` — usa `COALESCE(user_id, external_player_id)`
  como identidad del comprador (un jugador con cuenta y un jugador externo cuentan
  igual como "cliente" del vendedor). Resuelto con `GROUP BY seller_user_id,
  COALESCE(...)` en SQL, agregación final en PHP.

Endpoint `GET /superadmin/player-activity-summary`, sección nueva en el dashboard
SuperAdmin (`PlayerActivitySummarySection.vue`) justo antes de `RkeCommissionsSection`.

**Validado**: `vue-tsc --noEmit` sin errores; ruta confirmada con `route:list`; la
query SQL cruda (`GROUP BY` con `COALESCE` en `raw()`) se ejecutó directamente contra
la tabla real para confirmar que no dispara error `ONLY_FULL_GROUP_BY`. No se pudo
probar con pagos sintéticos completos — `bingo_card_payments.card_lot_id`/`room_id`
son `NOT NULL` con FK real, y no hay salas de Bingo en la BD de dev (mismo bloqueo ya
documentado en Fase 2) — validado por revisión de código en su lugar.

### Fase 4 — Lobby / Opportunity Feed unificado 🔴 ✅ Hecho
Bingo y Trivia tienen lobbies separados (`Games/Bingo/Room/GameRooms.vue` es el
listado real de salas, no `Games/Bingo/Lobby/Index.vue` que es la pantalla de UNA
sala; `Games/Trivia/Lobby.vue`). No existía ninguna vista que muestre ambos como
oportunidades comparables, ni ningún componente de tarjeta compartido entre los dos
juegos (confirmado por investigación: implementaciones visuales completamente
distintas, sin nada reutilizable a nivel de componente — solo a nivel de datos).

**Implementado**:
1. `OpportunityFeedService` (`app/Services/Games/`) — combina ambos juegos sin tocar
   su lógica:
   - Bingo: reutiliza `BingoRoomContextService::context('game-rooms', $userId)`, el
     mismo contexto que ya usa `GameRooms.vue` (filtrado por rol ya resuelto ahí).
   - Trivia: reutiliza `TriviaRoom::scopeJoinable()` — un scope que **ya existía en
     el modelo pero ningún controlador lo usaba** (`TriviaGameController::lobby()`
     construye su propia query aparte). Resuelve símbolo de moneda con una query
     separada a `Currency` (MySQL) porque `TriviaRoom` vive en la conexión
     `pgsql_trivia` — no se puede hacer una relación Eloquent cross-connection.
   - Es una vista de **descubrimiento**, no de compra: no resuelve precio de cartón
     por jugador/moneda (lógica más compleja, ya resuelta por la pantalla de sala al
     entrar) — solo expone lo que ya está disponible sin cálculo adicional.
2. `OpportunityFeedController` + rutas `GET /opportunities` (Inertia) y
   `GET /opportunities/fetch` (JSON, para refrescar sin recargar).
3. Frontend nuevo: `Pages/Games/Opportunities/Index.vue`,
   `Components/games/Opportunities/OpportunityCard.vue` (tarjeta unificada nueva,
   normaliza el shape de datos de ambos juegos), `useOpportunityFeed.ts`,
   `types/games/opportunity.ts`. Acceso rápido nuevo agregado al dashboard del
   jugador (`DashboardQuickLinks`, primero de la lista).
2. No se tocó el "perfil global visible en ambos lobbies" (punto 2 original) — quedó
   fuera de esta implementación, es una mejora menor sobre los lobbies existentes en
   sí, no parte del feed nuevo.

**Incidente durante la implementación (reportado de inmediato al usuario)**: al
limpiar una carpeta creada con casing incorrecto (`Components/Games`, con G
mayúscula, duplicando la carpeta real `Components/games`), un `rm -rf` sobre esa
ruta borró la carpeta real completa en Windows (filesystem case-insensitive — ambas
rutas apuntan al mismo directorio físico), eliminando 117 componentes reales de
Bingo/Trivia. Detectado de inmediato por errores de `vue-tsc --noEmit`. Recuperado
sin pérdida de datos con `git checkout -- backend/resources/js/Components/games/`
(todo estaba trackeado); el único archivo nuevo perdido (`OpportunityCard.vue`, no
trackeado aún) se recreó desde cero. Verificado: 117 archivos `.vue` de vuelta,
`git status` sin diferencias, typecheck limpio.

**Validado**: `vue-tsc --noEmit` sin errores en todo el frontend; rutas confirmadas
con `route:list`; `OpportunityFeedService::getFeed()` probado end-to-end vía
`artisan tinker` con un usuario real (`player1`) — devuelve `[]` correctamente
(confirmado que no hay salas de Bingo ni Trivia en la BD de dev, no es un bug).

### Fase 5 — Instrumentar funnel jugador externo → registrado 🔴 ✅ Hecho
El flujo original (`ExternalPlayer` → link temporal → `convertToUser()`) resultó ser
**100% manual de admin** — sin ningún self-service. Se amplió el alcance con
aprobación explícita del usuario: construir auto-vinculación real por teléfono, no
solo medir/incentivar el flujo manual existente.

**Bug crítico bloqueante encontrado y corregido primero** (no relacionado al alcance
original de la fase, pero descubierto en el camino y arreglado antes de seguir):
`Auth/Register.vue` enviaba su formulario a `POST /api/auth/register`, ruta
**inexistente** — cualquier registro público producía 404 en silencio (axios seguía
el 404 sin lanzarlo como error visible en algunos casos, y el `RegisteredUserController::store()`
real devolvía `RedirectResponse` en vez de JSON, incompatible con el patrón axios del
formulario). **Nadie podía registrarse en la plataforma.** Mismo patrón de bug se
encontró y corrigió también en `PasswordResetLinkController::store()` (forgot-password)
y `NewPasswordController::store()` (reset final) — los tres devolvían `RedirectResponse`
donde el frontend esperaba JSON.

**Corregido**:
1. Los 3 controladores (`RegisteredUserController`, `PasswordResetLinkController`,
   `NewPasswordController`) ahora devuelven `JsonResponse`, mismo patrón ya usado y
   validado en `AuthenticatedSessionController` (login).
2. `PasswordResetLinkController` ya no distingue en su respuesta si el email existe o
   no (mismo mensaje siempre) — cierra una enumeración de cuentas registradas que el
   bug anterior de hecho ocultaba sin querer (nunca se veía el mensaje distinto), pero
   que quedaba activa en el código.
3. Rate limiting agregado a `POST /register` (`throttle:5,1`), `POST /forgot-password`
   (`throttle:5,1`), `POST /resetPassword` (`throttle:5,1`) y `POST /login`
   (`throttle:20,1`, defensa en profundidad adicional al `RateLimiter` ya existente en
   `LoginRequest`, que no cubre credential-stuffing con distintos `login` desde la
   misma IP) — ninguna de las 4 rutas tenía límite de intentos antes.
4. Campo `politicas` (nombre inconsistente con el resto del stack en inglés) renombrado
   a `terms` en backend y frontend, a pedido explícito del usuario.

**Auto-vinculación por teléfono** (ampliación de alcance):
1. Campo `phone` opcional agregado al formulario de registro público — si coincide con
   uno o más `ExternalPlayer.phone`+`country_id` sin convertir, se vinculan
   automáticamente todos (`RegisteredUserController::linkExternalPlayerHistory()`),
   replicando `ExternalPlayerAdminService::convertToUser()` pero disparado por el
   propio jugador, sin generar contraseña temporal.
2. Incentivo de conversión: 300 XP al usuario nuevo cuando la auto-vinculación tiene
   éxito (`UserLevelService::calculateXpReward('external_player_conversion')`) — XP,
   no dinero, tal como pedía el diseño original de esta fase.
3. CTA "Regístrate y no pierdas tu premio" agregado al banner ya existente en
   `Games/Bingo/Game/Index.vue` (pantalla real que ve el jugador externo mientras
   juega) — enlaza a `/register?phone=...&country=...` para precargar el formulario.
   `ExternalPlayerGameController::game()` extendido para exponer `phone`/`country_id`
   del jugador externo (antes solo `link`/`first_name`/`last_name`).
4. Métrica de conversión agregada a `PlayerActivitySummaryService` (Fase 3, ya
   existente): `external_player_conversion` (total de jugadores externos, cuántos se
   convirtieron, tasa) — no distingue si la conversión fue manual (admin) o
   automática (auto-vinculación), mide el resultado. Sección nueva en el dashboard
   SuperAdmin.

**Validado**: `vue-tsc --noEmit` sin errores; los 3 controladores corregidos probados
end-to-end vía `artisan tinker` (registro con `terms`, rechazo sin aceptar términos,
forgot-password sin enumeración — misma respuesta exacta para email real vs
inexistente —, reset con token inválido rechazado sin tocar la contraseña, reset con
token válido cambiando la contraseña de verdad). Rutas con throttle confirmadas con
`route:list`. Métrica de conversión probada contra datos reales de dev (4 jugadores
externos existentes, 0 convertidos).

### Fase 6 — Misiones simples 🔴 ✅ Hecho
No existía ningún sistema de misiones con progreso y ventana de tiempo (solo logros
estáticos + racha de login).

**Corrección de diseño encontrada durante la implementación**: no se pudo reutilizar
`Achievement`/`AchievementService` tal cual, como sugería el plan original —
`AchievementService::checkAndUnlockAchievements()` usa `whereDoesntHave('users', ...)`,
es decir, un logro se desbloquea **una sola vez para siempre** por usuario. Es
incompatible con algo que debe reiniciarse cada día. Se construyó una tabla/servicio
paralelo mínimo en vez de forzar ese sistema a hacer algo para lo que no fue diseñado.

**Implementado**:
1. `daily_missions` (catálogo: código, nombre, `requirement_type` cerrado a
   `games_played`/`games_won`, `target`, `xp_reward`) + `user_daily_mission_progress`
   (progreso por usuario+misión+día calendario UTC — una fila nueva por día es el
   "reinicio", no hay job de reset).
2. `DailyMissionService::recordParticipation()` — llamado desde
   `GameCompletionService::reportParticipation()` (mismo punto de enganche que ya usan
   Bingo/Trivia, dentro de un try/catch que no revierte XP/resultado ya guardado si
   falla, mismo criterio que la evaluación de logros).
3. Reutiliza `UserLevelService::addXpToUser()` para el reward — sin ledger nuevo.
4. Endpoint `GET /profile/daily-missions`, tab nuevo "Misiones" en el perfil del
   jugador (mismo patrón de tabs con carga lazy que Fase 1/Referidos).
5. `DailyMissionSeeder` con 2 misiones mínimas: "Juega 3 partidas hoy" (75 XP),
   "Gana una partida hoy" (150 XP).

**Validado end-to-end vía `artisan tinker`**: progreso incremental correcto (2/3 →
3/3), ambas misiones completándose en el momento correcto, XP otorgado exactamente
una vez por misión por día (75+150=225, confirmado con delta real de XP), sin
doble-otorgamiento en partidas adicionales tras completar, progreso topado
correctamente en el target (no sigue subiendo más allá de 3/3). Endpoint HTTP real
probado con status 200 y payload correcto. `vue-tsc --noEmit` sin errores. Datos de
prueba limpiados de la BD tras validar.

### Fase 7 — Ranking de vendedores + meta simple 🔴
`Seller.commission` es hoy un decimal fijo, sin ranking ni meta.

1. Vista de ranking (volumen mensual) sobre datos ya agregados en Fase 2.
2. Meta simple, sin comisión progresiva todavía (eso es Fase 10, bloqueada).

### Fase 8 — Puente XP Bingo → Trivia 🔴
1. Incentivo de XP al primer intento de Trivia tras haber jugado Bingo — banner o
   notificación, sin cambiar reglas de ningún juego.

### Fase 9 — Simulación económica de Cargas 🟡
Fuera del repo de producción (hoja de cálculo o script standalone, no Laravel).

1. Simular 100 a 100.000 partidas con las variables identificadas: valor inicial,
   crecimiento, cantidad de cazadores, entrada, probabilidad de captura, abandono,
   comisión, duración, frecuencia de entrada/retirada.
2. Validar el principio ya decidido en `estrategia-producto-analisis.md` (sección K):
   el valor de la carga debe subir por aportes reales de cazadores (modelo equivalente
   al `pool` de Trivia), nunca por el solo paso del tiempo.
3. Detectar inflación, deflación, acumulación excesiva, concentración de riqueza,
   partidas sin liquidez, premios mal calibrados.
4. No bloquea ninguna fase anterior — puede correr en paralelo desde que empiece
   Fase 5.

### Fase 10 — Comisión progresiva de vendedores 🟢
Bloqueada explícitamente hasta calibrar con datos reales del ranking (Fase 7) — construirla
antes es fijar un incentivo económico a ciegas.

### Fase 11 — Prototipo de Cargas con moneda ficticia 🟢
Bloqueada hasta validar la simulación (Fase 9). MVP: 1 Transportista + N Cazadores +
1 Carga + 1 Ruta + Valor + Entrada + Reserva + Intercepción + Entrega + Retirada — nada
más. Reutiliza tal cual: `GameSession`, `WalletHold`/`WalletReservationService`,
`MatchmakingService`, `ReputationService`, `GamePrizeService`,
`GameCompletionService`/`game_participations`, Reverb. Ver tabla completa en
`estrategia-producto-analisis.md` sección L.

### Fase 12 — Roles avanzados de Cargas (Guardián, Rastreador, Espía) 🟢
Bloqueada hasta tener el MVP de Fase 11 probado con usuarios reales.

## Fuera de alcance de este plan

- Mercado, contratos, bandadas, saboteador, comerciante, contraespía.
- Niveles/reputación por rol de juego (se mantiene XP global).
- Ledger de reputación con historial de cada cambio.
- Conectar `MatchmakingService` a Trivia (sin evidencia de necesidad real hoy).
- Jackpots nuevos, salas especiales nuevas, temporadas — válidas pero de menor
  impacto relativo frente a las fases 1-8; se reconsideran después de esas.
- Cualquier valor económico definitivo de Cargas antes de que Fase 9 concluya.

## Progreso

| Fase | Prioridad | Estado | Notas |
|---|---|---|---|
| Fase 1 — Referidos | 🔴 | ✅ Hecho | Controller reescrito, ruta rota eliminada, XP conectado, tab de perfil nuevo |
| Fase 2 — Dashboard económico | 🔴 | ✅ Hecho | EconomySummaryService + vista SuperAdmin, cross-juego/moneda/comercializadora |
| Fase 3 — Jugadores activos/recurrentes | 🔴 | ✅ Hecho | PlayerActivitySummaryService; corregido a usar bingo_card_payments, no bingo_creator_sales |
| Fase 4 — Lobby/Opportunity Feed | 🔴 | ✅ Hecho | Página nueva /opportunities, OpportunityFeedService combina Bingo+Trivia |
| Fase 5 — Funnel externo→registrado | 🔴 | ✅ Hecho | Auto-vinculación por teléfono + XP; corregido bug crítico de registro/reset roto en el camino |
| Fase 6 — Misiones simples | 🔴 | ✅ Hecho | daily_missions/user_daily_mission_progress nuevo (Achievement no reutilizable) |
| Fase 7 — Ranking de vendedores | 🔴 | Pendiente | Depende de Fase 2 |
| Fase 8 — Puente XP Bingo→Trivia | 🔴 | Pendiente | |
| Fase 9 — Simulación económica Cargas | 🟡 | Pendiente | En paralelo, no bloquea |
| Fase 10 — Comisión progresiva vendedores | 🟢 | Bloqueada | Depende de Fase 7 |
| Fase 11 — Prototipo Cargas | 🟢 | Bloqueada | Depende de Fase 9 |
| Fase 12 — Roles avanzados Cargas | 🟢 | Bloqueada | Depende de Fase 11 |

## Changelog

- **2026-08-24**: plan creado a partir del análisis estratégico solicitado por el
  usuario (`progreso/estrategia-producto-analisis.md`), documentos de contexto
  `ESTADO_ECOSISTEMA.md` y `ALCANCE_JUEGOS_Y_COMERCIO.md` como fuente de verdad.
  Auditoría previa confirmó que dashboard económico consolidado, lobby unificado,
  misiones y ranking de vendedores no existen; y que el sistema de referidos existe
  parcialmente (columnas + evento de XP) pero su controller es código roto/huérfano
  del stack anterior (Blade) — hallazgo que definió la Fase 1 como la de mejor
  ratio impacto/esfuerzo del plan completo.

- **2026-08-24**: Fase 1 (Referidos) implementada y validada. Ver detalle completo en
  la sección de la fase arriba. Resumen: `RegisteredUserController::store()` ahora
  captura y guarda `referred_by_user_id`, otorga 500 XP al referente;
  `ReferralController` reescrito sin la clase inexistente que tenía; rutas rotas
  corregidas (`referral.dashboard` apuntaba a un método que no existía); tab
  "Referidos" nuevo en el perfil del jugador. `resources/js/Services/userService.ts`
  se identificó como un segundo archivo huérfano (rutas `/users/me/*` inexistentes,
  sin ningún importador real) — se dejó intacto, fuera del alcance de esta fase.

- **2026-08-24**: Fase 2 (Dashboard económico consolidado) implementada y validada. Ver
  detalle completo en la sección de la fase arriba. Resumen: `EconomySummaryService`
  nuevo cruza `bingo_creator_sales` (Bingo) y `wallet_movements` (Trivia, vía MySQL en
  vez de la conexión Postgres separada) por juego/moneda/comercializadora, con la
  limitación de comisión de Trivia documentada explícitamente en vez de ocultada.
  Nueva sección en el dashboard SuperAdmin, antes de los desgloses por juego ya
  existentes (RKE, Trivia). Validado con dato sintético vía tinker (limpiado tras
  probar) y por revisión de código para la parte de Bingo (sin datos reales de sala en
  dev para probar contra la FK real de `bingo_creator_sales.room_id`).

- **2026-08-24**: Fase 3 (Jugadores activos/recurrentes) implementada y validada. Ver
  detalle completo en la sección de la fase arriba. Hallazgo relevante: el plan
  original proponía usar `bingo_creator_sales` para "clientes recurrentes por
  vendedor", pero esa tabla no registra al comprador individual (es un agregado por
  sala) — se corrigió a `bingo_card_payments`, que sí tiene la identidad del
  comprador por pago. `PlayerActivitySummaryService` nuevo, sección nueva en el
  dashboard SuperAdmin.

- **2026-08-24**: Fase 4 (Lobby/Opportunity Feed unificado) implementada y validada.
  Ver detalle completo en la sección de la fase arriba. Reutiliza
  `BingoRoomContextService` (Bingo) y activa `TriviaRoom::scopeJoinable()` (Trivia —
  existía sin usar) sin tocar la lógica de ningún juego. Página nueva `/opportunities`
  con tarjeta unificada nueva (no había ninguna compartida entre los dos juegos).
  **Incidente reportado y resuelto en el momento**: un `rm -rf` sobre una carpeta con
  casing incorrecto borró por accidente la carpeta real de componentes de Bingo/Trivia
  (Windows es case-insensitive) — recuperado sin pérdida vía `git checkout`, ver
  detalle completo en la sección de la fase.

- **2026-08-30**: Fase 5 (Funnel jugador externo → registrado) implementada y
  validada, con alcance ampliado a pedido explícito del usuario (auto-vinculación
  real por teléfono, no solo instrumentación del flujo manual). **Bug crítico
  encontrado y corregido en el camino**: el registro público estaba completamente
  roto en producción (`POST /api/auth/register` no existía) — nadie podía crear
  cuenta. Mismo patrón de bug (`RedirectResponse` donde el frontend esperaba JSON
  vía axios) también afectaba a forgot-password y reset-password; los tres
  controladores se corrigieron con el mismo patrón JSON que ya usaba el login.
  Aprovechando la revisión, se agregó rate limiting a las 4 rutas de auth que no lo
  tenían (`register`, `forgot-password`, `resetPassword`, `login`) y se cerró una
  enumeración de cuentas en forgot-password. Campo `politicas` renombrado a `terms`
  (consistencia con el resto del stack en inglés, a pedido del usuario). Construida
  la auto-vinculación por teléfono con incentivo de XP (300, no dinero) y un CTA
  nuevo en la pantalla real de juego del invitado. Métrica de conversión agregada a
  `PlayerActivitySummaryService` (Fase 3).

- **2026-08-30**: Fase 6 (Misiones simples) implementada y validada. Hallazgo
  relevante: `Achievement`/`AchievementService` (sugerido en el plan original como
  base a reutilizar) resultó incompatible — es un sistema de desbloqueo único para
  siempre, no de progreso con ventana de tiempo. Se construyó `daily_missions` +
  `user_daily_mission_progress` (tablas nuevas, mínimas) enganchado al mismo punto
  que ya usan Bingo/Trivia (`GameCompletionService::reportParticipation()`),
  reutilizando `UserLevelService` para el XP sin ledger nuevo. 2 misiones sembradas,
  tab nuevo en el perfil del jugador. Probado end-to-end con múltiples partidas
  simuladas, confirmando progreso, completado, no-doble-otorgamiento de XP, y tope
  correcto en el target.
