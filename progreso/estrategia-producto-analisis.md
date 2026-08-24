# Análisis estratégico de producto — playrke (2026-08-24)

Documento de referencia: el diagnóstico y las decisiones que sustentan `progreso/estrategia-producto-plan.md` (que es el documento vivo de seguimiento — este archivo no se actualiza fase a fase, es la foto del análisis original). Elaborado a partir de `ESTADO_ECOSISTEMA.md` y `ALCANCE_JUEGOS_Y_COMERCIO.md` como fuente de verdad del estado técnico/funcional, más una auditoría específica de analytics/lobby/misiones/referidos/vendedores/KYC hecha para este análisis.

Regla que gobierna todo el documento (dicha explícitamente por el usuario): **si una tarea no mejora Bingo/Trivia, no mejora las métricas del negocio, y tampoco es una dependencia real del juego futuro (Transportistas vs Cazadores), no se desarrolla todavía.**

---

## A. Diagnóstico

La plataforma no está en fase de construcción de infraestructura — está en fase de **explotación de infraestructura ya construida**. Bingo y Trivia son productos maduros con wallet, XP, logros, reputación, anti-fraude, matchmaking, premios y jerarquía comercial funcionando en producción. Lo que falta no es "más motor", es **la capa de negocio que convierte ese motor en crecimiento**: no hay dashboard económico consolidado, no hay lobby cross-game, no hay misiones/temporadas, y el sistema de referidos existe a medias pero está roto y desconectado.

El cuello de botella hoy no es técnico, es de **producto y visibilidad de datos**, confirmado contra código real:

- `RkeSummaryService`/`TriviaSummaryService` reportan comisión **por juego, aislados** — no hay ninguna vista que cruce "cuánto entró, cuánto se apostó, cuánto se pagó, cuánto quedó de comisión" en un solo lugar.
- `resources/js/Pages/Games/Bingo/Lobby/Index.vue` y `Games/Trivia/Lobby.vue` son lobbies **separados** — un jugador que entra por Bingo no ve que existe Trivia como oportunidad activa ahora mismo.
- `App\Http\Controllers\Users\ReferralController.php` usa una clase `App\Models\Users` (con "s") que **no existe** en el proyecto actual, renderiza vistas Blade en un stack que es 100% Inertia/Vue — es código huérfano de una versión anterior. Pero `referral_code`/`referred_by_user_id` sí están en la tabla `users`, y `referral_signup` ya está tabulado en `UserLevelService::calculateXpReward()` con 500 XP. El 60% de un sistema de referidos ya existe, solo hay que reconectarlo, no construirlo.
- No existe ranking de vendedores, ni metas, ni comisión progresiva — `Seller.commission` es un decimal fijo, punto.

## B. Qué está bien y NO tocar

- Core de Bingo (salas, rondas, sorteo, patrones, comisiones, jackpot).
- Core de Trivia (modos, pool/garantizado, scoring anti-fraude).
- Wallet / `WalletMovement` / `MovementType`.
- Los 4 servicios cross-juego ya existentes: `GamePrizeService`, `GameCompletionService`, `WinEventService`, `AntiCheatService`.
- `GameSession` / `MatchmakingService` / `ReputationService` (construidos en la iniciativa de arquitectura previa, sin consumidor todavía — estado esperado, no un problema).
- KYC + `wallet_limits`.

## C. Qué está faltando (real, no especulativo)

1. Dashboard económico consolidado (cross-juego, cross-moneda, cross-comercializadora).
2. Lobby/feed unificado que muestre Bingo + Trivia como oportunidades.
3. Sistema de referidos funcional (existe roto, hay que arreglarlo).
4. Misiones con progreso y ventana de tiempo.
5. Ranking/metas para vendedores.
6. Métrica de "jugadores activos" real (hoy solo hay conteo total).

## D. Top 10 mejoras — impacto/esfuerzo/riesgo

