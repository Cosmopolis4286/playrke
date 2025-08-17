// ======================================================
// Archivo: resources/js/routes/bingoPatternsRoutes.ts
// Descripción: Rutas del módulo bingoPatternsRoutes
// Fecha de actualización (GMT): 2025-08-17T21:00:20Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const bingoPatternsRoutes = {
    "getInfo": "/bingo-patterns/getInfo/{id}",
    "handler": "/bingo-patterns/handler/{id?}/{action?}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route<K extends keyof typeof bingoPatternsRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    return baseRoute(bingoPatternsRoutes, name, params);
}