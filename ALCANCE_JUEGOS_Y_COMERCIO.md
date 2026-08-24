# Alcance funcional: Bingo, Trivia y jerarquía comercial (playrke)

Documento de contexto para compartir con otra sesión/herramienta de IA (p. ej. ChatGPT) que no tiene acceso a este repositorio. Describe, en lenguaje de negocio (no de implementación), cómo funcionan hoy los dos juegos en producción y cómo se vende/distribuye el acceso a ellos. Complementa a `ESTADO_ECOSISTEMA.md` (que cubre arquitectura técnica general: wallet, game engine, eventos, matchmaking) — este documento se enfoca en las reglas de negocio de Bingo/Trivia y en la cadena comercial de ventas.

---

## 1. Bingo

### 1.1 Modos y variantes de juego
Bingo tiene 3 modalidades de participación: **Solitario** (un jugador contra el sorteo), **Múltiple** (varios jugadores compitiendo en la misma sala) y **Ranking**. Independiente de la modalidad, el sistema soporta 4 variantes físicas de cartón:

- **Bingo 75**: cuadrícula 5×5, letras B-I-N-G-O, con casilla libre central.
- **Bingo 90**: cuadrícula 3×9, 5 números por fila.
- **Bingo 80**: cuadrícula 4×4, sin casilla libre.
- **Bingo 30 "Speed Bingo"**: cuadrícula 3×3, partidas rápidas.

Además existen **patrones de cartón** (figuras que definen cómo se gana: línea, cruz, casa llena, etc.), que pueden ser predefinidos por el sistema o creados por cada organizador. Cada ronda de sorteo usa un patrón específico. El premio se clasifica en 3 tipos según el patrón cumplido: casa llena, línea simple, o por número de serie del cartón.

### 1.2 Compra de cartones
Un jugador compra de **1 a 40 cartones por transacción**. La compra se agrupa en un "lote" que registra cantidad, precio unitario, tasa de cambio aplicada y quién vendió. El precio puede ser:
- **Fijo** por sala y moneda (definido de antemano), o
- **Dinámico**, convirtiendo un precio base en USD a la moneda local según la tasa de cambio vigente.

Para un jugador con cuenta y billetera propia, el pago se descuenta **de forma inmediata** del saldo al momento de la compra. Existe también un flujo de aprobación manual (pendiente/aprobado/rechazado/cancelado) que aplica a métodos de pago externos o a ventas registradas por vendedores/comercios con comprobante.

### 1.3 Roles dentro de una sala
Cada participante de una sala tiene un rol asignado:

- **Jugador (PLAYER)**: solo compra cartones y juega.
- **Vendedor (SELLER)**: vende cartones a terceros dentro de la sala, sin necesariamente jugar — cobra una comisión por cada venta que realiza.
- **Administrador (ADMIN)**: gestiona la sala.
- **Jugador-Vendedor (PLAYER_SELLER)**: combina ambos roles — juega y también vende.

El Vendedor gana una comisión porcentual sobre el monto de cada venta (configurable por vendedor, o con excepciones específicas por sala), que se descuenta del total antes de calcular el pozo de premios.

### 1.4 Ciclo de vida de una sala
Estados posibles: `Inactiva → Activa → En Progreso → Finalizada`, con posibilidad de `Cancelada` (desde Activa o En Progreso) o `Eliminada` (desde Inactiva) — es una secuencia estricta, no se permiten saltos inválidos entre estados.

Una sala la crea un usuario organizador (casa/comercio), se "publica" para hacerse visible y vendible, y puede iniciarse de forma **programada** (por fecha/hora) o **manual** (activación del anfitrión), sujeta opcionalmente a un mínimo de cartones/jugadores vendidos.

El sorteo ocurre en **rondas**, cada una con su propio patrón de victoria activo. Dentro de cada ronda se van cantando fichas una por una hasta que algún cartón cumple el patrón. El ganador se determina comparando los números marcados del cartón contra las fichas cantadas y el patrón vigente, con validación anti-fraude de tiempos mínimos para evitar reclamos imposibles. **Si se agotan todas las fichas sin que nadie complete el patrón**, la ronda se cierra igual como "sorteo sin ganador" — no se declara premio para esa ronda.

### 1.5 Reparto de comisiones
Sobre el monto total de cada venta de cartones se reparte así:

- **Comisión del vendedor** — porcentaje configurable, va al Vendedor que hizo la venta.
- **Comisión de sala** — porcentaje configurado en la sala, va al comercio/organizador dueño de la sala.
- **Comisión de plataforma (RKE)** — va a la plataforma.
- **Resto** — pasa al pozo de premios de la sala.

Opcionalmente puede descontarse también un pequeño porcentaje adicional para alimentar un jackpot, si la sala tiene uno asignado.

