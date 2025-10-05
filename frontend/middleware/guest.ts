/**
 * Middleware guest
 *
 * Protege rutas que solo deben ser accesibles para usuarios NO autenticados
 * (como login, register). Redirige al dashboard si ya está autenticado.
 */

export default defineNuxtRouteMiddleware(async (to, from) => {
  const { isAuthenticated, user, checkAuth } = useAuth()

  // Si ya está autenticado, redirigir según rol
  if (isAuthenticated.value) {
    const primaryRole = user.value?.roles?.[0] || 'player'

    const roleRoutes: Record<string, string> = {
      admin: '/admin/dashboard',
      customer: '/customer/dashboard',
      commerce: '/commerce/dashboard',
      seller: '/seller/dashboard',
      treasurer: '/treasurer/dashboard',
      player: '/player/lobby'
    }

    const targetRoute = roleRoutes[primaryRole] || '/player/lobby'

    return navigateTo(targetRoute, {
      redirectCode: 302
    })
  }

  // No autenticado, permitir acceso a páginas guest
  return
})
