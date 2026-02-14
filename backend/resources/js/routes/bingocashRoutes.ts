// ======================================================
// Archivo: resources/js/routes/bingocashRoutes.ts
// Descripción: Rutas del módulo bingocashRoutes
// Fecha de actualización (GMT): 2026-01-20T13:36:15Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

export const bingocashRoutes = {
  outGame: "/bingocash/game-room/{uuidCode}/{room?}/{currency?}",
  generateCarton: "/bingocash/generate-card/store",
  getDraw: "/bingocash/get-draw/{room}",
  getChips: "/bingocash/get/chips/{room}",
  getResult: "/bingocash/get/result/{room}/{value}/{column?}",
} as const;
