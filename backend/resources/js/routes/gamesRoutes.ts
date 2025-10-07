// ======================================================
// Archivo: resources/js/routes/gamesRoutes.ts
// Descripción: Rutas del módulo gamesRoutes
// Fecha de actualización (GMT): 2025-09-14T05:40:44Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const gamesRoutes = {
    "handle-customer": "/games/handle-customer/{id?}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route(name, params) {
    return baseRoute(gamesRoutes, name, params);
}

/**
 * Retorna la URL de una ruta específica dentro de gamesRoutes.
 * Reemplaza los placeholders con los parámetros proporcionados.
 * Elimina los opcionales que no tengan valor.
 */
export function getRoute(name, params) {
    let url = gamesRoutes[name];

    if (params) {
        Object.entries(params).forEach(([key, value]) => {
            url = url.replace(new RegExp("{"+key+"\??}", "g"), String(value));
        });
    }

    // Elimina cualquier placeholder opcional que no tenga valor
    url = url.replace(/\/{[^}]+\?}/g, "");

    return url;
}