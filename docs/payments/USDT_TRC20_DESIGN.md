# Módulo de Pagos USDT-TRC20 (TRON mainnet) — Diseño
> **Estado y advertencia:** este archivo es un documento de diseño fechado, no una especificación operativa ni una prueba de que USDT-TRC20 esté habilitado. El código debe inspeccionarse para identificar el adaptador/proveedor actualmente enlazado; el provider descrito en esta auditoría puede seguir siendo un stub. No depositar fondos reales ni asumir custodia/conciliación productiva a partir de este documento. La guía canónica de riesgos es [PAYMENTS_AND_PRIZES.md](PAYMENTS_AND_PRIZES.md).


> Alcance confirmado con el usuario: **mainnet real, fondos reales desde el día 1**.
> Custodia: **HD real con XPUB** provisto por el usuario (la seed permanece fuera del
> servidor, en su poder). Principio de diseño explícito pedido por el usuario:
> **el proveedor de blockchain/wallet debe ser intercambiable sin tocar la lógica de
> negocio** — arquitectura de puertos y adaptadores (Clean Architecture), no solo para
> el gateway ya existente sino para todo este módulo nuevo.

## 1. Diagnóstico de la arquitectura actual

### Ya existe y se reutiliza tal cual (Fases 1-10 de `MARKETPLACE_ARCHITECTURE_AUDIT.md`)

- **`App\Domains\Wallet\...`** (reestructurado recientemente por otra sesión, ya
  verificado consistente): `Wallet`, `WalletMovement`, `WalletHold`, `WalletLimit`
  (modelos), `WalletService`, `WalletReservationService`, `WalletFraudService`,
  `WalletLimitService` (servicios), con `WalletServiceProvider` haciendo bindings
  explícitos de sus repositorios (el auto-scan de `RepositoryServiceProvider` no cubre
  `app/Domains/**`).
- **`App\Domains\Marketplace\Crypto\...`** (mío, Fases 5-7): `DepositAddress`,
  `CryptoTransaction`, `CryptoDeposit`, `CryptoWithdrawal` (modelos),
  `CryptoGateway` (interfaz — el "puerto"), `TronUsdtProvider` (adaptador **STUB**,
  nunca produce direcciones reales ni firma nada), `DepositAddressService`,
  `DepositWatcher`, `WithdrawalService`.
- **`App\Domains\Marketplace\Inventory\...`**: `AssetDefinition`/`PlayerAsset` +
  `InventoryService` (grant/lock/unlock/transfer/consume) — esto **ya es** el sistema
  de "packs/consumibles" que el prompt pide entregar tras el pago. No se duplica.
- **Ledger**: `WalletMovement` con `idempotency_key` UNIQUE — la defensa real contra
  doble acreditación ya está resuelta a nivel de esquema, patrón usado en
  `WalletService::recordPrize()`/`recordMarketplaceCredit()`/`recordCryptoDeposit()`.
- **Reverb/WebSockets**: patrón `WalletUpdated` (broadcast best-effort, canal privado
  `App.Models.User.{id}`) ya establecido para notificar cambios de saldo en tiempo real.
- **Jobs + Scheduler**: patrón `schedule_dynamic()` en `routes/console.php` (con gate
  editable desde panel admin) + comandos `Console/Commands/Marketplace/*` ya siguiendo
  el estilo de log estructurado + `CommandService::logCommandExecution()`.
- **Reconciliación**: `CryptoDepositReconciliationCommand` (alerta depósitos
  confirmados sin acreditar) ya construido — se reutiliza el mismo patrón.

### Falta y es exactamente lo que este módulo entrega

- `TronBlockchainProvider` — implementación **real** de `CryptoGateway` contra TRON
  mainnet (hoy solo existe el stub).
- Derivación de direcciones real desde un XPUB real (`deriveAddress()` del stub es
  determinístico-falso; debe reemplazarse).
- Un **blockchain watcher real** que interroga TRON (vía TronGrid o un nodo propio) —
  hoy `DepositWatcher::process()` recibe la transacción ya parseada (`RawCryptoTransaction`),
  no la busca.
- `PaymentOrder` — no existe ningún concepto de "orden de compra de un pack específico"
  ligada a un precio en USDT y un producto a entregar. `App\Domains\Marketplace\Models\Order`
  es la orden de un **listing P2P** (comprador↔vendedor dentro del marketplace) — un
  dominio distinto, no se reutiliza ni se extiende para esto.
