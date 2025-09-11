import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'

export interface User {
  id: string
  username: string
  first_name?: string
  last_name?: string
  email: string
  email_verified_at?: string
  country_id: string
  phone_number?: string
  profile_photo_url?: string
  status: 'ACTIVE' | 'INACTIVE' | 'SUSPENDED' | 'PENDING_VERIFICATION'
  is_two_factor_enabled: boolean
  is_email_notifications_enabled: boolean
  referral_code: string
  created_at: string
  last_login_at?: string
}

export interface LoginCredentials {
  email: string
  password: string
  remember?: boolean
  code?: string // 2FA code
}

export interface RegisterData {
  username: string
  first_name: string
  last_name: string
  email: string
  password: string
  password_confirmation: string
  country_id: string
  phone_number?: string
  referral_code?: string
  terms: boolean
}

export interface AuthResponse {
  user: User
  token?: string
  two_factor_challenge?: boolean
  requires_email_verification?: boolean
}

export const useAuthStore = defineStore('auth', () => {
  // Estado reactivo
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const isLoading = ref(false)
  const errors = ref<Record<string, string[]>>({})
  const twoFactorChallenge = ref(false)
  const requiresEmailVerification = ref(false)

  // Computadas
  const isAuthenticated = computed(() => !!user.value && !!token.value)
  const isEmailVerified = computed(() => !!user.value?.email_verified_at)
  const hasTwoFactorEnabled = computed(() => user.value?.is_two_factor_enabled ?? false)
  const userFullName = computed(() => {
    if (!user.value) return ''
    return `${user.value.first_name || ''} ${user.value.last_name || ''}`.trim()
  })

  // Router
  const router = useRouter()

  // Configurar axios con token
  const setAuthHeader = (authToken?: string) => {
    const finalToken = authToken || token.value
    if (finalToken) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${finalToken}`
      localStorage.setItem('auth_token', finalToken)
    } else {
      delete axios.defaults.headers.common['Authorization']
      localStorage.removeItem('auth_token')
    }
  }

  // Inicializar axios si hay token
  if (token.value) {
    setAuthHeader()
  }

  // Funciones de autenticación
  const login = async (credentials: LoginCredentials): Promise<boolean> => {
    try {
      isLoading.value = true
      errors.value = {}
      
      const { data } = await axios.post<AuthResponse>('/login', credentials)

      if (data.two_factor_challenge) {
        twoFactorChallenge.value = true
        return false
      }

      if (data.requires_email_verification) {
        requiresEmailVerification.value = true
        return false
      }

      // Login exitoso
      user.value = data.user
      if (data.token) {
        token.value = data.token
        setAuthHeader(data.token)
      }

      await router.push('/dashboard')
      return true
    } catch (error: any) {
      handleAuthError(error)
      return false
    } finally {
      isLoading.value = false
    }
  }

  const verify2FA = async (code: string): Promise<boolean> => {
    try {
      isLoading.value = true
      errors.value = {}

      const { data } = await axios.post<AuthResponse>('/two-factor-challenge', { code })
      
      user.value = data.user
      if (data.token) {
        token.value = data.token
        setAuthHeader(data.token)
      }

      twoFactorChallenge.value = false
      await router.push('/dashboard')
      return true
    } catch (error: any) {
      handleAuthError(error)
      return false
    } finally {
      isLoading.value = false
    }
  }

  const register = async (data: RegisterData): Promise<boolean> => {
    try {
      isLoading.value = true
      errors.value = {}

      const response = await axios.post<AuthResponse>('/register', data)

      if (response.data.requires_email_verification) {
        requiresEmailVerification.value = true
        return true
      }

      // Registro exitoso con login automático
      user.value = response.data.user
      if (response.data.token) {
        token.value = response.data.token
        setAuthHeader(response.data.token)
        await router.push('/dashboard')
      }

      return true
    } catch (error: any) {
      handleAuthError(error)
      return false
    } finally {
      isLoading.value = false
    }
  }

  const logout = async (): Promise<void> => {
    try {
      if (isAuthenticated.value) {
        await axios.post('/logout')
      }
    } catch (error) {
      // Ignorar errores de logout
    } finally {
      // Limpiar estado local
      user.value = null
      token.value = null
      twoFactorChallenge.value = false
      requiresEmailVerification.value = false
      errors.value = {}
      setAuthHeader()
      await router.push('/login')
    }
  }

  const forgotPassword = async (email: string): Promise<boolean> => {
    try {
      isLoading.value = true
      errors.value = {}

      await axios.post('/forgot-password', { email })
      return true
    } catch (error: any) {
      handleAuthError(error)
      return false
    } finally {
      isLoading.value = false
    }
  }

  const resetPassword = async (data: {
    token: string
    email: string
    password: string
    password_confirmation: string
  }): Promise<boolean> => {
    try {
      isLoading.value = true
      errors.value = {}

      await axios.post('/reset-password', data)
      await router.push('/login')
      return true
    } catch (error: any) {
      handleAuthError(error)
      return false
    } finally {
      isLoading.value = false
    }
  }

  const resendEmailVerification = async (): Promise<boolean> => {
    try {
      isLoading.value = true
      errors.value = {}

      await axios.post('/email/verification-notification')
      return true
    } catch (error: any) {
      handleAuthError(error)
      return false
    } finally {
      isLoading.value = false
    }
  }

  const fetchUser = async (): Promise<User | null> => {
    try {
      if (!token.value) return null

      const { data } = await axios.get<{ user: User }>('/api/user')
      user.value = data.user
      return data.user
    } catch (error) {
      // Token inválido, limpiar sesión
      await logout()
      return null
    }
  }

  const updateProfile = async (profileData: Partial<User>): Promise<boolean> => {
    try {
      isLoading.value = true
      errors.value = {}

      const { data } = await axios.put<{ user: User }>(`/profile/${user.value?.id}`, profileData)
      user.value = data.user
      return true
    } catch (error: any) {
      handleAuthError(error)
      return false
    } finally {
      isLoading.value = false
    }
  }

  const changePassword = async (passwordData: {
    current_password: string
    password: string
    password_confirmation: string
  }): Promise<boolean> => {
    try {
      isLoading.value = true
      errors.value = {}

      await axios.patch(`/profile/${user.value?.id}/password`, passwordData)
      return true
    } catch (error: any) {
      handleAuthError(error)
      return false
    } finally {
      isLoading.value = false
    }
  }

  const enable2FA = async (): Promise<{ qr_code: string; recovery_codes: string[] } | null> => {
    try {
      isLoading.value = true
      errors.value = {}

      const { data } = await axios.post('/user/two-factor-authentication')
      return data
    } catch (error: any) {
      handleAuthError(error)
      return null
    } finally {
      isLoading.value = false
    }
  }

  const confirm2FA = async (code: string): Promise<boolean> => {
    try {
      isLoading.value = true
      errors.value = {}

      await axios.post('/user/confirmed-two-factor-authentication', { code })
      if (user.value) {
        user.value.is_two_factor_enabled = true
      }
      return true
    } catch (error: any) {
      handleAuthError(error)
      return false
    } finally {
      isLoading.value = false
    }
  }

  const disable2FA = async (): Promise<boolean> => {
    try {
      isLoading.value = true
      errors.value = {}

      await axios.delete('/user/two-factor-authentication')
      if (user.value) {
        user.value.is_two_factor_enabled = false
      }
      return true
    } catch (error: any) {
      handleAuthError(error)
      return false
    } finally {
      isLoading.value = false
    }
  }

  // Manejo de errores
  const handleAuthError = (error: any) => {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    } else if (error.response?.status === 401) {
      // Token expirado o inválido
      logout()
    } else {
      errors.value = {
        general: [error.response?.data?.message || 'Error de conexión']
      }
    }
  }

  // Limpiar errores
  const clearErrors = () => {
    errors.value = {}
  }

  const clearAuthState = () => {
    twoFactorChallenge.value = false
    requiresEmailVerification.value = false
    clearErrors()
  }

  return {
    // Estado
    user,
    token,
    isLoading,
    errors,
    twoFactorChallenge,
    requiresEmailVerification,
    
    // Computadas
    isAuthenticated,
    isEmailVerified,
    hasTwoFactorEnabled,
    userFullName,
    
    // Acciones
    login,
    verify2FA,
    register,
    logout,
    forgotPassword,
    resetPassword,
    resendEmailVerification,
    fetchUser,
    updateProfile,
    changePassword,
    enable2FA,
    confirm2FA,
    disable2FA,
    clearErrors,
    clearAuthState,
  }
})