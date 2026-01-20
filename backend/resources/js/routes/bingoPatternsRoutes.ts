// ======================================================
// Archivo: resources/js/routes/bingoPatternsRoutes.ts
// Descripción: Rutas del módulo bingoPatternsRoutes
// Fecha de actualización (GMT): 2026-01-20T11:54:46Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

/** Todas las rutas del módulo */
export type BingoPatternsRoutesRoutes = typeof bingoPatternsRoutes

/** Nombre de ruta */
export type BingoPatternsRoutesRouteName = keyof BingoPatternsRoutesRoutes

export const bingoPatternsRoutes = {
  bingoPatternShow: "/bingo-patterns/{id}",
  bingoPatternHandle: "/bingo-patterns/handler/{id?}/{action?}",
} as const;
