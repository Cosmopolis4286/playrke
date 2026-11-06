# Marketplace P2P + Escrow + HD Wallet cripto — Auditoría de arquitectura
> **Estado y advertencia:** auditoría/diseño de arquitectura, no runbook ni certificación de funcionalidades productivas. Los componentes y estados descritos deben contrastarse con el código actual; las integraciones externas pueden estar en modo stub o no habilitadas. No procesar fondos reales ni activar escrow/cripto basándose únicamente en este documento. Consulta [../guides/RELEASE_READINESS.md](../guides/RELEASE_READINESS.md).


> FASE 0 del plan. Iniciativa nueva, en paralelo al plan de estrategia de producto
> (`progreso/estrategia-producto-plan.md`) — no gobernada por su regla de priorización
> Bingo/Trivia/Cargas. Alcance de esta fase: diseño y evaluación de viabilidad. La capa
> cripto se diseña completa pero la integración real con TRON/custodia de claves privadas
> queda explícitamente bloqueada hasta validación legal — ver sección 12.

## 1. Qué existe actualmente

### Motor de wallet (maduro, listo para reutilizar tal cual)

- **`Wallet`** (`app/Models/Wallet.php`, tabla `wallets`): `balance` + `bonus_balance`
  (no retirable), `status` (ACTIVE/INACTIVE/BLOCKED), único por `(user_id, currency_id)`,
  `CHECK (balance >= 0)` y `CHECK (bonus_balance >= 0)` a nivel de MySQL — no solo
  aplicación. `getPlayableBalance()`, `getAvailableBalance()` (descuenta holds activos),
  `hasSufficientAvailableBalance()`.
- **`WalletMovement`** (tabla `wallet_movements`): ledger append-only. `type` es FK lógica
  a `movement_types.code` (catálogo **dinámico**, gestionable por admin — no un ENUM fijo).
  `status` sí es ENUM fijo: `PENDING/COMPLETED/CANCELLED/REJECTED`. Columna
  **`idempotency_key` (nullable, unique)** — la pieza central de idempotencia de todo el
  sistema.
- **`WalletHold`** (tabla `wallet_holds`, migración `2026_08_24_120000`): reserva que
  **no toca `balance`/`bonus_balance`** mientras está `HELD`; solo se resta del disponible
  vía `getAvailableBalance()`. Transiciona a `CAPTURED` (genera un `WalletMovement` real)
  o `RELEASED` (sin movimiento). `CHECK (amount > 0)`.
- **`WalletService`** (`app/Services/WalletService.php`): orquestador central de todos los
  efectos sobre wallet. Métodos reutilizables directamente: `recordPrize()` (con
  `idempotencyKey` + manejo de carrera vía `QueryException` sobre el índice único),
  `recordHoldCapture()`, `recordRefund()`, `findActiveWallet()`,
  `findOwnedWalletByCurrency()`.
- **`WalletReservationService`** (`app/Services/WalletReservationService.php`):
  `hold()` / `capture()` / `release()` sobre `WalletHold`, con `lockForUpdate()` en la
  creación del hold. **Este es, conceptualmente, ya un escrow de una sola pata** (reserva
  del comprador) — ver sección 6.
- **`GamePrizeService`** (`app/Services/Games/GamePrizeService.php`): patrón de
  referencia para *cualquier* settlement idempotente y recuperable en este proyecto — FSM
  con `lockForUpdate()` de dos fases (marcar `processing` → re-verificar bajo lock antes
  de acreditar), separación entre transacción de estado y transacción de pago, contrato
  (`GamePrizeContract`) en vez de acoplarse a un modelo concreto.
- **`MovementType`** (tabla `movement_types`): catálogo admin-editable de tipos de
  movimiento (`code`, `is_credit`, `requires_approval`, `visible_roles`, `game_group`).
  Agregar tipos nuevos para marketplace/escrow es una migración de datos (seeder), no una
  migración de schema.
- **`WalletFraudService`**: velocity de depósitos/retiros, duplicate-reference,
  double-submit (Cache, TTL 30s). Acoplado a depósitos/retiros — no cubre patrones P2P
  (self-trading, wash trading).
- **`ReputationService`** (`app/Services/Games/ReputationService.php`): score 0-100
  cacheado, derivado de `AntiCheatFlag` (severidad), `UserSuspension` (activa) y volumen
  de `GameParticipation`. No conoce nada de trading — necesitará una señal nueva si el
  marketplace debe alimentarlo.
- **Eventos/WebSockets (Reverb)**: `WalletUpdated`, `WalletMovementRequested`,
  `WalletMovementProcessed` — todos con broadcast *best-effort* (try/catch +
  `Log::warning`, nunca bloquean la operación de wallet). Patrón a replicar para eventos
  de marketplace/escrow.
- **`CommissionCalculationService` / `SellerCommissionTierService`**
  (`app/Domains/Games/Bingo/Services/Payment/`, `app/Services/Roles/Seller/`): patrón de
  diseño reutilizable *como plantilla* (tasa base + tiers opcionales tras feature flag,
  resuelto por servicio dedicado + cache) para `MarketplaceFeeService` — pero acoplado a
  `Seller`/Bingo, no debe extenderse ni importarse directamente.
- **Feature flags**: patrón establecido = `config/<dominio>.php` + `env()` (ver
  `config/sellers.php` como ejemplo canónico ya en el repo). No hay paquete de flags.

### Lo que NO existe (confirmado por auditoría exhaustiva, no supuesto)

- **Marketplace / Inventory / Asset / Listing / Order / Trade / Escrow / Transfer**: cero
  resultados en todo `app/` y `app/Domains/`. Ningún dominio parcial, ningún prototipo.
