/**
 * Store de Pinia para manejo de autenticación.
 *
 * Gestiona el estado global de autenticación, persistencia
 * de sesión y sincronización entre pestañas del navegador.
 */

import { defineStore } from 'pinia'
import type { User } from '../types/auth'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(null)   // ← NECESARIO (no existía)
  const isLoading = ref(false)

  const isAuthenticated = computed(() => !!token.value && !!user.value)

  const userRoles = computed(() => user.value?.roles || [])
  const userPermissions = computed(() => user.value?.permissions || [])

  /**
   * Guarda la sesión del usuario
   */
  const setAuth = (tokenValue: string, userData: User): void => {
    token.value = tokenValue
    user.value = userData

    if (process.client) {
      localStorage.setItem('auth_user', JSON.stringify(userData))
      localStorage.setItem('auth_token', tokenValue)
    }
  }

  /**
   * Limpia toda la sesión
   */
  const clearAuth = (): void => {
    token.value = null
    user.value = null

    if (process.client) {
      localStorage.removeItem('auth_user')
      localStorage.removeItem('auth_token')
    }
  }

  /**
   * Actualiza el usuario
   */
  const updateUser = (userData: User): void => {
    user.value = userData

    if (process.client) {
      localStorage.setItem('auth_user', JSON.stringify(userData))
    }
  }

  /**
   * Carga sesión almacenada
   */
  const getAuth = () => {
    if (!process.client) return { user: null, token: null }

    try {
      const storedUser = localStorage.getItem('auth_user')
      const storedToken = localStorage.getItem('auth_token')

      return {
        user: storedUser ? JSON.parse(storedUser) : null,
        token: storedToken ?? null
      }
    } catch {
      return { user: null, token: null }
    }
  }

  // Helpers
  const hasRole = (role: string) => userRoles.value.includes(role)
  const hasPermission = (permission: string) =>
    userPermissions.value.includes(permission)

  const hasAnyRole = (roles: string[]) => roles.some(hasRole)
  const hasAllRoles = (roles: string[]) => roles.every(hasRole)

  const isAdmin = () =>
    hasAnyRole(['super_admin', 'admin', 'moderator', 'support'])

  const isPlayer = () => hasAnyRole(['player', 'vip_player'])

  /**
   * Getter auxiliar para niveles
   * (si tu User tiene "admin_level" o algo similar)
   */
  const getAdminLevel = () => {
    return (user.value as any)?.admin_level ?? 0
  }

  const setLoading = (loading: boolean) => {
    isLoading.value = loading
  }

  // Inicializar desde localStorage
  if (process.client) {
    const stored = getAuth()
    if (stored.user) user.value = stored.user
    if (stored.token) token.value = stored.token
  }

  return {
    // State
    user,
    token,           // ← NECESARIO
    isLoading,

    // Getters
    isAuthenticated,
    userRoles,
    userPermissions,

    // Actions
    setAuth,
    clearAuth,
    updateUser,
    getAuth,
    hasRole,
    hasPermission,
    hasAnyRole,
    hasAllRoles,
    isAdmin,
    isPlayer,
    getAdminLevel,   // ← NECESARIO
    setLoading
  }
})
