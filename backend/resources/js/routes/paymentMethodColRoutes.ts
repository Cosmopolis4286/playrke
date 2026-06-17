// ======================================================
// Archivo: resources/js/routes/paymentMethodColRoutes.ts
// DescripciÃ³n: Rutas del mÃ³dulo paymentMethodColRoutes
// Fecha de actualizaciÃ³n (GMT): 2025-09-14T05:40:45Z
// Generado automÃ¡ticamente con make:ts-routes-modules
// ======================================================

import { buildRoute as baseRoute } from './helper';

export const paymentMethodColRoutes = {
    "create": "/payment_accounts_co",
    "show": "/payment_accounts_co/{id}/info",
    "update": "/payment_accounts_co/{id}",
    "destroy": "/payment_accounts_co/{id}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del mÃ³dulo.
 */
export function route(name, params) {
    return baseRoute(paymentMethodColRoutes, name, params);
}

/**
 * Retorna la URL de una ruta especÃ­fica dentro de paymentMethodColRoutes.
 * Reemplaza los placeholders con los parÃ¡metros proporcionados.
 * Elimina los opcionales que no tengan valor.
 */
export function getRoute(name, params) {
    let url = paymentMethodColRoutes[name];

    if (params) {
        Object.entries(params).forEach(([key, value]) => {
            url = url.replace(new RegExp("{"+key+"\??}", "g"), String(value));
        });
    }

    // Elimina cualquier placeholder opcional que no tenga valor
    url = url.replace(/\/{[^}]+\?}/g, "");

    return url;
}

