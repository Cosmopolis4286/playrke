import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

export const useAuthStore = defineStore('auth', () => {
  // State
  const user = ref(null)
  const isAuthenticated = ref(false)
  const isLoading = ref(false)
  const permissions = ref([])
  const roles = ref([])

  // Getters
  const userName = computed(() => user.value?.name || '')
  const userEmail = computed(() => user.value?.email || '')
  const userAvatar = computed(() => user.value?.avatar || '')
  const hasPermission = computed(() => (permission: string) => {
    return permissions.value.includes(permission)
  })
  const hasRole = computed(() => (role: string) => {
    return roles.value.includes(role)
  })

  // Actions
  const setUser = (userData: any) => {
    user.value = userData
    isAuthenticated.value = !!userData
    permissions.value = userData?.permissions || []
    roles.value = userData?.roles || []
  }

  const login = async (credentials: { email: string; password: string; remember?: boolean }) => {
    isLoading.value = true
    try {
      await router.post('/login', credentials)
    } catch (error) {
      console.error('Login error:', error)
      throw error
    } finally {
      isLoading.value = false
    }
  }

  const logout = async () => {
    isLoading.value = true
    try {
      await router.post('/logout')
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      user.value = null
      isAuthenticated.value = false
      permissions.value = []
      roles.value = []
      isLoading.value = false
    }
  }

  const updateProfile = async (profileData: any) => {
    isLoading.value = true
    try {
      await router.patch('/profile', profileData)
      // Update user data after successful profile update
      if (user.value) {
        user.value = { ...user.value, ...profileData }
      }
    } catch (error) {
      console.error('Profile update error:', error)
      throw error
    } finally {
      isLoading.value = false
    }
  }

  return {
    // State
    user,
    isAuthenticated,
    isLoading,
    permissions,
    roles,
    
    // Getters
    userName,
    userEmail,
    userAvatar,
    hasPermission,
    hasRole,
    
    // Actions
    setUser,
    login,
    logout,
    updateProfile
  }
})