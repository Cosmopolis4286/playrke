import { computed } from 'vue'
import { useRuntimeConfig } from '#app'
import type { LoginCredentials, RegisterData, AuthResponse, User } from '../types/auth'
import { useAuthStore } from '../stores/auth'
import { useToast } from '#imports' // auto-import from /composables/useToast.ts

export const useAuth = () => {
  const auth = useAuthStore()
  const config = useRuntimeConfig()
  const toast = useToast() 

  const api = $fetch.create({
    baseURL: (config.public?.apiBase ?? '').replace(/\/$/, ''),
    credentials: 'include',
    onRequest({ options }: { options: any }) {
      options.headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(options.headers ?? {}) as Record<string, string>
      }

      if (auth.token) {
        options.headers.Authorization = `Bearer ${auth.token}`
      }
    },

    onResponseError({ response }: { response: any }) {
      console.error('[Auth API Error]', response)
    }
  })

  // -----------------------------------------------------
  // LOGIN
  // -----------------------------------------------------
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
      
      toast.add({
        title: 'Éxito',
        description: 'Sesión iniciada correctamente',
        color: 'green'
      })
      return response
    } catch (error: any) {
      const message = error.data?.message || error.message || 'Error al iniciar sesión'
      toast.add({
        title: 'Error',
        description: message,
        color: 'red'
      })
      console.error('[Auth] Error en login:', error)
      throw error
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
      const response = await api<AuthResponse>('/auth/register', {
        method: 'POST',
        body: data
      })

      auth.setUser(response.user)
      auth.setToken(response.token)
      auth.setAuthenticated(true)
      
      toast.add({
        title: 'Éxito',
        description: 'Registro completado correctamente',
        color: 'green'
      })
      return response
    } catch (error: any) {
      const message = error.data?.message || error.message || 'Error en el registro'
      toast.add({
        title: 'Error',
        description: message,
        color: 'red'
      })
      console.error('[Auth] Error en registro:', error)
      throw error
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
      auth.clearAuth()
      toast.add({
        title: 'Sesión cerrada',
        description: 'Has cerrado sesión correctamente',
        color: 'blue'
      })
    } catch (error: any) {
      const message = error.data?.message || 'Error al cerrar sesión'
      toast.add({
        title: 'Error',
        description: message,
        color: 'red'
      })
      console.error('[Auth] Error en logout:', error)
      throw error
    }
  }

  // -----------------------------------------------------
  // CHECK AUTH (sin toast, solo log)
  // -----------------------------------------------------
  const checkAuth = async (): Promise<boolean> => {
    try {
      const user = await api<User>('/auth/me')
      auth.setUser(user)
      auth.setAuthenticated(true)
      return true
    } catch (error: any) {
      if (error.status === 401 || error.status === 500) {
        console.warn('[Auth] Usuario no autenticado:', error.message)
        return false
      }
      console.error('[Auth] Error verificando autenticación:', error)
      return false
    }
  }

  // -----------------------------------------------------
  // RELOAD USER
  // -----------------------------------------------------
  const reloadUser = async (): Promise<User | null> => {
    try {
      const user = await api<User>('/auth/me')
      auth.setUser(user)
      return user
    } catch (error: any) {
      const message = error.data?.message || 'Error al recargar usuario'
      toast.add({
        title: 'Error',
        description: message,
        color: 'red'
      })
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

    hasRole: auth.hasRole,
    hasPermission: auth.hasPermission,
    isAdmin: auth.isAdmin,
    isPlayer: auth.isPlayer
  }
}
