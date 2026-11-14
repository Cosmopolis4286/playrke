# Depósitos USDT-TRC20 a la billetera (una dirección + monto único)

Recarga de la billetera con USDT por la red TRON **sin intermediarios**: no hay
pasarela ni comisión. Todos los depósitos llegan a **una sola dirección propia** y
cada orden se identifica por un **monto único**.

## Flujo

1. El jugador entra a **Billetera → Depositar USDT** y pide, por ejemplo, 10 USDT.
2. El servidor crea la orden con un monto único: `10.000347 USDT` (10 + un sufijo
   aleatorio de 0.000001 a 0.009999 que no choca con otra orden vigente o vencida
   en las últimas 48 h). Muestra el QR de la dirección, el monto exacto y una
   cuenta regresiva (30 min).
3. El jugador paga desde Binance u otra billetera, por la red TRC20.
4. `payments:watch-usdt-deposits` corre cada minuto: lee en TronGrid las
   transferencias USDT entrantes a la dirección, registra cada una una sola vez
   (`usdt_inbound_transfers`, `tx_hash` único) y la empareja por monto:
   - con una orden vigente → `CONFIRMING`, y al llegar a 19 confirmaciones se
     **acredita el monto pedido** (10 USDT; el sufijo < 0.01 queda como redondeo).
     El movimiento es `CRYPTO_DEPOSIT` con llave idempotente `usdt_deposit:{tx}`.
   - con una orden vencida (pago tardío) → `MANUAL_REVIEW`.
   - sin dueño (monto distinto) → transferencia `UNMATCHED`.
5. Si no se acreditó, el jugador pega su **txid** en la misma pantalla y la orden
   pasa a `MANUAL_REVIEW` (vinculada a la transferencia si ya se vio).
6. Tesorería, en **Billetera → Revisión USDT**, verifica el txid en Tronscan y
   **acredita** (con el monto realmente recibido) o **rechaza** con un motivo que
   ve el jugador. Nadie puede revisar un depósito propio.

## Puesta en marcha

1. Crear la billetera receptora en una **hardware wallet** (Ledger/Trezor) o, como
   mínimo, en una billetera dedicada. La seed queda **fuera del servidor**.
2. Pedir una API key gratuita en https://www.trongrid.io.
3. Configurar en `.env`:
   ```
   USDT_DEPOSITS_ENABLED=true
   USDT_DEPOSIT_ADDRESS=T...           # dirección pública receptora
   PAYMENTS_TRON_API_KEY=...           # TronGrid
   ```
4. Tener la moneda `USDT` activa y un `WalletLimit` para USDT por nivel KYC
   (los límites de depósito se aplican al crear la orden).
5. Verificar que el scheduler corre (`php artisan schedule:work` / cron).
6. Prueba real con un monto chico antes de anunciarlo.

## Operación y riesgos

- **Montos distintos:** algunos exchanges descuentan la comisión de retiro del
  monto enviado; la pantalla le pide al jugador que el monto **llegue** exacto.
  Lo que no coincide cae en revisión, nunca se acredita solo a quien no corresponde.
- **Privacidad:** la dirección es pública en la blockchain; cualquiera puede ver el
  volumen recibido.
- **Barrido:** no hace falta: todo queda en una dirección. Mover fondos a frío o
  pagar premios se hace desde la billetera (fuera del servidor).
- **Caída de TronGrid:** el watcher reintenta cada minuto con un cursor que relee
  10 minutos hacia atrás; nada se pierde ni se duplica.
