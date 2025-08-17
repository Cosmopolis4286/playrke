// ======================================================
// Archivo: resources/js/routes/infoRoutes.ts
// Descripción: Rutas del módulo infoRoutes
// Fecha de actualización (GMT): 2025-08-17T21:00:20Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const infoRoutes = {
    "terms": "/info/terms",
    "about": "/info/about",
    "privacy": "/info/privacy",
    "help-center": "/info/help-center",
    "power-of-knowledge": "/info/power-of-knowledge"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route<K extends keyof typeof infoRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    return baseRoute(infoRoutes, name, params);
}