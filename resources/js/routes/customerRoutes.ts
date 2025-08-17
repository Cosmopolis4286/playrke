// ======================================================
// Archivo: resources/js/routes/customerRoutes.ts
// Descripción: Rutas del módulo customerRoutes
// Fecha de actualización (GMT): 2025-08-17T20:34:38Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const customerRoutes = {
    "create": "/customer/create",
    "getInfo": "/customer/getInfo/{id}",
    "update": "/customer/update/{id}",
    "delete": "/customer/delete/{id}",
    "changePassw": "/customer/changePassw/{id}",
    "changeStatus": "/customer/changeStatus/{id}",
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