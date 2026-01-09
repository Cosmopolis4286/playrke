// ======================================================
// Archivo: resources/js/routes/bingoAutomaticRoomRoutes.ts
// Descripción: Rutas del módulo bingoAutomaticRoomRoutes
// Fecha de actualización (GMT): 2025-09-14T05:40:44Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const bingoAutomaticRoomRoutes = {
    "bingo.automatic.show": "/bingo-automatic-room/{id}",
    "bingo.automatic.create": "/bingo-automatic-room",
    "bingo.automatic.update": "/bingo-automatic-room/{id}",
    "bingo.automatic.changeStatus": "/bingo-automatic-room/{id}/status",
    "bingo.automatic.destroy": "/bingo-automatic-room/{id}/delete",
    "bingo.automatic.copy": "/bingo-automatic-room/{id}/copy",
    "bingo.automatic.execute-job": "/bingo-automatic-room/execute-job/{creatorType}/{creatorId}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route(name, params) {
    return baseRoute(bingoAutomaticRoomRoutes, name, params);
}

/**
 * Retorna la URL de una ruta específica dentro de bingoAutomaticRoomRoutes.
 * Reemplaza los placeholders con los parámetros proporcionados.
 * Elimina los opcionales que no tengan valor.
 */
export function getRoute(name, params) {
    let url = bingoAutomaticRoomRoutes[name];

    if (params) {
        Object.entries(params).forEach(([key, value]) => {
            url = url.replace(new RegExp("{"+key+"\??}", "g"), String(value));
        });
    }

    // Elimina cualquier placeholder opcional que no tenga valor
    url = url.replace(/\/{[^}]+\?}/g, "");

    return url;
}
