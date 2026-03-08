import { ref, computed, readonly, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import backendClient from '@js/plugins/axios'
import type { DemoCredential, DemoCredentialsResponse } from '@js/types/demo-credentials'

export type { DemoCredential }

// Singleton state (shared across component instances)
const credentials = ref<DemoCredential[]>([])
const isLoading = ref(false)
const error = ref<string | null>(null)
let loadPromise: Promise<void> | null = null

export const useDemoCredentials = () => {
  const page = usePage<{ app?: { env?: string } }>()
  const isDevEnv = () => ['local', 'testing'].includes(page.props.app?.env ?? '')

  const isAvailable = computed(
    () => isDevEnv() && credentials.value.length > 0,
  )

  const loadCredentials = async (): Promise<void> => {
    if (!isDevEnv()) return
    if (credentials.value.length > 0) return
    if (loadPromise) return loadPromise

    isLoading.value = true
    error.value = null

    loadPromise = (async () => {
      try {
        const res = await backendClient.get<DemoCredentialsResponse>('/dev/demo-credentials')
        if (res.data?.success && res.data.data) {
          credentials.value = res.data.data
        }
      } catch (err: any) {
        error.value = err?.response?.data?.message ?? 'No se pudieron cargar las credenciales demo'
      } finally {
        isLoading.value = false
        loadPromise = null
      }
    })()

    return loadPromise
  }

  const refresh = async () => {
    credentials.value = []
    loadPromise = null
    await loadCredentials()
  }

  onMounted(() => {
    if (isDevEnv()) loadCredentials()
  })

  return {
    credentials: readonly(credentials),
    isLoading: readonly(isLoading),
    error: readonly(error),
    isAvailable,
    refresh,
  }
}

export default useDemoCredentials
