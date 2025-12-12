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

  const authStore = useAuthStore()
  const { isAuthenticated, checkAuth } = useAuth()

  // IMPORTANTE: Inicializar desde localStorage si no hay sesión activa
  // Esto resuelve el problema de "pierde sesión al recargar"
  if (!isAuthenticated.value && process.client) {
    const stored = authStore.getAuth()
    if (stored.token && stored.user) {
      authStore.setAuth(stored.token, stored.user)
    }
  }

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