- **Infraestructura cripto**: cero. `Currency.type` ya soporta el valor `CRYPTO` en su
  ENUM, pero no hay ninguna moneda cripto activa, ni `HDWallet`, ni `DepositAddress`, ni
  nada relacionado a TRON/USDT/derivación de direcciones. `BinanceAccount` es solo un
  directorio de cuentas Binance del usuario para retiros manuales — no es una wallet
  on-chain.
- **Máquina de estados de pagos genérica**: no existe. El "estado de pago" vive como FSM
  simple embebida en cada modelo de premio (`prize_status`), orquestada por
  `GamePrizeService`. No hay una abstracción reutilizable de FSM de pagos — el patrón sí
  es reutilizable (copiarlo), la clase no.
- **Jobs de reconciliación**: no existe ningún comando/job que compare ledger interno vs.
  fuente externa. Lo más cercano es `NotifyStaleWalletMovementsCommand` (alerta de
  antigüedad, no reconciliación) y `RetryFailedBingoRefundsCommand` (reintento, no
  reconciliación). Todo lo de reconciliación cripto/escrow del pedido original es
  código nuevo.
- **Consumibles/boosters/tickets/power-ups**: cero. Ningún concepto de ítem de juego
  modelado hoy — el `Asset`/`PlayerAsset` de esta iniciativa es terreno virgen.

## 2. Qué puede reutilizarse (sin modificar)

| Necesidad del marketplace | Pieza existente a reutilizar |
|---|---|
| Reservar fondos del comprador | `WalletReservationService::hold()` |
| Liberar reserva sin cargo (cancelación) | `WalletReservationService::release()` |
| Capturar reserva como gasto real | `WalletReservationService::capture()` → `WalletService::recordHoldCapture()` |
| Acreditar al vendedor / comisión de forma idempotente | Patrón `WalletService::recordPrize()` (idempotency_key + manejo de `QueryException`) |
| Catálogo de tipos de movimiento nuevos (`MARKETPLACE_SALE`, `ESCROW_REFUND`, `MARKETPLACE_FEE`, ...) | `MovementType` (fila nueva vía seeder, sin tocar schema) |
| Broadcast best-effort de eventos económicos | Patrón `WalletUpdated` (try/catch + Log::warning) |
| Feature flags | Patrón `config/<dominio>.php` + `env()` |
| Plantilla de fee configurable con tiers | Patrón de diseño de `CommissionCalculationService`/`SellerCommissionTierService` (replicar, no importar) |
| Señal de comportamiento para score de confianza | `ReputationService` como consumidor futuro (marketplace *alimenta*, no reemplaza) |
| IDs | `SnowflakeId` trait + `snowflake()`/`foreignSnowflake()` en migraciones — casts `id => string` obligatorio (ver `[[feedback_bingo_field_rename_migrations]]` en memoria del proyecto) |

## 3. Qué está incompleto

- **`WalletHold` es de una sola pata**: reserva saldo, pero no bloquea ningún activo. Un
  escrow de marketplace necesita bloquear *dos* cosas simultáneamente (dinero del
  comprador + activo del vendedor) — hay que extender el concepto, no el modelo.
- **`Currency` soporta `type = CRYPTO`** en el enum pero no hay ninguna fila cripto real,
  ni columnas para red/contrato de token (ej. `network`, `contract_address`,
  `decimals` on-chain vs. `decimal_places` de presentación).
- **`WalletFraudService`** no tiene ganchos para patrones P2P (self-trading, cuentas
  relacionadas, operaciones circulares) — es velocity/duplicidad de depósito-retiro
  únicamente.

## 4. Qué falta (construir desde cero)

- Dominio `Marketplace` completo: `AssetDefinition`, `PlayerAsset` (inventario),
  `Listing`, `Order`, `Escrow`, `Dispute`, fees configurables.
- Dominio `Crypto` completo: `DepositAddress`, `CryptoDeposit`, `CryptoWithdrawal`,
  `CryptoTransaction`, `CryptoProvider`/`CryptoGateway` (abstracción multi-red),
  `DepositWatcher`, `WithdrawalService`, capa de firma aislada (fuera del alcance de
  código de esta fase — solo diseño de la interfaz).
- Extensión de `MarketplaceFraudService` (nuevo, no reutiliza `WalletFraudService`
  directamente — lo compone) para wash-trading/self-trading/operaciones circulares.
- Jobs de reconciliación nuevos: `EscrowReconciliation`, `CryptoDepositReconciliation`,
  `CryptoWithdrawalReconciliation` — no existe ningún precedente de "job de
  reconciliación" en el repo; se diseñan desde cero siguiendo el estilo de
  `NotifyStaleWalletMovementsCommand` (idempotentes, de solo alerta, nunca
  auto-corrección silenciosa).

## 5. Qué debe modificarse

- `Currency`: agregar columnas para soportar cripto real (red, dirección de contrato,
  decimales on-chain) — aditivo, no rompe lo existente.
- `MovementType`: seeder con los tipos nuevos (`MARKETPLACE_SALE`, `MARKETPLACE_PURCHASE`,
  `MARKETPLACE_FEE`, `ESCROW_REFUND`, `CRYPTO_DEPOSIT`, `CRYPTO_WITHDRAWAL`, etc.), todas
  con `is_system = true` como las originales.
- Ninguna clase existente de `WalletService`/`WalletReservationService`/`GamePrizeService`
  requiere cambios de comportamiento — se consumen tal cual.

## 6. Qué NO debe tocarse

- `WalletService`, `WalletReservationService`, `GamePrizeService`,
  `GameCompletionService`, `CommissionCalculationService`, `SellerCommissionTierService`,
  `ReputationService`, `AchievementService`, `UserLevelService` — ninguno se modifica ni
  se extiende por herencia. El marketplace es un **consumidor** de sus métodos públicos.
