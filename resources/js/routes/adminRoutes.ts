// ======================================================
// Archivo: resources/js/routes/adminRoutes.ts
// Descripción: Rutas del módulo adminRoutes
// Fecha de actualización (GMT): 2025-09-14T05:40:44Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const adminRoutes = {
    "dashboard": "/admin",
    "customers": "/admin/customers",
    "commerces": "/admin/commerces",
    "sellers": "/admin/sellers",
    "treasurers": "/admin/treasurers",
    "other-treasurers": "/admin/other-treasurers",
    "subsidiaries": "/admin/subsidiaries",
    "users": "/admin/users",
    "getUserInfo": "/admin/get-user-info/{userRole}/{exclude?}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route(name, params) {
    return baseRoute(adminRoutes, name, params);
}

/**
 * Retorna la URL de una ruta específica dentro de adminRoutes.
 * Reemplaza los placeholders con los parámetros proporcionados.
 * Elimina los opcionales que no tengan valor.
 */
export function getRoute(name, params) {
    let url = adminRoutes[name];

    if (params) {
        Object.entries(params).forEach(([key, value]) => {
            url = url.replace(new RegExp("{"+key+"\??}", "g"), String(value));
        });
    }

    // Elimina cualquier placeholder opcional que no tenga valor
    url = url.replace(/\/{[^}]+\?}/g, "");

    return url;
}