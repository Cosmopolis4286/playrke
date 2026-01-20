// ======================================================
// Archivo: resources/js/routes/directoryRoutes.ts
// Descripción: Rutas del módulo directoryRoutes
// Fecha de actualización (GMT): 2026-01-20T11:54:46Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

/** Todas las rutas del módulo */
export type DirectoryRoutesRoutes = typeof directoryRoutes

/** Nombre de ruta */
export type DirectoryRoutesRouteName = keyof DirectoryRoutesRoutes

export const directoryRoutes = {
  index: "/directory/directory",
  country: "/directory/{countryCode}",
} as const;
