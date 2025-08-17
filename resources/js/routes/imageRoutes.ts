// ======================================================
// Archivo: resources/js/routes/imageRoutes.ts
// Descripción: Rutas del módulo imageRoutes
// Fecha de actualización (GMT): 2025-08-17T21:00:20Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const imageRoutes = {
    "show-image-payment-prize-ctns": "/image/payments/prizes/{filename}",
    "show-image-payment-ctns": "/image/payments/{filename}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route<K extends keyof typeof imageRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    return baseRoute(imageRoutes, name, params);
}