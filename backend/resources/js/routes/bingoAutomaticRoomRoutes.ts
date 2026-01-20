// ======================================================
// Archivo: resources/js/routes/bingoAutomaticRoomRoutes.ts
// Descripción: Rutas del módulo bingoAutomaticRoomRoutes
// Fecha de actualización (GMT): 2026-01-20T11:54:46Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

/** Todas las rutas del módulo */
export type BingoAutomaticRoomRoutesRoutes = typeof bingoAutomaticRoomRoutes

/** Nombre de ruta */
export type BingoAutomaticRoomRoutesRouteName = keyof BingoAutomaticRoomRoutesRoutes

export const bingoAutomaticRoomRoutes = {
  bingoAutomaticShow: "/bingo-automatic-room/{id}",
  bingoAutomaticCreate: "/bingo-automatic-room",
  bingoAutomaticUpdate: "/bingo-automatic-room/{id}",
  bingoAutomaticChangeStatus: "/bingo-automatic-room/{id}/status",
  bingoAutomaticDestroy: "/bingo-automatic-room/{id}/delete",
  bingoAutomaticCopy: "/bingo-automatic-room/{id}/copy",
  bingoAutomaticExecuteJob: "/bingo-automatic-room/execute-job/{creatorType}/{creatorId}",
} as const;
