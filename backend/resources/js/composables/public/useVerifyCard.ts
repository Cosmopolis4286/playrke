import { reactive, ref } from 'vue'
import backendClient from '@js/plugins/axios'
import { useValidatedForm } from '@js/composables/form/useValidatedForm'
import { verifyCardSchema } from './verifyCardSchema'

export interface CardPrize {
  type_label: string
  status_label: string
  amount_usd: number
}

export interface CardResult {
  serial_number: string
  card_numbers: Record<string, (number | null)[]>
  status: number
  status_label: string
  room: { name: string | null; date: string | null; time: string | null }
  prize: CardPrize | null
}

/**
 * POST /api/bingo/verify-card (BingoCardVerifyController::verify) — el
 * backend responde 422 si el formato es inválido, 403 si el serial y el
 * teléfono no coinciden (AuthorizationException en BingoCardVerificationService),
 * y 429 por throttle:10,1.
 */
export function useVerifyCard() {
  const form = reactive({ serial_number: '', phone_last4: '' })
  const result = ref<CardResult | null>(null)

  const { loading, isBusy, touched, submitted, markTouched, fieldError, validate, reportHttpError, toast } =
    useValidatedForm(form, verifyCardSchema)

  async function submit(turnstileToken?: string): Promise<void> {
    if (isBusy.value) return
    submitted.value = true
    result.value = null

    if (!validate()) {
      toast.warning('Revisa los campos marcados antes de continuar.')
      return
    }

    loading.value = true
    try {
      const { data } = await backendClient.post('/api/bingo/verify-card', {
        serial_number: form.serial_number.trim(),
        phone_last4: form.phone_last4.trim(),
        'cf-turnstile-response': turnstileToken,
      })
      result.value = data.card
    } catch (error: any) {
      reportHttpError(error, {
        forbidden: 'El serial y el teléfono no coinciden con ningún cartón.',
        validation: 'Revisa el serial y el teléfono ingresados.',
      })
    } finally {
      loading.value = false
    }
  }

  return { form, result, loading, isBusy, touched, submitted, markTouched, fieldError, submit }
}
