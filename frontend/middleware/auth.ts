/**
 * Middleware de autenticación
 *
 * Protege rutas que requieren usuario autenticado.
 * Redirige a login si el usuario no está autenticado.
 */

export default defineNuxtRouteMiddleware(async (to, from) => {
  const { isAuthenticated, checkAuth } = useAuth()

  // Verificar autenticación
  if (!isAuthenticated.value) {
    // Intentar verificar sesión
    const hasAuth = await checkAuth()

    if (!hasAuth) {
      // Determinar contexto basado en la ruta
      const isAdminRoute = to.path.startsWith('/admin')
      const loginPath = isAdminRoute ? '/admin/auth/login' : '/player/auth/login'

      // No autenticado, redirigir a login del contexto correspondiente
      return navigateTo(loginPath, {
        redirectCode: 302
      })
    }
  }

  // Autenticado, permitir acceso
  return
})
