import { computed } from 'vue'
import type { AdministratorEditData } from '@/js/types/roles/admin/administrator'

/**
 * Validación reactiva del formulario de edición de un administrador
 * existente — mismo patrón que useAdminUserEditForm.ts (v-model computado,
 * sin watchers).
 */
export function useAdministratorEditForm(
    modelValueGetter: () => Partial<AdministratorEditData>,
    emit: (event: 'update:modelValue', value: Partial<AdministratorEditData>) => void
) {
    const form = computed<Partial<AdministratorEditData>>({
        get: () => modelValueGetter(),
        set: value => emit('update:modelValue', value)
    })

    const errors = computed<Record<string, string>>(() => {
        const e: Record<string, string> = {}

        if (!form.value.first_name?.trim()) {
            e.first_name = 'El nombre es requerido.'
        }

        if (!form.value.last_name?.trim()) {
            e.last_name = 'El apellido es requerido.'
        }

        if (!form.value.username || form.value.username.length < 3) {
            e.username = 'El username debe tener al menos 3 caracteres.'
        }

        if (!form.value.email) {
            e.email = 'El email es requerido.'
        }

        if (!form.value.country_id) {
            e.country_id = 'El país es requerido.'
        }

        return e
    })

    const isValid = computed(() => Object.keys(errors.value).length === 0)

    return {
        form,
        errors,
        isValid
    }
}
