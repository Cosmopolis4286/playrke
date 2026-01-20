// ======================================================
// Archivo: resources/js/routes/treasurerRoutes.ts
// Descripción: Rutas del módulo treasurerRoutes
// Fecha de actualización (GMT): 2026-01-20T11:54:46Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

/** Todas las rutas del módulo */
export type TreasurerRoutesRoutes = typeof treasurerRoutes

/** Nombre de ruta */
export type TreasurerRoutesRouteName = keyof TreasurerRoutesRoutes

export const treasurerRoutes = {
  paymentDetails: "/treasurer/payment-details/{treasurer}/{currency}",
} as const;
