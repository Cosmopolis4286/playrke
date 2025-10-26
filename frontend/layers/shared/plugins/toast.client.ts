/**
 * Plugin de notificaciones Toast
 * Proporciona sistema de notificaciones visuales
 */

import { useUIStore } from '../stores/ui'

export default defineNuxtPlugin(() => {
  const uiStore = useUIStore()

  const toast = {
    success(message: string, title = 'Éxito') {
      uiStore.notify({
        type: 'success',
        title,
        message
      })
    },

    error(message: string, title = 'Error') {
      uiStore.notify({
        type: 'error',
        title,
        message
      })
    },

    warning(message: string, title = 'Advertencia') {
      uiStore.notify({
        type: 'warning',
        title,
        message
      })
    },

    info(message: string, title = 'Información') {
      uiStore.notify({
        type: 'info',
        title,
        message
      })
    },

    custom(options: {
      type?: 'success' | 'error' | 'warning' | 'info'
      title?: string
      message: string
      duration?: number
    }) {
      uiStore.notify({
        type: options.type || 'info',
        title: options.title,
        message: options.message,
        duration: options.duration
      })
    }
  }

  return {
    provide: {
      toast
    }
  }
})
