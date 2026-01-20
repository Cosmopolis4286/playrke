// ======================================================
// Archivo: resources/js/routes/apiRoutes.ts
// Descripción: Rutas del módulo apiRoutes
// Fecha de actualización (GMT): 2026-01-20T11:54:46Z
// Generado automáticamente con make:ts-routes-modules
// ======================================================

/** Todas las rutas del módulo */
export type ApiRoutesRoutes = typeof apiRoutes

/** Nombre de ruta */
export type ApiRoutesRouteName = keyof ApiRoutesRoutes

export const apiRoutes = {
  user: "/api/user",
  authLogin: "/api/auth/login",
  authRegister: "/api/auth/register",
  authForgotPassword: "/api/auth/forgot-password",
  authResetPassword: "/api/auth/resetPassword",
  apiAuthLogin: "/api/api/auth/login",
  apiAuthRegister: "/api/api/auth/register",
  apiAuthLogout: "/api/api/auth/logout",
  apiUser: "/api/api/user",
  ping: "/api/ping",
} as const;
