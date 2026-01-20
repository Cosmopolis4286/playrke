// ======================================================
// Archivo: resources/js/routes/bingocashRoutes.ts
// Descripción: Rutas del módulo bingocashRoutes
// Fecha de actualización (GMT): 2026-01-20T11:54:46Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

/** Todas las rutas del módulo */
export type BingocashRoutesRoutes = typeof bingocashRoutes

/** Nombre de ruta */
export type BingocashRoutesRouteName = keyof BingocashRoutesRoutes

export const bingocashRoutes = {
  outGame: "/bingocash/game-room/{uuidCode}/{room?}/{currency?}",
  generateCarton: "/bingocash/carton/generate/store",
  getDraw: "/bingocash/get-draw/{room}",
  getChips: "/bingocash/get/chips/{room}",
  getResult: "/bingocash/get/result/{room}/{value}/{column?}",
} as const;
