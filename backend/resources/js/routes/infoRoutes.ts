// ======================================================
// Archivo: resources/js/routes/infoRoutes.ts
// Descripción: Rutas del módulo infoRoutes
// Fecha de actualización (GMT): 2025-12-29T00:05:30Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const infoRoutes = {
    "terms": "/info/terms",
    "privacy": "/info/privacy",
    "about": "/info/about",
    "help-center": "/info/help-center",
    "power-of-knowledge": "/info/power-of-knowledge"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route(name, params) {
    return baseRoute(infoRoutes, name, params);
}

/**
 * Retorna la URL de una ruta específica dentro de infoRoutes.
 * Reemplaza los placeholders con los parámetros proporcionados.
 * Elimina los opcionales que no tengan valor.
 */
export function getRoute(name, params) {
    let url = infoRoutes[name];

    if (params) {
        Object.entries(params).forEach(([key, value]) => {
            url = url.replace(new RegExp("{"+key+"\??}", "g"), String(value));
        });
    }

    // Elimina cualquier placeholder opcional que no tenga valor
    url = url.replace(/\/{[^}]+\?}/g, "");

    return url;
}