| # | Mejora | Impacto | Esfuerzo | Riesgo | Dependencias |
|---|---|---|---|---|---|
| 1 | Arreglar y reconectar el sistema de referidos | Alto | Bajo | Bajo | Ninguna |
| 2 | Dashboard económico consolidado | Alto | Medio | Bajo | Ninguna |
| 3 | Funnel de jugador externo → registrado (instrumentar) | Alto | Medio | Bajo | #2 para medir baseline |
| 4 | Lobby/Opportunity Feed unificado | Alto | Medio | Bajo | Ninguna |
| 5 | Métrica de jugadores activos/recurrentes | Alto | Bajo | Bajo | Ninguna |
| 6 | Misiones diarias/semanales simples | Medio | Bajo | Bajo | Ninguna |
| 7 | Ranking de vendedores + meta simple | Medio | Bajo | Bajo | #2 (mismos datos agregados) |
| 8 | Trivia como "segundo paso" tras Bingo | Medio | Bajo | Bajo | Ninguna |
| 9 | Simulación económica de Cargas (fuera del repo) | Medio | Medio | Bajo | Ninguna |
| 10 | Comisión progresiva para vendedores recurrentes | Medio | Medio | Medio | #7 |

Deliberadamente fuera del top 10: jackpots nuevos, salas especiales, temporadas — válidas pero de menor impacto relativo frente a adquisición/conversión/medición, que es donde está el gap real.

## E. Roadmap de 3 meses (resumen — el detalle vive en `estrategia-producto-plan.md`)

