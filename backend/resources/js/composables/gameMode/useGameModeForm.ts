import { ref, watch, computed } from 'vue'
import type { GameMode } from '@/js/types/gameMode'

/**
 * Composable para manejar el formulario de GameMode
 *
 * @param modelValue Valor inicial del formulario (create / edit)
 * @param emit Emit de Vue para sync con v-model
 */
export function useGameModeForm(
    modelValue: Partial<GameMode>,
    emit: (event: 'update:modelValue', value: Partial<GameMode>) => void
) {
    /* =======================
     * State
     * ======================= */
    const form = ref<Partial<GameMode>>({
        game_id: undefined,
        code: '',
        label: '',
        status: 'ACTIVE'
    })

    const errors = ref<Partial<Record<keyof GameMode, string>>>({})

    /* =======================
     * Computed
     * ======================= */

    const isEditMode = computed(() => !!modelValue?.id)

    const isValid = computed(() => {
        return (
            !errors.value.game_id &&
            !errors.value.code &&
            !errors.value.label &&
            !!form.value.game_id &&
            !!form.value.code &&
            !!form.value.label
        )
    })

    /* =======================
     * Methods
     * ======================= */

    const validate = () => {
        errors.value = {}

        if (!form.value.game_id) {
            errors.value.game_id = 'El juego es requerido.'
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
                id: value.id,
                game_id: value.game_id,
                code: value.code ?? '',
                label: value.label ?? '',
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
        () => [form.value.game_id, form.value.code, form.value.label],
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
