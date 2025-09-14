// ======================================================
// Archivo: resources/js/routes/pixRoutes.ts
// Descripción: Rutas del módulo pixRoutes
// Fecha de actualización (GMT): 2025-09-14T05:40:45Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const pixRoutes = {
    "index": "/pix",
    "create": "/pix",
    "show": "/pix/{id}/info",
    "update": "/pix/{id}",
    "destroy": "/pix/{id}",
    "banks": "/pix/banks"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route(name, params) {
    return baseRoute(pixRoutes, name, params);
}

/**
 * Retorna la URL de una ruta específica dentro de pixRoutes.
 * Reemplaza los placeholders con los parámetros proporcionados.
 * Elimina los opcionales que no tengan valor.
 */
export function getRoute(name, params) {
    let url = pixRoutes[name];

    if (params) {
        Object.entries(params).forEach(([key, value]) => {
            url = url.replace(new RegExp("{"+key+"\??}", "g"), String(value));
        });
    }

    // Elimina cualquier placeholder opcional que no tenga valor
    url = url.replace(/\/{[^}]+\?}/g, "");

    return url;
}