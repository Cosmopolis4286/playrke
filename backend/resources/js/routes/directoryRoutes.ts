// ======================================================
// Archivo: resources/js/routes/directoryRoutes.ts
// Descripción: Rutas del módulo directoryRoutes
// Fecha de actualización (GMT): 2025-09-14T05:40:44Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const directoryRoutes = {
    "country": "/directory/{countryCode}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route(name, params) {
    return baseRoute(directoryRoutes, name, params);
}

/**
 * Retorna la URL de una ruta específica dentro de directoryRoutes.
 * Reemplaza los placeholders con los parámetros proporcionados.
 * Elimina los opcionales que no tengan valor.
 */
export function getRoute(name, params) {
    let url = directoryRoutes[name];

    if (params) {
        Object.entries(params).forEach(([key, value]) => {
            url = url.replace(new RegExp("{"+key+"\??}", "g"), String(value));
        });
    }

    // Elimina cualquier placeholder opcional que no tenga valor
    url = url.replace(/\/{[^}]+\?}/g, "");

    return url;
}