- El ENUM `wallet_movements.status` (`PENDING/COMPLETED/CANCELLED/REJECTED`) — el escrow
  define sus propios estados en su propia tabla, nunca reutiliza ni extiende este ENUM.
- `bingo_creator_sales` / `bingo_card_payments` / lógica de Bingo-Trivia — cero
  acoplamiento del marketplace a un juego concreto (regla explícita del pedido original).

## 7. Riesgos arquitectónicos

- **Cross-conexión MySQL/Postgres**: si algún activo o metadata de marketplace terminara
  viviendo en `pgsql_trivia` (no debería, pero es el error más fácil de cometer por
  analogía con Trivia), se rompe la atomicidad exactamente como ya está documentado en el
  docblock de `WalletService` — el escrow completo debe vivir en MySQL, misma conexión que
  `wallets`/`wallet_movements`.
- **Falta de máquina de estados formalizada**: sin una librería de FSM, cada transición de
  `Escrow`/`Listing`/`CryptoWithdrawal` es responsabilidad de disciplina manual (guards
  explícitos por método, como ya hace `GamePrizeService`). Es viable pero exige el mismo
  rigor en cada método nuevo.
- **Dos "reservas" con semántica distinta compitiendo por el mismo saldo**: si un usuario
  tiene un `WalletHold` de un juego (Cargas, futuro) y simultáneamente el marketplace
  necesita reservar el mismo saldo disponible, `getAvailableBalance()` ya las suma
  correctamente (resta todos los holds activos) — pero cualquier lógica nueva que
  recalcule disponible sin pasar por `Wallet::getAvailableBalance()` reintroduce el bug.

## 8. Riesgos de concurrencia

- Igual que `recordTriviaEntry`/`recordHoldCapture`, cualquier operación que lea+escriba
  `Wallet.balance`/`bonus_balance` o cree un `WalletHold` debe usar
  `Wallet::lockForUpdate()` dentro de `DB::transaction()` — nunca confiar en el balance
  leído fuera del lock.
- **Compra concurrente del mismo listing**: dos compradores pujando por el mismo
  `PlayerAsset` en el mismo instante. Requiere `SELECT ... FOR UPDATE` sobre la fila del
  `Listing` (o del `PlayerAsset`) al momento de crear la `Order`/`Escrow`, más una
  transición de estado atómica `ACTIVE → RESERVED` con `WHERE status = 'ACTIVE'` en el
  `UPDATE` (afectar 0 filas = perdiste la carrera, lanzar excepción de dominio). Ver
  sección 24 para el test que debe probarlo.
- **Doble settlement**: replicar exactamente el patrón de dos transacciones de
  `GamePrizeService::payout()` (transacción 1 cambia estado bajo lock; transacción 2
  re-verifica bajo lock antes de mover dinero/activo) + `idempotency_key` en cada
  `WalletMovement` generado por el escrow.

## 9. Riesgos de doble gasto

- Un `PlayerAsset` vendido y a la vez usado/consumido en un juego. Mitigación: el estado
  `LOCKED` del inventario debe ser verificado (bajo `lockForUpdate()`) en *todo* punto de
  consumo de un asset — no solo en el marketplace. Esto implica que cualquier
  `AssetConsumptionService` futuro (Cargas: radares, escudos, etc.) debe consultar el
  estado del `PlayerAsset` antes de permitir el consumo, igual que el marketplace lo
  bloquea antes de vender.
- Fondos: ya cubierto por el sistema de holds — un `WalletHold` capturado no puede
  liberarse ni volver a capturarse (`isActive()` ya lo impide en
  `WalletReservationService`).

## 10. Riesgos de doble transferencia

- Transferir el mismo `PlayerAsset` dos veces (bug de reintento de job, o dos requests
  concurrentes). Mitigación: la transferencia de ownership debe ser una transición de
  estado atómica con `UPDATE ... WHERE status = 'ASSET_LOCKED' AND escrow_id = ?` — 0 filas
  afectadas = ya se transfirió, tratar como éxito idempotente (mismo criterio que
  `recordPrize()` ante colisión de `idempotency_key`).

## 11. Riesgos de duplicación de activos

- Un `PlayerAsset` no debe poder "existir dos veces" tras un fallo parcial de settlement
  (ej. crear el asset del comprador sin descontar/transferir el del vendedor). Mitigación:
  la transferencia de un `PlayerAsset` transferible es un `UPDATE owner_id` sobre la fila
  existente (nunca un `DELETE` + `INSERT`), dentro de la misma transacción que resuelve el
  escrow. Para activos apilables (`quantity`), usar `UPDATE ... SET quantity = quantity -
  ?` con `WHERE quantity >= ?` (0 filas afectadas = fondos de inventario insuficientes,
  igual que `CHECK (balance >= 0)` en wallets).

## 12. Riesgos relacionados con blockchain

- **Custodia de claves**: la aplicación web NUNCA debe tener la master seed/private key en
  el mismo proceso/entorno que sirve tráfico HTTP. **Esta fase NO implementa custodia
  real** — se diseña la interfaz `CryptoGateway`/`Signer` como límite de responsabilidad,
  y la integración con TRON real queda bloqueada explícitamente hasta:
  1. Decisión de proveedor (custodial de terceros vs. HSM/KMS propio) — decisión de
     negocio/legal, no técnica.
  2. Validación regulatoria (posible requisito de licencia VASP/money transmitter según
     jurisdicción) — fuera del alcance de este documento.
- **Replay / doble procesamiento de depósito**: mitigado por constraint único sobre
  `tx_hash` en `crypto_transactions` — mismo patrón que `idempotency_key` en
  `wallet_movements`.
- **Confirmaciones insuficientes**: el watcher debe exigir un mínimo configurable de
  confirmaciones antes de crear el `CryptoDeposit` — nunca acreditar sobre una transacción
  no confirmada.
