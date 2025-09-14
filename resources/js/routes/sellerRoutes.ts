// ======================================================
// Archivo: resources/js/routes/sellerRoutes.ts
// Descripción: Rutas del módulo sellerRoutes
// Fecha de actualización (GMT): 2025-09-14T04:10:28Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const sellerRoutes = {
    "create": "/seller",
    "show": "/seller/{id}",
    "update": "/seller/{id}",
    "destroy": "/seller/{id}",
    "changePassword": "/seller/{id}/password",
    "changeStatus": "/seller/{id}/status",
    "carton-generate": "/seller/bingo/carton/generate",
    "payment-status": "/seller/bingo/carton/payment/status",
    "price-currency": "/seller/bingo/price-currency/{room}/{country}",
    "winner-info": "/seller/bingo/winner-info/{ctnId}",
    "player-info": "/seller/bingo/player/{playerLink}/info",
    "player-cartons": "/seller/bingo/player/cartons/{playerLink}/{room}",
    "get-player": "/seller/bingo/player/search",
    "carton-price": "/seller/bingo/carton/price/{roomId}",
    "payment-details": "/seller/payment-details/{currency}",
    "sales-report-player": "/seller/bingo/sales-report/{room}/player/{playerLink}",
    "generate-carton": "/seller/bingo/carton/generate"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route(name, params) {
    return baseRoute(sellerRoutes, name, params);
}

/**
 * Retorna la URL de una ruta específica dentro de sellerRoutes.
 * Reemplaza los placeholders con los parámetros proporcionados.
 * Elimina los opcionales que no tengan valor.
 */
export function getRoute(name, params) {
    let url = sellerRoutes[name];

    if (params) {
        Object.entries(params).forEach(([key, value]) => {
            url = url.replace(new RegExp("{"+key+"\??}", "g"), String(value));
        });
    }

    // Elimina cualquier placeholder opcional que no tenga valor
    url = url.replace(/\/{[^}]+\?}/g, "");

    return url;
}