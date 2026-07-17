import { computed, ref, type ComputedRef, type Ref } from 'vue'
import { router } from '@inertiajs/vue3'

interface FormRefLike {
  currenciesMissingRate?: string[]
  removeCurrenciesMissingRate?: () => void
  isValid?: boolean
}

/**
 * Modal de decisión cuando el formulario de sala tiene monedas seleccionadas
 * sin tasa de cambio activa — en vez de solo deshabilitar "Guardar", ofrece
 * quitar esas monedas o ir a configurar la tasa antes de continuar.
 */
export function useMissingRateModal(deps: {
  formRef: Ref<FormRefLike | null>
  allowedCurrenciesOptions: ComputedRef<{ value: string; label: string }[]>
  handleSubmit: () => void
}) {
  const { formRef, allowedCurrenciesOptions, handleSubmit } = deps

  const showMissingRateModal = ref(false)

  const currenciesMissingRateLabels = computed(() => {
    const missing = formRef.value?.currenciesMissingRate ?? []
    return missing
      .map(id => allowedCurrenciesOptions.value.find(opt => String(opt.value) === id)?.label ?? id)
      .join(', ')
  })

  /**
   * Intercepta el click de "Guardar": si la única razón de invalidez es que
   * hay monedas seleccionadas sin tasa de cambio activa, en vez de dejar el
   * botón simplemente deshabilitado se ofrece al usuario decidir cómo seguir
   * (quitar esas monedas o ir a configurar la tasa) antes de continuar.
   */
  const onSaveClick = () => {
    const missing = formRef.value?.currenciesMissingRate ?? []
    if (missing.length > 0) {
      showMissingRateModal.value = true
      return
    }
    handleSubmit()
  }

  const goToExchangeRates = () => {
    router.visit('/exchange-rate')
  }

  const continueWithoutMissingCurrencies = () => {
    formRef.value?.removeCurrenciesMissingRate?.()
    showMissingRateModal.value = false
  }

  /**
   * Habilita el botón "Guardar" cuando el formulario es válido, o cuando la
   * única razón de invalidez son monedas sin tasa activa — en ese caso el
   * click abre el modal de decisión (onSaveClick) en vez de enviar.
   */
  const canAttemptSave = computed(() => {
    if (formRef.value?.isValid) return true
    return (formRef.value?.currenciesMissingRate?.length ?? 0) > 0
  })

  return {
    showMissingRateModal, currenciesMissingRateLabels,
    onSaveClick, goToExchangeRates, continueWithoutMissingCurrencies, canAttemptSave,
  }
}
