/**
 * Plugin de notificaciones Toast (cliente)
 * - Usa vue-toast-notification si está disponible
 * - Fallback: usa el store UI (uiStore.notify)
 */

import 'vue-toast-notification/dist/theme-default.css'
import VueToast from 'vue-toast-notification'
import { useUIStore } from '../stores/ui'

export default defineNuxtPlugin((nuxtApp) => {
  const uiStore = useUIStore()

  // Registrar vue-toast (cliente)
  nuxtApp.vueApp.use(VueToast, {
    duration: 5000,
    position: 'top-right'
  })

  // Obtener la API del plugin (globalProperties.$toast)
  const nativeToast = (nuxtApp.vueApp.config.globalProperties as any).$toast

  const wrapper = {
    success(message: string, title?: string, opts: Record<string, any> = {}) {
      // Preferir native toast si existe
      if (nativeToast) {
        if (typeof nativeToast.success === 'function') {
          nativeToast.success(message, { ...opts })
        } else {
          nativeToast.open({ message: title ? `${title}: ${message}` : message, type: 'success', ...opts })
        }
        return
      }
      // Fallback al uiStore
      uiStore.notify({ type: 'success', title: title ?? 'Éxito', message, duration: opts.duration })
    },

    error(message: string, title?: string, opts: Record<string, any> = {}) {
      if (nativeToast) {
        if (typeof nativeToast.error === 'function') {
          nativeToast.error(message, { ...opts })
        } else {
          nativeToast.open({ message: title ? `${title}: ${message}` : message, type: 'error', ...opts })
        }
        return
      }
      uiStore.notify({ type: 'error', title: title ?? 'Error', message, duration: opts.duration })
    },

    info(message: string, title?: string, opts: Record<string, any> = {}) {
      if (nativeToast) {
        if (typeof nativeToast.info === 'function') {
          nativeToast.info(message, { ...opts })
        } else {
          nativeToast.open({ message: title ? `${title}: ${message}` : message, type: 'info', ...opts })
        }
        return
      }
      uiStore.notify({ type: 'info', title: title ?? 'Información', message, duration: opts.duration })
    },

    warning(message: string, title?: string, opts: Record<string, any> = {}) {
      if (nativeToast) {
        if (typeof nativeToast.warning === 'function') {
          nativeToast.warning(message, { ...opts })
        } else {
          nativeToast.open({ message: title ? `${title}: ${message}` : message, type: 'warning', ...opts })
        }
        return
      }
      uiStore.notify({ type: 'warning', title: title ?? 'Advertencia', message, duration: opts.duration })
    },

    custom(options: {
      type?: 'success' | 'error' | 'warning' | 'info'
      title?: string
      message: string
      duration?: number
      [key: string]: any
    }) {
      const { type = 'info', title, message, duration, ...rest } = options
      if (nativeToast) {
        nativeToast.open({ message: title ? `${title}: ${message}` : message, type, duration, ...rest })
        return
      }
      uiStore.notify({ type, title, message, duration })
    }
  }

  // Inyectar y exponer como $toast
  nuxtApp.provide('toast', wrapper)
  ;(nuxtApp.vueApp.config.globalProperties as any).$toast = wrapper

  return {
    provide: {
      toast: wrapper
    }
  }
})