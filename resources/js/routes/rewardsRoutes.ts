// ======================================================
// Archivo: resources/js/routes/rewardsRoutes.ts
// Descripción: Rutas del módulo rewardsRoutes
// Fecha de actualización (GMT): 2025-08-17T20:34:38Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const rewardsRoutes = {
    "index": "/rewards",
    "create": "/rewards/create",
    "getInfo": "/rewards/getInfo/{id}",
    "update": "/rewards/update/{id}",
    "delete": "/rewards/delete/{id}",
    "changeStatus": "/rewards/changeStatus/{id}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route<K extends keyof typeof rewardsRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    return baseRoute(rewardsRoutes, name, params);
}