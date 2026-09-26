import { ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'
import type { SubsidiaryProfile } from '@/js/types/roles/subsidiary/profile/subsidiary-profile'

/**
 * Autogestión de la ficha propia de la agencia — patrón simple de edición
 * in-place (sin modal, distinto del patrón admin de tabla+modal: aquí es
 * una sola entidad, mismo espíritu que /profile genérico del usuario).
 *
 * `targetUserId` (opcional): cuando un admin/superadmin está inspeccionando
 * la ficha de OTRA agencia (ver AdminProfileAccessService en backend), se
 * reenvía en el PUT como target_user_id. Cualquier otro rol que lo reciba
 * distinto de su propio ID recibe 403 en el backend — este parámetro nunca
 * se resuelve del lado del cliente, solo se propaga.
 */
export function useSubsidiaryProfile(initial: SubsidiaryProfile, targetUserId: string | null = null) {
    const subsidiary = ref<SubsidiaryProfile>({ ...initial })
    const editMode = ref(false)
    const saving = ref(false)
    /** true brevemente tras guardar con éxito — feedback visual además del toast, que desaparece rápido. */
    const justSaved = ref(false)

    function emptyForm() {
        return {
            name: subsidiary.value.name,
            phone: subsidiary.value.phone,
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
            await backendClient.put('/subsidiary/profile', {
                ...form.value,
                target_user_id: targetUserId ?? undefined,
            })
            subsidiary.value = { ...subsidiary.value, name: form.value.name, phone: form.value.phone }
            editMode.value = false
            showToast('Datos actualizados correctamente', 'success')

            justSaved.value = true
            setTimeout(() => { justSaved.value = false }, 2500)

            return true
        } catch (error: any) {
            showToast(error?.response?.data?.message ?? 'No se pudo actualizar', 'error')
            return false
        } finally {
            saving.value = false
        }
    }

    return { subsidiary, editMode, saving, justSaved, form, toggleEdit, save }
}
