// ======================================================
// Archivo: resources/js/routes/binanceRoutes.ts
// DescripciÃ³n: Rutas del mÃ³dulo binanceRoutes
// Fecha de actualizaciÃ³n (GMT): 2025-09-14T05:40:44Z
// Generado automÃ¡ticamente con make:ts-routes-modules
// ======================================================

import { buildRoute as baseRoute } from './helper';

export const binanceRoutes = {
    "create": "/binance",
    "show": "/binance/{id}/info",
    "update": "/binance/{id}",
    "destroy": "/binance/{id}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del mÃ³dulo.
 */
export function route(name, params) {
    return baseRoute(binanceRoutes, name, params);
}

/**
 * Retorna la URL de una ruta especÃ­fica dentro de binanceRoutes.
 * Reemplaza los placeholders con los parÃ¡metros proporcionados.
 * Elimina los opcionales que no tengan valor.
 */
export function getRoute(name, params) {
    let url = binanceRoutes[name];

    if (params) {
        Object.entries(params).forEach(([key, value]) => {
            url = url.replace(new RegExp("{"+key+"\??}", "g"), String(value));
        });
    }

    // Elimina cualquier placeholder opcional que no tenga valor
    url = url.replace(/\/{[^}]+\?}/g, "");

    return url;
}
