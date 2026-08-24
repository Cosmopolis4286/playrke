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

### Fase 4 — Lobby / Opportunity Feed unificado 🔴
Bingo y Trivia tienen lobbies separados (`Games/Bingo/Lobby/Index.vue`,
`Games/Trivia/Lobby.vue`). No existe ninguna vista que muestre ambos como
oportunidades comparables.

1. Vista nueva que liste sesiones/salas activas de ambos juegos con entrada, premio y
   tiempo de inicio — sin tocar la lógica de ninguno de los dos juegos.
2. Exponer también el perfil global (XP/nivel) de forma visible en ambos lobbies
   (dato que ya existe, solo falta visibilidad).

### Fase 5 — Instrumentar funnel jugador externo → registrado 🔴
El flujo ya existe (`ExternalPlayer` → link temporal → `convertToUser()`). Falta
medirlo y agregar el incentivo en el punto correcto (tras una victoria, no antes de
jugar).

1. Depende de Fase 2 (dashboard) para tener baseline antes de cambiar el incentivo.
2. Incentivo de registro post-victoria (a definir: XP, no dinero — evitar afectar
   economía).

### Fase 6 — Misiones simples 🔴
No existe hoy ningún sistema de misiones con progreso y ventana de tiempo (solo
logros estáticos + racha de login).

1. Misión mínima tipo "juega N partidas hoy" sobre `game_participations`/`Achievement`
   ya existentes — sin nueva infraestructura de recompensas, reutilizar
   `GameCompletionService`/`AchievementService`.

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
| Fase 4 — Lobby/Opportunity Feed | 🔴 | Pendiente | |
| Fase 5 — Funnel externo→registrado | 🔴 | Pendiente | Depende de Fase 2 |
| Fase 6 — Misiones simples | 🔴 | Pendiente | |
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
