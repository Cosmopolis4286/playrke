// ======================================================
// Archivo: resources/js/routes/commerceRoutes.ts
// Descripción: Rutas del módulo commerceRoutes
// Fecha de actualización (GMT): 2025-08-10T04:48:16Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

export const commerceRoutes = {
    "getPaymentTreasurerDetails": "/commerce/getPaymentTreasurerDetails/{treasurer}/{coin}",
    "getPaymentCommerceDetails": "/commerce/getPaymentCommerceDetails/{commerce}/{coin}",
    "sellers": "/commerce/sellers",
    "rewards": "/commerce/rewards",
    "getCoinByCountry": "/commerce/seller/getCoinByCountry/{country}/{seller}",
    "getSellerInfoByCtn": "/commerce/seller/getSellerInfoByCtn/{ctnId}",
    "ctnsLotPayment": "/commerce/bingo/carton-lot/payment"
} as const;

/**
 * Obtiene la URL para la ruta dada con parámetros opcionales.
 * @param name Nombre de la ruta (clave del objeto)
 * @param params Parámetros para reemplazar en la URL (por ejemplo {id})
 * @returns URL con parámetros reemplazados
 */
export function route<K extends keyof typeof commerceRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    let url = commerceRoutes[name] as string;

    if (params) {
        for (const [key, value] of Object.entries(params)) {
            url = url.replace(new RegExp('\{' + key + '\}', 'g'), String(value));
        }
    }

    return url;
}