- **Red/token equivocado**: validar explícitamente `network` y `contract_address` contra
  una lista blanca configurada (`config/crypto.php`), nunca inferir el token del monto o
  del formato de la dirección.
- **Reorg de cadena**: fuera de alcance de la Fase 5-7 (dirección/depósito) tal como
  diseñada — se documenta como limitación conocida, no se resuelve en este documento.

## 13. Propuesta de arquitectura

```
app/Domains/
├── Marketplace/
│   ├── Models/          (Listing, Order, Trade, MarketplaceFee)
│   ├── Services/        (ListingService, OrderService, MarketplaceFeeService)
│   ├── Events/
│   └── Contracts/
├── Inventory/
│   ├── Models/          (AssetDefinition, PlayerAsset)
│   ├── Services/        (InventoryService — lock/unlock/consume/transfer)
│   └── Contracts/
├── Escrow/
│   ├── Models/          (Escrow)
│   ├── Services/        (EscrowService — create/fund/lockAsset/release/cancel/refund/dispute/resolve)
│   └── Events/
└── Crypto/
    ├── Models/          (DepositAddress, CryptoTransaction, CryptoDeposit, CryptoWithdrawal)
    ├── Services/        (CryptoGateway contract, WithdrawalService, DepositWatcher)
    └── Providers/        (TronUsdtProvider implements CryptoGateway — única pieza acoplada a una red)
```

Todo lo económico (Escrow, Inventory, Marketplace) sigue consumiendo `WalletService`/
`WalletReservationService` como única fuente de verdad de saldo — nunca se escribe
`wallets.balance` fuera de esas dos clases.

## 14. Modelo de activos

Dos entidades separadas, consistente con el patrón `MovementType` (catálogo) vs.
`WalletMovement` (instancia):

**`AssetDefinition`** (tabla `asset_definitions`) — catálogo, admin-gestionable:
```
id                   snowflake PK
code                 string(60) unique      // ej. HUNTER_RADAR
name                 string(120)
description          string(255) nullable
category             string(40)             // BOOSTER | CONSUMABLE | COSMETIC | EQUIPMENT | RESOURCE | TICKET
rarity               string(20) default COMMON
stackable            boolean default true    // true = quantity acumulable; false = instancia única (no fungible)
tradable             boolean default false
transferable         boolean default false
consumable           boolean default false
marketplace_enabled  boolean default false
game_group           string(40) nullable     // BINGO | TRIVIA | CARGAS | null=genérico — mismo patrón que movement_types.game_group
metadata             json nullable           // atributos propios del tipo (ej. radar_range, shield_durability)
status               enum(ACTIVE,INACTIVE) default ACTIVE
sort_order           integer default 0
timestamps
```

**`PlayerAsset`** (tabla `player_assets`) — instancia por dueño:
```
id                   snowflake PK
asset_definition_id  FK snowflake -> asset_definitions
owner_id             FK snowflake -> users
quantity             integer default 1       // ignorado (siempre 1) si stackable=false
status               enum(AVAILABLE,LOCKED,CONSUMED,TRANSFERRED,EXPIRED) default AVAILABLE
locked_by_escrow_id  FK snowflake nullable -> escrows   // solo mientras status=LOCKED
acquired_via         string(40)              // MARKETPLACE_PURCHASE | GAME_PRIZE | ADMIN_GRANT | ...
acquired_reference   string(64) nullable     // ID de la orden/premio que lo originó
metadata             json nullable           // estado propio de la instancia (ej. shield_charge_remaining)
expires_at           timestamp nullable
timestamps

unique(owner_id, asset_definition_id) cuando stackable=true  // una fila por owner+definición, se suma quantity
index(status)
index(locked_by_escrow_id)
```

Nunca se asume que todos los assets son comerciables: `tradable`/`transferable`/
`consumable`/`marketplace_enabled` son 4 flags independientes en la *definición*, no en
la instancia — un `AssetDefinition` con `tradable=false` nunca puede listarse,
independientemente de cuántas instancias existan.

## 15. Inventario — estados y transiciones

`AVAILABLE → LOCKED → (CONSUMED | TRANSFERRED | AVAILABLE)`, y `AVAILABLE → EXPIRED`
directo (job de expiración, fuera de esta fase de diseño detallado).

Todas las transiciones pasan por `InventoryService` (`app/Domains/Inventory/Services/`),
nunca por un `PlayerAsset::update()` directo desde otro dominio:

```php
final class InventoryService
{
    public function lock(PlayerAsset $asset, string $escrowId, int $quantity): void;   // AVAILABLE -> LOCKED
    public function unlock(PlayerAsset $asset): void;                                   // LOCKED -> AVAILABLE (cancelación de escrow)
    public function transfer(PlayerAsset $asset, string $newOwnerId, int $quantity): void; // LOCKED -> AVAILABLE (nueva fila en el owner destino) | TRANSFERRED si se agota
    public function consume(PlayerAsset $asset, int $quantity): void;                   // AVAILABLE -> CONSUMED (o resta quantity si stackable)
}
```

`lock()`/`transfer()`/`consume()` usan `PlayerAsset::lockForUpdate()` +
`UPDATE ... WHERE status = 'AVAILABLE'` (o `LOCKED` según transición) — 0 filas
afectadas se trata como conflicto de dominio (`AssetNotAvailableException`), nunca como
éxito silencioso. Mismo criterio que `CHECK (balance >= 0)` en wallets: la base de datos
impide el estado imposible, la excepción de aplicación es una capa adicional, no la única.

Un asset `LOCKED` no puede: venderse de nuevo (`ListingService` rechaza si el
`PlayerAsset` no está `AVAILABLE`), transferirse fuera del flujo de escrow, consumirse,
ni modificarse (ningún otro dominio debe hacer `PlayerAsset::update()` sobre metadata
mientras `status=LOCKED`).

