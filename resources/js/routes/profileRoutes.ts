// ======================================================
// Archivo: resources/js/routes/profileRoutes.ts
// Descripción: Rutas del módulo profileRoutes
// Fecha de actualización (GMT): 2025-08-17T20:34:38Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const profileRoutes = {
    "index": "/profile",
    "update": "/profile/{id}",
    "changePassword": "/profile/changePassword/{id}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route<K extends keyof typeof profileRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    return baseRoute(profileRoutes, name, params);
}