- Endpoints públicos de pagos, página de estado en Nuxt, QR.
- `PaymentAttempt`/trazabilidad fina por orden de pago (más granular que
  `CryptoTransaction`, que es genérico para depósitos/retiros de cualquier origen).

### Qué NO se toca

- El dominio Wallet reestructurado (`App\Domains\Wallet\...`) — se consume tal cual.
- `CryptoTransaction`/`CryptoDeposit` (Fase 6) — se reutilizan como el ledger de
  transacciones on-chain; `PaymentOrder` referencia una `CryptoTransaction`, no la
  duplica.
- `InventoryService`/`AssetDefinition`/`PlayerAsset` — la entrega del pack usa
  `AssetConsumptionService::grantByCode()` ya existente (Fase 10).
- El Escrow P2P — el prompt es explícito: sin escrow P2P en este módulo.

## 2. Principio arquitectónico: puertos y adaptadores, explícito

```
                    ┌─────────────────────────────┐
                    │   PaymentService (dominio)   │   ← nunca conoce TRON
                    │   BlockchainWatcherService    │      ni TronGrid ni TronWeb
                    └───────────────┬─────────────┘
                                    │ depende de
                                    ▼
                    ┌─────────────────────────────┐
                    │  BlockchainProviderInterface  │   ← el "puerto", estable
                    │  (puerto único, agnóstico     │
                    │   de red/proveedor)           │
                    └───────────────┬─────────────┘
                                    │ implementado por
                    ┌───────────────┴───────────────┐
                    ▼                                ▼
        TronGridProvider                  (futuro: TronNodeProvider,
        (adaptador de hoy,                 EthereumProvider, etc. —
         vía TronGrid API)                 mismo puerto, cero cambios
                                            en PaymentService)
```

- **`BlockchainProviderInterface`** es la interfaz de la que depende toda la lógica de
  negocio. Reemplazar el proveedor (TronGrid → nodo propio → otro indexador → otra red)
  es **un solo binding nuevo** en un `ServiceProvider`, igual que
  `MarketplaceServiceProvider::register()` hace hoy con `CryptoGateway → TronUsdtProvider`.
- **Separación recepción/firma real**: `BlockchainProviderInterface` solo tiene métodos
  de **lectura** (derivar dirección desde XPUB, consultar transacciones, confirmaciones).
  No expone ningún método de firma/broadcast de retiro — eso es intencional: este módulo
  es "USDT entra → se acredita un pack", nunca "Playrke envía USDT". Cero necesidad de
  una clave privada en el servidor para este alcance. Si en el futuro se necesita firmar
  (retiros, Fase 7 del Marketplace), esa responsabilidad vive en un servicio de custodia
  aislado — ya diseñado conceptualmente en `CryptoGateway::broadcastWithdrawal()` (Fase 5-7,
  bloqueado hasta resolver proveedor de custodia para retiros).
- **HD sin private key en el servidor**: `TronGridProvider::deriveAddress()` deriva la
  dirección pública TRON desde el XPUB usando aritmética de curva elíptica pública
  (BIP32 sin la clave privada) — matemáticamente imposible reconstruir la seed desde ahí.
  El XPUB en sí **no es secreto absoluto** (quien lo tenga puede ver todas las direcciones
  y sus balances, pero no puede gastar) — igual se trata como configuración sensible
  (`.env`, nunca en el repo).

## 3. Diagrama de flujo (feliz)

