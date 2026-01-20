import type { CommerceRoutes, CommerceAdminRouteName } from './commerceRoutesType';
import { commerceRoutes } from '../../commerceRoutes';

export const commerceAdminRoutes: Pick<CommerceRoutes, CommerceAdminRouteName> = {
  store: commerceRoutes.store,
  show: commerceRoutes.show,
  update: commerceRoutes.update,
  delete: commerceRoutes.delete,
  destroy: commerceRoutes.destroy,
  resetPassword: commerceRoutes.resetPassword,
  changeStatus: commerceRoutes.changeStatus,
};
