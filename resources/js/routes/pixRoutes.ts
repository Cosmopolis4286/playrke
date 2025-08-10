// ======================================================
// Archivo: resources/js/routes/pixRoutes.ts
// Descripción: Rutas del módulo pixRoutes
// Fecha de actualización (GMT): 2025-08-10T14:33:52Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const pixRoutes = {
    "index": "/pix",
    "create": "/pix/create",
    "update": "/pix/update/{id}",
    "delete": "/pix/delete/{id}",
    "getInfo": "/pix/getInfo/{id}",
    "getBanks": "/pix/get/banks"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route<K extends keyof typeof pixRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    return baseRoute(pixRoutes, name, params);
}