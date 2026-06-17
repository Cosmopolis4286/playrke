// ======================================================
// Archivo: resources/js/routes/customerRoutes.ts
// DescripciÃ³n: Rutas del mÃ³dulo customerRoutes
// Fecha de actualizaciÃ³n (GMT): 2025-12-29T00:05:30Z
// Generado automÃ¡ticamente con make:ts-routes-modules
// ======================================================

import { buildRoute as baseRoute } from './helper';

export const customerRoutes = {
    "store": "/customer",
    "show": "/customer/{id}",
    "update": "/customer/{id}",
    "destroy": "/customer/{id}",
    "resetPassword": "/customer/{id}/password",
    "changeStatus": "/customer/{id}/status"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del mÃ³dulo.
 */
export function route(name, params) {
    return baseRoute(customerRoutes, name, params);
}

/**
 * Retorna la URL de una ruta especÃ­fica dentro de customerRoutes.
 * Reemplaza los placeholders con los parÃ¡metros proporcionados.
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

