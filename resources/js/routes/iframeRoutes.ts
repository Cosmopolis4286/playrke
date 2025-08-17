// ======================================================
// Archivo: resources/js/routes/iframeRoutes.ts
// Descripción: Rutas del módulo iframeRoutes
// Fecha de actualización (GMT): 2025-08-17T21:00:20Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const iframeRoutes = {
    "index": "/iframe",
    "get-route-game-rooms": "/iframe/get/route/game-rooms/{userToken}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route<K extends keyof typeof iframeRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    return baseRoute(iframeRoutes, name, params);
}