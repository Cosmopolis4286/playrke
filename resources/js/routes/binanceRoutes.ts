// ======================================================
// Archivo: resources/js/routes/binanceRoutes.ts
// Descripción: Rutas del módulo binanceRoutes
// Fecha de actualización (GMT): 2025-08-17T20:34:38Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const binanceRoutes = {
    "create": "/binance/create",
    "update": "/binance/update/{id}",
    "delete": "/binance/delete/{id}",
    "getInfo": "/binance/getInfo/{id}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route<K extends keyof typeof binanceRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    return baseRoute(binanceRoutes, name, params);
}