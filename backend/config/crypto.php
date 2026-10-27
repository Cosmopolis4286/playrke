<?php

return [
    /*
    |--------------------------------------------------------------------
    | Feature flags cripto
    |--------------------------------------------------------------------
    |
    | Ver MARKETPLACE_ARCHITECTURE_AUDIT.md secciones 12 y 34. Todos en
    | false por defecto — CRYPTO_DEPOSITS_ENABLED solo debe activarse
    | cuando exista un CryptoGateway real detrás (no TronUsdtProvider stub,
    | ver ese archivo). CRYPTO_WITHDRAWALS_ENABLED queda para las Fases 6-7,
    | ni siquiera tiene código detrás todavía.
    |
    */

    'deposits_enabled' => env('CRYPTO_DEPOSITS_ENABLED', false),
    'withdrawals_enabled' => env('CRYPTO_WITHDRAWALS_ENABLED', false),
    'usdt_tron_enabled' => env('USDT_TRON_ENABLED', false),

    /*
    |--------------------------------------------------------------------
    | Whitelist de redes/tokens — DepositWatcher (Fase 6)
    |--------------------------------------------------------------------
    |
    | El watcher NUNCA debe inferir el token del formato de la dirección o
    | del monto — solo acepta transacciones que matcheen exactamente esta
    | whitelist. contract_address queda null hasta configurar la dirección
    | real del contrato USDT-TRC20 (a calibrar con el proveedor de custodia
    | real, ver TronUsdtProvider) — mismo criterio de "ejemplo sin datos
    | reales" que config/sellers.php.
    |
    */

    'min_confirmations' => env('CRYPTO_MIN_CONFIRMATIONS', 19),

    'tokens' => [
        'usdt' => ['network' => 'TRON', 'contract_address' => env('USDT_TRON_CONTRACT_ADDRESS')], // a calibrar
    ],
];