## 16. Marketplace — Listings y Orders

**`Listing`** (tabla `marketplace_listings`):
```
id             snowflake PK
seller_id      FK -> users
player_asset_id FK -> player_assets
quantity       integer
price          decimal(15,2)
currency_id    FK -> currencies
status         enum(DRAFT,ACTIVE,RESERVED,SOLD,CANCELLED,EXPIRED,SUSPENDED) default DRAFT
expires_at     timestamp nullable
timestamps

index(status, currency_id)
index(seller_id)
```

**`Order`** (tabla `marketplace_orders`):
```
id           snowflake PK
listing_id   FK -> marketplace_listings
buyer_id     FK -> users
quantity     integer
price        decimal(15,2)      // copiado del listing al crear — el precio no cambia si el listing se edita después
fee_amount   decimal(15,2)      // resuelto por MarketplaceFeeService al crear la orden
escrow_id    FK -> escrows nullable
status       enum(PENDING,ESCROW,COMPLETED,CANCELLED,FAILED) default PENDING
timestamps

index(buyer_id)
index(status)
```

`ListingService::activate()`/`cancel()`/`expire()` gestionan las transiciones de
`Listing`. `OrderService::buy()` es el único punto de entrada para comprar — ver flujo
completo en sección 17.

## 17. Escrow — estados y servicio central

**`Escrow`** (tabla `escrows`):
```
id                snowflake PK
order_id          FK -> marketplace_orders unique   // 1:1 — un order tiene a lo sumo un escrow activo
buyer_id          FK -> users
seller_id         FK -> users
player_asset_id   FK -> player_assets
quantity          integer
amount            decimal(15,2)     // precio total pagado por el comprador
fee_amount        decimal(15,2)     // comisión de plataforma, se resta al liberar
seller_amount     decimal(15,2)     // amount - fee_amount, lo que recibe el vendedor
currency_id       FK -> currencies
wallet_hold_id    FK -> wallet_holds nullable   // reserva de fondos del comprador
status            enum(CREATED,FUNDED,ASSET_LOCKED,READY_TO_SETTLE,RELEASED,CANCELLED,REFUNDED,DISPUTED,EXPIRED) default CREATED
expires_at        timestamp
dispute_reason    string(255) nullable
resolution        enum(BUYER_WINS,SELLER_WINS,PARTIAL,CANCELLED) nullable
resolved_by       FK -> users nullable
resolved_at       timestamp nullable
timestamps

index(status)
index(buyer_id)
index(seller_id)
```

**`EscrowService`** (`app/Domains/Escrow/Services/EscrowService.php`) — siguiendo
exactamente el patrón de dos fases de `GamePrizeService::payout()`:

```php
final class EscrowService
{
    public function create(Order $order): Escrow;                 // CREATED
    public function fund(Escrow $escrow): Escrow;                  // WalletReservationService::hold() -> FUNDED
    public function lockAsset(Escrow $escrow): Escrow;              // InventoryService::lock() -> ASSET_LOCKED -> READY_TO_SETTLE
    public function release(Escrow $escrow): Escrow;                // settlement atómico, ver sección 18 — RELEASED
    public function cancel(Escrow $escrow): Escrow;                 // antes de FUNDED — CANCELLED
    public function refund(Escrow $escrow): Escrow;                 // después de FUNDED, antes de RELEASED — hold release + asset unlock -> REFUNDED
    public function expire(Escrow $escrow): Escrow;                 // cron sobre expires_at vencido y no resuelto -> refund() -> EXPIRED
    public function dispute(Escrow $escrow, string $reason): Escrow; // READY_TO_SETTLE -> DISPUTED
    public function resolve(Escrow $escrow, string $resolution, User $admin): Escrow; // DISPUTED -> RELEASED|REFUNDED, con auditoría
}
```

Cada método valida el estado de entrada explícitamente (`if ($escrow->status !==
self::STATUS_X) throw new DomainException(...)`) — igual que
`WalletService::approveDeposit()` valida `status === PENDING` antes de actuar. Ninguna
transición se infiere implícitamente.

## 18. Flujo de compra — paso a paso con idempotencia

```
OrderService::buy(Listing $listing, User $buyer, int $quantity)
  1. DB::transaction():
     - UPDATE marketplace_listings SET status='RESERVED' WHERE id=? AND status='ACTIVE'
       -> 0 filas afectadas = ListingNoLongerAvailableException (ver sección 8, carrera
          de dos compradores)
     - crear Order (status=PENDING), fee_amount = MarketplaceFeeService::calculate(...)
     - EscrowService::create(order) -> Escrow CREATED
  2. EscrowService::fund(escrow)
     - WalletReservationService::hold($buyerWallet, $escrow->amount) -> WalletHold HELD
     - Escrow.wallet_hold_id = hold.id; status = FUNDED
  3. EscrowService::lockAsset(escrow)
     - InventoryService::lock($playerAsset, $escrow->id, $quantity) -> PlayerAsset LOCKED
     - status = ASSET_LOCKED -> READY_TO_SETTLE
  4. EscrowService::release(escrow)   [ver sección 19 — settlement atómico e idempotente]
     - InventoryService::transfer(asset, buyer_id, quantity)
     - WalletReservationService::capture(hold, 'MARKETPLACE_SALE') -> WalletMovement (débito comprador ya reflejado por el hold)
     - WalletService::recordPrize()-style credit al vendedor con
       idempotencyKey = "marketplace_escrow:{escrow.id}:seller_credit"
     - WalletService-style credit del fee a la wallet de plataforma
       (PlatformWalletService, ya existente) con
       idempotencyKey = "marketplace_escrow:{escrow.id}:platform_fee"
     - Order.status = COMPLETED; Listing.status = SOLD; Escrow.status = RELEASED
```

