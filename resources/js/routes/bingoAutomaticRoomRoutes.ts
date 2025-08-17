// ======================================================
// Archivo: resources/js/routes/bingoAutomaticRoomRoutes.ts
// Descripción: Rutas del módulo bingoAutomaticRoomRoutes
// Fecha de actualización (GMT): 2025-08-17T21:00:19Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const bingoAutomaticRoomRoutes = {
    "getInfo": "/bingo-automatic-room/automatic-room/getInfo/{id}",
    "create": "/bingo-automatic-room/automatic-room/create",
    "update": "/bingo-automatic-room/automatic-room/update/{id}",
    "changeStatus": "/bingo-automatic-room/automatic-room/changeStatus/{id}",
    "delete": "/bingo-automatic-room/automatic-room/delete/{id}",
    "copy": "/bingo-automatic-room/automatic-room/copy/{id}",
    "executeGenerateBingoRoomsJob": "/bingo-automatic-room/automatic-room/executeGenerateBingoRoomsJob/{creatorType}/{creatorID}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route<K extends keyof typeof bingoAutomaticRoomRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    return baseRoute(bingoAutomaticRoomRoutes, name, params);
}