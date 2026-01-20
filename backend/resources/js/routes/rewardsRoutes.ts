// ======================================================
// Archivo: resources/js/routes/rewardsRoutes.ts
// Descripción: Rutas del módulo rewardsRoutes
// Fecha de actualización (GMT): 2026-01-20T11:54:46Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

/** Todas las rutas del módulo */
export type RewardsRoutesRoutes = typeof rewardsRoutes

/** Nombre de ruta */
export type RewardsRoutesRouteName = keyof RewardsRoutesRoutes

export const rewardsRoutes = {
  index: "/rewards",
  country: "/rewards/{countryCode}",
} as const;
