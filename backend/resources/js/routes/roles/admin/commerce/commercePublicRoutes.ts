import type { CommerceRoutes, CommerceAdminRouteName } from './commerceRoutesType';
import { commerceRoutes } from '../../commerceRoutes';

export const commercePublicRoutes: Pick<CommerceRoutes, CommerceAdminRouteName> = {
  rewards: commerceRoutes.rewards,
  sellers: commerceRoutes.sellers,
  currencyByCountry: commerceRoutes.currencyByCountry,
  sellerInfoByCarton: commerceRoutes.sellerInfoByCarton,
  cartonLotPayment: commerceRoutes.cartonLotPayment,
  paymentDetailsPublic: commerceRoutes.paymentDetailsPublic
};
