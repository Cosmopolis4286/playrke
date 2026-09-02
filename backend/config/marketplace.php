<?php

return [

    /*
    |--------------------------------------------------------------------
    | Feature flags del Marketplace P2P
    |--------------------------------------------------------------------
    |
    | Ver MARKETPLACE_ARCHITECTURE_AUDIT.md. Todos en false por defecto —
    | activar explícitamente cuando cada capa esté probada. escrow_enabled
    | y p2p_trading_enabled quedan separados de marketplace_enabled para
    | poder, por ejemplo, dejar ver listings (solo lectura) sin permitir
    | comprar todavía.
    |
    */

    'enabled' => env('MARKETPLACE_ENABLED', false),
    'p2p_trading_enabled' => env('P2P_TRADING_ENABLED', false),
    'escrow_enabled' => env('ESCROW_ENABLED', false),

    /*
    |--------------------------------------------------------------------
    | Comisión de plataforma
    |--------------------------------------------------------------------
    |
    | "a calibrar": ninguno de estos porcentajes viene de datos reales de
    | mercado — son solo para validar que el mecanismo de fee por categoría
    | funciona. Mismo criterio que config/sellers.php.
    |
    */

    'default_fee_percent' => env('MARKETPLACE_DEFAULT_FEE_PERCENT', 5.0),

    'fee_by_category' => [
        // a calibrar — ejemplo: cosméticos con fee más alto que recursos
        // 'COSMETIC' => 8.0,
    ],

];
