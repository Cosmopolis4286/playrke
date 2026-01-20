// ======================================================
// Archivo: resources/js/routes/sellerRoutes.ts
// Descripción: Rutas del módulo sellerRoutes
// Fecha de actualización (GMT): 2026-01-20T11:54:46Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

/** Todas las rutas del módulo */
export type SellerRoutesRoutes = typeof sellerRoutes

/** Nombre de ruta */
export type SellerRoutesRouteName = keyof SellerRoutesRoutes

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
