// ======================================================
// Archivo: resources/js/routes/reportRoutes.ts
// Descripción: Rutas del módulo reportRoutes
// Fecha de actualización (GMT): 2025-08-17T20:34:38Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const reportRoutes = {
    "index": "/report",
    "store": "/report/store"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route<K extends keyof typeof reportRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    return baseRoute(reportRoutes, name, params);
}