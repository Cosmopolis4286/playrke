import { computed } from 'vue'
import type { LoginCredentials, RegisterData, AuthResponse, User } from '../types/auth'
import { useAuthStore } from '../stores/auth'
import { parseLaravelError } from '../utils/parseLaravelError'
import { useApi } from './useApi'

export const useAuth = () => {
  const auth = useAuthStore()
  const { $toast } = useNuxtApp()

  // Helpers de notificaciones
  const notifySuccess = (msg: string, title?: string) =>
    $toast.success(msg, title)

  const notifyError = (msg: string, title?: string) =>
    $toast.error(msg, title)

  // API helper
  const api = useApi()

  // LOGIN
  const login = async (credentials: LoginCredentials): Promise<AuthResponse> => {
    auth.setLoading(true)
    try {
      const response = await api.post<AuthResponse>('/auth/login', credentials)

      if (!response.success || !response.token || !response.user) {
        throw new Error(response.message || 'Error en autenticación')
      }

      auth.setAuth(response.token, response.user)
      notifySuccess('Sesión iniciada correctamente')
      return response
    } catch (error: any) {
      const msg = parseLaravelError(error)
      notifyError(msg)
      throw error
    } finally {
      auth.setLoading(false)
    }
  }

  // REGISTER
  const register = async (data: RegisterData): Promise<AuthResponse> => {
    auth.setLoading(true)
    try {
      const response = await api.post<AuthResponse>('/auth/register', data)

      if (!response.success || !response.token || !response.user) {
        throw new Error(response.message || 'Error en registro')
      }

      auth.setAuth(response.token, response.user)
      notifySuccess('Registro completado correctamente')
      return response
    } catch (error: any) {
      const msg = parseLaravelError(error)
      notifyError(msg)
      throw error
    } finally {
      auth.setLoading(false)
    }
  }

  // LOGOUT
  const logout = async (): Promise<void> => {
    try {
      await api.post('/auth/logout', {})  // body vacío obligatorio
      auth.clearAuth()
      notifySuccess('Has cerrado sesión')
    } catch (error: any) {
      notifyError(parseLaravelError(error))
      throw error
    }
  }

  // CHECK AUTH
  const checkAuth = async (): Promise<boolean> => {
    try {
      const user = await api.get<User>('/auth/me')
      auth.updateUser(user)
      return true
    } catch {
      return false
    }
  }

  // RELOAD USER
  const reloadUser = async (): Promise<User | null> => {
    try {
      const user = await api.get<User>('/auth/me')
      auth.updateUser(user)
      return user
    } catch (error: any) {
      notifyError(parseLaravelError(error))
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

    hasRole: auth.hasRole,
    hasPermission: auth.hasPermission,
    isAdmin: auth.isAdmin,
    isPlayer: auth.isPlayer,
  }
}
