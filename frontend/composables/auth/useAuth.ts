import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth/authStore'
import { useRouter, useRoute } from 'vue-router'

export const useAuth = () => {
  const authStore = useAuthStore()
  const router = useRouter()
  const route = useRoute()

  // Estado reactivo del store
  const {
    user,
    isLoading,
    errors,
    isAuthenticated,
    isEmailVerified,
    hasTwoFactorEnabled,
    userFullName,
    twoFactorChallenge,
    requiresEmailVerification
  } = authStore

  // Funciones del store
  const {
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
    clearAuthState
  } = authStore

  // Utilidades adicionales
  const redirectToIntended = async (fallback = '/dashboard') => {
    const intended = route.query.redirect as string || fallback
    await router.push(intended)
  }

  const requireAuth = () => {
    if (!isAuthenticated.value) {
      router.push('/login')
      return false
    }
    return true
  }

  const requireEmailVerification = () => {
    if (!isEmailVerified.value) {
      router.push('/verify-email')
      return false
    }
    return true
  }

  const requireGuest = () => {
    if (isAuthenticated.value) {
      router.push('/dashboard')
      return false
    }
    return true
  }

  // Verificar permisos del usuario
  const hasRole = (role: string): boolean => {
    return user.value?.roles?.includes(role) ?? false
  }

  const hasPermission = (permission: string): boolean => {
    return user.value?.permissions?.includes(permission) ?? false
  }

  // Estados de usuario
  const isActive = computed(() => user.value?.status === 'ACTIVE')
  const isPendingVerification = computed(() => user.value?.status === 'PENDING_VERIFICATION')
  const isSuspended = computed(() => user.value?.status === 'SUSPENDED')

  // Helpers para formularios
  const getFieldError = (field: string): string => {
    return errors.value[field]?.[0] || ''
  }

  const hasFieldError = (field: string): boolean => {
    return !!(errors.value[field]?.length)
  }

  const hasAnyError = computed(() => {
    return Object.keys(errors.value).length > 0
  })

  // Avatar del usuario
  const getUserAvatar = computed(() => {
    if (user.value?.profile_photo_url) {
      return user.value.profile_photo_url
    }
    
    // Avatar por defecto con iniciales
    const initials = userFullName.value
      .split(' ')
      .map(name => name.charAt(0))
      .join('')
      .slice(0, 2)
      .toUpperCase()
    
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(initials)}&background=0ea5e9&color=ffffff&size=128`
  })

  // Formatear fecha de último login
  const lastLoginFormatted = computed(() => {
    if (!user.value?.last_login_at) return 'Nunca'
    
    const date = new Date(user.value.last_login_at)
    return new Intl.DateTimeFormat('es', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    }).format(date)
  })

  return {
    // Estado
    user,
    isLoading,
    errors,
    isAuthenticated,
    isEmailVerified,
    hasTwoFactorEnabled,
    userFullName,
    twoFactorChallenge,
    requiresEmailVerification,
    isActive,
    isPendingVerification,
    isSuspended,
    getUserAvatar,
    lastLoginFormatted,

    // Funciones de autenticación
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

    // Utilidades
    redirectToIntended,
    requireAuth,
    requireEmailVerification,
    requireGuest,
    hasRole,
    hasPermission,
    clearErrors,
    clearAuthState,

    // Helpers para formularios
    getFieldError,
    hasFieldError,
    hasAnyError
  }
}