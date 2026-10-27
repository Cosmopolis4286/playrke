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
];
