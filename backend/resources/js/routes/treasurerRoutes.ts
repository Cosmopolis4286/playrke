// ======================================================
// Archivo: resources/js/routes/treasurerRoutes.ts
// Descripción: Rutas del módulo treasurerRoutes
// Fecha de actualización (GMT): 2025-12-29T00:05:30Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const treasurerRoutes = {
    "store": "/treasurer",
    "show": "/treasurer/{id}",
    "update": "/treasurer/{id}",
    "destroy": "/treasurer/{id}",
    "changePassword": "/treasurer/{id}/password",
    "changeStatus": "/treasurer/{id}/status",
    "payment-details": "/treasurer/payment-details/{treasurer}/{currency}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route(name, params) {
    return baseRoute(treasurerRoutes, name, params);
}

/**
 * Retorna la URL de una ruta específica dentro de treasurerRoutes.
 * Reemplaza los placeholders con los parámetros proporcionados.
 * Elimina los opcionales que no tengan valor.
 */
export function getRoute(name, params) {
    let url = treasurerRoutes[name];

    if (params) {
        Object.entries(params).forEach(([key, value]) => {
            url = url.replace(new RegExp("{"+key+"\??}", "g"), String(value));
        });
    }

    // Elimina cualquier placeholder opcional que no tenga valor
    url = url.replace(/\/{[^}]+\?}/g, "");

    return url;
}