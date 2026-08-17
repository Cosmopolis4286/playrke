import { ref, watch, computed } from 'vue'
import type { JackpotType } from '@/js/types/jackpotType'

/**
 * Composable para manejar el formulario de JackpotType
 *
 * @param modelValue Valor inicial del formulario (create / edit)
 * @param emit Emit de Vue para sync con v-model
 */
export function useJackpotTypeForm(
    modelValue: Partial<JackpotType>,
    emit: (event: 'update:modelValue', value: Partial<JackpotType>) => void
) {
    const form = ref<Partial<JackpotType>>({
        game_id: undefined,
        currency_id: undefined,
        code: '',
        label: '',
        periodicity: 'PER_ROOM',
        status: 'ACTIVE'
    })

    const errors = ref<Partial<Record<keyof JackpotType, string>>>({})

    const isEditMode = computed(() => !!modelValue?.id)

    const isValid = computed(() => {
        return (
            !errors.value.game_id &&
            !errors.value.currency_id &&
            !errors.value.code &&
            !errors.value.label &&
            !!form.value.game_id &&
            !!form.value.currency_id &&
            !!form.value.code &&
            !!form.value.label
        )
    })

    const validate = () => {
        errors.value = {}

        if (!form.value.game_id) {
            errors.value.game_id = 'El juego es requerido.'
        }

        if (!form.value.currency_id) {
            errors.value.currency_id = 'La moneda es requerida.'
        }

        if (!form.value.code?.trim()) {
            errors.value.code = 'El código es requerido.'
        } else if (!/^[A-Za-z0-9_]{2,30}$/.test(form.value.code)) {
            errors.value.code = 'Debe tener 2-30 caracteres: letras, números y guion bajo.'
        }

        if (!form.value.label?.trim()) {
            errors.value.label = 'El nombre es requerido.'
        } else if (form.value.label.length < 2) {
            errors.value.label = 'Debe tener al menos 2 caracteres.'
        }
    }

    watch(
        () => modelValue,
        value => {
            form.value = {
                id: value.id,
                game_id: value.game_id,
                currency_id: value.currency_id,
                code: value.code ?? '',
                label: value.label ?? '',
                periodicity: value.periodicity ?? 'PER_ROOM',
                status: value.status ?? 'ACTIVE'
            }
        },
        { immediate: true, deep: true }
    )

    watch(
        form,
        value => {
            emit('update:modelValue', { ...value })
        },
        { deep: true }
    )

    watch(
        () => [form.value.game_id, form.value.currency_id, form.value.code, form.value.label],
        validate,
        { immediate: true }
    )

    return {
        form,
        errors,
        isEditMode,
        isValid,
        validate
    }
}
