/**
 * Middleware de roles
 *
 * Protege rutas según el rol del usuario.
 * Debe usarse en conjunto con el middleware de auth.
 *
 * Uso en página:
 * definePageMeta({
 *   middleware: ['auth', 'role'],
 *   roles: ['admin', 'moderator']
 * })
 */

export default defineNuxtRouteMiddleware((to, from) => {
  const { hasAnyRole } = useAuth()

  // Obtener roles requeridos del meta de la ruta
  const requiredRoles = to.meta.roles as string[] | undefined

  if (!requiredRoles || requiredRoles.length === 0) {
    // No se requieren roles específicos
    return
  }

  // Verificar si el usuario tiene alguno de los roles requeridos
  if (!hasAnyRole(requiredRoles)) {
    // No tiene permiso, redirigir a página de acceso denegado
    return navigateTo('/403', {
      redirectCode: 403
    })
  }

  // Tiene el rol necesario, permitir acceso
  return
})
