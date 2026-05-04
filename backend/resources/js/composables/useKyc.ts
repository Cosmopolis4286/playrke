import { ref, computed } from 'vue'
import backendClient, { getCsrfCookie } from '@js/plugins/axios'
import type { KycState, WithdrawalLimitUsage } from '@js/types/kyc'

export function useKyc() {
  const state   = ref<KycState | null>(null)
  const limits  = ref<WithdrawalLimitUsage | null>(null)
  const loading = ref(false)
  const uploading = ref(false)
  const error   = ref<string | null>(null)
  const success = ref<string | null>(null)

  const isVerified  = computed(() => state.value?.kyc_status === 'VERIFIED')
  const isPending   = computed(() => state.value?.kyc_status === 'PENDING')
  const isRejected  = computed(() => state.value?.kyc_status === 'REJECTED')
  const kycLevel    = computed(() => state.value?.kyc_level ?? 0)

  async function fetchStatus() {
    loading.value = true
    try {
      const { data } = await backendClient.get<{ success: boolean; data: KycState }>('/kyc/status')
      if (data.success) state.value = data.data
    } catch {
      // silencioso
    } finally {
      loading.value = false
    }
  }

  async function fetchLimits(currencyId: string) {
    try {
      const { data } = await backendClient.get<{ success: boolean; data: WithdrawalLimitUsage }>(
        '/wallet/limits', { params: { currency_id: currencyId } }
      )
      if (data.success) limits.value = data.data
    } catch {
      // silencioso
    }
  }

  async function submitDocument(form: {
    document_type: string
    document_number: string
    front: File
    back?: File | null
    selfie?: File | null
  }): Promise<boolean> {
    uploading.value = true
    error.value     = null
    success.value   = null

    const fd = new FormData()
    fd.append('document_type',   form.document_type)
    fd.append('document_number', form.document_number)
    fd.append('front', form.front)
    if (form.back)   fd.append('back',   form.back)
    if (form.selfie) fd.append('selfie', form.selfie)

    try {
      await getCsrfCookie()
      const { data } = await backendClient.post<{ success: boolean; message: string }>('/kyc/submit', fd)
      if (data.success) {
        success.value = data.message
        await fetchStatus()
        return true
      }
      error.value = data.message
      return false
    } catch (err: any) {
      const errs = err?.response?.data?.errors
      error.value = errs
        ? Object.values(errs).flat().join(' ')
        : (err?.response?.data?.message ?? 'Error al enviar el documento.')
      return false
    } finally {
      uploading.value = false
    }
  }

  return {
    state, limits, loading, uploading, error, success,
    isVerified, isPending, isRejected, kycLevel,
    fetchStatus, fetchLimits, submitDocument,
  }
}
