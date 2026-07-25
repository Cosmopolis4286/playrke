import { commerceRoutes } from '../../../commerceRoutes';

type CommerceRoutes = typeof commerceRoutes;
type CommerceAdminRouteName = 'prizes' | 'sellers' | 'currencyByCountry' | 'sellerInfoByCarton' | 'cartonLotPayment' | 'paymentDetailsPublic';

export const commercePublicRoutes: Pick<CommerceRoutes, CommerceAdminRouteName> = {
  prizes: commerceRoutes.prizes,
  sellers: commerceRoutes.sellers,
  currencyByCountry: commerceRoutes.currencyByCountry,
  sellerInfoByCarton: commerceRoutes.sellerInfoByCarton,
  cartonLotPayment: commerceRoutes.cartonLotPayment,
  paymentDetailsPublic: commerceRoutes.paymentDetailsPublic
};
