// ======================================================
// Archivo: resources/js/routes/adminRoutes.ts
// Descripción: Rutas del módulo adminRoutes
// Fecha de actualización (GMT): 2026-01-20T13:36:15Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

export const adminRoutes = {
  dashboard: "/admin",
  customers: "/admin/customers",
  commerces: "/admin/commerces",
  sellers: "/admin/sellers",
  treasurers: "/admin/treasurers",
  otherTreasurers: "/admin/other-treasurers",
  subsidiaries: "/admin/subsidiaries",
  users: "/admin/users",
  getUserInfo: "/admin/get-user-info/{userRole}/{exclude?}",
  commerceStore: "/admin/commerce",
  commerceShow: "/admin/commerce/{commerce}",
  commerceUpdate: "/admin/commerce/{commerce}",
  commerceDelete: "/admin/commerce/{commerce}/delete",
  commerceDestroy: "/admin/commerce/{commerce}",
  commerceResetPassword: "/admin/commerce/{commerce}/password",
  commerceChangeStatus: "/admin/commerce/{commerce}/status",
  customerStore: "/admin/customer",
  customerShow: "/admin/customer/{customer}",
  customerUpdate: "/admin/customer/{customer}",
  customerDelete: "/admin/customer/{customer}/delete",
  customerDestroy: "/admin/customer/{customer}",
  customerResetPassword: "/admin/customer/{customer}/password",
  customerChangeStatus: "/admin/customer/{customer}/status",
  treasurerStore: "/admin/treasurer",
  treasurerShow: "/admin/treasurer/{treasurer}",
  treasurerUpdate: "/admin/treasurer/{treasurer}",
  treasurerDelete: "/admin/treasurer/{treasurer}/delete",
  treasurerDestroy: "/admin/treasurer/{treasurer}",
  treasurerResetPassword: "/admin/treasurer/{treasurer}/password",
  treasurerChangeStatus: "/admin/treasurer/{treasurer}/status",
  otherTreasurersStore: "/admin/other-treasurers",
  otherTreasurersShow: "/admin/other-treasurers/{otherTreasurer}",
  otherTreasurersUpdate: "/admin/other-treasurers/{otherTreasurer}",
  otherTreasurersDelete: "/admin/other-treasurers/{otherTreasurer}/delete",
  otherTreasurersDestroy: "/admin/other-treasurers/{otherTreasurer}",
  otherTreasurersResetPassword: "/admin/other-treasurers/{otherTreasurer}/password",
  otherTreasurersChangeStatus: "/admin/other-treasurers/{otherTreasurer}/status",
} as const;
