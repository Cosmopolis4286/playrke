// ======================================================
// Archivo: resources/js/routes/commerceRoutes.ts
// Descripción: Rutas del módulo commerceRoutes
// Fecha de actualización (GMT): 2026-01-20T13:36:15Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

export const commerceRoutes = {
  rewards: "/commerce/rewards",
  sellers: "/commerce/sellers",
  currencyByCountry: "/commerce/seller/currency/{country}/{seller}",
  sellerInfoByCarton: "/commerce/seller/info/{ctnId}",
  cartonLotPayment: "/commerce/bingo/carton-lot/payment",
  paymentDetailsPublic: "/commerce/payment-details/{commerce}/{currency}",
  getPaymentCommerceDetails: "/commerce/getPaymentCommerceDetails/{commerce}/{currency}",
} as const;
