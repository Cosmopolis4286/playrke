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

      // Hacer login
      const data = await $fetch<{ user: User }>(`${config.public.apiBase.replace('/api', '')}/login`, {
        method: 'POST',
        body: credentials,
        credentials: 'include'
      })

      // Guardar usuario en store
      authStore.setAuth('', data.user) // Token está en cookie, no lo necesitamos

      // Redirigir según rol
      await redirectByRole(data.user)
    } catch (error: any) {
      console.error('Error en login:', error)
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

      // Hacer registro
      const response = await $fetch<{ user: User }>(`${config.public.apiBase.replace('/api', '')}/register`, {
        method: 'POST',
        body: data,
        credentials: 'include'
      })

      // Guardar usuario en store
      authStore.setAuth('', response.user)

      // Redirigir según rol
      await redirectByRole(response.user)
    } catch (error: any) {
      console.error('Error en registro:', error)
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
      // Llamar endpoint de logout
      await $fetch(`${config.public.apiBase.replace('/api', '')}/logout`, {
        method: 'POST',
        credentials: 'include'
      })
    } catch (error) {
      console.warn('Error en logout:', error)
    } finally {
      // Limpiar estado local
      authStore.clearAuth()
      authStore.setLoading(false)

      // Redirigir a login
      await router.push('/auth/login')
    }
  }

  /**
   * Obtiene el usuario autenticado actual.
   */
  const fetchUser = async (): Promise<User | null> => {
    try {
      const data = await $fetch<User>(`${config.public.apiBase}/user`, {
        credentials: 'include'
      })

      authStore.updateUser(data)
      return data
    } catch (error) {
      console.error('Error obteniendo usuario:', error)
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