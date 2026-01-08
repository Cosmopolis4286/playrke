// ======================================================
// Archivo: resources/js/routes/customerRoutes.ts
// Descripción: Rutas del módulo customerRoutes
// Fecha de actualización (GMT): 2025-12-29T00:05:30Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const customerRoutes = {
    "store": "/customer",
    "show": "/customer/{id}",
    "update": "/customer/{id}",
    "destroy": "/customer/{id}",
    "resetPassword": "/customer/{id}/password",
    "changeStatus": "/customer/{id}/status"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route(name, params) {
    return baseRoute(customerRoutes, name, params);
}

/**
 * Retorna la URL de una ruta específica dentro de customerRoutes.
 * Reemplaza los placeholders con los parámetros proporcionados.
 * Elimina los opcionales que no tengan valor.
 */
export function getRoute(name, params) {
    let url = customerRoutes[name];

    if (params) {
        Object.entries(params).forEach(([key, value]) => {
            url = url.replace(new RegExp("{"+key+"\??}", "g"), String(value));
        });
    }

    // Elimina cualquier placeholder opcional que no tenga valor
    url = url.replace(/\/{[^}]+\?}/g, "");

    return url;
}
