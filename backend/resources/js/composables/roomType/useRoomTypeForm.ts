import { ref, watch, computed } from 'vue'
import type { RoomType } from '@/js/types/roomType'

/**
 * Composable para manejar el formulario de RoomType
 *
 * @param modelValue Valor inicial del formulario (create / edit)
 * @param emit Emit de Vue para sync con v-model
 */
export function useRoomTypeForm(
    modelValue: Partial<RoomType>,
    emit: (event: 'update:modelValue', value: Partial<RoomType>) => void
) {
    const form = ref<Partial<RoomType>>({
        code: '',
        label: '',
        status: 'ACTIVE'
    })

    const errors = ref<Partial<Record<keyof RoomType, string>>>({})

    const isEditMode = computed(() => !!modelValue?.id)

    const isValid = computed(() => {
        return (
            !errors.value.code &&
            !errors.value.label &&
            !!form.value.code &&
            !!form.value.label
        )
    })

    const validate = () => {
        errors.value = {}

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
                code: value.code ?? '',
                label: value.label ?? '',
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
        () => [form.value.code, form.value.label],
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
