import { useUIStore } from '../stores/ui'

export default defineNuxtPlugin((nuxtApp) => {
  const uiStore = useUIStore()
  const { add: addToast } = useToast()

  type ToastType = 'success' | 'error' | 'info' | 'warning'

  interface ToastOptions {
    duration?: number
    [key: string]: any
  }

  const getColor = (type: ToastType): string =>
    ({ success: 'green', error: 'red', warning: 'yellow', info: 'blue' }[type])

  const send = (
    type: ToastType,
    message: string,
    title?: string,
    opts: ToastOptions = {}
  ) => {
    if (addToast) {
      addToast({
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
    success: (msg: string, title?: string, opts: ToastOptions = {}) =>
      send('success', msg, title, opts),

    error: (msg: string, title?: string, opts: ToastOptions = {}) =>
      send('error', msg, title, opts),

    info: (msg: string, title?: string, opts: ToastOptions = {}) =>
      send('info', msg, title, opts),

    warning: (msg: string, title?: string, opts: ToastOptions = {}) =>
      send('warning', msg, title, opts),

    custom: (options: {
      type?: ToastType
      title?: string
      message: string
      duration?: number
      [key: string]: any
    }) => {
      const { type = 'info', title, message, duration, ...rest } = options
      send(type, message, title, { duration, ...rest })
    }
  }

  return { provide: { toast: wrapper } }
})
