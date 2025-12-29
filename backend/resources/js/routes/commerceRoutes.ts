// ======================================================
// Archivo: resources/js/routes/commerceRoutes.ts
// Descripción: Rutas del módulo commerceRoutes
// Fecha de actualización (GMT): 2025-12-29T00:05:30Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const commerceRoutes = {
    "store": "/commerce",
    "show": "/commerce/{id}",
    "update": "/commerce/{id}",
    "destroy": "/commerce/{id}",
    "changePassword": "/commerce/{id}/password",
    "changeStatus": "/commerce/{id}/status",
    "rewards": "/commerce/rewards",
    "sellers": "/commerce/sellers",
    "currency-by-country": "/commerce/seller/currency/{country}/{seller}",
    "seller-info-by-carton": "/commerce/seller/info/{ctnId}",
    "carton-lot-payment": "/commerce/bingo/carton-lot/payment",
    "payment-details-public": "/commerce/payment-details/{commerce}/{currency}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route(name, params) {
    return baseRoute(commerceRoutes, name, params);
}

/**
 * Retorna la URL de una ruta específica dentro de commerceRoutes.
 * Reemplaza los placeholders con los parámetros proporcionados.
 * Elimina los opcionales que no tengan valor.
 */
export function getRoute(name, params) {
    let url = commerceRoutes[name];

    if (params) {
        Object.entries(params).forEach(([key, value]) => {
            url = url.replace(new RegExp("{"+key+"\??}", "g"), String(value));
        });
    }

    // Elimina cualquier placeholder opcional que no tenga valor
    url = url.replace(/\/{[^}]+\?}/g, "");

    return url;
}