// ======================================================
// Archivo: resources/js/routes/pixRoutes.ts
// Descripción: Rutas del módulo pixRoutes
// Fecha de actualización (GMT): 2026-01-20T11:54:46Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

/** Todas las rutas del módulo */
export type PixRoutesRoutes = typeof pixRoutes

/** Nombre de ruta */
export type PixRoutesRouteName = keyof PixRoutesRoutes

export const pixRoutes = {
  index: "/pix",
  store: "/pix",
  show: "/pix/{id}/info",
  update: "/pix/{id}",
  destroy: "/pix/{id}",
  banks: "/pix/banks",
} as const;