`settle(ESCROW-123)` llamado dos veces: el segundo llamado encuentra `escrow.status !=
READY_TO_SETTLE` (ya es `RELEASED`) y retorna sin efecto — mismo criterio de guard que
`GamePrizeService::payout()` con `isWaiting()/isProcessing()`. Aun si dos workers pasan el
guard de estado simultáneamente por una carrera, los dos `idempotency_key` (crédito
vendedor y crédito de fee) garantizan que `WalletMovement` no se duplica — la
`QueryException` sobre el índice único de `idempotency_key` se maneja exactamente como en
`WalletService::recordPrize()`.

## 19. Atomicidad del settlement

El settlement completo (paso 4 de la sección 18) ocurre en una única `DB::transaction()`
porque **todas** las tablas involucradas (`escrows`, `marketplace_orders`,
`marketplace_listings`, `player_assets`, `wallets`, `wallet_movements`, `wallet_holds`)
viven en la misma conexión MySQL — a diferencia de Trivia (Postgres), aquí sí hay
atomicidad real de Laravel/MySQL disponible y debe usarse. Dentro de esa transacción:

- `SELECT ... FOR UPDATE` sobre `Escrow` al entrar (re-verificar `READY_TO_SETTLE` bajo
  lock, igual que la segunda transacción de `GamePrizeService::payout()`).
- `SELECT ... FOR UPDATE` sobre `Wallet` del vendedor y de la plataforma antes de
  incrementar balance (mismo patrón que `recordHoldCapture()`).
- `UPDATE player_assets SET owner_id=?, status='AVAILABLE' WHERE id=? AND status='LOCKED'
  AND locked_by_escrow_id=?` — 0 filas afectadas dentro de esta transacción es un error
  de invariante (no una carrera esperada, ya que el lock de `Escrow` ya serializó el
  acceso) y debe abortar con excepción, nunca continuar silenciosamente.

Nunca se confía solo en validación de aplicación: los `UNIQUE`/`CHECK` constraints de
`wallets`, `wallet_movements.idempotency_key` y las transiciones `WHERE status=X` son la
defensa real; las excepciones de dominio son la capa de UX sobre esa defensa.

## 20. Fees configurables

`config/marketplace.php` (mismo patrón que `config/sellers.php`):
```php
return [
    'enabled' => env('MARKETPLACE_ENABLED', false),
    'p2p_trading_enabled' => env('P2P_TRADING_ENABLED', false),
    'escrow_enabled' => env('ESCROW_ENABLED', false),
    'default_fee_percent' => env('MARKETPLACE_DEFAULT_FEE_PERCENT', 5.0),
    'fee_by_category' => [
        // 'COSMETIC' => 8.0,  // a calibrar — mismo criterio que sellers.php: ejemplo marcado, no dato real
    ],
];
```

`MarketplaceFeeService::calculate(Listing $listing, float $amount): float` resuelve:
`fee_by_category[$listing->assetDefinition->category] ?? default_fee_percent`, aplicado
sobre `amount`. Igual que `CommissionCalculationService`, el fee se registra siempre en
el ledger (`Escrow.fee_amount`, y su propio `WalletMovement` de tipo `MARKETPLACE_FEE`
al liberar) — nunca se descuenta silenciosamente sin dejar rastro contable.

## 21. HD Wallet / Address Derivation — diseño de interfaz (sin custodia real)

**`DepositAddress`** (tabla `crypto_deposit_addresses`):
```
id                 snowflake PK
user_id            FK -> users
currency_id        FK -> currencies      // fila USDT nueva, type=CRYPTO
network            string(20)            // TRON | ETHEREUM | POLYGON — abstracción desde el día 1
derivation_index   unsignedBigInteger unique   // índice secuencial global, nunca reutilizado
address            string(64) unique
status             enum(ACTIVE,INACTIVE) default ACTIVE
timestamps

unique(user_id, currency_id, network)   // una dirección por usuario+moneda+red
```

La aplicación Laravel **nunca calcula la dirección a partir de la master key**. El límite
de responsabilidad:

```
Laravel (CryptoGateway::deriveAddress(userId, network))
   ↓  HTTP/RPC interno, solo pide "dame la próxima dirección para este índice"
Signer / Custody Layer (proceso/servicio aislado, fuera de este repo)
   ↓  posee la master seed, deriva la address pública (BIP44-style)
Devuelve: { derivation_index, address }  — nunca la clave privada ni la seed
```

`CryptoGateway` (interfaz, `app/Domains/Crypto/Contracts/CryptoGateway.php`):
```php
interface CryptoGateway
{
    public function deriveAddress(string $userId): array;              // {derivation_index, address}
    public function broadcastWithdrawal(CryptoWithdrawal $withdrawal): string; // devuelve tx_hash
    public function getConfirmations(string $txHash): int;
}
```

`TronUsdtProvider implements CryptoGateway` es la única clase acoplada a TRON — swap por
`EthereumUsdtProvider`/`PolygonUsdcProvider` futuro sin tocar `DepositAddress` ni
`CryptoDeposit`. **Esta fase entrega la interfaz y el contrato; la implementación real
contra un Signer/HSM queda fuera de alcance hasta la decisión de proveedor (sección 12)**.

## 22. Depósitos USDT

**`CryptoTransaction`** (tabla `crypto_transactions`) — ledger cripto, separado de
`wallet_movements`:
```
id              snowflake PK
tx_hash         string(80) unique          // constraint física contra doble-acreditación
network         string(20)
token           string(20)                 // USDT, USDC...
direction       enum(DEPOSIT,WITHDRAWAL)
from_address    string(64)
to_address      string(64)
amount          decimal(20,8)              // precisión on-chain, distinta de decimal(15,2) fiat
status          enum(PENDING,CONFIRMED,CREDITED,FAILED) default PENDING
confirmations   integer default 0
user_id         FK -> users nullable       // resuelto vía to_address -> DepositAddress -> user
wallet_id       FK -> wallets nullable
timestamps
confirmed_at    timestamp nullable
```

