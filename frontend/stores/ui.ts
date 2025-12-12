/**
 * Store de UI usando Pinia
 *
 * Gestiona el estado de la interfaz de usuario:
 * - Modales
 * - Notificaciones
 * - Loading states
 * - Sidebar
 */

import { defineStore } from 'pinia'

export interface Notification {
  id: string
  type: 'success' | 'error' | 'warning' | 'info'
  title: string
  message: string
  duration?: number
  timestamp: Date
}

export interface Modal {
  id: string
  component?: string
  props?: Record<string, any>
  isOpen: boolean
}

export interface UIState {
  notifications: Notification[]
  modals: Modal[]
  isLoading: boolean
  loadingMessage: string
  isSidebarOpen: boolean
  theme: 'light' | 'dark' | 'auto'
  layout: 'admin' | 'player' | 'default'
}

export const useUIStore = defineStore('ui', {
  state: (): UIState => ({
    notifications: [],
    modals: [],
    isLoading: false,
    loadingMessage: '',
    isSidebarOpen: true,
    theme: 'dark',
    layout: 'default'
  }),

  getters: {
    /**
     * Obtiene las notificaciones activas
     */
    activeNotifications: (state) => {
      return state.notifications.filter((n, index) => index < 5)
    },

    /**
     * Obtiene el modal actual (el último abierto)
     */
    currentModal: (state) => {
      return state.modals.find(m => m.isOpen)
    },

    /**
     * Verifica si hay algún modal abierto
     */
    hasOpenModal: (state) => {
      return state.modals.some(m => m.isOpen)
    }
  },

  actions: {
    /**
     * Muestra una notificación
     */
    notify(notification: Omit<Notification, 'id' | 'timestamp'>) {
      const id = `notification-${Date.now()}-${Math.random()}`
      const newNotification: Notification = {
        id,
        ...notification,
        timestamp: new Date(),
        duration: notification.duration || 5000
      }

      this.notifications.push(newNotification)

      // Auto-remover después de la duración
      if (newNotification.duration > 0) {
        setTimeout(() => {
          this.removeNotification(id)
        }, newNotification.duration)
      }
    },

    /**
     * Muestra una notificación de éxito
     */
    notifySuccess(title: string, message: string, duration?: number) {
      this.notify({ type: 'success', title, message, duration })
    },

    /**
     * Muestra una notificación de error
     */
    notifyError(title: string, message: string, duration?: number) {
      this.notify({ type: 'error', title, message, duration })
    },

    /**
     * Muestra una notificación de advertencia
     */
    notifyWarning(title: string, message: string, duration?: number) {
      this.notify({ type: 'warning', title, message, duration })
    },

    /**
     * Muestra una notificación informativa
     */
    notifyInfo(title: string, message: string, duration?: number) {
      this.notify({ type: 'info', title, message, duration })
    },

    /**
     * Remueve una notificación
     */
    removeNotification(id: string) {
      this.notifications = this.notifications.filter(n => n.id !== id)
    },

    /**
     * Limpia todas las notificaciones
     */
    clearNotifications() {
      this.notifications = []
    },

    /**
     * Abre un modal
     */
    openModal(modal: Omit<Modal, 'id' | 'isOpen'>) {
      const id = `modal-${Date.now()}-${Math.random()}`
      const newModal: Modal = {
        id,
        ...modal,
        isOpen: true
      }

      this.modals.push(newModal)
    },

    /**
     * Cierra un modal
     */
    closeModal(id: string) {
      const modal = this.modals.find(m => m.id === id)
      if (modal) {
        modal.isOpen = false
      }

      // Remover después de un delay para animación
      setTimeout(() => {
        this.modals = this.modals.filter(m => m.id !== id)
      }, 300)
    },

    /**
     * Cierra el modal actual
     */
    closeCurrentModal() {
      const current = this.currentModal
      if (current) {
        this.closeModal(current.id)
      }
    },

    /**
     * Cierra todos los modales
     */
    closeAllModals() {
      this.modals.forEach(modal => {
        modal.isOpen = false
      })

      setTimeout(() => {
        this.modals = []
      }, 300)
    },

    /**
     * Muestra el loading global
     */
    showLoading(message: string = 'Cargando...') {
      this.isLoading = true
      this.loadingMessage = message
    },

    /**
     * Oculta el loading global
     */
    hideLoading() {
      this.isLoading = false
      this.loadingMessage = ''
    },

    /**
     * Toggle del sidebar
     */
    toggleSidebar() {
      this.isSidebarOpen = !this.isSidebarOpen
    },

    /**
     * Abre el sidebar
     */
    openSidebar() {
      this.isSidebarOpen = true
    },

    /**
     * Cierra el sidebar
     */
    closeSidebar() {
      this.isSidebarOpen = false
    },

    /**
     * Cambia el tema
     */
    setTheme(theme: UIState['theme']) {
      this.theme = theme

      // Aplicar al DOM
      if (process.client) {
        if (theme === 'auto') {
          const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
          document.documentElement.classList.toggle('dark', prefersDark)
        } else {
          document.documentElement.classList.toggle('dark', theme === 'dark')
        }
      }
    },

    /**
     * Establece el layout actual
     */
    setLayout(layout: UIState['layout']) {
      this.layout = layout
    }
  }

  // TODO: Configurar persistencia con @pinia-plugin-persistedstate cuando se instale
  // persist: {
  //   storage: localStorage,
  //   paths: ['theme', 'isSidebarOpen']
  // }
})

export type UIStore = ReturnType<typeof useUIStore>
