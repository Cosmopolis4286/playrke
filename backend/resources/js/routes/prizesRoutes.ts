// ======================================================
// Archivo: resources/js/routes/prizesRoutes.ts
// Descripción: Rutas del módulo Prizes (CRUD completo)
// Fecha de actualización (GMT): 2026-02-18T00:00:00Z
// ======================================================

export const prizesRoutes = {
  index:        '/prizes',
  fetchList:    '/prizes/fetch-list',
  store:        '/prizes',
  show:         (id: string | number) => `/prizes/${id}/show`,
  update:       (id: string | number) => `/prizes/${id}/update`,
  delete:       (id: string | number) => `/prizes/${id}/delete`,
  destroy:      (id: string | number) => `/prizes/${id}`,
  toggleStatus: (id: string | number) => `/prizes/${id}/status`,
} as const
