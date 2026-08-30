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

];
