// ======================================================
// Archivo: resources/js/routes/adminRoutes.ts
// Descripción: Rutas del módulo adminRoutes
// Fecha de actualización (GMT): 2025-08-17T20:34:38Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const adminRoutes = {
    "customers": "/admin/customers",
    "commerces": "/admin/commerces",
    "otherTreasurers": "/admin/other-treasurers",
    "sellers": "/admin/sellers",
    "subsidiaries": "/admin/subsidiaries",
    "treasurers": "/admin/treasurers",
    "users": "/admin/users",
    "getUserInfo": "/admin/get-user-info/{userRole}/{exclude?}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route<K extends keyof typeof adminRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    return baseRoute(adminRoutes, name, params);
}