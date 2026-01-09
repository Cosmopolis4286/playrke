// ======================================================
// Archivo: resources/js/routes/exchangeRateRoutes.ts
// Descripción: Rutas del módulo exchangeRateRoutes
// Fecha de actualización (GMT): 2025-09-14T05:40:44Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const exchangeRateRoutes = {
    "index": "/exchange-rate/{creatorType}/{creatorId?}",
    "create": "/exchange-rate",
    "show": "/exchange-rate/{id}",
    "update": "/exchange-rate/{id}",
    "destroy": "/exchange-rate/{id}",
    "changeStatus": "/exchange-rate/{id}/status",
    "get-rates": "/exchange-rate/rates",
    "last-value": "/exchange-rate/last-value/{currency}",
    "historical": "/exchange-rate/historical/{currency?}/{creatorType}/{creatorId}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route(name, params) {
    return baseRoute(exchangeRateRoutes, name, params);
}

/**
 * Retorna la URL de una ruta específica dentro de exchangeRateRoutes.
 * Reemplaza los placeholders con los parámetros proporcionados.
 * Elimina los opcionales que no tengan valor.
 */
export function getRoute(name, params) {
    let url = exchangeRateRoutes[name];

    if (params) {
        Object.entries(params).forEach(([key, value]) => {
            url = url.replace(new RegExp("{"+key+"\??}", "g"), String(value));
        });
    }

    // Elimina cualquier placeholder opcional que no tenga valor
    url = url.replace(/\/{[^}]+\?}/g, "");

    return url;
}
