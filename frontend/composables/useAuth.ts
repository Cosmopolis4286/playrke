import { computed } from 'vue'
import type { LoginCredentials, RegisterData, AuthResponse, User } from '~/types/auth'
import { useAuthStore } from '~/stores/auth'
import { parseLaravelError } from '~/utils/parseLaravelError'
import { useApi } from './useApi'

export const useAuth = () => {
  const auth = useAuthStore()
  const { $toast } = useNuxtApp()
  const api = useApi()

  // Helpers de notificaciones
  const notifySuccess = (msg: string, title?: string) => $toast.success(msg, title)
  const notifyError = (msg: string, title?: string) => $toast.error(msg, title)

  // LOGIN
  const login = async (credentials: LoginCredentials): Promise<AuthResponse> => {
    auth.setLoading(true)
    try {
      await api.csrfCookie()
      const response = await api.post<AuthResponse>('/auth/login', credentials)

      // Validar respuesta del backend
      if (!response.success || !response.user) {
        throw new Error(response.message || 'Error en autenticación')
      }

      // El backend usa sesiones, no tokens - usar 'session' como placeholder
      const sessionToken = response.token || 'session'

      auth.setAuth(sessionToken, response.user)
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
      await api.csrfCookie()
      const response = await api.post<AuthResponse>('/auth/register', data)

      // Validar respuesta del backend
      if (!response.success || !response.user) {
        throw new Error(response.message || 'Error en registro')
      }

      // El backend usa sesiones, no tokens - usar 'session' como placeholder
      const sessionToken = response.token || 'session'

      auth.setAuth(sessionToken, response.user)
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
      await api.csrfCookie()
      await api.post('/auth/logout', {})  // body vacío obligatorio
      auth.clearAuth()
      notifySuccess('Has cerrado sesión')

      // Redirigir a la página de login correspondiente
      const router = useRouter()
      const route = useRoute()
      const isAdminRoute = route.path.startsWith('/admin')
      const loginPath = isAdminRoute ? '/admin/auth/login' : '/player/auth/login'

      await navigateTo(loginPath, {
        replace: true,
        redirectCode: 302
      })
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
    hasAnyRole: auth.hasAnyRole,
    hasAllRoles: auth.hasAllRoles,
    isAdmin: auth.isAdmin,
    isPlayer: auth.isPlayer,
  }
}
