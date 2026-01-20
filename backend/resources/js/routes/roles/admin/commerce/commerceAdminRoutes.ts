import { adminRoutes } from '@js/routes/adminRoutes';

export const commerceAdminRoutes = {
  store: adminRoutes.commerceStore,
  show: adminRoutes.commerceShow,
  update: adminRoutes.commerceUpdate,
  delete: adminRoutes.commerceDelete,
  destroy: adminRoutes.commerceDestroy,
  resetPassword: adminRoutes.commerceResetPassword,
  changeStatus: adminRoutes.commerceChangeStatus,
} as const;
