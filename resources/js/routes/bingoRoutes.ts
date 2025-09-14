// ======================================================
// Archivo: resources/js/routes/bingoRoutes.ts
// Descripción: Rutas del módulo bingoRoutes
// Fecha de actualización (GMT): 2025-09-14T04:10:27Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const bingoRoutes = {
    "game-rooms-admin": "/bingo/game-rooms/admin/{user}/{currency?}",
    "activate-winner": "/bingo/activate-winner",
    "game-rooms-commerce": "/bingo/{roomType}/game-rooms/{currency?}",
    "create": "/bingo/create",
    "show": "/bingo/{id}/{startTime?}",
    "update": "/bingo/update/{id}",
    "destroy": "/bingo/delete/{id}",
    "change-status": "/bingo/status/{id}",
    "replay": "/bingo/replay/{id}",
    "publish": "/bingo/publish/{id}",
    "refund": "/bingo/refund/{id}",
    "copy": "/bingo/copy/{id}",
    "form": "/bingo/form/{roomId?}",
    "game-info": "/bingo/show-game-info",
    "configuration": "/bingo/configuration/{userToken?}",
    "patterns": "/bingo/configuration/patterns/{userToken?}",
    "encrypted-info": "/bingo/info-encrypted/{id}/{creatorUser}/{currency}",
    "public-room-info": "/bingo/public-room-info",
    "pending-payments": "/bingo/public-room-pending-payments",
    "rounds-config": "/bingo/rounds-config/{room}/{type}",
    "datetime-rooms": "/bingo/datetime-rooms/{creatorUser}/{player}",
    "price-currency": "/bingo/price-currency/{creation}/{creatorType}/{creatorId}/{room?}",
    "call-number": "/bingo/room/{roomId}/call-number",
    "participate": "/bingo/participate/public-room/{id?}/{getOut?}",
    "notify-payment": "/bingo/carton/notify/payment/win-ctn",
    "handle-rules": "/bingo/handle-rules/{id?}",
    "sales-report": "/bingo/sales-report/{currency?}/{dateFilter?}",
    "sales-by-room": "/bingo/sales-report-by-room/{room}/{currency}",
    "winners-by-room": "/bingo/sales-report-by-room-winners/{room}/{currency}",
    "winners": "/bingo/winners/{type}/{dateFilter}/{currency?}",
    "cartons-sold": "/bingo/cartons-sold/{currency?}/{dateFilter?}",
    "view-carton": "/bingo/view-ctn/{ctnId}",
    "room-summary": "/bingo/{roomType}/{creatorType}/{creatorId?}/{currency?}",
    "session-key": "/bingo/k3yg4m3/play-rke",
    "test.regenerate-draw": "/bingo/test/regenerate-draw"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route(name, params) {
    return baseRoute(bingoRoutes, name, params);
}

/**
 * Retorna la URL de una ruta específica dentro de bingoRoutes.
 * Reemplaza los placeholders con los parámetros proporcionados.
 * Elimina los opcionales que no tengan valor.
 */
export function getRoute(name, params) {
    let url = bingoRoutes[name];

    if (params) {
        Object.entries(params).forEach(([key, value]) => {
            url = url.replace(new RegExp("{"+key+"\??}", "g"), String(value));
        });
    }

    // Elimina cualquier placeholder opcional que no tenga valor
    url = url.replace(/\/{[^}]+\?}/g, "");

    return url;
}