```
Usuario en Nuxt              Backend Laravel                    TRON mainnet
     │                              │                                  │
     ├─ POST /payments/orders ─────►│                                  │
     │                              ├─ PaymentOrderService::create()   │
     │                              │  - resuelve producto/pack        │
     │                              │  - resuelve/deriva DepositAddress│
     │                              │  - fija expected_amount, expires │
     │◄──── PaymentOrder (pending) ─┤                                  │
     │                              │                                  │
     ├─ ve QR + dirección + monto   │                                  │
     │                              │                                  │
     │   (usuario paga desde        │                                  │
     │    TronLink, fuera de        ├────────────── ────────────────►  │ tx broadcast
     │    esta app)                 │                                  │
     │                              │                                  │
     │                    ┌─────────┴─────────┐                        │
     │                    │ BlockchainWatcher  │◄── polling periódico ──┤
     │                    │ (Job + Scheduler)  │    (getTransactions    │
     │                    └─────────┬─────────┘     por dirección)     │
     │                              │                                  │
     │                    detecta tx entrante                          │
     │                    ├─ valida: red, contrato USDT,                │
     │                    │  to_address, token, decimals               │
     │                    ├─ PaymentOrder → DETECTED                    │
     │◄── WS: payment.detected ─────┤                                  │
     │                              │                                  │
     │                    (jobs periódicos siguientes)                 │
     │                    ├─ consulta confirmations ───────────────────►│
     │                    ├─ confirmations >= umbral?                  │
     │                    │    no → CONFIRMING, reintenta               │
     │                    │    sí → sigue                               │
     │                    ├─ compara received_amount vs expected:       │
     │                    │    < → UNDERPAID (no acredita)              │
     │                    │    > → OVERPAID (no acredita automático,    │
     │                    │         alerta admin)                      │
     │                    │    == → CONFIRMED                           │
     │◄── WS: payment.confirmed ─────┤                                  │
     │                              │                                  │
     │                    ├─ DB::transaction():                        │
     │                    │   PurchaseService::deliver()                │
     │                    │   - idempotency_key = "payment:{order_id}" │
     │                    │   - AssetConsumptionService::grantByCode() │
     │                    │   - Purchase::create() (ledger)             │
     │                    │   - PaymentOrder → CREDITED                 │
     │◄── WS: payment.credited ─────┤                                  │
     │                              │                                  │
     ├─ "Compra completada"         │                                  │
```

## 4. Modelo de datos

Todas las tablas nuevas en `database/migrations/payments/`, cargadas por un
`PaymentServiceProvider` dedicado (mismo patrón que `MarketplaceServiceProvider`).
IDs Snowflake (`snowflake()`/`foreignSnowflake()`), montos en `decimal(20,8)` (precisión
on-chain — nunca float), nunca en `int` de "unidades mínimas" porque USDT-TRC20 usa 6
decimales y el resto del proyecto ya usa `decimal` para todo lo monetario.

### `payment_products` (packs/consumibles vendibles por USDT)

```
id                  snowflake PK
code                string(60) unique          -- ej. STARTER_PACK
name                string(120)
asset_definition_id FK -> asset_definitions     -- QUÉ se entrega (reusa Inventory)
asset_quantity      unsignedInteger             -- CUÁNTO se entrega
price_usdt          decimal(20,8)               -- precio fijo en USDT
status              enum(ACTIVE,INACTIVE)
timestamps
```

### `payment_orders`

```
id                     snowflake PK
user_id                FK -> users
payment_product_id     FK -> payment_products
deposit_address_id     FK -> crypto_deposit_addresses (nullable hasta asignarse)
network                string(20)               -- 'TRON'
token                  string(20)               -- 'USDT'
token_contract         string(64)               -- contrato USDT-TRC20 configurado, snapshot al crear
expected_amount        decimal(20,8)
received_amount        decimal(20,8) nullable
crypto_transaction_id  FK -> crypto_transactions nullable
status                 enum(PENDING,DETECTED,CONFIRMING,CONFIRMED,UNDERPAID,
                             OVERPAID,EXPIRED,FAILED,CANCELLED,CREDITED)
detected_at            timestamp nullable
confirmed_at           timestamp nullable
expires_at             timestamp
metadata               json nullable
timestamps

index(status)
index(user_id)
index(deposit_address_id)
unique(crypto_transaction_id)   -- una tx nunca se asocia a dos órdenes
```

### `payment_attempts` (trazabilidad fina — cada vez que el watcher evalúa la orden)

```
id                snowflake PK
payment_order_id  FK -> payment_orders
tx_hash           string(100) nullable          -- null si aún no se detectó nada
attempted_action  string(40)                    -- 'detect' | 'confirm' | 'credit'
result            enum(SUCCESS,REJECTED,ERROR)
reason            string(255) nullable          -- por qué se rechazó (underpaid, wrong contract...)
created_at         timestamp
-- sin updated_at: es un log append-only

index(payment_order_id)
index(tx_hash)
```

### `purchases` (ledger de "qué se le entregó a quién y por qué pago")

```
id                    snowflake PK
user_id               FK -> users
payment_order_id      FK -> payment_orders unique   -- 1:1, idempotencia estructural
payment_product_id    FK -> payment_products
player_asset_id       FK -> player_assets            -- resultado de AssetConsumptionService::grantByCode()
delivered_at          timestamp
timestamps
```

