// ======================================================
// Archivo: resources/js/routes/bingoPatternsRoutes.ts
// Descripción: Rutas del módulo bingoPatternsRoutes
// Fecha de actualización (GMT): 2025-09-14T04:10:28Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const bingoPatternsRoutes = {
    "bingo-pattern.show": "/bingo-patterns/{id}",
    "bingo-pattern.handle": "/bingo-patterns/handler/{id?}/{action?}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route(name, params) {
    return baseRoute(bingoPatternsRoutes, name, params);
}

/**
 * Retorna la URL de una ruta específica dentro de bingoPatternsRoutes.
 * Reemplaza los placeholders con los parámetros proporcionados.
 * Elimina los opcionales que no tengan valor.
 */
export function getRoute(name, params) {
    let url = bingoPatternsRoutes[name];

    if (params) {
        Object.entries(params).forEach(([key, value]) => {
            url = url.replace(new RegExp("{"+key+"\??}", "g"), String(value));
        });
    }

    // Elimina cualquier placeholder opcional que no tenga valor
    url = url.replace(/\/{[^}]+\?}/g, "");

    return url;
}