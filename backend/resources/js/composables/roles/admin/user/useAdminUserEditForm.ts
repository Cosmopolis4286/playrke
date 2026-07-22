import { computed } from 'vue'
import type { AdminUserEditData } from '@/js/types/roles/admin/admin-user'

/**
 * Composable para el formulario de edición de Usuario (Admin > Usuarios).
 *
 * Responsabilidades:
 * - Adaptar modelValue al formulario
 * - Validación reactiva
 *
 * ❌ Sin watchers
 * ✅ v-model computado
 */
export function useAdminUserEditForm(
    modelValueGetter: () => Partial<AdminUserEditData>,
    emit: (event: 'update:modelValue', value: Partial<AdminUserEditData>) => void
) {
    const form = computed<Partial<AdminUserEditData>>({
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
