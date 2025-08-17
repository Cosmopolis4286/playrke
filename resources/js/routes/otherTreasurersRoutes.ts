// ======================================================
// Archivo: resources/js/routes/otherTreasurersRoutes.ts
// Descripción: Rutas del módulo otherTreasurersRoutes
// Fecha de actualización (GMT): 2025-08-17T21:00:20Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const otherTreasurersRoutes = {
    "getInfo": "/other-treasurers/getInfo/{id}",
    "update": "/other-treasurers/update/{id}",
    "delete": "/other-treasurers/delete/{id}",
    "changeStatus": "/other-treasurers/changeStatus/{id}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route<K extends keyof typeof otherTreasurersRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    return baseRoute(otherTreasurersRoutes, name, params);
}