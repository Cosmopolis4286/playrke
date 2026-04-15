import { ref, watch, computed } from 'vue'
import type { Game } from '@/js/types/game'

export function useGameForm(
    modelValue: Partial<Game>,
    emit: (event: 'update:modelValue', value: Partial<Game>) => void
) {
    const form = ref<Partial<Game>>({
        name: '',
        status: 'ACTIVE',
    })

    const errors = ref<Partial<Record<keyof Game, string>>>({})

    const isValid = computed(() =>
        !errors.value.name &&
        !!form.value.name?.trim() &&
        !!form.value.status
    )

    function validate() {
        errors.value = {}

        if (!form.value.name?.trim()) {
            errors.value.name = 'El nombre es requerido.'
        } else if (form.value.name.trim().length < 2) {
            errors.value.name = 'Debe tener al menos 2 caracteres.'
        } else if (form.value.name.trim().length > 50) {
            errors.value.name = 'Máximo 50 caracteres.'
        }

        if (!form.value.status) {
            errors.value.status = 'El estado es requerido.'
        }
    }

    // Sync modelValue → form
    watch(
        () => modelValue,
        value => {
            form.value = {
                id:     value.id,
                name:   value.name ?? '',
                status: value.status ?? 'ACTIVE',
            }
        },
        { immediate: true, deep: true }
    )

    // Sync form → parent
    watch(form, value => emit('update:modelValue', { ...value }), { deep: true })

    // Validación reactiva
    watch(() => [form.value.name, form.value.status], validate, { immediate: true })

    return { form, errors, isValid, validate }
}
