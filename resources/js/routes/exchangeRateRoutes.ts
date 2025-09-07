// ======================================================
// Archivo: resources/js/routes/exchangeRateRoutes.ts
// Descripción: Rutas del módulo exchangeRateRoutes
// Fecha de actualización (GMT): 2025-08-17T21:00:20Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const exchangeRateRoutes = {
    "create": "/exchange-rate/create",
    "getExchangeRates": "/exchange-rate/getExchangeRates",
    "getInfo": "/exchange-rate/getInfo/{id}",
    "update": "/exchange-rate/update/{id}",
    "delete": "/exchange-rate/delete/{id}",
    "changeStatus": "/exchange-rate/changeStatus/{id}",
    "getLastValueByCoin": "/exchange-rate/getLastValueByCoin/{currency}",
    "historical": "/exchange-rate/historical/{currency?}/{creatorType}/{creatorId}",
    "index": "/exchange-rate/{creatorType}/{creatorId?}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route<K extends keyof typeof exchangeRateRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    return baseRoute(exchangeRateRoutes, name, params);
}