Reutilizadas sin cambio: `crypto_deposit_addresses` (Fase 5, `DepositAddress`),
`crypto_transactions` (Fase 6). `PaymentOrder.deposit_address_id` apunta a una fila de
`DepositAddress` — este módulo decide si reutiliza una dirección por usuario+red (más
simple, patrón ya construido en `DepositAddressService::getOrCreate()`) o deriva una
dirección **por orden** para poder identificar el pago solo por dirección de destino sin
depender del monto exacto. Ver sección 6 (riesgo de colisión de monto).

## 5. Endpoints

```
POST   /api/payments/orders                    -- crea PaymentOrder para un payment_product
GET    /api/payments/orders/{id}                -- detalle completo
GET    /api/payments/orders/{id}/status         -- solo status (polling fallback)
GET    /api/payments/orders/{id}/qr             -- imagen QR (SVG/PNG)
GET    /api/payments/transactions/{txHash}       -- detalle de una CryptoTransaction (admin/soporte)
```

**Sin `POST /api/payments/webhooks/tron`** por decisión de diseño: TronGrid no ofrece
webhooks nativos confiables para este caso de uso (a diferencia de servicios como
Alchemy/Moralis) — el mecanismo primario es **polling activo** vía Job+Scheduler
consultando transacciones por dirección, tal como el propio prompt permite explícitamente
("si existe una alternativa confiable mediante eventos/webhooks" — no existe una nativa y
gratuita de TronGrid hoy; se documenta como decisión, no como omisión). Si más adelante se
contrata un indexador con webhooks reales (ej. un proveedor de terceros), se agrega un
endpoint de webhook que solo **encola** el mismo Job de verificación — nunca confía en el
payload del webhook para acreditar directamente (ver sección 6).

Controllers delgados (`PaymentOrderController`, `PaymentTransactionController`) que
delegan 100% en `PaymentOrderService`/`PurchaseService` — sin lógica blockchain en
ningún controller, tal como exige el prompt.

## 6. Riesgos y cómo se cubren

| Riesgo (del prompt) | Cobertura |
|---|---|
| Doble acreditación / mismo tx_hash dos veces | `crypto_transactions.tx_hash` UNIQUE (ya existe) + `purchases.payment_order_id` UNIQUE + `idempotency_key` en `WalletMovement`/entrega — triple capa, ninguna depende de lógica de aplicación sola |
| Pago insuficiente/excesivo | Comparación exacta `received_amount` vs `expected_amount` antes de pasar a CONFIRMED; under/overpayment quedan en estado propio, nunca se acreditan automáticamente |
| Token/contrato falso | `token_contract` es snapshot server-side al crear la orden desde `config('payments.tron.usdt_contract')` — el watcher solo acepta transferencias de ESE contrato exacto, nunca infiere por símbolo |
| Dirección incorrecta | El watcher solo consulta transacciones **hacia** `deposit_address_id` de la orden — nunca acredita algo recibido en otra dirección |
| Confirmaciones insuficientes | Estado `CONFIRMING` explícito con umbral configurable (`TRON_CONFIRMATIONS_REQUIRED`), igual patrón que `DepositWatcher` de la Fase 6 |
| Orden expirada | `expires_at` — el watcher ignora/expira órdenes vencidas; un pago que llega tarde a una dirección de orden expirada se marca para revisión manual (nunca se pierde el dinero, pero tampoco se acredita solo) |
| Race condition (dos jobs procesando la misma orden) | `SELECT ... FOR UPDATE` sobre `PaymentOrder` + transición atómica de estado (`UPDATE ... WHERE status = X`), mismo patrón que `EscrowService`/`InventoryService` ya construidos |
| Reorg de cadena | Fuera de alcance total (igual que Fase 6) — se documenta como limitación conocida; el umbral de confirmaciones alto mitiga la probabilidad práctica |
| Webhook duplicado/malicioso/replay | No hay webhook externo de confianza en el diseño primario — si se agrega uno después, nunca acredita directo, solo dispara la misma verificación on-chain real |
| Entrega duplicada del pack | `purchases.payment_order_id` UNIQUE + `PurchaseService::deliver()` idempotente (mismo patrón `GamePrizeService::payout()`: verificar bajo lock antes de entregar) |
| XPUB comprometido | Solo permite *ver* direcciones/saldos, nunca gastar — riesgo de privacidad, no de robo. Igual se trata como secreto de configuración |
| Custodia de fondos reales sin retiro | Los USDT que llegan a las direcciones derivadas quedan ahí — este módulo no mueve esos fondos a ningún lado. Requiere que el usuario, fuera de esta app, barra (sweep) periódicamente esas direcciones hacia su wallet fría — **explícitamente fuera de alcance de código**, es un proceso operativo manual del usuario con su propia clave privada en TronLink u otra wallet, tal como el prompt excluye retiros |

