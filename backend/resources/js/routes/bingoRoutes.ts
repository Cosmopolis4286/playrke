// ======================================================
// Archivo: resources/js/routes/bingoRoutes.ts
// Descripción: Rutas del módulo bingoRoutes
// Fecha de actualización (GMT): 2026-01-20T11:54:46Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

/** Todas las rutas del módulo */
export type BingoRoutesRoutes = typeof bingoRoutes

/** Nombre de ruta */
export type BingoRoutesRouteName = keyof BingoRoutesRoutes

export const bingoRoutes = {
  gameRoomsAdmin: "/bingo/game-rooms/admin/{user}/{currency?}",
  activateWinner: "/bingo/activate-winner",
  create: "/bingo/create",
  update: "/bingo/update/{id}",
  delete: "/bingo/delete/{id}",
  changeStatus: "/bingo/status/{id}",
  replay: "/bingo/replay/{id}",
  publish: "/bingo/publish/{id}",
  refund: "/bingo/refund/{id}",
  copy: "/bingo/copy/{id}",
  gameRoomsCommerce: "/bingo/{roomType}/game-rooms/{currency?}",
  rewards: "/bingo/rewards",
  sellers: "/bingo/sellers",
  getCurrencyByCountry: "/bingo/seller/getCurrencyByCountry/{country}/{seller}",
  getSellerInfoByCtn: "/bingo/seller/getSellerInfoByCtn/{ctnId}",
  ctnsLotPayment: "/bingo/bingo/carton-lot/payment",
  form: "/bingo/form/{roomId?}",
  gameInfo: "/bingo/show-game-info",
  configuration: "/bingo/configuration/{userToken?}",
  patterns: "/bingo/configuration/patterns/{userToken?}",
  encryptedInfo: "/bingo/info-encrypted/{id}/{creatorUser}/{currency}",
  publicRoomInfo: "/bingo/public-room-info",
  pendingPayments: "/bingo/public-room-pending-payments",
  roundsConfig: "/bingo/rounds-config/{room}/{type}",
  datetimeRooms: "/bingo/datetime-rooms/{creatorUser}/{player}",
  priceCurrency: "/bingo/price-currency/{creation}/{creatorType}/{creatorId}/{room?}",
  callNumber: "/bingo/room/{roomId}/call-number",
  participate: "/bingo/participate/public-room/{id?}/{getOut?}",
  notifyPayment: "/bingo/carton/notify/payment/win-ctn",
  handleRules: "/bingo/handle-rules/{id?}",
  salesReport: "/bingo/sales-report/{currency?}/{dateFilter?}",
  salesByRoom: "/bingo/sales-report-by-room/{room}/{currency}",
  winnersByRoom: "/bingo/sales-report-by-room-winners/{room}/{currency}",
  winners: "/bingo/winners/{type}/{dateFilter}/{currency?}",
  cartonsSold: "/bingo/cartons-sold/{currency?}/{dateFilter?}",
  viewCarton: "/bingo/view-ctn/{ctnId}",
  roomSummary: "/bingo/{roomType}/{creatorType}/{creatorId?}/{currency?}",
  sessionKey: "/bingo/k3yg4m3/play-rke",
  testRegenerateDraw: "/bingo/test/regenerate-draw",
} as const;
