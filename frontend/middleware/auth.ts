/**
 * Middleware de autenticación
 *
 * Protege rutas que requieren usuario autenticado.
 * Redirige a login si el usuario no está autenticado.
 */

export default defineNuxtRouteMiddleware(async (to, from) => {
  // CRITICAL: Excluir rutas de autenticación para evitar loops infinitos
  // Las rutas /admin/auth/* y /player/auth/* usan el middleware 'guest' en su lugar
  const authRoutes = [
    '/admin/auth/login',
    '/admin/auth/register',
    '/admin/auth/forgot-password',
    '/player/auth/login',
    '/player/auth/register',
    '/player/auth/forgot-password'
  ]

  if (authRoutes.some(route => to.path.startsWith(route))) {
    return
  }

  const { isAuthenticated, checkAuth } = useAuth()

  // NOTA: La restauración desde localStorage ahora se hace en el plugin 01.restore-auth.client.ts
  // No es necesario duplicar la lógica aquí

  // Verificar autenticación
  if (!isAuthenticated.value) {
    // Intentar verificar sesión con el backend
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
