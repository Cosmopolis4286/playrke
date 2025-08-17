// ======================================================
// Archivo: resources/js/routes/bingocashRoutes.ts
// Descripción: Rutas del módulo bingocashRoutes
// Fecha de actualización (GMT): 2025-08-17T20:34:38Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

import { route as baseRoute } from './helper.js';

export const bingocashRoutes = {
    "bingo.out-game": "/bingocash/game-room/{uuidCode}/{room?}/{coin?}",
    "bingo.generateCarton": "/bingocash/carton/generate/store",
    "bingo.getDraw": "/bingocash/getDraw/{room}",
    "bingo.getChips": "/bingocash/get/chips/{room}",
    "bingo.getResult": "/bingocash/get/result/{room}/{value}/{column?}"
} as const;

/**
 * Retorna la URL de la ruta especificada usando las rutas del módulo.
 */
export function route<K extends keyof typeof bingocashRoutes>(
    name: K,
    params?: Record<string, string | number>
): string {
    return baseRoute(bingocashRoutes, name, params);
}