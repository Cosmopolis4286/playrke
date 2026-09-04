import { ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'
import type { CommerceProfile } from '@/js/types/roles/commerce/profile/commerce-profile'

/**
 * Autogestión de la ficha propia del comercio — patrón simple de edición
 * in-place (sin modal), mismo espíritu que useSubsidiaryProfile.ts.
 */
export function useCommerceProfile(initial: CommerceProfile) {
    const commerce = ref<CommerceProfile>({ ...initial })
    const editMode = ref(false)
    const saving = ref(false)

    function emptyForm() {
        return { name: commerce.value.name, phone: commerce.value.phone }
    }

    const form = ref(emptyForm())

    function toggleEdit() {
        if (editMode.value) {
            form.value = emptyForm()
        }
        editMode.value = !editMode.value
    }

    async function save(): Promise<boolean> {
        if (saving.value) return false

        saving.value = true
        try {
            await backendClient.put('/commerce/profile', form.value)
            commerce.value = { ...commerce.value, name: form.value.name, phone: form.value.phone }
            editMode.value = false
            showToast('Datos actualizados correctamente', 'success')
            return true
        } catch (error: any) {
            showToast(error?.response?.data?.message ?? 'No se pudo actualizar', 'error')
            return false
        } finally {
            saving.value = false
        }
    }

    return { commerce, editMode, saving, form, toggleEdit, save }
}