- **Mes 1**: dashboard económico (#2), jugadores activos (#5), arreglar referidos (#1).
- **Mes 2**: lobby unificado (#4), instrumentar funnel externo→registrado (#3), misiones (#6).
- **Mes 3**: ranking vendedores (#7), puente Trivia (#8), simulación económica (#9) en paralelo.
- Fuera de los 3 meses: comisión progresiva (#10) y todo lo de Cargas más allá de la simulación.

## F. Métricas prioritarias (orden real de construcción)

1. Dinero ingresado por juego/moneda/comercializadora.
2. Jugadores activos (diarios/semanales).
3. Ticket promedio y frecuencia de juego por jugador.
4. Conversión jugador externo → registrado.
5. Volumen y clientes recurrentes por vendedor.
6. Velocity del dinero (después de 1-5).
7. LTV/ARPU (después de tener 2-3 meses de datos limpios).

## G. Experimentos de producto

- A/B en el prompt de registro del jugador externo (tras victoria vs tras derrota).
- A/B en el orden del Opportunity Feed (empieza pronto vs premio más alto).
- Incentivo de XP al primer intento de Trivia tras jugar Bingo.
- Calibrar el umbral de meta de vendedor con datos reales del ranking antes de construir comisión progresiva.

## H. Plan Bingo

No tocar mecánica de juego. Palancas: incentivo de registro post-victoria (no antes de jugar), misión de "segunda compra", ranking de vendedores para visibilidad (sin comisión progresiva todavía), y usar Opportunity Feed + dashboard para anunciar/medir jackpots y salas especiales que ya existen mecánicamente.

## I. Plan Trivia

Puente de descubrimiento vía lobby unificado + incentivo de XP al primer intento. Retención: exponer el ranking histórico del jugador (no solo el de la partida actual), sin tocar el motor de scoring. No conectar `MatchmakingService` a Trivia todavía — `scopeJoinable()` ya resuelve "unirse a sala" y no hay evidencia de que falte matchmaking real hasta que el volumen de salas simultáneas lo justifique.

## J. Plan cross-game

1. Exponer mejor el perfil global (XP/nivel) en ambos lobbies — ya existe, solo falta visibilidad.
2. Opportunity Feed — la pieza central de esta sección.
3. Vista de actividad/historial combinado sobre `game_participations` (ya es cross-juego por diseño, campo `game`).
4. Misiones cross-juego y eventos compartidos: posterior, no parte del mínimo cross-game.

## K. Plan de simulación económica (Cargas)

Simulación fuera del repo de producción (hoja de cálculo o script standalone), 100 a 100.000 partidas, variables: valor inicial, crecimiento, cazadores, entrada, probabilidad de captura, abandono, comisión, duración, frecuencia.

Sobre valor nominal vs capital comprometido: de las 4 opciones planteadas por el usuario, **(A) "el valor crece por aportes reales de jugadores" es la única sostenible sin imprimir dinero** — equivalente al modelo `pool` que Trivia ya usa. (C) "el valor representa potencial, no dinero garantizado" es riesgosa por experiencia de usuario (sensación de engaño si "3.500" mostrado no es "3.500" retirable). (D) híbrida solo es válida si el componente de bonificación está financiado por un pool separado (tipo jackpot), nunca por creación arbitraria de valor. **Recomendación: diseñar Cargas sobre el mismo principio que ya usa Trivia pool — el valor de la carga sube porque cazadores pagan entrada real que se suma al bote, no porque pase el tiempo.**

## L. Qué reutilizar tal cual para Transportistas/Cazadores

| Pieza necesaria | Existe hoy | Acción |
|---|---|---|
| Sesión de partida con ciclo de vida | `GameSession` | Usar tal cual |
| Reserva de saldo con retiro parcial | `WalletHold`/`WalletReservationService` | Usar tal cual |
| Encontrar partida disponible | `MatchmakingService` | Usar tal cual |
| Reputación de jugador | `ReputationService` | Usar tal cual |
| Pago de premio/interceptación | `GamePrizeService` | Usar tal cual |
| Registro de resultado final + XP | `GameCompletionService`/`game_participations` | Usar tal cual |
| Tiempo real | Laravel Reverb + `useEchoChannel.ts` | Usar tal cual, definir nombres de canal nuevos |
| Identificador games-agnostic | `GameSlug::CARGAS` | Ya agregado |

No falta infraestructura de plataforma. Solo falta diseño de dominio específico del juego (reglas de ruta, intercepción, etapas) — trabajo de producto, no de arquitectura.

## M. Qué NO desarrollar todavía

Guardián/Rastreador/Espía/Saboteador, mercado, contratos, bandadas, comisión progresiva de vendedores (antes del ranking calibrado), niveles por rol, ledger de reputación con historial, matchmaking conectado a Trivia (sin evidencia de necesidad), y cualquier valor económico definitivo de Cargas antes de simular.

## N. Arquitectura mínima necesaria para el futuro

Ninguna nueva. Todo lo que Cargas necesitará ya existe (ver L). La única "arquitectura" pendiente es de producto: dashboard económico (D#2) y Opportunity Feed (D#4), que además sirven a Bingo/Trivia hoy.

## O. Riesgos técnicos

- **Concurrencia en Cargas** (resoluble con infraestructura ya existente, no infraestructura nueva):
  - Transportista abandona mientras cazador intercepta → `lockForUpdate()` sobre `WalletHold`/`GameSession`, mismo patrón que `WalletReservationService::capture()`/`release()`.
  - Dos cazadores capturan a la vez → transición atómica de `GameSession` a `FINISHED` (`WHERE status = 'RUNNING'` en el update), el segundo pierde la carrera.
  - Jugador entra mientras cambia de estado → extender `MatchmakingService::isEligible()` dentro de una transacción con lock al crear `GameSessionParticipant`.
  - Pago procesado dos veces → reutilizar idempotencia de `GamePrizeService` con clave determinística tipo `cargas_capture:{session_id}:{hunter_id}`.
  - WebSocket retrasado → mismo patrón ya usado en Bingo/Trivia (broadcast fuera de transacción + reconciliación por refresh).
- **Referidos legacy**: el `ReferralController` roto es un riesgo latente (clase inexistente) — arreglar o eliminar explícitamente, no dejar como código muerto.

## P. Riesgos económicos

- **`entry_fee` de Trivia** como deuda técnica activa documentada en `TriviaSummaryService` — cualquier métrica de "dinero ingresado por Trivia" debe confirmar que lee del campo correcto antes de publicarse.
- **Comisión progresiva sin datos**: construirla antes de tener el ranking de vendedores corriendo es calibrar un incentivo económico a ciegas.
- **Cargas con valor nominal desconectado de capital real**: el riesgo mayor de todo el análisis. Lanzar con "el valor sube porque pasa el tiempo" sin respaldo real de aportes de cazadores es economía inflacionaria desde el día uno — la simulación (K) debe validar esto antes de cualquier línea de código de producción.

## Q. Orden exacto de implementación

1. Arreglar/reconectar referidos — 🔴
2. Dashboard económico consolidado — 🔴
3. Métrica de jugadores activos/recurrentes — 🔴
4. Lobby/Opportunity Feed unificado — 🔴
5. Instrumentar funnel jugador externo → registrado — 🔴
6. Misiones simples — 🔴
7. Ranking de vendedores + meta — 🔴
8. Puente XP Bingo → Trivia — 🔴
9. Simulación económica de Cargas — 🟡 (en paralelo desde el punto 5, no bloquea nada)
10. Comisión progresiva de vendedores — 🟢 (solo tras calibrar con datos del punto 7)
11. Prototipo de Cargas con moneda ficticia — 🟢 (solo tras validar la simulación)
12. Roles avanzados de Cargas — 🟢 (no antes del MVP probado con usuarios reales)