### 1.6 Premios
El pago de premio sigue un ciclo de 5 estados: **en espera** (premio generado, pendiente de pago) → **procesando** (acreditación en curso) → **cobrado** (pagado y acreditado exitosamente en la billetera del ganador) — con dos estados alternos, **cancelado** y **suspendido** (por ejemplo, si el ganador no tiene billetera activa, queda pendiente de revisión manual). Los premios de este juego son monetarios/digitales, acreditados directo al saldo del ganador — no se maneja entrega de premios físicos en este dominio.

### 1.7 Jackpots
Un jackpot es un pozo de premio acumulado, definido por juego y moneda, con dos tipos de periodicidad: **por sala** (se acumula y se gana solo dentro de una misma sala) o **compartido entre salas** (semanal, quincenal, mensual — ej. "Jackpot Semanal USDT"). Cada sala puede aportar un porcentaje configurable de sus ventas a un jackpot específico. Es **independiente y adicional** al premio normal de la sala — se acumula en un pozo aparte que crece hasta ser ganado.

---

## 2. Trivia

### 2.1 Modos de juego
Cada sala tiene asignado un modo de juego, con tres variantes relevantes:

- **Individual**: cada jugador compite solo, sin equipo asignado.
- **Equipos (TEAMS)**: los jugadores se agrupan en varios equipos; el puntaje se acumula por equipo y se define un equipo ganador.
- **Duelo (DUEL)**: caso especial de equipos, normalmente 2 bandos enfrentados; si hay empate en puntaje, se desempata por número de respuestas correctas.

### 2.2 Flujo de entrada y pago
Para unirse a una sala, el sistema cobra una tarifa de entrada de la billetera del jugador **antes** de registrarlo, con dos modelos posibles:

- **Multi-moneda**: la sala define un precio distinto por cada moneda aceptada. El sistema busca en qué moneda el jugador tiene billetera activa y cobra ahí; si no tiene ninguna de las monedas aceptadas, se rechaza el ingreso.
- **Mono-moneda (modelo legado)**: precio fijo en una sola moneda, o un precio base en USD que se convierte automáticamente a la moneda de la billetera activa del jugador.

Al pagar, el jugador queda registrado como "confirmado", con marca de tiempo de cuándo pagó y el monto pagado (guardado también en su equivalente USD, solo para fines estadísticos comparables entre monedas).

### 2.3 Modelos de premio
- **Garantizado**: el organizador fija el premio total de antemano (más un aporte opcional de patrocinador). No depende de cuántos jueguen — funciona como un torneo con premio patrocinado.
- **Bolsa acumulada (pool)**: el premio nace de sumar las entradas pagadas por todos los jugadores más el aporte de patrocinador. La plataforma retiene una comisión transparente (con un tope de seguridad del 30%) y reparte el resto.

En ambos casos el reparto entre posiciones sigue un esquema configurable (por defecto 1°=60%, 2°=25%, 3°=15%). En el modelo de bolsa acumulada se garantiza un mínimo de 3 posiciones pagas, para evitar que el ganador se lleve absolutamente todo.

### 2.4 Ciclo de vida de una partida
La **sala** es el "lobby" donde la gente se inscribe y paga; su estado va de espera → jugando → cerrada/cancelada. Dentro de una sala puede existir más de una **partida** (aunque en el flujo típico se juega una sola). La partida es la ejecución real de preguntas y respuestas, con su propio estado (jugando/pausada/finalizada/cancelada) y su propio contador de pregunta actual.

El motor de juego envía preguntas una por una (de un banco global, sin repetir, filtrables por categoría), recibe respuestas, calcula puntaje, y si nadie responde a tiempo, avanza la pregunta automáticamente para que la partida no se quede colgada.

### 2.5 Participantes
Los estados posibles de un participante son: registrado → confirmado (pagó) → jugando → termina en eliminado, ganador, o abandonado. En el motor de juego implementado actualmente **no hay eliminación progresiva real por pregunta** — todos los inscritos responden todas las preguntas del set, y el ranking se decide al final por desempeño acumulado.

### 2.6 Equipos
En los modos de equipos/duelo, el jugador elige un equipo al momento de unirse a la sala; el sistema valida que el equipo exista y tenga cupo disponible antes de aceptarlo.

### 2.7 Scoring, ranking y antifraude
El ranking final se calcula (no por azar) con esta prioridad: 1° más respuestas correctas, 2° menor tiempo de respuesta promedio, 3° mayor racha de aciertos consecutivos. Hay una regla anti-fraude explícita: un jugador con cero respuestas correctas **nunca** cobra premio, aunque le haya tocado una posición pagada.

### 2.8 Premios
Sigue el mismo ciclo de 5 estados que Bingo: en espera → procesando → cobrado (o cancelado/suspendido tras varios reintentos fallidos, congelado para revisión manual). El pago usa el mismo mecanismo central de pago de premios que Bingo, con protección para no pagar dos veces al mismo ganador.

---

## 3. Jerarquía comercial y flujo de ventas

Esta jerarquía es **distinta** de los roles de juego (jugador/vendedor dentro de una sala) — es la estructura de negocio que decide quién puede vender acceso a los juegos y cómo se reparte el dinero entre los distintos niveles de distribución.

