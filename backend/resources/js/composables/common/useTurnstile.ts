import { ref, onBeforeUnmount } from 'vue'

/**
 * Carga el script de Cloudflare Turnstile una sola vez (compartido entre
 * cualquier número de composables/páginas montadas a la vez) y expone un
 * helper para renderizar el widget en un contenedor dado.
 *
 * Backend: App\Rules\CloudflareTurnstile verifica el token vía siteverify.
 * Sin VITE_TURNSTILE_SITE_KEY (dev/local sin cuenta Cloudflare) el widget
 * simplemente no se renderiza — el backend también falla abierto en ese caso.
 */

declare global {
  interface Window {
    turnstile?: {
      render: (container: string | HTMLElement, options: Record<string, unknown>) => string
      reset: (widgetId?: string) => void
      remove: (widgetId?: string) => void
    }
    onTurnstileLoad?: () => void
  }
}

const SCRIPT_URL = 'https://challenges.cloudflare.com/turnstile/v0/api.js?onload=onTurnstileLoad&render=explicit'
let scriptLoadingPromise: Promise<void> | null = null

function loadTurnstileScript(): Promise<void> {
  if (window.turnstile) return Promise.resolve()

  if (!scriptLoadingPromise) {
    scriptLoadingPromise = new Promise((resolve) => {
      window.onTurnstileLoad = () => resolve()
      const script = document.createElement('script')
      script.src = SCRIPT_URL
      script.async = true
      script.defer = true
      document.head.appendChild(script)
    })
  }

  return scriptLoadingPromise
}

export function useTurnstile() {
  const siteKey = import.meta.env.VITE_TURNSTILE_SITE_KEY as string | undefined
  const token = ref<string>('')
  let widgetId: string | undefined

  async function render(container: HTMLElement): Promise<void> {
    if (!siteKey) return

    await loadTurnstileScript()

    widgetId = window.turnstile?.render(container, {
      sitekey: siteKey,
      callback: (t: string) => { token.value = t },
      'expired-callback': () => { token.value = '' },
      'error-callback': () => { token.value = '' },
    })
  }

  function reset(): void {
    token.value = ''
    if (widgetId) window.turnstile?.reset(widgetId)
  }

  onBeforeUnmount(() => {
    if (widgetId) window.turnstile?.remove(widgetId)
  })

  return { siteKey, token, render, reset }
}
