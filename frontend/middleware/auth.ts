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
      // No autenticado, redirigir a login
      return navigateTo('/auth/login', {
        redirectCode: 302
      })
    }
  }

  // Autenticado, permitir acceso
  return
})
