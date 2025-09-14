// ======================================================
// Archivo: resources/js/routes/iframeRoutes.ts
// Descripción: Rutas del módulo iframeRoutes
// Fecha de actualización (GMT): 2025-09-14T05:40:45Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const iframeRoutes = {
    "index": "/iframe",
    "game-rooms-route": "/iframe/get/route/game-rooms/{userToken}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route(name, params) {
    return baseRoute(iframeRoutes, name, params);
}

/**
 * Retorna la URL de una ruta específica dentro de iframeRoutes.
 * Reemplaza los placeholders con los parámetros proporcionados.
 * Elimina los opcionales que no tengan valor.
 */
export function getRoute(name, params) {
    let url = iframeRoutes[name];

    if (params) {
        Object.entries(params).forEach(([key, value]) => {
            url = url.replace(new RegExp("{"+key+"\??}", "g"), String(value));
        });
    }

    // Elimina cualquier placeholder opcional que no tenga valor
    url = url.replace(/\/{[^}]+\?}/g, "");

    return url;
}