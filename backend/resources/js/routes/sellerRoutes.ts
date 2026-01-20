// ======================================================
// Archivo: resources/js/routes/sellerRoutes.ts
// Descripción: Rutas del módulo sellerRoutes
// Fecha de actualización (GMT): 2026-01-20T01:45:55Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

export const sellerRoutes = {
  create: "/seller",
  show: "/seller/{id}",
  update: "/seller/{id}",
  destroy: "/seller/{id}",
  resetPassword: "/seller/{id}/password",
  changeStatus: "/seller/{id}/status",
  cartonGenerate: "/seller/bingo/carton/generate",
  paymentStatus: "/seller/bingo/carton/payment/status",
  priceCurrency: "/seller/bingo/price-currency/{room}/{country}",
  winnerInfo: "/seller/bingo/winner-info/{ctnId}",
  playerInfo: "/seller/bingo/player/{playerLink}/info",
  playerCartons: "/seller/bingo/player/cartons/{playerLink}/{room}",
  getPlayer: "/seller/bingo/player/search",
  cartonPrice: "/seller/bingo/carton/price/{roomId}",
  paymentDetails: "/seller/payment-details/{currency}",
  salesReportPlayer: "/seller/bingo/sales-report/{room}/player/{playerLink}",
  generateCarton: "/seller/bingo/carton/generate",
} as const;
