// ======================================================
// Archivo: resources/js/routes/profileRoutes.ts
// Descripción: Rutas del módulo profileRoutes
// Fecha de actualización (GMT): 2026-01-20T11:54:46Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

/** Todas las rutas del módulo */
export type ProfileRoutesRoutes = typeof profileRoutes

/** Nombre de ruta */
export type ProfileRoutesRouteName = keyof ProfileRoutesRoutes

export const profileRoutes = {
  index: "/profile",
  update: "/profile/{id}",
  changePassword: "/profile/password",
} as const;
