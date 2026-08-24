# Estado del ecosistema playrke

Documento de contexto para compartir con otra sesión/herramienta de IA (p. ej. ChatGPT) que no tiene acceso a este repositorio. Describe qué existe hoy en la plataforma, en lenguaje neutro, sin asumir que el lector vio ninguna conversación previa. Se actualiza a medida que avanza la iniciativa de arquitectura común (wallet/game engine/eventos) — ver `CHANGELOG_ARQUITECTURA.md` para el detalle cronológico.

Con la arquitectura de plataforma ya resuelta, el trabajo activo se movió a la capa de producto/negocio: `progreso/estrategia-producto-plan.md` es el plan vivo (adquisición, retención, economía, y qué preparar para el futuro juego de Cargas), con su análisis de referencia en `progreso/estrategia-producto-analisis.md`. Ver también `ALCANCE_JUEGOS_Y_COMERCIO.md` para el detalle funcional de Bingo/Trivia y la jerarquía comercial de ventas.

## Qué es playrke

Plataforma de minijuegos con dinero real/virtual. Monorepo pnpm con dos paquetes:

- **`backend/`**: Laravel 11 + Inertia.js + Vue 3 + TypeScript. Es donde vive todo el desarrollo activo (dashboards de administración, wallet, juegos, jobs). WebSocket vía Laravel Reverb.
- **`frontend/`**: Nuxt 4, SPA separada orientada al jugador final. Existe en el repo pero está fuera del foco de trabajo actual — el desarrollo activo ocurre en `backend/resources/js` (Inertia).

Bases de datos: **MySQL** es la conexión principal (usuarios, roles, wallet, Bingo). **PostgreSQL** existe como una conexión separada dedicada exclusivamente a Trivia (`pgsql_trivia`) — es decir, la plataforma ya opera con dos motores de base de datos simultáneamente, no es un plan futuro.

## Juegos implementados hoy

- **Bingo**: el más maduro. Arquitectura de dominio propia (`app/Domains/Games/Bingo/`) con salas, rondas, sorteos, cartones, patrones, comisiones (plataforma/sala/vendedor) y un flujo de pago de premios con máquina de estados (waiting→processing→claimed/cancelled/suspended). Vive en MySQL.
- **Trivia**: arquitectura de dominio equivalente (`app/Domains/Games/Trivia/`), pero vive en la base Postgres separada. Soporta salas con premio fijo o acumulado (pool), equipos, y el mismo tipo de máquina de estados de pago de premio que Bingo.
- **Stop y Hangman**: mencionados en la visión de producto, pero **no tienen código implementado todavía** — son planes, no funcionalidad existente.

Bingo y Trivia siguen resolviendo manualmente cómo se arma una sala/partida (sin cambios). Sí se construyó un servicio de matchmaking genérico para la sesión mínima mencionada arriba, con filtrado por rango de entrada, moneda, nivel mínimo, cupo y reputación — pensado para un juego futuro de duración variable, no conectado a Bingo/Trivia.

También se construyó, como parte de esto, un primer sistema de **reputación de jugador** (score 0-100), calculado exclusivamente a partir de señales que ya existían: historial de señales anti-fraude, si tiene una suspensión activa, y volumen de partidas jugadas sin incidentes. No introduce ningún dato nuevo pedido al jugador ni un sistema de puntuación arbitrario — es un resumen de comportamiento ya registrado.

## Wallet y economía

Ya existe un sistema de wallet con bastante madurez, no es un diseño desde cero:

- Cada usuario tiene una wallet por moneda, con saldo retirable (`balance`) y saldo de bono no retirable (`bonus_balance`).
- Todo movimiento de dinero queda registrado en un ledger de auditoría (`wallet_movements`) con saldo antes/después, tipo de movimiento (catálogo configurable: depósito, retiro, compra de cartón, premio, entrada a Trivia, comisión, etc.), estado (pendiente/completado/cancelado/rechazado), y clave de idempotencia para evitar acreditaciones duplicadas en reintentos.
- Hay soporte multi-moneda con tasas de cambio configurables por comercializadora (revendedor mayorista).
- Existe una jerarquía comercial real de varios niveles: Comercializadora → Agencia → Vendedor → Jugador captado sin cuenta propia. Esto es un sistema de distribución/reventa, no solo "jugadores".

**Gap real que sí existía y ya se cerró**: no había concepto de "saldo reservado" (dinero comprometido en una partida en curso, distinto de gastado) — el dinero se debitaba de forma instantánea al entrar a una partida. Ya se agregó (reserva → captura o liberación), pensado para partidas de mayor duración donde el jugador puede retirarse a medio camino.

La ficha de administración de un jugador ya incluye su historial de movimientos de wallet paginado (junto con nivel/XP, logros y estado de seguridad) — esto se verificó durante la implementación y ya estaba construido, no fue necesario agregarlo.

## XP, niveles y logros

Existe un sistema de experiencia y niveles, pero es **global por usuario** — no hay niveles separados por rol o por juego. También existe un sistema de logros con recompensas de XP. Ninguno de los dos está conectado directamente al ledger de wallet todavía (son sistemas paralelos).

