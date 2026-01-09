// ======================================================
// Archivo: resources/js/routes/apiRoutes.ts
// Descripción: Rutas del módulo apiRoutes
// Fecha de actualización (GMT): 2025-12-29T00:05:30Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const apiRoutes = {
    "user": "/api/user",
    "auth.login": "/api/auth/login",
    "auth.register": "/api/auth/register",
    "auth.forgot-password": "/api/auth/forgot-password",
    "auth.resetPassword": "/api/auth/resetPassword",
    "ping": "/api/ping"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route(name, params) {
    return baseRoute(apiRoutes, name, params);
}

/**
 * Retorna la URL de una ruta específica dentro de apiRoutes.
 * Reemplaza los placeholders con los parámetros proporcionados.
 * Elimina los opcionales que no tengan valor.
 */
export function getRoute(name, params) {
    let url = apiRoutes[name];

    if (params) {
        Object.entries(params).forEach(([key, value]) => {
            url = url.replace(new RegExp("{"+key+"\??}", "g"), String(value));
        });
    }

    // Elimina cualquier placeholder opcional que no tenga valor
    url = url.replace(/\/{[^}]+\?}/g, "");

    return url;
}
