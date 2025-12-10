import { computed } from 'vue'
import { useRuntimeConfig } from '#imports'
import type { LoginCredentials, RegisterData, AuthResponse, User } from '../types/auth'
import { useAuthStore } from '../stores/auth'

export const useAuth = () => {
  const auth = useAuthStore()
  const config = useRuntimeConfig()
  const { $toast } = useNuxtApp()

  // ----------------------------
  // Helper de notificaciones
  // ----------------------------
  const notifySuccess = (msg: string, title?: string) =>
    $toast.success(msg, title)

  const notifyError = (msg: string, title?: string) =>
    $toast.error(msg, title)

  // ----------------------------
  // API instance
  // ----------------------------
  const api = $fetch.create({
    baseURL: (config.public?.apiBase ?? '').replace(/\/$/, ''),
    credentials: 'include',

    onRequest({ options }) {
      const headers = new Headers(options.headers)

      headers.set('accept', 'application/json')
      headers.set('X-Requested-With', 'XMLHttpRequest')

      options.headers = headers
    },

    onResponseError({ response }) {
      const msg = response?._data?.message ?? `Error ${response?.status ?? 'desconocido'}`
      notifyError(msg)
      console.error('[Auth API Error]', response)
    }
  })

  // ----------------------------
  // LOGIN
  // ----------------------------
  const login = async (credentials: LoginCredentials): Promise<AuthResponse> => {
    auth.setLoading(true)
    try {
      const response = await api<AuthResponse>('/auth/login', {
        method: 'POST',
        body: credentials
      })

      auth.setUser(response.user)
      auth.setToken(response.token)
      auth.setAuthenticated(true)

      notifySuccess('Sesión iniciada correctamente', 'Éxito')

      return response
    } catch (error: any) {
      notifyError(error.data?.message || 'Error al iniciar sesión')
      console.error('[Auth] Error en login:', error)
      throw error
    } finally {
      auth.setLoading(false)
    }
  }

  // ----------------------------
  // REGISTER
  // ----------------------------
  const register = async (data: RegisterData): Promise<AuthResponse> => {
    auth.setLoading(true)
    try {
      const response = await api<AuthResponse>('/auth/register', {
        method: 'POST',
        body: data
      })

      auth.setUser(response.user)
      auth.setToken(response.token)
      auth.setAuthenticated(true)

      notifySuccess('Registro completado correctamente', 'Éxito')

      return response
    } catch (error: any) {
      notifyError(error.data?.message || 'Error en el registro')
      console.error('[Auth] Error en register:', error)
      throw error
    } finally {
      auth.setLoading(false)
    }
  }

  // ----------------------------
  // LOGOUT
  // ----------------------------
  const logout = async (): Promise<void> => {
    try {
      await api('/auth/logout', { method: 'POST' })
      auth.clearAuth()
      notifySuccess('Has cerrado sesión correctamente', 'Sesión cerrada')
    } catch (error: any) {
      notifyError(error.data?.message || 'Error al cerrar sesión')
      console.error('[Auth] Error en logout:', error)
      throw error
    }
  }

  // ----------------------------
  // CHECK AUTH
  // ----------------------------
  const checkAuth = async (): Promise<boolean> => {
    try {
      const user = await api<User>('/auth/me')
      auth.setUser(user)
      auth.setAuthenticated(true)
      return true
    } catch (error: any) {
      if (error.status === 401 || error.status === 500) return false
      console.error('[Auth] Error verificando autenticación:', error)
      return false
    }
  }

  // ----------------------------
  // RELOAD USER
  // ----------------------------
  const reloadUser = async (): Promise<User | null> => {
    try {
      const user = await api<User>('/auth/me')
      auth.setUser(user)
      return user
    } catch (error: any) {
      notifyError(error.data?.message || 'Error al recargar usuario')
      console.error('[Auth] Error en reloadUser:', error)
      return null
    }
  }

  return {
    user: computed(() => auth.user),
    isAuthenticated: computed(() => auth.isAuthenticated),
    isLoading: computed(() => auth.isLoading),

    login,
    register,
    logout,
    reloadUser,
    checkAuth,

    // helpers de roles del store
    hasRole: auth.hasRole,
    hasPermission: auth.hasPermission,
    isAdmin: auth.isAdmin,
    isPlayer: auth.isPlayer
  }
}
