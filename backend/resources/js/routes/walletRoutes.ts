// ======================================================
// Archivo: resources/js/routes/walletRoutes.ts
// DescripciÃ³n: Rutas del mÃ³dulo walletRoutes
// Fecha de actualizaciÃ³n (GMT): 2025-09-14T05:40:45Z
// Generado automÃ¡ticamente con make:ts-routes-modules
// ======================================================

import { buildRoute as baseRoute } from './helper';

export const walletRoutes = {
    "index": "/wallet"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del mÃ³dulo.
 */
export function route(name, params) {
    return baseRoute(walletRoutes, name, params);
}

/**
 * Retorna la URL de una ruta especÃ­fica dentro de walletRoutes.
 * Reemplaza los placeholders con los parÃ¡metros proporcionados.
 * Elimina los opcionales que no tengan valor.
 */
export function getRoute(name, params) {
    let url = walletRoutes[name];

    if (params) {
        Object.entries(params).forEach(([key, value]) => {
            url = url.replace(new RegExp("{"+key+"\??}", "g"), String(value));
        });
    }

    // Elimina cualquier placeholder opcional que no tenga valor
    url = url.replace(/\/{[^}]+\?}/g, "");

    return url;
}