## 7. Modo sandbox vs. producción

Pese a que el usuario confirmó mainnet real, se construye igual el flag de aislamiento
que pide el prompt, porque development/tests seguirán necesitando un modo mock:

```
PAYMENTS_TRON_NETWORK=mainnet|shasta|nile   -- red real vs. testnets
PAYMENTS_TRON_PROVIDER=tron_grid|mock       -- mock solo para tests automatizados, nunca en producción
```

`MockBlockchainProvider` (implementa `BlockchainProviderInterface`, vive en
`tests/Support/` o `app/Domains/Payments/Blockchain/MockBlockchainProvider.php` detrás de
un guard que impide bindearlo si `app()->environment('production')`) — permite que la
suite de tests corra sin llamadas HTTP reales, seleccionable solo en `local`/`testing`.

## 8. Archivos a crear (ninguno existente se modifica salvo bindings de provider)

```
database/migrations/payments/
  xxxx_create_payment_products_table.php
  xxxx_create_payment_orders_table.php
  xxxx_create_payment_attempts_table.php
  xxxx_create_purchases_table.php

app/Domains/Payments/
  Models/PaymentProduct.php, PaymentOrder.php, PaymentAttempt.php, Purchase.php
  Contracts/BlockchainProviderInterface.php
  Providers/TronGridProvider.php
  Services/PaymentOrderService.php
  Services/BlockchainWatcherService.php
  Services/PurchaseService.php
  Services/QrCodeService.php
  Events/PaymentDetected.php, PaymentConfirmed.php, PaymentCredited.php
  Exceptions/InvalidPaymentTransactionException.php, PaymentOrderExpiredException.php
  Jobs/ProcessPaymentOrderJob.php
  Console/Commands/WatchPaymentOrdersCommand.php

app/Http/Controllers/Payments/
  PaymentOrderController.php
  PaymentTransactionController.php

app/Http/Requests/Payments/CreatePaymentOrderRequest.php
app/Http/Resources/Payments/PaymentOrderResource.php

app/Providers/PaymentServiceProvider.php   (registrado en bootstrap/providers.php)

config/payments.php

frontend (Nuxt, fuera del backend Laravel):
  pages/payments/[id].vue
  composables/usePaymentStatus.ts

tests/Unit/Domains/Payments/**
tests/Feature/Payments/**
```

## 9. Dependencias nuevas

- **`comhon/tron-php`** o **`iexbase/tron-api`** (cliente PHP para TRON/TronGrid) —
  a evaluar cuál tiene mejor soporte de TRC20 y mantenimiento activo; alternativa:
  llamadas HTTP directas a la API REST de TronGrid vía `Illuminate\Http\Client` sin
  librería adicional (más control, menos superficie de dependencia externa — preferido
  si el cliente disponible está poco mantenido).
- **`simplesoftwareio/simple-qrcode`** (o `endroid/qr-code`, del que depende) — generación
  de QR, ya ampliamente usado en el ecosistema Laravel.
- Derivación BIP32 de direcciones TRON desde XPUB: **`kornrunner/keccak`** +
  **`simplito/elliptic-php`**, o evaluar si el cliente TRON elegido ya incluye esto.

## 10. Fases de implementación

| Fase | Contenido |
|---|---|
| A | Migraciones + modelos (`PaymentProduct`, `PaymentOrder`, `PaymentAttempt`, `Purchase`) |
| B | `BlockchainProviderInterface` + `TronGridProvider` (derivación real desde XPUB, consulta de transacciones/confirmaciones reales contra TronGrid) — con `MockBlockchainProvider` en paralelo para tests |
| C | `PaymentOrderService` (crear orden, asignar/derivar dirección, QR) + endpoints HTTP |
| D | `BlockchainWatcherService` + Job + comando de scheduler (detección → confirmación) |
| E | `PurchaseService` (entrega idempotente vía `AssetConsumptionService`) + eventos Reverb |
| F | Frontend Nuxt (`/payments/{id}`, WS + polling fallback) |
| G | Panel admin (listado de órdenes, reproceso seguro) + reconciliación |
| H | Tests (los 20 casos del prompt) |

Cada fase se implementa y valida contra `MockBlockchainProvider`/testnet antes de
confirmar el binding real a mainnet — el interruptor final (`PAYMENTS_TRON_NETWORK=mainnet`)
es una variable de entorno, nunca un cambio de código.
