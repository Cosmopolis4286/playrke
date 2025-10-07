/**
 * Plugin de autenticación para administradores
 * Verifica permisos de admin antes de acceder a rutas protegidas
 */

export default defineNuxtPlugin(() => {
  const router = useRouter()
  const { user, hasRole } = useAuth()

  // Verificar permisos de admin en cada navegación
  router.beforeEach((to, from, next) => {
    // Solo verificar rutas de admin
    if (to.path.startsWith('/admin')) {
      if (!user.value) {
        // Redirigir a login si no está autenticado
        return next('/auth/login')
      }

      if (!hasRole(['admin', 'superadmin'])) {
        // Redirigir a inicio si no tiene permisos
        return next('/')
      }
    }

    next()
  })

  return {
    provide: {
      adminAuth: {
        checkPermission: (permission: string) => {
          return user.value?.permissions?.includes(permission) || false
        },
        isSuperAdmin: () => {
          return hasRole('superadmin')
        }
      }
    }
  }
})
