/**
 * Composable de autenticación compartido.
 *
 * Proporciona funcionalidad de autenticación común entre
 * las secciones admin y player, usando Laravel Sanctum
 * para autenticación stateful con cookies.
 */

import type { User, LoginCredentials, RegisterData } from '../types/auth'
import { useAuthStore } from '../stores/auth'

export const useAuth = () => {
  const config = useRuntimeConfig()
  const router = useRouter()

  // Store de Pinia para persistencia
  const authStore = useAuthStore()

  // Estados reactivos del store
  const user = computed(() => authStore.user)
  const token = computed(() => authStore.token)
  const isLoading = computed(() => authStore.isLoading)
  const isAuthenticated = computed(() => authStore.isAuthenticated)

  /**
   * Obtiene el token CSRF de Laravel Sanctum.
   */
  const getCsrfToken = async (): Promise<void> => {
    await $fetch(`${config.public.apiBase.replace('/api', '')}/sanctum/csrf-cookie`, {
      credentials: 'include'
    })
  }

  /**
   * Realiza el login del usuario.
   *
   * @param credentials Credenciales de acceso
   */
  const login = async (credentials: LoginCredentials): Promise<void> => {
    authStore.setLoading(true)

    try {
      // Obtener CSRF token primero
      await getCsrfToken()

      // Hacer login usando la ruta correcta de la API
      const data = await $fetch<{ success: boolean; user: User }>(`${config.public.apiBase}/auth/login`, {
        method: 'POST',
        body: credentials,
        credentials: 'include'
      })

      // Guardar usuario en store
      authStore.setAuth('', data.user) // Token está en cookie, no lo necesitamos

      console.log('[Auth] Login exitoso:', data.user.username)

      // No redirigir automáticamente - dejamos que el componente maneje la redirección
    } catch (error: any) {
      console.error('[Auth] Error en login:', error)
      throw error
    } finally {
      authStore.setLoading(false)
    }
  }

  /**
   * Registra un nuevo usuario.
   *
   * @param data Datos de registro
   */
  const register = async (data: RegisterData): Promise<void> => {
    authStore.setLoading(true)

    try {
      // Obtener CSRF token primero
      await getCsrfToken()

      // Agregar country_id por defecto si no existe (requerido por backend)
      const registerData = {
        ...data,
        country_id: data.country_id || 1 // Default country ID, ajustar según necesidad
      }

      // Hacer registro usando la ruta correcta de la API
      const response = await $fetch<{ success: boolean; user: User }>(`${config.public.apiBase}/auth/register`, {
        method: 'POST',
        body: registerData,
        credentials: 'include'
      })

      // Guardar usuario en store
      authStore.setAuth('', response.user)

      console.log('[Auth] Registro exitoso:', response.user.username)

      // No redirigir automáticamente - dejamos que el componente maneje la redirección
    } catch (error: any) {
      console.error('[Auth] Error en registro:', error)
      throw error
    } finally {
      authStore.setLoading(false)
    }
  }

  /**
   * Realiza el logout del usuario.
   */
  const logout = async (): Promise<void> => {
    authStore.setLoading(true)

    try {
      // Llamar endpoint de logout usando la ruta correcta de la API
      await $fetch(`${config.public.apiBase}/auth/logout`, {
        method: 'POST',
        credentials: 'include'
      })

      console.log('[Auth] Logout exitoso')
    } catch (error) {
      console.warn('[Auth] Error en logout:', error)
    } finally {
      // Limpiar estado local
      authStore.clearAuth()
      authStore.setLoading(false)

      // Determinar contexto para redirigir al login correspondiente
      const currentPath = router.currentRoute.value.path
      const isAdminRoute = currentPath.startsWith('/admin')
      const loginPath = isAdminRoute ? '/admin/auth/login' : '/player/auth/login'

      // Redirigir a login del contexto correspondiente
      await router.push(loginPath)
    }
  }

  /**
   * Obtiene el usuario autenticado actual.
   *
   * Manejo de errores semántico:
   * - 401: No autenticado (esperado, no es error)
   * - 403: Sin permisos (log warning)
   * - 5xx: Error del servidor (log error)
   * - Network: Error de red (log error)
   */
  const fetchUser = async (): Promise<User | null> => {
    try {
      const data = await $fetch<User>(`${config.public.apiBase}/user`, {
        credentials: 'include'
      })

      authStore.updateUser(data)
      return data
    } catch (error: any) {
      // 401 es esperado cuando no hay sesión - no es un error
      if (error?.response?.status === 401 || error?.statusCode === 401) {
        authStore.clearAuth()
        return null
      }

      // 403 indica problema de permisos - warning
      if (error?.response?.status === 403 || error?.statusCode === 403) {
        console.warn('[Auth] Access forbidden - insufficient permissions')
        authStore.clearAuth()
        return null
      }

      // Otros errores son inesperados - log error
      console.error('[Auth] Unexpected error fetching user:', {
        status: error?.response?.status || error?.statusCode,
        message: error?.message,
        url: `${config.public.apiBase}/user`
      })

      authStore.clearAuth()
      return null
    }
  }

  /**
   * Verifica si el usuario está autenticado.
   */
  const checkAuth = async (): Promise<boolean> => {
    try {
      const userData = await fetchUser()
      return !!userData
    } catch {
      return false
    }
  }

  /**
   * Redirige al usuario según su rol principal.
   */
  const redirectByRole = async (userData: User): Promise<void> => {
    const primaryRole = userData.roles?.[0] || 'player'

    const roleRoutes: Record<string, string> = {
      admin: '/admin/dashboard',
      customer: '/customer/dashboard',
      commerce: '/commerce/dashboard',
      seller: '/seller/dashboard',
      treasurer: '/treasurer/dashboard',
      player: '/player/lobby'
    }

    const targetRoute = roleRoutes[primaryRole] || '/player/lobby'
    await router.push(targetRoute)
  }

  /**
   * Verifica si el usuario tiene un rol específico.
   */
  const hasRole = (role: string): boolean => {
    return authStore.hasRole(role)
  }

  /**
   * Verifica si el usuario tiene un permiso específico.
   */
  const hasPermission = (permission: string): boolean => {
    return authStore.hasPermission(permission)
  }

  /**
   * Verifica si el usuario tiene cualquiera de los roles especificados.
   */
  const hasAnyRole = (roles: string[]): boolean => {
    return authStore.hasAnyRole(roles)
  }

  return {
    // Estados
    user,
    token,
    isLoading,
    isAuthenticated,

    // Métodos
    login,
    register,
    logout,
    fetchUser,
    checkAuth,
    getCsrfToken,
    redirectByRole,

    // Helpers de roles
    hasRole,
    hasPermission,
    hasAnyRole
  }
}