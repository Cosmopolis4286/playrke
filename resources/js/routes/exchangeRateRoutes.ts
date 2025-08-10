// ======================================================
// Archivo: resources/js/routes/exchangeRateRoutes.ts
// Descripción: Rutas del módulo exchangeRateRoutes
// Fecha de actualización (GMT): 2025-08-10T14:33:52Z
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
    "getLastValueByCoin": "/exchange-rate/getLastValueByCoin/{coin}",
    "historical": "/exchange-rate/historical/{coin?}/{creatorType}/{creatorID}",
    "index": "/exchange-rate/{creatorType}/{creatorID?}"
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