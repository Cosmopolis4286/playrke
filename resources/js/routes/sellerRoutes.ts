// ======================================================
// Archivo: resources/js/routes/sellerRoutes.ts
// Descripción: Rutas del módulo sellerRoutes
// Fecha de actualización (GMT): 2025-08-10T04:48:16Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

export const sellerRoutes = {
    "create": "/seller/create",
    "getInfo": "/seller/getInfo/{id}",
    "update": "/seller/update/{id}",
    "delete": "/seller/delete/{id}",
    "changeStatus": "/seller/changeStatus/{id}",
    "changePassw": "/seller/changePassw/{id}",
    "getPaymentCommerceDetails": "/seller/getPaymentCommerceDetails/{coin}",
    "cartonGenerate": "/seller/bingo/carton/generate",
    "generateCarton": "/seller/bingo/carton/generate/store",
    "getCtnPrice": "/seller/bingo/carton/get/price/{roomID}",
    "getCtnPriceCoin": "/seller/bingo/get/ctn-price-coin/{room}/{country}",
    "getPlayer": "/seller/bingo/player/get/player",
    "showPlayerCartons": "/seller/bingo/player/cartons/{playerLink}{room}",
    "getWinnerInfoForCtn": "/seller/bingo/getWinnerInfoForCtn/{ctnId}",
    "getPlayerInfo": "/seller/bingo/player/{playerLink}/info",
    "ctnsLotPayment": "/seller/bingo/carton/payment/status"
} as const;

/**
 * Obtiene la URL para la ruta dada con parámetros opcionales.
 * @param name Nombre de la ruta (clave del objeto)
 * @param params Parámetros para reemplazar en la URL (por ejemplo {id})
 * @returns URL con parámetros reemplazados
 */
export function route<K extends keyof typeof sellerRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    let url = sellerRoutes[name] as string;

    if (params) {
        for (const [key, value] of Object.entries(params)) {
            url = url.replace(new RegExp('\{' + key + '\}', 'g'), String(value));
        }
    }

    return url;
}