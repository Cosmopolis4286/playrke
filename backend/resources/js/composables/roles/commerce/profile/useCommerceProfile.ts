import { ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'
import type { CommerceProfile } from '@/js/types/roles/commerce/profile/commerce-profile'

/**
 * Autogestión de la ficha propia del comercio — patrón simple de edición
 * in-place (sin modal), mismo espíritu que useSubsidiaryProfile.ts.
 *
 * `targetUserId` (opcional): cuando un admin/superadmin está inspeccionando
 * la ficha de OTRO comercio (ver AdminProfileAccessService en backend), se
 * reenvía en el PUT como target_user_id. Cualquier otro rol que lo reciba
 * distinto de su propio ID recibe 403 en el backend — este parámetro nunca
 * se resuelve del lado del cliente, solo se propaga.
 */
export function useCommerceProfile(initial: CommerceProfile, targetUserId: string | null = null) {
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
            await backendClient.put('/commerce/profile', {
                ...form.value,
                target_user_id: targetUserId ?? undefined,
            })
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
