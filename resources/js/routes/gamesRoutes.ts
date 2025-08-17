// ======================================================
// Archivo: resources/js/routes/gamesRoutes.ts
// Descripción: Rutas del módulo gamesRoutes
// Fecha de actualización (GMT): 2025-08-17T20:34:38Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const gamesRoutes = {
    "handleCustomerGames": "/games/handleCustomerGames/{id?}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route<K extends keyof typeof gamesRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    return baseRoute(gamesRoutes, name, params);
}