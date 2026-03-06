import { computed } from 'vue'
import type { Player } from '@/js/types/roles/player/player'

/**
 * Composable para formulario de Players.
 *
 * - v-model computado
 * - Validación reactiva
 * - Expone estado de edición
 */
export function useAdminPlayerForm(
    modelValueGetter: () => Partial<Player>,
    emit: (event: 'update:modelValue', value: Partial<Player>) => void
) {
    const form = computed<Partial<Player>>({
        get: () => modelValueGetter(),
        set: value => emit('update:modelValue', value)
    })

    const isEditMode = computed(() => !!form.value.id)

    const errors = computed<Record<string, string>>(() => {
        const e: Record<string, string> = {}

        if (!form.value.first_name?.trim()) {
            e.first_name = 'El nombre es requerido.'
        }

        if (!form.value.last_name?.trim()) {
            e.last_name = 'El apellido es requerido.'
        }

        if (!form.value.username || form.value.username.length < 5) {
            e.username = 'El username debe tener al menos 5 caracteres.'
        }

        if (!form.value.email) {
            e.email = 'El email es requerido.'
        }

        if (!form.value.country_id) {
            e.country_id = 'El país es requerido.'
        }

        if (!form.value.phone?.trim()) {
            e.phone = 'El teléfono es requerido.'
        }

        return e
    })

    const isValid = computed(() => Object.keys(errors.value).length === 0)

    return { form, errors, isValid, isEditMode }
}
