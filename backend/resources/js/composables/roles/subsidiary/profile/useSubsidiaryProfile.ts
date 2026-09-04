import { ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'
import type { SubsidiaryProfile } from '@/js/types/roles/subsidiary/profile/subsidiary-profile'

/**
 * Autogestión de la ficha propia de la agencia — patrón simple de edición
 * in-place (sin modal, distinto del patrón admin de tabla+modal: aquí es
 * una sola entidad, mismo espíritu que /profile genérico del usuario).
 */
export function useSubsidiaryProfile(initial: SubsidiaryProfile) {
    const subsidiary = ref<SubsidiaryProfile>({ ...initial })
    const editMode = ref(false)
    const saving = ref(false)

    function emptyForm() {
        return {
            name: subsidiary.value.name,
            phone: subsidiary.value.phone,
            brand: subsidiary.value.brand ?? '',
            product: subsidiary.value.product ?? '',
        }
    }

    const form = ref(emptyForm())

    function toggleEdit() {
        if (editMode.value) {
            // Cancelar: descarta cambios no guardados
            form.value = emptyForm()
        }
        editMode.value = !editMode.value
    }

    async function save(): Promise<boolean> {
        if (saving.value) return false

        saving.value = true
        try {
            await backendClient.put('/subsidiary/profile', form.value)
            subsidiary.value = {
                ...subsidiary.value,
                name: form.value.name,
                phone: form.value.phone,
                brand: form.value.brand || null,
                product: form.value.product || null,
            }
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

    return { subsidiary, editMode, saving, form, toggleEdit, save }
}
