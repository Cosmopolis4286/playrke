/**
 * Middleware admin-only
 *
 * Protege rutas que solo deben ser accesibles para administradores.
 * Debe usarse en conjunto con el middleware de auth.
 *
 * Uso en página:
 * definePageMeta({
 *   middleware: ['auth', 'admin-only']
 * })
 */

export default defineNuxtRouteMiddleware((to, from) => {
  const { isAdmin } = useAuth()

  if (!isAdmin()) {
    // No es admin, redirigir a página de acceso denegado o al dashboard del usuario
    return navigateTo('/403', {
      redirectCode: 403
    })
  }

  // Es admin, permitir acceso
  return
})
