<?php

return [
    /*
    |--------------------------------------------------------------------
    | Red y proveedor de blockchain
    |--------------------------------------------------------------------
    |
    | Ver PAYMENTS_USDT_TRC20_DESIGN.md secciones 2 y 7. El binding real
    | (BlockchainProviderInterface -> TronGridProvider|MockBlockchainProvider)
    | se decide en PaymentServiceProvider según PAYMENTS_TRON_PROVIDER —
    | 'mock' nunca debe usarse en producción (el ServiceProvider lo bloquea
    | explícitamente si app()->environment('production')).
    |
    */

    'tron' => [
        'network' => env('PAYMENTS_TRON_NETWORK', 'mainnet'),
        'provider' => env('PAYMENTS_TRON_PROVIDER', 'tron_grid'),

        // API pública de TronGrid. api_key es opcional sin autenticación
        // pero con rate limit muy bajo — se recomienda una key gratuita de
        // https://www.trongrid.io.
        'api_url' => env('PAYMENTS_TRON_API_URL', 'https://api.trongrid.io'),
        'api_key' => env('PAYMENTS_TRON_API_KEY'),

        // Contrato oficial USDT-TRC20 en mainnet. Nunca inferir por símbolo:
        // toda transacción se valida contra este valor exacto.
        'usdt_contract' => env('PAYMENTS_TRON_USDT_CONTRACT', 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t'),

        // XPUB de solo-lectura para derivar direcciones de depósito.
        // NUNCA la seed/private key — ver docblock de TronGridProvider.
        // Sin valor por defecto: sin esto, el provider real no puede
        // arrancar (falla explícito en boot, nunca deriva una dirección
        // "falsa" en silencio).
        'xpub' => env('PAYMENTS_TRON_XPUB'),

        'confirmations_required' => (int) env('PAYMENTS_TRON_CONFIRMATIONS_REQUIRED', 19),
    ],

    /*
    |--------------------------------------------------------------------
    | Expiración de órdenes
    |--------------------------------------------------------------------
    */

    'order_expiration_minutes' => (int) env('PAYMENTS_ORDER_EXPIRATION_MINUTES', 30),

    /*
    |--------------------------------------------------------------------
    | Depósitos USDT-TRC20 a la billetera (una dirección + monto único)
    |--------------------------------------------------------------------
    |
    | Sin intermediarios: todos los depósitos llegan a UNA dirección propia
    | (custodia en hardware wallet; aquí solo va la dirección pública, nunca
    | la seed). Cada orden recibe un monto único (monto pedido + un sufijo
    | de 0.000001 a 0.009999 USDT) que identifica al jugador; el watcher
    | (payments:watch-usdt-deposits) lee las transferencias TRC20 entrantes
    | vía TronGrid y acredita la orden que coincide. Lo que no coincide va a
    | revisión de tesorería. Ver UsdtDepositService / UsdtDepositWatcher.
    |
    */
    'usdt_deposits' => [
        'enabled' => (bool) env('USDT_DEPOSITS_ENABLED', false),
        'receiving_address' => env('USDT_DEPOSIT_ADDRESS'),
        'currency_iso' => env('USDT_DEPOSIT_CURRENCY_ISO', 'USDT'),
        // Ventana para pagar el monto exacto. Un pago que llega después va
        // a revisión manual (no se acredita solo: el monto pudo reasignarse).
        'window_minutes' => (int) env('USDT_DEPOSIT_WINDOW_MINUTES', 30),
        // Un monto expirado no se reasigna a otra orden durante este tiempo,
        // para que un pago tardío siga identificando a su dueño.
        'amount_reuse_cooldown_hours' => (int) env('USDT_DEPOSIT_AMOUNT_COOLDOWN_HOURS', 48),
    ],
];
