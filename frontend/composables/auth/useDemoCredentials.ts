import { ref, computed } from 'vue'
import { useApi } from '../useApi'

export interface DemoCredential {
  id: string
  email: string
  password: string
  firstName: string
  lastName: string
  role: string
  description: string
}

export function useDemoCredentials() {
  const credentials = ref<DemoCredential[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  const api = useApi()

  const adminCredentials = computed(() =>
    credentials.value.filter(c => c.role === 'admin')
  )

  const playerCredentials = computed(() =>
    credentials.value.filter(c => c.role === 'player')
  )

  const commerceCredentials = computed(() => [] as DemoCredential[])
  const customerCredentials = computed(() => [] as DemoCredential[])

  const totalCredentials = computed(() => credentials.value.length)
  const isAvailable = computed(() => {
    const isDev = import.meta.env.DEV || process.env.NODE_ENV === 'development'
    return isDev && credentials.value.length > 0
  })

  async function refresh() {
    isLoading.value = true
    error.value = null

    try {
      const response = await api.get('/demo-credentials')

      // 👇 Acceso correcto a los datos
      credentials.value = response.data ?? []
    } catch (err: any) {
      error.value = err.message || 'Error desconocido'
    } finally {
      isLoading.value = false
    }
  }

  // No ejecutar refresh() automáticamente - debe ser llamado manualmente
  // refresh()

  return {
    credentials,
    isLoading: computed(() => isLoading.value),
    error: computed(() => error.value),
    adminCredentials,
    commerceCredentials,
    customerCredentials,
    playerCredentials,
    totalCredentials,
    isAvailable,
    refresh,
  }
}
