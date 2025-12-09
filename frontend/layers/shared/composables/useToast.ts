export const useToast = () => {
  const nuxtApp = useNuxtApp()
  // preferenciable: nuxt injecto/prop $toast
  const toast = nuxtApp.$toast ?? nuxtApp._context?.provides?.toast

  const raw = (toast ?? { open: () => {} }) as any

  const success = (message: string, opts: Record<string, any> = {}) =>
    raw.open?.({ message, type: 'success', ...opts })
  const error = (message: string, opts: Record<string, any> = {}) =>
    raw.open?.({ message, type: 'error', ...opts })
  const info = (message: string, opts: Record<string, any> = {}) =>
    raw.open?.({ message, type: 'info', ...opts })

  return {
    raw,
    success,
    error,
    info
  }
}