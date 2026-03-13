import { ref, watch, computed } from 'vue'
import type { Currency } from '@/js/types/currency'

/**
 * Composable para manejar el formulario de Currency
 *
 * @param modelValue Valor inicial del formulario (create / edit)
 * @param emit Emit de Vue para sync con v-model
 */
export function useCurrencyForm(
    modelValue: Partial<Currency>,
    emit: (event: 'update:modelValue', value: Partial<Currency>) => void
) {
    /* =======================
     * State
     * ======================= */
    const form = ref<Partial<Currency>>({
        name: '',
        iso_code: '',
        symbol: null,
        description: null,
        type: 'FIAT',
        country_id: null,
        decimal_places: 2,
        status: 'ACTIVE'
    })

    const currencyId = ref(modelValue.id)

    const errors = ref<Partial<Record<keyof Currency, string>>>({})

    /* =======================
     * Computed
     * ======================= */

    const isEditMode = computed(() => !!modelValue?.id)

    const isValid = computed(() => {
        return (
            !errors.value.name &&
            !errors.value.iso_code &&
            !errors.value.type &&
            !errors.value.decimal_places &&
            !!form.value.name &&
            !!form.value.iso_code &&
            !!form.value.type &&
            form.value.decimal_places != null
        )
    })

    /* =======================
     * Methods
     * ======================= */

    const validate = () => {
        errors.value = {}

        if (!form.value.name?.trim()) {
            errors.value.name = 'El nombre es requerido.'
        } else if (form.value.name.length < 2) {
            errors.value.name = 'Debe tener al menos 2 caracteres.'
        }

        if (!form.value.iso_code?.trim()) {
            errors.value.iso_code = 'El código ISO es requerido.'
        } else if (!/^[A-Z0-9]{3,10}$/.test(form.value.iso_code)) {
            errors.value.iso_code = 'Debe tener 3-10 caracteres alfanuméricos en mayúsculas.'
        }

        if (!form.value.type) {
            errors.value.type = 'El tipo es requerido.'
        }

        if (form.value.decimal_places == null) {
            errors.value.decimal_places = 'Los decimales son requeridos.'
        }
    }

    const reset = () => {
        form.value = {
            name: '',
            iso_code: '',
            symbol: null,
            description: null,
            type: 'FIAT',
            country_id: null,
            decimal_places: 2,
            status: 'ACTIVE'
        }
        errors.value = {}
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
            currencyId.value = value.id
            form.value = {
                id: value.id,
                name: value.name ?? '',
                iso_code: value.iso_code ?? '',
                symbol: value.symbol ?? null,
                description: value.description ?? null,
                type: value.type ?? 'FIAT',
                country_id: value.country_id ?? null,
                decimal_places: value.decimal_places ?? 2,
                status: value.status ?? 'ACTIVE'
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
            emit('update:modelValue', { ...value })
        },
        { deep: true }
    )

    /**
     * Validación reactiva
     */
    watch(
        () => [form.value.name, form.value.iso_code, form.value.type, form.value.decimal_places],
        validate,
        { immediate: true }
    )

    return {
        form,
        errors,
        isEditMode,
        isValid,
        validate,
        reset
    }
}
