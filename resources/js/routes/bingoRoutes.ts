// ======================================================
// Archivo: resources/js/routes/bingoRoutes.ts
// Descripción: Rutas del módulo bingoRoutes
// Fecha de actualización (GMT): 2025-08-17T20:34:38Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const bingoRoutes = {
    "handleForm": "/bingo/form/{roomId?}",
    "callNumber": "/bingo/room/{roomId}/callNumber",
    "showGameInfo": "/bingo/showGameInfo",
    "configuration": "/bingo/configuration/{userToken?}",
    "bingoPatterns": "/bingo/configuration/patterns/{userToken?}",
    "getInfo": "/bingo/getInfo/{id}/{starTime?}",
    "getInfoByEncrypt": "/bingo/getInfoByEncrypt/{id}/{creatorUser}/{coin}",
    "getInfoPublicRoom": "/bingo/getInfoPublicRoom",
    "getInfoPublicRoomPendingPayments": "/bingo/getInfoPublicRoomPendingPayments",
    "getRoundsConfig": "/bingo/getRoundsConfig/{room}/{type}",
    "getDatetimeRooms": "/bingo/getDateTimeRooms/{creatorUser}/{player}",
    "getCtnPriceForCoin": "/bingo/getCtnPriceForCoin/{creation}/{creatorType}/{creatorID}/{room?}",
    "participateInPublicRoom": "/bingo/participate/public-room/{id?}/{getOut?}",
    "notifyPaymentPrizeWinningCtn": "/bingo/carton/notify/payment/win-ctn",
    "handleBingoRules": "/bingo/handle-bingo-rules/{id?}",
    "reportSalesRooms": "/bingo/sales-report/{coin?}/{dateFilter?}",
    "reportSalesByRoom": "/bingo/sales-report-by-room/{room}/{coin}",
    "reportSalesByRoomWinners": "/bingo/sales-report-by-room-winners/{room}/{coin}",
    "reportSalesByRoomPlayer": "/bingo/sales-report-by-room/{room}/player/{playerLink}",
    "ctnsAwarded": "/bingo/winners/{type}/{dateFilter}/{coin?}",
    "ctnsSold": "/bingo/cartons-sold/{coin?}/{dateFilter?}",
    "create": "/bingo/create",
    "update": "/bingo/update/{id}",
    "delete": "/bingo/delete/{id}",
    "changeStatus": "/bingo/status/{id}",
    "replay": "/bingo/replay/{id}",
    "publish": "/bingo/publish/{id}",
    "refund": "/bingo/refund/{id}",
    "copy": "/bingo/copy/{id}",
    "test.regenerateDraw": "/bingo/test/regenerate-draw",
    "gameRoomsAdmin": "/bingo/game-rooms/admin/{user}/{coin?}",
    "gameRoomsCommerce": "/bingo/{roomType}/game-rooms/{coin?}",
    "actWn": "/bingo/actWn",
    "viewCtn": "/bingo/view-ctn/{ctnId}",
    "handleGameRoomSummary": "/bingo/{roomType}/{creatorType}/{creatorID?}/{coin?}",
    "k3yg4m3": "/bingo/k3yg4m3/play-rke"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route<K extends keyof typeof bingoRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    return baseRoute(bingoRoutes, name, params);
}