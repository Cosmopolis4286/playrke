import { ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'
import type { SellerProfile } from '@/js/types/roles/seller/profile/seller-profile'

/**
 * Autogestión de la ficha propia del vendedor — patrón simple de edición
 * in-place (sin modal), mismo espíritu que useCommerceProfile.ts /
 * useSubsidiaryProfile.ts.
 */
export function useSellerProfile(initial: SellerProfile) {
    const seller = ref<SellerProfile>({ ...initial })
    const editMode = ref(false)
    const saving = ref(false)

    function emptyForm() {
        return { name: seller.value.name, phone: seller.value.phone }
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
            await backendClient.put('/seller/profile', form.value)
            seller.value = { ...seller.value, name: form.value.name, phone: form.value.phone }
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

    return { seller, editMode, saving, form, toggleEdit, save }
}
