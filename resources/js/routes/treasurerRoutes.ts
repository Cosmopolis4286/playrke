// ======================================================
// Archivo: resources/js/routes/treasurerRoutes.ts
// Descripción: Rutas del módulo treasurerRoutes
// Fecha de actualización (GMT): 2025-08-17T21:00:20Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const treasurerRoutes = {
    "create": "/treasurer/create",
    "getInfo": "/treasurer/getInfo/{id}",
    "update": "/treasurer/update/{id}",
    "delete": "/treasurer/delete/{id}",
    "changePassw": "/treasurer/changePassw/{id}",
    "changeStatus": "/treasurer/changeStatus/{id}",
    "": "/treasurer/getPaymentTreasurerDetails/{treasurer}/{coin}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route<K extends keyof typeof treasurerRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    return baseRoute(treasurerRoutes, name, params);
}