import { ref, computed, onMounted } from 'vue'
import backendClient, { getCsrfCookie } from '@js/plugins/axios'
import type { TosStatus } from '@js/types/tos'

export function useTos() {
  const status  = ref<TosStatus | null>(null)
  const loading = ref(false)
  const accepting = ref(false)
  const error   = ref<string | null>(null)

  const needsAcceptance = computed(() => status.value?.needs_acceptance === true)
  const currentVersion  = computed(() => status.value?.version ?? null)

  async function fetchStatus() {
    loading.value = true
    try {
      const { data } = await backendClient.get<TosStatus & { success: boolean }>('/tos/current')
      status.value = data
    } catch {
      // silencioso — si falla no bloqueamos UI
    } finally {
      loading.value = false
    }
  }

  async function accept(): Promise<boolean> {
    accepting.value = true
    error.value     = null
    try {
      await getCsrfCookie()
      await backendClient.post('/tos/accept')
      // actualizar estado local sin re-fetch
      if (status.value) {
        status.value = {
          ...status.value,
          needs_acceptance: false,
          accepted_version: status.value.version,
          accepted_at: new Date().toISOString(),
        }
      }
      return true
    } catch (err: any) {
      error.value = err?.response?.data?.message ?? 'Error al aceptar los términos.'
      return false
    } finally {
      accepting.value = false
    }
  }

  onMounted(fetchStatus)

  return { status, loading, accepting, error, needsAcceptance, currentVersion, fetchStatus, accept }
}
