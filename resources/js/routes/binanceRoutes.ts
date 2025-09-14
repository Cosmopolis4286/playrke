// ======================================================
// Archivo: resources/js/routes/binanceRoutes.ts
// Descripción: Rutas del módulo binanceRoutes
// Fecha de actualización (GMT): 2025-09-14T04:10:27Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const binanceRoutes = {
    "create": "/binance",
    "show": "/binance/{id}/info",
    "update": "/binance/{id}",
    "destroy": "/binance/{id}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route(name, params) {
    return baseRoute(binanceRoutes, name, params);
}

/**
 * Retorna la URL de una ruta específica dentro de binanceRoutes.
 * Reemplaza los placeholders con los parámetros proporcionados.
 * Elimina los opcionales que no tengan valor.
 */
export function getRoute(name, params) {
    let url = binanceRoutes[name];

    if (params) {
        Object.entries(params).forEach(([key, value]) => {
            url = url.replace(new RegExp("{"+key+"\??}", "g"), String(value));
        });
    }

    // Elimina cualquier placeholder opcional que no tenga valor
    url = url.replace(/\/{[^}]+\?}/g, "");

    return url;
}