// ======================================================
// Archivo: resources/js/routes/commerceRoutes.ts
// Descripción: Rutas del módulo commerceRoutes
// Fecha de actualización (GMT): 2025-08-10T14:33:52Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const commerceRoutes = {
    "getPaymentTreasurerDetails": "/commerce/getPaymentTreasurerDetails/{treasurer}/{coin}",
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