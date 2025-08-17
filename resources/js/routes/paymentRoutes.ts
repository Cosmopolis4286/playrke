// ======================================================
// Archivo: resources/js/routes/paymentRoutes.ts
// Descripción: Rutas del módulo paymentRoutes
// Fecha de actualización (GMT): 2025-08-17T20:34:38Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const paymentRoutes = {
    "proof": "/payment/proof/{fileName}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route<K extends keyof typeof paymentRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    return baseRoute(paymentRoutes, name, params);
}