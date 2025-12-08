import type { LoginCredentials, RegisterData, AuthResponse, User } from '../types/auth'
import { useAuthStore } from '../stores/auth'

export const useAuth = () => {
  const auth = useAuthStore()
  const config = useRuntimeConfig()

  const api = $fetch.create({
    baseURL: config.public.apiBase,

    // -----------------------------
    // FIX: Headers deben ser Record<string, string>
    // -----------------------------
    onRequest({ options }) {
      options.headers = options.headers ?? {}

      if (auth.token) {
        options.headers = {
          ...options.headers as Record<string, string>,
          Authorization: `Bearer ${auth.token}`
        }
      }
    },

    onResponseError({ response }) {
      console.error('[Auth API Error]', response)
    }
  })

  // -----------------------------------------------------
  // LOGIN
  // -----------------------------------------------------
  const login = async (credentials: LoginCredentials): Promise<AuthResponse> => {
    auth.setLoading(true)

    try {
      const res = await api<AuthResponse>('/auth/login', {
        method: 'POST',

        // -----------------------------
        // FIX: body debe ser tipo unknown
        // $fetch lo convierte a JSON
        // -----------------------------
        body: credentials as unknown
      })

      if (res.success && res.user) {
        auth.setAuth(res.user)
      }

      return res
    } catch (error: any) {
      console.error('[Auth] Error en login:', error)
      return {
        success: false,
        message: error?.response?._data?.message || 'Error al iniciar sesión'
      }
    } finally {
      auth.setLoading(false)
    }
  }

  // -----------------------------------------------------
  // REGISTER
  // -----------------------------------------------------
  const register = async (data: RegisterData): Promise<AuthResponse> => {
    auth.setLoading(true)

    try {
      const res = await api<AuthResponse>('/auth/register', {
        method: 'POST',
        body: data as unknown
      })

      if (res.success && res.user) {
        auth.setAuth(res.user)
      }

      return res
    } catch (error: any) {
      console.error('[Auth] Error en registro:', error)
      return {
        success: false,
        message: error?.response?._data?.message || 'Error al registrarse'
      }
    } finally {
      auth.setLoading(false)
    }
  }

  // -----------------------------------------------------
  // LOGOUT
  // -----------------------------------------------------
  const logout = async (): Promise<void> => {
    try {
      await api('/auth/logout', { method: 'POST' })
    } finally {
      auth.clearAuth()
    }
  }

  const reloadUser = async (): Promise<User | null> => {
    try {
      const user = await api<User>('/auth/me')
      auth.updateUser(user)
      return user
    } catch {
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

    hasRole: auth.hasRole,
    hasPermission: auth.hasPermission,
    isAdmin: auth.isAdmin,
    isPlayer: auth.isPlayer
  }
}
