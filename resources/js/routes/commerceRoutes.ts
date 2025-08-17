// ======================================================
// Archivo: resources/js/routes/commerceRoutes.ts
// Descripción: Rutas del módulo commerceRoutes
// Fecha de actualización (GMT): 2025-08-17T21:00:20Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const commerceRoutes = {
    "create": "/commerce/create",
    "getInfo": "/commerce/getInfo/{id}",
    "update": "/commerce/update/{id}",
    "delete": "/commerce/delete/{id}",
    "changePassw": "/commerce/changePassw/{id}",
    "changeStatus": "/commerce/changeStatus/{id}",
    "getPaymentCommerceDetails": "/commerce/getPaymentCommerceDetails/{commerce}/{coin}",
    "rewards": "/commerce/rewards",
    "sellers": "/commerce/sellers",
    "getCoinByCountry": "/commerce/seller/getCoinByCountry/{country}/{seller}",
    "getSellerInfoByCtn": "/commerce/seller/getSellerInfoByCtn/{ctnId}",
    "ctnsLotPayment": "/commerce/bingo/carton-lot/payment"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route<K extends keyof typeof commerceRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    return baseRoute(commerceRoutes, name, params);
}