`CryptoDeposit` (tabla `crypto_deposits`) referencia `crypto_transaction_id` +
`wallet_movement_id` (nullable hasta acreditar) — separa "la blockchain confirmó" de "el
ledger interno acreditó", que pueden ocurrir en momentos distintos si el acreditado es
asíncrono.

`DepositWatcher::process(rawTx)`:
```
1. Validar network contra config('crypto.networks') — whitelist, nunca inferir.
2. Validar token/contract_address contra config('crypto.tokens.usdt.contract_address') —
   nunca aceptar un contrato no configurado aunque el símbolo diga "USDT".
3. Resolver to_address -> DepositAddress -> user_id (si no hay match, ignorar/loggear —
   no es un depósito de ningún usuario de la plataforma).
4. Validar confirmations >= config('crypto.min_confirmations').
5. INSERT crypto_transactions con tx_hash UNIQUE — colisión = ya procesado, salir
   idempotentemente (mismo patrón que idempotency_key).
6. Dentro de una transacción MySQL: WalletService-style credit (nuevo método,
   ej. recordCryptoDeposit(), calcado de recordAdminDeposit()) con
   idempotencyKey = "crypto_deposit:{tx_hash}".
7. Marcar crypto_transactions.status = CREDITED.
```

## 23. Fuente de verdad — sin mezclar responsabilidades

| Dominio | Fuente de verdad para |
|---|---|
| Blockchain | Existencia de tx, confirmaciones, monto enviado, address destino |
| `wallets`/`wallet_movements` | Saldo interno, reservas, movimientos, premios, comisiones |
| `player_assets` | Ownership, quantity, estado del asset |
| `marketplace_listings`/`marketplace_orders`/`escrows` | Listing, order, trade, escrow |

Ninguna caché ni desnormalización reemplaza a su fuente — ej. `Escrow.seller_amount` es
un valor calculado y persistido en el momento de crear el escrow (no se recalcula al
liberar), para que una auditoría posterior vea exactamente qué se prometió vs. qué se
liberó.

## 24. Reconciliación

Todos como `Illuminate\Console\Command` idempotentes (ejecutables repetidamente sin
efecto acumulativo), estilo `NotifyStaleWalletMovementsCommand` — de solo alerta, nunca
auto-corrección silenciosa:

- `CryptoDepositReconciliation`: para cada `crypto_transactions` con `status=CONFIRMED`
  sin `wallet_movement_id` asociado tras N minutos, alertar (crédito atascado).
- `EscrowReconciliation`: para cada `Escrow` en `READY_TO_SETTLE` más viejo que su
  `expires_at`, alertar si no fue ni liberado ni expirado por el cron correspondiente.
- `MarketplaceReconciliation`: `Listing.status=SOLD` sin `Order.status=COMPLETED`
  correspondiente (o viceversa) — inconsistencia entre las dos tablas.
- `WalletReconciliation`: ya cubierto conceptualmente por
  `NotifyStaleWalletMovementsCommand` existente — extender su alcance a los nuevos
  `movement_types` de marketplace, no duplicar el comando.

## 25. Antifraude del marketplace

`MarketplaceFraudService` nuevo (compone `WalletFraudService`, no lo reemplaza):
self-trading (comprador y vendedor con el mismo `user_id` o misma `DepositAddress`
verificada), operaciones circulares (A vende a B, B vende el mismo asset de vuelta a A en
ventana corta), precios anómalos (desviación > N% del precio mediano reciente de la
misma `AssetDefinition`), velocity de listings/compras por usuario (mismo mecanismo de
`Cache` con TTL que ya usa `WalletFraudService::checkDoubleSubmit()`).

## 26. Reputación — señales nuevas, dominio separado

`ReputationService::recalculate()` gana un input nuevo (trades completados sin disputa
vs. disputas perdidas como vendedor/comprador), pero el marketplace **nunca** escribe
directamente en `UserReputation` — expone las señales vía un método consultado por
`ReputationService`, igual que hoy consulta `AntiCheatFlag`/`GameParticipation`. Rol de
plataforma, rol de marketplace y rol de juego permanecen conceptos separados (ninguna
tabla nueva de "marketplace role").

## 27. Disputas

`EscrowService::dispute()`/`resolve()` (sección 17). Resolución `BUYER_WINS` ejecuta
`refund()` completo; `SELLER_WINS` ejecuta `release()` completo; `PARTIAL` requiere un
monto explícito del admin y genera dos `WalletMovement` (uno de refund parcial, uno de
release parcial) más `InventoryService::unlock()` o `transfer()` según corresponda.
`resolved_by`/`resolved_at`/`resolution` en la tabla `escrows` son la auditoría mínima —
un log adicional vía `Auditable` trait (ya usado por `WalletMovement`/`WalletHold`) cubre
el detalle de quién/cuándo.

## 28. Eventos y WebSockets

Eventos nuevos en `app/Domains/Marketplace/Events/` y `app/Domains/Escrow/Events/`,
todos `ShouldBroadcastNow` con broadcast best-effort (try/catch + `Log::warning`, mismo
patrón que `WalletUpdated`): `ListingCreated`, `OrderCreated`, `EscrowFunded`,
`AssetLocked`, `TradeCompleted`, `EscrowDisputed`, `EscrowResolved`,
`CryptoDepositConfirmed`, `WithdrawalStatusChanged`. Canal privado por usuario
(`PrivateChannel("App.Models.User.{userId}")`, igual que `WalletUpdated`) para eventos
que afectan a un comprador/vendedor específico; canal de admins solo para disputas
nuevas y depósitos/retiros que requieren aprobación (mismo patrón que
`WalletMovementRequested`). No se difunde cada cambio de estado interno del escrow al
cliente — solo los que cambian lo que el usuario ve en pantalla.

