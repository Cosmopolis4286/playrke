// ======================================================
// Archivo: resources/js/routes/pagomovilRoutes.ts
// Descripción: Rutas del módulo pagomovilRoutes
// Fecha de actualización (GMT): 2025-08-17T20:34:38Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const pagomovilRoutes = {
    "index": "/pagomovil",
    "create": "/pagomovil/create",
    "update": "/pagomovil/update/{id}",
    "delete": "/pagomovil/delete/{id}",
    "getInfo": "/pagomovil/getInfo/{id}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route<K extends keyof typeof pagomovilRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    return baseRoute(pagomovilRoutes, name, params);
}