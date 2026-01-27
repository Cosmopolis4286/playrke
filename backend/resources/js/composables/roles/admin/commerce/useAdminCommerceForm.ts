import { ref, watch, computed } from 'vue'
import type { ExchangeRate } from '@/js/types/exchange-rate'

/**
 * Composable para manejar el formulario de Comercios
 *
 * @param modelValue Valor inicial del formulario (create / edit)
 * @param emit Emit de Vue para sync con v-model
 */
export function useAdminCommerceForm(
    modelValue: Partial<ExchangeRate>,
    emit: (event: 'update:modelValue', value: ExchangeRate) => void
) {
    /* =======================
     * State
     * ======================= */
    const form = ref({
        currency_id: '',
        amount: null as number | null,
        status: false
    })

    const exchangeRateId = ref(modelValue.id)

    const errors = ref<{ currency_id?: string; amount?: string }>({})

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
    const isEditMode = computed(() => !!modelValue?.currency_id)

    /**
     * Estado de validez del formulario
     */
    const isValid = computed(
        () => !errors.value.currency_id && !errors.value.amount
    )

    /* =======================
     * Methods
     * ======================= */

    /**
     * Resetea el formulario
     */
    const reset = () => {
        form.value = {
            currency_id: '',
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

        if (!form.value.currency_id) {
            errors.value.currency_id = 'La divisa es requerida.'
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
                currency_id: value.currency_id.toString() ?? '',
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
                id: exchangeRateId.value,
                currency_id: value.currency_id,
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
        () => [form.value.currency_id, form.value.amount],
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
