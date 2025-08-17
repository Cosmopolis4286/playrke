// ======================================================
// Archivo: resources/js/routes/walletRoutes.ts
// Descripción: Rutas del módulo walletRoutes
// Fecha de actualización (GMT): 2025-08-17T21:00:20Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const walletRoutes = {
    "index": "/wallet"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route<K extends keyof typeof walletRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    return baseRoute(walletRoutes, name, params);
}