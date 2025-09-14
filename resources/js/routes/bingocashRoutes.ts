// ======================================================
// Archivo: resources/js/routes/bingocashRoutes.ts
// Descripción: Rutas del módulo bingocashRoutes
// Fecha de actualización (GMT): 2025-09-14T04:10:28Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const bingocashRoutes = {
    "out-game": "/bingocash/game-room/{uuidCode}/{room?}/{currency?}",
    "generate-carton": "/bingocash/carton/generate/store",
    "get-draw": "/bingocash/get-draw/{room}",
    "get-chips": "/bingocash/get/chips/{room}",
    "get-result": "/bingocash/get/result/{room}/{value}/{column?}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route(name, params) {
    return baseRoute(bingocashRoutes, name, params);
}

/**
 * Retorna la URL de una ruta específica dentro de bingocashRoutes.
 * Reemplaza los placeholders con los parámetros proporcionados.
 * Elimina los opcionales que no tengan valor.
 */
export function getRoute(name, params) {
    let url = bingocashRoutes[name];

    if (params) {
        Object.entries(params).forEach(([key, value]) => {
            url = url.replace(new RegExp("{"+key+"\??}", "g"), String(value));
        });
    }

    // Elimina cualquier placeholder opcional que no tenga valor
    url = url.replace(/\/{[^}]+\?}/g, "");

    return url;
}