// ======================================================
// Archivo: resources/js/routes/paymentMethodColRoutes.ts
// Descripción: Rutas del módulo paymentMethodColRoutes
// Fecha de actualización (GMT): 2025-08-17T21:00:20Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const paymentMethodColRoutes = {
    "create": "/payment-method-col/create",
    "update": "/payment-method-col/update/{id}",
    "delete": "/payment-method-col/delete/{id}",
    "getInfo": "/payment-method-col/getInfo/{id}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route<K extends keyof typeof paymentMethodColRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    return baseRoute(paymentMethodColRoutes, name, params);
}