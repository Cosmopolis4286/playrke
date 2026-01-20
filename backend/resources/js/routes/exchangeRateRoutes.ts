// ======================================================
// Archivo: resources/js/routes/exchangeRateRoutes.ts
// Descripción: Rutas del módulo exchangeRateRoutes
// Fecha de actualización (GMT): 2026-01-20T11:54:46Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

/** Todas las rutas del módulo */
export type ExchangeRateRoutesRoutes = typeof exchangeRateRoutes

/** Nombre de ruta */
export type ExchangeRateRoutesRouteName = keyof ExchangeRateRoutesRoutes

export const exchangeRateRoutes = {
  index: "/exchange-rate",
  store: "/exchange-rate",
  show: "/exchange-rate/{id}",
  update: "/exchange-rate/{id}",
  destroy: "/exchange-rate/{id}",
  changeStatus: "/exchange-rate/{id}/status",
  publicIndex: "/exchange-rate/public",
} as const;
