// ======================================================
// Archivo: resources/js/routes/rewardsRoutes.ts
// Descripción: Rutas del módulo rewardsRoutes
// Fecha de actualización (GMT): 2025-09-14T04:10:28Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const rewardsRoutes = {
    "index": "/rewards",
    "create": "/rewards",
    "show": "/rewards/{id}",
    "update": "/rewards/{id}",
    "destroy": "/rewards/{id}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route(name, params) {
    return baseRoute(rewardsRoutes, name, params);
}

/**
 * Retorna la URL de una ruta específica dentro de rewardsRoutes.
 * Reemplaza los placeholders con los parámetros proporcionados.
 * Elimina los opcionales que no tengan valor.
 */
export function getRoute(name, params) {
    let url = rewardsRoutes[name];

    if (params) {
        Object.entries(params).forEach(([key, value]) => {
            url = url.replace(new RegExp("{"+key+"\??}", "g"), String(value));
        });
    }

    // Elimina cualquier placeholder opcional que no tenga valor
    url = url.replace(/\/{[^}]+\?}/g, "");

    return url;
}