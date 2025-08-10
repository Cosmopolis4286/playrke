// ======================================================
// Archivo: resources/js/routes/customerRoutes.ts
// Descripción: Rutas del módulo customerRoutes
// Fecha de actualización (GMT): 2025-08-10T14:33:52Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const customerRoutes = {
    "getUserBalance": "/customer/getUserBalance/{userToken}",
    "getUserId": "/customer/getUserId/{userToken}",
    "increaseBalance": "/customer/increaseBalance/{userToken}/{balance?}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route<K extends keyof typeof customerRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    return baseRoute(customerRoutes, name, params);
}