### 3.1 Comercializadora (Commerce)
Es el socio de negocio de más alto nivel dentro de la cadena comercial (por debajo de Admin/SuperAdmin/Tesorería). Representa una empresa distribuidora que opera bajo su propia marca, con su propio país, sus propias monedas habilitadas, y sus propios juegos autorizados. Tiene una comisión propia que gana sobre cada venta hecha bajo su estructura.

Cada Comercializadora puede tener **su propia tasa de cambio**, distinta de la tasa oficial de la plataforma — esto le permite operar con un margen propio al vender saldo en moneda local. No funciona como un banco de fichas al por mayor; funciona más como una franquicia: administra sus propias Agencias y Vendedores, define comisiones, y percibe ingreso por cada cartón vendido bajo su red.

### 3.2 Agencia (Subsidiary)
Nivel intermedio **opcional** entre la Comercializadora y el Vendedor — una sucursal regional que administra su propio grupo de vendedores. Tiene comisión propia, usuario de acceso y país propios, pero usa las monedas de su Comercializadora "padre" (no tiene las suyas).

Un Vendedor **puede** reportar directo a la Comercializadora sin pasar por una Agencia — la cadena de 4 niveles admite un atajo de 3 niveles cuando no hace falta la capa regional.

### 3.3 Vendedor (Seller / "taquilla")
Quien vende los cartones directamente al comprador final. Tiene comisión propia, que puede tener excepciones específicas por sala. **Este rol es específico de Bingo** — no existe un flujo equivalente de vendedor/comisión en Trivia; la venta de acceso vía vendedor con comisión y captación de jugador externo es exclusiva del producto Bingo.

### 3.4 Jugador externo (comprador sin cuenta)
Es un comprador **sin cuenta registrada** en la plataforma, captado por un Vendedor (por ejemplo, alguien que compra un cartón sin necesidad de registrarse). El sistema le genera un link temporal de acceso (vigente por días configurables) para que entre a jugar sin login. Sus datos (nombre, teléfono, país) quedan como ficha comercial asociada al Vendedor y a la Comercializadora que hizo la venta.

Importante: esto **no es lo mismo** que un rol técnico de plataforma con nombre similar — es una entidad de negocio (comprador anónimo), no un tipo de cuenta de usuario. Un jugador externo puede eventualmente convertirse en usuario registrado con cuenta y contraseña propia, pasando a ser un jugador interno con billetera propia.

### 3.5 Tesorero (Treasurer)
Rol de finanzas a nivel de toda la plataforma (no depende de ninguna Comercializadora). Administra monedas y juegos habilitados globalmente, con comisión propia. El flujo explícito de aprobación de depósitos/retiros vive hoy en el panel general de administración — el módulo dedicado de tesorería existe en el código pero no está activo en el sistema todavía.

### 3.6 Multi-moneda y tasas de cambio
Hay dos niveles de tasa de cambio: una tasa **oficial global** de la plataforma por moneda, y tasas **propias por Comercializadora** (monto y porcentaje independientes). Esto permite que cada Comercializadora ajuste el tipo de cambio con el que opera en su territorio, generando un margen adicional de negocio distinto al oficial.

### 3.7 Flujo completo de venta (reconstruido de principio a fin)
1. La Comercializadora define sus monedas, juegos permitidos y su tasa de cambio propia.
2. Opcionalmente crea Agencias que administran grupos de Vendedores; si no, los Vendedores reportan directo a la Comercializadora.
3. Un Vendedor genera una venta de cartones de Bingo para un comprador — con cuenta (jugador interno) o sin cuenta (jugador externo, recibe un link de acceso).
4. Al confirmarse la venta, el monto se reparte automáticamente en: comisión del vendedor, comisión de la sala/Comercializadora dueña, comisión de la plataforma, y el resto al pozo de premios de la sala.
5. Todo este desglose queda registrado por venta, permitiendo auditar cuánto ganó cada eslabón de la cadena.

### 3.8 Visibilidad en el panel de administración
El panel de SuperAdmin ya reporta esta jerarquía como una estructura real (no solo como bloques de datos independientes): por cada Comercializadora muestra sus vendedores directos, el número de Agencias, los vendedores que cuelgan de esas Agencias, y el total combinado de la red completa.

---

## Notas para quien lea este documento sin contexto adicional

- Bingo y Trivia comparten infraestructura de plataforma (billetera, sistema de premios, experiencia/niveles del jugador — ver `ESTADO_ECOSISTEMA.md`), pero cada uno implementa sus propias reglas de negocio de forma independiente, como se describe arriba.
- La jerarquía comercial (sección 3) es exclusiva de la venta de acceso a Bingo hoy — Trivia se paga directo de la billetera del jugador, sin la cadena de vendedores/comisiones descrita aquí.
- Ningún dato de este documento describe planes o diseño futuro — todo lo aquí descrito ya está implementado y en funcionamiento.