## Tiempo real / eventos

La plataforma ya usa WebSocket en producción (Laravel Reverb) para notificar en vivo: números cantados en Bingo, cambios de sala, resultados de Trivia, movimientos de wallet pendientes de aprobación, actividad administrativa, usuarios en línea. Cada juego implementa sus propios eventos de tiempo real de forma independiente para lo específico de su mecánica (ej. "se cantó el número 42"), pero **ya existen varios servicios centrales cross-juego** para los aspectos que sí se comparten:

- Un feed público de "ganancias en vivo" de toda la plataforma, sin importar el juego ni si el premio es dinero, un objeto o un servicio.
- Un registro central de señales sospechosas/anti-fraude, que acumula severidad por usuario sumando entre todos los juegos (para que hacer trampa en dos juegos distintos sume, no resetee).
- El registro de resultado final de partida (mencionado arriba) y el pago de premios también son servicios centrales, no algo que cada juego reimplemente.

Todos siguen el mismo patrón de diseño: un servicio único que cada juego llama explícitamente en su punto de interés (cuando termina una partida, cuando se paga un premio, cuando se detecta algo sospechoso) — no hay un "bus de eventos" con listeners automáticos, es una decisión de diseño deliberada del equipo.

## Hacia dónde va la plataforma

El objetivo de la iniciativa en curso era extraer de Bingo y Trivia lo que fuera genéricamente reutilizable — el concepto de "sesión de partida", "participante", "recompensa otorgada" — en una capa común ("Game Engine"). Durante la implementación se descubrió que **esa capa común, en su mayor parte, ya existía**: una iniciativa previa y separada (documentada en `progreso/xp-logros-plan.md`, completada semanas antes de esta) ya había construido el registro cross-game de resultados de partida, el otorgamiento de XP/logros, y ya estaba enganchada en producción tanto en Bingo como en Trivia. El pago de premios cross-game también ya existía (mencionado más abajo).

Por eso el alcance final fue mucho más chico que lo planeado originalmente:

1. **Bingo y Trivia NO se migraron a ninguna capa nueva** — cada uno ya resuelve bien su propio ciclo de vida de partida, y no se encontró ningún problema real que justificara tocarlos.
2. Se construyó únicamente una tabla mínima de "sesión de partida" (sin participantes, sin monto de entrada/premio normalizado) cuyo único propósito es dar un ancla real al sistema de reservas de saldo (ver más abajo), pensada para un futuro juego de duración variable — no para los juegos actuales.
3. Un futuro juego completamente distinto (con mecánica de riesgo/economía tipo "una parte arriesga un valor que sube con el tiempo, otra parte paga por intentar interceptarlo") podrá construirse reutilizando wallet, XP, eventos y WebSocket ya existentes, más esa sesión mínima si su mecánica de duración variable la necesita — pero el diseño de ese juego en sí queda para más adelante.

Ese juego futuro **no está en desarrollo todavía** — la fase actual fue exclusivamente de infraestructura compartida, y terminó siendo mucho menor de lo previsto porque gran parte ya existía.

## Decisiones de diseño ya tomadas para esta iniciativa

- No se introduce contabilidad de doble entrada completa ni event sourcing — se extiende el patrón de ledger de auditoría ya existente.
- El cruce entre la base MySQL (wallet) y la base Postgres (Trivia) nunca se resuelve con una transacción distribuida (Laravel no la garantiza entre conexiones distintas) — se usa el patrón ya validado en producción: escribir primero en la base de origen, aplicar el efecto en la otra con una clave de idempotencia, y dejar que el sistema de colas/reintentos ya existente absorba fallos parciales.
- Los roles del futuro juego (ej. un rol que arriesga vs. un rol que persigue) se modelarán como un atributo de la partida, no como roles de plataforma — la jerarquía comercial (Comercializadora/Agencia/Vendedor) es un sistema distinto y no debe mezclarse con roles de juego.
- No se está tocando el frontend Nuxt separado en esta iniciativa.

## Correcciones importantes descubiertas durante la implementación

- El patrón "outbox + idempotencia + reconciliación" para el cruce MySQL↔Postgres **ya estaba construido en producción** antes de esta iniciativa: existe un servicio (`GamePrizeService`) que paga premios de cualquier juego con una máquina de estados y claves de idempotencia, usado ya por Bingo y Trivia, más dos comandos programados cada 5 minutos que reintentan pagos que fallaron.
- El registro de "qué pasó en una partida" (jugador, si ganó, XP otorgado) y su conexión con el sistema de XP/logros/notificaciones **también ya estaba construido**, por una iniciativa previa distinta a esta (completada aproximadamente un mes antes). Esa iniciativa dejó su propio documento de seguimiento en el repo, con el mismo formato que este.
- Conclusión general: antes de asumir que hace falta construir una pieza de infraestructura compartida, vale la pena revisar si el equipo ya la resolvió en una iniciativa anterior — en este proyecto ya ocurrió más de una vez en la misma semana de trabajo.
