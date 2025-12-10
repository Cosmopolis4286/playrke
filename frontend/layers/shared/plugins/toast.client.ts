/**
 * Plugin global de Toast usando Nuxt UI
 * - Reemplaza vue-toast-notification por useToast() de @nuxt/ui
 * - API compatible con el wrapper previo ($toast)
 */

import { useUIStore } from '../stores/ui'

export default defineNuxtPlugin((nuxtApp) => {
  const uiStore = useUIStore()

  // Instancia del Toast nativo de Nuxt UI
  const uiToast = useToast()

  const getColor = (type: string) =>
    type === 'success'
      ? 'green'
      : type === 'error'
      ? 'red'
      : type === 'warning'
      ? 'yellow'
      : 'blue'

  const send = (
    type: 'success' | 'error' | 'info' | 'warning',
    message: string,
    title?: string,
    opts: Record<string, any> = {}
  ) => {
    if (uiToast) {
      uiToast.add({
        title: title ?? message,
        description: title ? message : undefined,
        color: getColor(type),
        timeout: opts.duration ?? 5000,
        ...opts
      })
      return
    }

    uiStore.notify({
      type,
      title: title ?? type.toUpperCase(),
      message,
      duration: opts.duration
    })
  }

  const wrapper = {
    success: (msg: string, title?: string, opts = {}) =>
      send('success', msg, title, opts),

    error: (msg: string, title?: string, opts = {}) =>
      send('error', msg, title, opts),

    info: (msg: string, title?: string, opts = {}) =>
      send('info', msg, title, opts),

    warning: (msg: string, title?: string, opts = {}) =>
      send('warning', msg, title, opts),

    custom(options: {
      type?: 'success' | 'error' | 'warning' | 'info'
      title?: string
      message: string
      duration?: number
      [key: string]: any
    }) {
      const { type = 'info', title, message, duration, ...rest } = options

      if (uiToast) {
        uiToast.add({
          title: title ?? message,
          description: title ? message : undefined,
          color: getColor(type),
          timeout: duration ?? 5000,
          ...rest
        })
        return
      }

      uiStore.notify({
        type,
        title,
        message,
        duration
      })
    }
  }

  // Inyectar globalmente
  nuxtApp.provide('toast', wrapper)
  ;(nuxtApp.vueApp.config.globalProperties as any).$toast = wrapper

  return {
    provide: { toast: wrapper }
  }
})
