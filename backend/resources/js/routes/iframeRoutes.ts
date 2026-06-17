// ======================================================
// Archivo: resources/js/routes/iframeRoutes.ts
// DescripciÃ³n: Rutas del mÃ³dulo iframeRoutes
// Fecha de actualizaciÃ³n (GMT): 2025-09-14T05:40:45Z
// Generado automÃ¡ticamente con make:ts-routes-modules
// ======================================================

import { buildRoute as baseRoute } from './helper';

export const iframeRoutes = {
    "index": "/iframe",
    "game-rooms-route": "/iframe/get/route/game-rooms/{userToken}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del mÃ³dulo.
 */
export function route(name, params) {
    return baseRoute(iframeRoutes, name, params);
}

/**
 * Retorna la URL de una ruta especÃ­fica dentro de iframeRoutes.
 * Reemplaza los placeholders con los parÃ¡metros proporcionados.
 * Elimina los opcionales que no tengan valor.
 */
export function getRoute(name, params) {
    let url = iframeRoutes[name];

    if (params) {
        Object.entries(params).forEach(([key, value]) => {
            url = url.replace(new RegExp("{"+key+"\??}", "g"), String(value));
        });
    }

    // Elimina cualquier placeholder opcional que no tenga valor
    url = url.replace(/\/{[^}]+\?}/g, "");

    return url;
}
