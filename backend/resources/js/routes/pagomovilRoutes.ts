// ======================================================
// Archivo: resources/js/routes/pagomovilRoutes.ts
// Descripción: Rutas del módulo pagomovilRoutes
// Fecha de actualización (GMT): 2026-01-20T11:54:46Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

/** Todas las rutas del módulo */
export type PagomovilRoutesRoutes = typeof pagomovilRoutes

/** Nombre de ruta */
export type PagomovilRoutesRouteName = keyof PagomovilRoutesRoutes

export const pagomovilRoutes = {
  index: "/pagomovil",
  store: "/pagomovil",
  show: "/pagomovil/{id}/info",
  update: "/pagomovil/{id}",
  destroy: "/pagomovil/{id}",
} as const;
