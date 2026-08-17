import { computed } from 'vue'
import type { AdministratorCreateData } from '@/js/types/roles/admin/administrator'

/**
 * Validación reactiva del formulario de alta de un nuevo administrador —
 * mismo patrón que useAdminUserEditForm.ts (v-model computado, sin watchers).
 */
export function useAdministratorCreateForm(
    modelValueGetter: () => Partial<AdministratorCreateData>,
    emit: (event: 'update:modelValue', value: Partial<AdministratorCreateData>) => void
) {
    const form = computed<Partial<AdministratorCreateData>>({
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
        } else if (!/^[a-zA-Z0-9_]+$/.test(form.value.username)) {
            e.username = 'El username solo puede contener letras, números y guiones bajos.'
        }

        if (!form.value.email) {
            e.email = 'El email es requerido.'
        }

        if (!form.value.country_id) {
            e.country_id = 'El país es requerido.'
        }

        if (!form.value.password || form.value.password.length < 8) {
            e.password = 'La contraseña debe tener al menos 8 caracteres.'
        } else if (!/[A-Za-z]/.test(form.value.password) || !/[0-9]/.test(form.value.password)) {
            e.password = 'La contraseña debe incluir letras y números.'
        }

        if (form.value.password !== form.value.password_confirmation) {
            e.password_confirmation = 'Las contraseñas no coinciden.'
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
