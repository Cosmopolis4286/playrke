# Depósitos USDT-TRC20 a la billetera (una dirección + monto único)

Recarga de la billetera con USDT por la red TRON **sin intermediarios**: no hay
pasarela ni comisión. Todos los depósitos llegan a **una sola dirección propia** y
cada orden se identifica por un **monto único**.

## Flujo

1. El jugador entra a **Billetera → Depositar USDT** y pide, por ejemplo, 10 USDT.
2. El servidor crea la orden con un monto único: `10.000347 USDT` (10 + un sufijo
   aleatorio `0347`, de 0001 a 9999, que **ninguna otra orden** vigente o vencida
   en las últimas 48 h tiene, sea cual sea su monto). Muestra el QR de la
   dirección, el monto exacto y una cuenta regresiva (30 min).
3. El jugador paga desde Binance u otra billetera, por la red TRC20.
4. `payments:watch-usdt-deposits` corre cada minuto: lee en TronGrid las
   transferencias USDT entrantes a la dirección, registra cada una una sola vez
   (`usdt_inbound_transfers`, `tx_hash` único) y la empareja por sus **4 últimos
   decimales** (el sufijo único), que sobreviven a la comisión redonda que descuenta
   el exchange (pidió 10.000347, Binance cobró 1 → llega 9.000347):
   - orden vigente y diferencia ≤ `USDT_DEPOSIT_AMOUNT_TOLERANCE` (3 USDT) →
     `CONFIRMING`, y con 19 confirmaciones se **acredita lo que llegó** truncado a
     2 decimales (10.00 o 9.00). Movimiento `CRYPTO_DEPOSIT`, llave idempotente
     `usdt_deposit:{tx}`.
   - diferencia mayor, o pago tardío (orden vencida) → `MANUAL_REVIEW`.
   - decimales que no son de nadie (ej. pagó "10" redondo) → transferencia `UNMATCHED`.
5. Si no se acreditó, el jugador pega su **txid** en la misma pantalla y la orden
   pasa a `MANUAL_REVIEW` (vinculada a la transferencia si ya se vio). También
   puede **reportar un pago sin orden** ("¿Ya pagaste sin generar el monto?"):
   se crea una orden directamente en revisión (máx. 3 reportes abiertos por jugador).
6. Tesorería, en **Billetera → Revisión USDT**, verifica el txid en Tronscan y
   **acredita** (con el monto realmente recibido) o **rechaza** con un motivo que
   ve el jugador. Nadie puede revisar un depósito propio. Si el pago reportado
   aún no se vio en la red, hay que indicar el monto a mano.
7. El menú muestra un **contador** de pendientes en "Revisión USDT", y cada
   transferencia sin dueño por más de 24 h genera una **alerta de conciliación**.

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

- **Montos distintos:** si el exchange descuenta su comisión, se reconoce por los
  decimales y se acredita lo que llegó. Lo que no se puede atribuir con certeza cae
  en revisión; nunca se acredita solo a quien no corresponde.
- **Devoluciones:** nunca automáticas a la dirección de origen. Si vino de un
  exchange, esa dirección es compartida por miles de usuarios y el dinero se pierde.
  Se devuelve a mano, a una dirección que el jugador confirme.
- **Privacidad:** la dirección es pública en la blockchain; cualquiera puede ver el
  volumen recibido.
- **Barrido:** no hace falta: todo queda en una dirección. Mover fondos a frío o
  pagar premios se hace desde la billetera (fuera del servidor).
- **Caída de TronGrid:** el watcher reintenta cada minuto con un cursor que relee
  10 minutos hacia atrás; nada se pierde ni se duplica.
