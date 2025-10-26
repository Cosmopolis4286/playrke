/**
 * Store de Pinia para manejo de autenticación.
 *
 * Gestiona el estado global de autenticación, persistencia
 * de sesión y sincronización entre pestañas del navegador.
 */

import { defineStore } from 'pinia'
import type { User, AuthState } from '../types/auth'

export const useAuthStore = defineStore('auth', () => {
  // Estados reactivos
  const user = ref<User | null>(null)
  const token = ref<string | null>(null)
  const isLoading = ref(false)

  // Getters computados
  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const userRoles = computed(() => user.value?.roles || [])
  const userPermissions = computed(() => user.value?.permissions || [])

  /**
   * Establece la información de autenticación.
   *
   * @param authToken Token de autenticación
   * @param userData Datos del usuario
   */
  const setAuth = (authToken: string, userData: User): void => {
    token.value = authToken
    user.value = userData

    // Persistir en localStorage
    if (process.client) {
      localStorage.setItem('auth_token', authToken)
      localStorage.setItem('auth_user', JSON.stringify(userData))
    }
  }

  /**
   * Limpia toda la información de autenticación.
   */
  const clearAuth = (): void => {
    user.value = null
    token.value = null

    // Limpiar localStorage
    if (process.client) {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
    }
  }

  /**
   * Actualiza los datos del usuario.
   *
   * @param userData Nuevos datos del usuario
   */
  const updateUser = (userData: User): void => {
    user.value = userData

    // Actualizar localStorage
    if (process.client) {
      localStorage.setItem('auth_user', JSON.stringify(userData))
    }
  }

  /**
   * Obtiene la información de autenticación desde localStorage.
   *
   * @returns Información de autenticación almacenada
   */
  const getAuth = (): { token: string | null; user: User | null } => {
    if (!process.client) {
      return { token: null, user: null }
    }

    try {
      const storedToken = localStorage.getItem('auth_token')
      const storedUser = localStorage.getItem('auth_user')

      return {
        token: storedToken,
        user: storedUser ? JSON.parse(storedUser) : null
      }
    } catch (error) {
      console.error('Error obteniendo datos de auth desde localStorage:', error)
      return { token: null, user: null }
    }
  }

  /**
   * Verifica si el usuario tiene un rol específico.
   *
   * @param role Rol a verificar
   * @returns True si el usuario tiene el rol
   */
  const hasRole = (role: string): boolean => {
    return userRoles.value.includes(role)
  }

  /**
   * Verifica si el usuario tiene un permiso específico.
   *
   * @param permission Permiso a verificar
   * @returns True si el usuario tiene el permiso
   */
  const hasPermission = (permission: string): boolean => {
    return userPermissions.value.includes(permission)
  }

  /**
   * Verifica si el usuario tiene alguno de los roles especificados.
   *
   * @param roles Array de roles a verificar
   * @returns True si el usuario tiene al menos uno de los roles
   */
  const hasAnyRole = (roles: string[]): boolean => {
    return roles.some(role => hasRole(role))
  }

  /**
   * Verifica si el usuario tiene todos los roles especificados.
   *
   * @param roles Array de roles a verificar
   * @returns True si el usuario tiene todos los roles
   */
  const hasAllRoles = (roles: string[]): boolean => {
    return roles.every(role => hasRole(role))
  }

  /**
   * Obtiene el nivel de acceso del usuario (para admin).
   *
   * @returns Nivel de acceso o null si no es admin
   */
  const getAdminLevel = (): string | null => {
    if (hasRole('super_admin')) return 'super'
    if (hasRole('admin')) return 'admin'
    if (hasRole('moderator')) return 'moderator'
    if (hasRole('support')) return 'support'
    return null
  }

  /**
   * Verifica si el usuario es administrador.
   *
   * @returns True si es administrador
   */
  const isAdmin = (): boolean => {
    return hasAnyRole(['super_admin', 'admin', 'moderator', 'support'])
  }

  /**
   * Verifica si el usuario es jugador.
   *
   * @returns True si es jugador
   */
  const isPlayer = (): boolean => {
    return hasAnyRole(['player', 'vip_player'])
  }

  /**
   * Establece el estado de carga.
   *
   * @param loading Estado de carga
   */
  const setLoading = (loading: boolean): void => {
    isLoading.value = loading
  }

  /**
   * Obtiene el estado completo de autenticación.
   *
   * @returns Estado de autenticación
   */
  const getAuthState = (): AuthState => {
    return {
      user: user.value,
      token: token.value,
      isAuthenticated: isAuthenticated.value,
      isLoading: isLoading.value
    }
  }

  // Inicializar desde localStorage al crear el store
  if (process.client) {
    const storedAuth = getAuth()
    if (storedAuth.token && storedAuth.user) {
      token.value = storedAuth.token
      user.value = storedAuth.user
    }
  }

  return {
    // Estados (devolver refs directamente, no readonly)
    user,
    token,
    isLoading,

    // Getters
    isAuthenticated,
    userRoles,
    userPermissions,

    // Acciones
    setAuth,
    clearAuth,
    updateUser,
    getAuth,
    hasRole,
    hasPermission,
    hasAnyRole,
    hasAllRoles,
    getAdminLevel,
    isAdmin,
    isPlayer,
    setLoading,
    getAuthState
  }
})

// Exportar tipo del store para TypeScript
export type AuthStore = ReturnType<typeof useAuthStore>