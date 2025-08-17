// ======================================================
// Archivo: resources/js/routes/sellerRoutes.ts
// Descripción: Rutas del módulo sellerRoutes
// Fecha de actualización (GMT): 2025-08-17T21:00:20Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const sellerRoutes = {
    "create": "/seller/create",
    "getInfo": "/seller/getInfo/{id}",
    "update": "/seller/update/{id}",
    "delete": "/seller/delete/{id}",
    "changePassw": "/seller/changePassw/{id}",
    "changeStatus": "/seller/changeStatus/{id}",
    "cartonGenerate": "/seller/bingo/carton/generate",
    "generateCarton": "/seller/bingo/carton/generate/store",
    "ctnsLotPayment": "/seller/bingo/carton/payment/status",
    "getCtnPriceCoin": "/seller/bingo/get/ctn-price-coin/{room}/{country}",
    "getWinnerInfoForCtn": "/seller/bingo/getWinnerInfoForCtn/{ctnId}",
    "getPlayerInfo": "/seller/bingo/player/{playerLink}/info",
    "showPlayerCartons": "/seller/bingo/player/cartons/{playerLink}/{room}",
    "getPlayer": "/seller/bingo/player/get/player",
    "getCtnPrice": "/seller/bingo/carton/get/price/{roomID}",
    "getPaymentCommerceDetails": "/seller/getPaymentCommerceDetails/{coin}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route<K extends keyof typeof sellerRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    return baseRoute(sellerRoutes, name, params);
}