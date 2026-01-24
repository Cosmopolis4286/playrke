import { ref, watch, computed } from 'vue'

/**
 * Modelo del formulario de tasa de cambio
 */
export interface ExchangeRateFormModel {
    id?: string
    currency?: string
    amount?: number
    status?: string
}

/**
 * Composable para manejar el formulario de Exchange Rate
 *
 * @param modelValue Valor inicial del formulario (create / edit)
 * @param emit Emit de Vue para sync con v-model
 */
export function useExchangeRateForm(
    modelValue: ExchangeRateFormModel,
    emit: (event: 'update:modelValue', value: ExchangeRateFormModel) => void
) {
    /* =======================
     * State
     * ======================= */
    const form = ref({
        currency: '',
        amount: null as number | null,
        status: false
    })

    const errors = ref<{ currency?: string; amount?: string }>({})

    /* =======================
     * Mask
     * ======================= */
    const maskOptions = {
        mask: Number,
        scale: 2,
        thousandsSeparator: '',
        padFractionalZeros: false,
        normalizeZeros: true,
        radix: '.',
        mapToRadix: [',']
    }

    /* =======================
     * Computed
     * ======================= */

    /**
     * Indica si el formulario está en modo edición
     */
    const isEditMode = computed(() => !!modelValue?.currency)

    /**
     * Estado de validez del formulario
     */
    const isValid = computed(
        () => !errors.value.currency && !errors.value.amount
    )

    /* =======================
     * Methods
     * ======================= */

    /**
     * Resetea el formulario
     */
    const reset = () => {
        form.value = {
            currency: '',
            amount: null,
            status: false
        }
        errors.value = {}
    }

    /**
     * Valida los campos del formulario
     */
    const validate = () => {
        errors.value = {}

        if (!form.value.currency) {
            errors.value.currency = 'La divisa es requerida.'
        }

        if (!form.value.amount || form.value.amount <= 0) {
            errors.value.amount = 'El cambio debe ser mayor a cero.'
        }
    }

    /* =======================
     * Watchers
     * ======================= */

    /**
     * Sync modelValue → form (edit / create)
     */
    watch(
        () => modelValue,
        value => {
            form.value = {
                currency: value.currency ?? '',
                amount: value.amount ?? null,
                status: value.status === 'ACTIVE'
            }
        },
        { immediate: true, deep: true }
    )

    /**
     * Sync form → modelValue
     */
    watch(
        form,
        value => {
            emit('update:modelValue', {
                id: '333',
                currency: value.currency,
                amount: value.amount ?? undefined,
                status: value.status ? 'ACTIVE' : 'INACTIVE'
            })
        },
        { deep: true }
    )

    /**
     * Validación reactiva
     */
    watch(
        () => [form.value.currency, form.value.amount],
        validate,
        { immediate: true }
    )

    return {
        form,
        errors,
        maskOptions,
        isEditMode,
        isValid,
        validate,
        reset
    }
}
