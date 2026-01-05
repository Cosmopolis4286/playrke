// ======================================================
// Archivo: resources/js/routes/commerceRoutes.ts
// Descripción: Rutas del módulo commerceRoutes
// Fecha de actualización (GMT): 2025-12-29T00:05:30Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

export const commerceRoutes = {
    "store": "/admin/commerce",
    "show": "/admin/commerce/{id}",
    "update": "/admin/commerce/{id}",
    "destroy": "/admin/commerce/{id}",
    "changePassword": "/admin/commerce/{id}/password",
    "changeStatus": "/admin/commerce/{id}/status",
    "rewards": "/commerce/rewards",
    "sellers": "/commerce/sellers",
    "currency-by-country": "/commerce/seller/currency/{country}/{seller}",
    "seller-info-by-carton": "/commerce/seller/info/{ctnId}",
    "carton-lot-payment": "/commerce/bingo/carton-lot/payment",
    "payment-details-public": "/commerce/payment-details/{commerce}/{currency}"
} as const;
