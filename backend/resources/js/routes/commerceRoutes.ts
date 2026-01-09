// ======================================================
// Archivo: resources/js/routes/commerceRoutes.ts
// Descripción: Rutas del módulo commerce
// Generado automáticamente con make:ts-routes-modules
// ======================================================

/** Todas las rutas del módulo */
export type CommerceRoutes = typeof commerceRoutes

/** Nombre de ruta */
export type CommerceRouteName = keyof CommerceRoutes

/** Solo rutas administrativas */
export type CommerceAdminRouteName = {
  [K in CommerceRouteName]: CommerceRoutes[K] extends `/admin/commerce${string}` ? K : never
}[CommerceRouteName]

/** Solo rutas públicas */
export type CommercePublicRouteName = Exclude<CommerceRouteName, CommerceAdminRouteName>

/**
 * Rutas base del módulo commerce
 * ⚠️ Archivo generado: no editar a mano
 */
export const commerceRoutes = {
  store: '/admin/commerce',
  show: '/admin/commerce/{id}',
  update: '/admin/commerce/{id}',
  delete: '/admin/commerce/{id}/delete',
  destroy: '/admin/commerce/{id}',
  resetPassword: '/admin/commerce/{id}/password',
  changeStatus: '/admin/commerce/{id}/status',
  rewards: '/commerce/rewards',
  sellers: '/commerce/sellers',
  currencyByCountry: '/commerce/seller/currency/{country}/{seller}',
  sellerInfoByCarton: '/commerce/seller/info/{ctnId}',
  cartonLotPayment: '/commerce/bingo/carton-lot/payment',
  paymentDetailsPublic: '/commerce/payment-details/{commerce}/{currency}'
} as const

export const commerceAdminRoutes = {
  store: commerceRoutes.store,
  show: commerceRoutes.show,
  update: commerceRoutes.update,
  delete: commerceRoutes.delete,
  destroy: commerceRoutes.destroy,
  resetPassword: commerceRoutes.resetPassword,
  changeStatus: commerceRoutes.changeStatus
} satisfies Pick<CommerceRoutes, CommerceAdminRouteName>

export const commercePublicRoutes = {
  rewards: commerceRoutes.rewards,
  sellers: commerceRoutes.sellers,
  currencyByCountry: commerceRoutes.currencyByCountry,
  sellerInfoByCarton: commerceRoutes.sellerInfoByCarton,
  cartonLotPayment: commerceRoutes.cartonLotPayment,
  paymentDetailsPublic: commerceRoutes.paymentDetailsPublic
} satisfies Pick<CommerceRoutes, CommercePublicRouteName>
