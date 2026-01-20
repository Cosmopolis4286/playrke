// ======================================================
// Archivo: resources/js/routes/paymentRoutes.ts
// Descripción: Rutas del módulo paymentRoutes
// Fecha de actualización (GMT): 2026-01-20T11:54:46Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

/** Todas las rutas del módulo */
export type PaymentRoutesRoutes = typeof paymentRoutes

/** Nombre de ruta */
export type PaymentRoutesRouteName = keyof PaymentRoutesRoutes

export const paymentRoutes = {
  proof: "/payment/proof/{fileName}",
} as const;
