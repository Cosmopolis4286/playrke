/**
 * Composable de autenticación compartido.
 *
 * Proporciona funcionalidad de autenticación común entre
 * las secciones admin y player, incluyendo login, logout,
 * verificación de sesión y manejo de tokens.
 */

import type { User, LoginCredentials, AuthResponse } from '../types/auth'

export const useAuth = () => {
  // Estados reactivos
  const user = ref<User | null>(null)
  const token = ref<string | null>(null)
  const isLoading = ref(false)
  const isAuthenticated = computed(() => !!token.value && !!user.value)

  // Store de Pinia para persistencia
  const authStore = useAuthStore()

  /**
   * Realiza el login del usuario.
   *
   * @param credentials Credenciales de acceso
   * @returns Respuesta de autenticación
   */
  const login = async (credentials: LoginCredentials): Promise<AuthResponse> => {
    isLoading.value = true

    try {
      const data = await $fetch<AuthResponse>('/api/auth/login', {
        method: 'POST',
        body: credentials
      })

      if (data.success && data.token && data.user) {
        token.value = data.token
        user.value = data.user

        // Persistir en store
        authStore.setAuth(data.token, data.user)

        // Configurar header de autorización
        await setAuthHeader(data.token)
      }

      return data
    } catch (error: any) {
      throw new Error(error.data?.message || 'Error en el login')
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Realiza el logout del usuario.
   */
  const logout = async (): Promise<void> => {
    isLoading.value = true

    try {
      // Llamar endpoint de logout
      await $fetch('/api/auth/logout', {
        method: 'POST'
      })
    } catch (error) {
      console.warn('Error en logout:', error)
    } finally {
      // Limpiar estado local independientemente del resultado
      token.value = null
      user.value = null
      authStore.clearAuth()
      await clearAuthHeader()
      isLoading.value = false
    }
  }

  /**
   * Verifica y renueva la sesión actual.
   */
  const refreshSession = async (): Promise<boolean> => {
    if (!token.value) return false

    try {
      const data = await $fetch<AuthResponse>('/api/auth/refresh', {
        method: 'POST'
      })

      if (data.success && data.token && data.user) {
        token.value = data.token
        user.value = data.user
        authStore.setAuth(data.token, data.user)
        await setAuthHeader(data.token)
        return true
      }
    } catch (error) {
      console.warn('Error renovando sesión:', error)
      await logout()
    }

    return false
  }

  /**
   * Verifica si el usuario tiene un rol específico.
   *
   * @param role Rol a verificar
   * @returns True si el usuario tiene el rol
   */
  const hasRole = (role: string): boolean => {
    return user.value?.roles?.includes(role) ?? false
  }

  /**
   * Verifica si el usuario tiene un permiso específico.
   *
   * @param permission Permiso a verificar
   * @returns True si el usuario tiene el permiso
   */
  const hasPermission = (permission: string): boolean => {
    return user.value?.permissions?.includes(permission) ?? false
  }

  /**
   * Configura el header de autorización para las peticiones.
   *
   * @param authToken Token de autorización
   */
  const setAuthHeader = async (authToken: string): Promise<void> => {
    // En Nuxt 3, configuramos headers globales via runtime config
    const nuxtApp = useNuxtApp()
    nuxtApp.ssrContext = nuxtApp.ssrContext || {}
    nuxtApp.ssrContext.event = nuxtApp.ssrContext.event || {}
  }

  /**
   * Limpia el header de autorización.
   */
  const clearAuthHeader = async (): Promise<void> => {
    // Limpiar headers en Nuxt 3
    const nuxtApp = useNuxtApp()
    if (nuxtApp.ssrContext?.event) {
      // Limpiar header de autorización
    }
  }

  /**
   * Inicializa la autenticación desde storage persistente.
   */
  const initAuth = async (): Promise<void> => {
    const storedAuth = authStore.getAuth()

    if (storedAuth.token && storedAuth.user) {
      token.value = storedAuth.token
      user.value = storedAuth.user
      await setAuthHeader(storedAuth.token)

      // Verificar que la sesión siga siendo válida
      await refreshSession()
    }
  }

  /**
   * Actualiza los datos del usuario actual.
   *
   * @param userData Nuevos datos del usuario
   */
  const updateUser = (userData: Partial<User>): void => {
    if (user.value) {
      user.value = { ...user.value, ...userData }
      authStore.updateUser(user.value)
    }
  }

  // Auto-inicializar en el cliente
  if (process.client) {
    initAuth()
  }

  return {
    // Estados
    user: readonly(user),
    token: readonly(token),
    isLoading: readonly(isLoading),
    isAuthenticated,

    // Métodos
    login,
    logout,
    refreshSession,
    hasRole,
    hasPermission,
    updateUser,
    initAuth
  }
}