// ======================================================
// Archivo: resources/js/routes/infoRoutes.ts
// Descripción: Rutas del módulo infoRoutes
// Fecha de actualización (GMT): 2026-01-20T11:54:46Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

/** Todas las rutas del módulo */
export type InfoRoutesRoutes = typeof infoRoutes

/** Nombre de ruta */
export type InfoRoutesRouteName = keyof InfoRoutesRoutes

export const infoRoutes = {
  terms: "/info/terms",
  privacy: "/info/privacy",
  about: "/info/about",
  helpCenter: "/info/help-center",
  powerOfKnowledge: "/info/power-of-knowledge",
} as const;
