// ======================================================
// Archivo: resources/js/routes/directoryRoutes.ts
// Descripción: Rutas del módulo directoryRoutes
// Fecha de actualización (GMT): 2025-08-17T21:00:20Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const directoryRoutes = {
    "index": "/directory/{countryCode}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route<K extends keyof typeof directoryRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    return baseRoute(directoryRoutes, name, params);
}