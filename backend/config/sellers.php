<?php

return [
    /*
    |--------------------------------------------------------------------
    | Comisión progresiva por volumen mensual
    |--------------------------------------------------------------------
    |
    | Ver progreso/estrategia-producto-plan.md, Fase 10. Bloqueada en el
    | plan original hasta calibrar con datos reales del ranking de
    | vendedores (Fase 7) — el usuario aprobó explícitamente construir el
    | MECANISMO ahora con tramos placeholder, dejando "enabled" en false
    | por defecto para que ningún pago real cambie hasta activarlo a
    | propósito con umbrales ya calibrados.
    |
    | "a calibrar": ninguno de estos números viene de datos reales de
    | ventas — son solo para validar que el mecanismo de tramos funciona.
    | No activar en producción sin revisar con datos reales del ranking
    | mensual (SellerRankingService).
    |
    */

    'progressive_commission_enabled' => env('SELLER_PROGRESSIVE_COMMISSION_ENABLED', false),

    // Tramos por volumen de venta acumulado en el mes (sale_amount, misma
    // fuente que SellerRankingService). Cada tramo suma un bonus porcentual
    // ADICIONAL sobre la comisión base ya calculada (SellerRoomLimits o
    // Seller::commission) — nunca la reemplaza.
    'progressive_commission_tiers' => [
        // a calibrar
        ['min_volume' => 0,     'bonus_percent' => 0.0],
        // a calibrar
        ['min_volume' => 500,   'bonus_percent' => 1.0],
        // a calibrar
        ['min_volume' => 1500,  'bonus_percent' => 2.5],
        // a calibrar
        ['min_volume' => 3000,  'bonus_percent' => 4.0],
    ],

    /*
    |--------------------------------------------------------------------
    | Códigos de compra para compradores recurrentes
    |--------------------------------------------------------------------
    |
    | El vendedor otorga un código a un comprador que ya le compró antes;
    | con ese código el comprador compra por su cuenta desde el enlace
    | público del vendedor (/comprar/{token}). El código vence al agotar
    | sus usos o al pasar su vigencia, lo que ocurra primero. El vendedor
    | elige ambos límites entre estas opciones al otorgarlo.
    |
    */

    'purchase_codes' => [
        'uses_options' => [1, 5, 10],
        'default_uses' => (int) env('SELLER_PURCHASE_CODE_DEFAULT_USES', 5),
        'valid_days_options' => [7, 30, 90],
        'default_valid_days' => (int) env('SELLER_PURCHASE_CODE_DEFAULT_DAYS', 30),

        // Protección contra adivinar códigos en el enlace público: tras N
        // intentos fallidos para el mismo enlace+teléfono (o desde la misma
        // IP), se bloquea por M minutos.
        'max_failed_attempts' => (int) env('SELLER_PURCHASE_CODE_MAX_FAILED', 5),
        'lockout_minutes' => (int) env('SELLER_PURCHASE_CODE_LOCKOUT_MINUTES', 15),
    ],
];
