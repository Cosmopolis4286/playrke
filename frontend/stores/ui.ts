import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useUIStore = defineStore('ui', () => {
  // State
  const sidebarOpen = ref(true)
  const theme = ref('light')
  const notifications = ref([])
  const loading = ref(false)
  const loadingText = ref('')
  
  // Mobile detection
  const isMobile = ref(false)
  
  // Modals
  const modals = ref(new Set())

  // Getters
  const isDark = computed(() => theme.value === 'dark')
  const unreadNotifications = computed(() => 
    notifications.value.filter((n: any) => !n.read)
  )
  const hasOpenModal = computed(() => modals.value.size > 0)

  // Actions
  const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value
  }

  const setSidebarOpen = (open: boolean) => {
    sidebarOpen.value = open
  }

  const toggleTheme = () => {
    theme.value = theme.value === 'light' ? 'dark' : 'light'
    applyTheme()
    localStorage.setItem('theme', theme.value)
  }

  const setTheme = (newTheme: 'light' | 'dark') => {
    theme.value = newTheme
    applyTheme()
    localStorage.setItem('theme', newTheme)
  }

  const applyTheme = () => {
    document.documentElement.setAttribute('data-theme', theme.value)
    if (theme.value === 'dark') {
      document.documentElement.classList.add('dark')
    } else {
      document.documentElement.classList.remove('dark')
    }
  }

  const addNotification = (notification: any) => {
    const id = Math.random().toString(36).substr(2, 9)
    const newNotification = {
      id,
      read: false,
      timestamp: new Date(),
      ...notification
    }
    notifications.value.unshift(newNotification)
    
    // Auto-remove after 5 seconds if it's a temporary notification
    if (notification.temporary !== false) {
      setTimeout(() => {
        removeNotification(id)
      }, 5000)
    }
    
    return id
  }

  const removeNotification = (id: string) => {
    const index = notifications.value.findIndex((n: any) => n.id === id)
    if (index > -1) {
      notifications.value.splice(index, 1)
    }
  }

  const markNotificationAsRead = (id: string) => {
    const notification = notifications.value.find((n: any) => n.id === id)
    if (notification) {
      notification.read = true
    }
  }

  const clearAllNotifications = () => {
    notifications.value = []
  }

  const setLoading = (isLoading: boolean, text = '') => {
    loading.value = isLoading
    loadingText.value = text
  }

  const setMobile = (mobile: boolean) => {
    isMobile.value = mobile
    if (mobile && sidebarOpen.value) {
      sidebarOpen.value = false
    }
  }

  const openModal = (modalId: string) => {
    modals.value.add(modalId)
    // Disable body scroll when modal is open
    if (modals.value.size === 1) {
      document.body.style.overflow = 'hidden'
    }
  }

  const closeModal = (modalId: string) => {
    modals.value.delete(modalId)
    // Re-enable body scroll when no modals are open
    if (modals.value.size === 0) {
      document.body.style.overflow = ''
    }
  }

  const closeAllModals = () => {
    modals.value.clear()
    document.body.style.overflow = ''
  }

  // Initialize theme on store creation
  const initializeTheme = () => {
    const savedTheme = localStorage.getItem('theme')
    if (savedTheme && ['light', 'dark'].includes(savedTheme)) {
      theme.value = savedTheme
    } else {
      // Detect system preference
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
      theme.value = prefersDark ? 'dark' : 'light'
    }
    applyTheme()
  }

  // Toast notifications (using the notification system)
  const toast = {
    success: (message: string, title?: string) => 
      addNotification({ type: 'success', message, title }),
    
    error: (message: string, title?: string) => 
      addNotification({ type: 'error', message, title }),
    
    warning: (message: string, title?: string) => 
      addNotification({ type: 'warning', message, title }),
    
    info: (message: string, title?: string) => 
      addNotification({ type: 'info', message, title })
  }

  return {
    // State
    sidebarOpen,
    theme,
    notifications,
    loading,
    loadingText,
    isMobile,
    modals,
    
    // Getters
    isDark,
    unreadNotifications,
    hasOpenModal,
    
    // Actions
    toggleSidebar,
    setSidebarOpen,
    toggleTheme,
    setTheme,
    addNotification,
    removeNotification,
    markNotificationAsRead,
    clearAllNotifications,
    setLoading,
    setMobile,
    openModal,
    closeModal,
    closeAllModals,
    initializeTheme,
    toast
  }
})