## 29. Seguridad — resumen por capa

Cubierto en detalle en las secciones 8-12. Resumen operativo: toda mutación de saldo pasa
por `WalletService`/`WalletReservationService` (nunca `Wallet::update()` directo desde
Marketplace/Escrow); toda mutación de inventario pasa por `InventoryService`; toda
transición de estado usa `UPDATE ... WHERE status = X` con verificación de filas
afectadas, nunca `if ($model->status === X) { $model->update(...) }` sin lock.

## 30. Testing

Unit: `MarketplaceFeeService::calculate()` por categoría/tier; transiciones de FSM de
`Escrow`/`Listing` (cada transición inválida lanza excepción). Feature: crear listing,
comprar, cancelar, expirar. Integration: flujo completo compra→escrow→settlement contra
BD real (mismo estilo que las validaciones vía `artisan tinker` documentadas en
`estrategia-producto-plan.md`). Concurrencia (crítico, sección 8): dos requests
simultáneas comprando el mismo `Listing` — asertar `1 succeeds, 1 fails` con
`Http::pool()` o procesos paralelos reales, no solo llamadas secuenciales al servicio
(una llamada secuencial nunca revela la carrera). Doble settlement: llamar
`EscrowService::release()` dos veces seguidas sobre el mismo escrow, asertar un solo
`WalletMovement` de crédito al vendedor.

## 31. Migraciones — convenciones a seguir

Nuevas tablas: `snowflake()`/`foreignSnowflake()` (nunca `bigIncrements`), `CHECK
(amount > 0)` vía `DB::statement()` cuando el driver es MySQL (mismo guard condicional
que `wallet_holds`), `unique` sobre `tx_hash`/`idempotency_key`-equivalentes desde la
migración inicial (no agregarlo después). Todas las columnas nuevas de un concepto van en
su migración `Schema::create` inicial — nunca una migración `add_column` separada para
algo que se sabe desde el diseño (regla ya establecida en memoria del proyecto,
`[[feedback_migrations_consolidadas]]`).

## 32. Observabilidad

Logs estructurados (`Log::info/warning/error` con array de contexto, mismo estilo que
`GamePrizeService`) en cada transición de `Escrow`, cada `crypto_transactions` procesada,
cada `WalletHold` de marketplace creado/capturado/liberado. Nunca loggear direcciones
completas de wallet cripto de terceros sin necesidad, y nunca — bajo ninguna
circunstancia — private keys, seeds, o credenciales del `CryptoGateway`.

## 33. Admin, API, Frontend

Vistas admin (Inertia/Vue, `backend/resources/js`): listados de Escrows/Disputas/
Depósitos/Retiros con búsqueda por `user`/`asset`/`order`/`escrow_id`/`tx_hash` — mismo
patrón de tabla+filtros server-side ya establecido en la auditoría de pantallas admin
existente (`[[feedback_admin_screens_audit_2026_08]]`). Endpoints REST bajo
`/marketplace/*`, `/wallet/deposit-address`, `/wallet/withdrawals` — adaptados a las
convenciones de rutas ya vigentes en el proyecto (ver bloque de memoria sobre HTTP
methods del directorio como ejemplo del estilo esperado). Frontend inicial únicamente en
`backend/resources/js` — el Nuxt separado queda fuera de alcance.

## 34. Feature flags

`MARKETPLACE_ENABLED`, `P2P_TRADING_ENABLED`, `ESCROW_ENABLED`,
`CRYPTO_DEPOSITS_ENABLED`, `CRYPTO_WITHDRAWALS_ENABLED`, `USDT_TRON_ENABLED` — todos en
`config/marketplace.php`/`config/crypto.php`, default `false`. Si
`CRYPTO_WITHDRAWALS_ENABLED=false`, el marketplace sigue operando con moneda interna
(fiat/juego) sin ninguna dependencia de la capa cripto — Escrow/Wallet/Marketplace/Game
permanecen desacoplados de Crypto en todo momento, no solo cuando el flag está apagado.

## 35. Resumen de fases — ver tabla al final del documento

(sección "Fases de implementación propuestas", ya incluida arriba).

## Fases de implementación propuestas

| Fase | Contenido | Bloqueada por |
|---|---|---|
| 0 | Este audit | — |
| 1 | `Inventory` + `AssetDefinition`/`PlayerAsset` | Aprobación de este audit |
| 2 | `Marketplace` básico (Listing/Order, sin escrow real) | Fase 1 |
| 3 | `Escrow` (fund/lock/release/cancel/dispute) | Fase 2 |
| 4 | Integración completa con Wallet/Ledger (fees, idempotencia end-to-end) | Fase 3 |
| 5 | HD deposit addresses (diseño de interfaz `CryptoGateway`, sin claves reales) | Fase 4 + decisión de custodia |
| 6 | Deposit watcher (contra testnet/sandbox del proveedor elegido) | Fase 5 + proveedor elegido |
| 7 | Withdrawals | Fase 6 |
| 8 | Anti-fraude + reconciliación | Fase 4 (no depende de cripto) |
| 9 | Admin + observabilidad | Fase 4 |
| 10 | Integración con juegos (Cargas y otros) | Fase 4 |

**Fases 5-7 (custodia real de fondos) quedan bloqueadas** hasta resolver la pregunta legal/
de proveedor — ver sección 12. Fases 1-4 y 8-10 no dependen de esa decisión y pueden
avanzar en paralelo con moneda interna únicamente.
