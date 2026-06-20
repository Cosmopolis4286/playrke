// ======================================================
// Archivo: resources/js/routes/sellerRoutes.ts
// Descripción: Rutas del módulo sellerRoutes
// Fecha de actualización (GMT): 2026-01-20T13:36:15Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

export const sellerRoutes = {
  create: "/seller",
  show: "/seller/{id}",
  update: "/seller/{id}",
  destroy: "/seller/{id}",
  resetPassword: "/seller/{id}/password",
  changeStatus: "/seller/{id}/status",
  generateBingoCard: "/seller/bingo/generate-card",
  paymentStatus: "/seller/bingo/carton/payment/status",
  priceCurrency: "/seller/bingo/price-currency/{room}/{country}",
  playerInfo: "/seller/bingo/player/{playerLink}/info",
  playerCartons: "/seller/bingo/player/cartons/{playerLink}/{room}",
  getPlayer: "/seller/bingo/player/search",
  paymentDetails: "/seller/payment-details/{currency}",
  salesReportPlayer: "/seller/bingo/sales-report/{room}/player/{playerLink}",
  generateCard: "/seller/bingo/generate-card",
} as const;
