import { computed, ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'
import type { RoleProfile, RoleProfileRole } from '@/js/types/roles/shared/role-profile-common'

/**
 * Ficha de autogestión de Commerce/Subsidiary/Seller ("Mi …") — reemplaza a
 * useCommerceProfile/useSubsidiaryProfile/useSellerProfile, que eran la misma
 * lógica copiada 3 veces.
 *
 * `targetUserId` (opcional): cuando un admin/superadmin inspecciona la ficha
 * de OTRO usuario (ver AdminProfileAccessService en backend), se reenvía en el
 * PUT como target_user_id. Cualquier otro rol que lo envíe recibe 403 en el
 * backend — este parámetro nunca se resuelve del lado del cliente.
 */
const ENDPOINTS: Record<RoleProfileRole, string> = {
    commerce: '/commerce/profile',
    subsidiary: '/subsidiary/profile',
    seller: '/seller/profile',
}

type EditableField = 'name' | 'phone'

export function useRoleProfile<T extends RoleProfile>(initial: T, targetUserId: string | null = null) {
    const profile = ref(initial) as { value: T }
    const editMode = ref(false)
    const saving = ref(false)
    /** true brevemente tras guardar con éxito — feedback visual además del toast. */
    const justSaved = ref(false)
    const errors = ref<Partial<Record<EditableField, string>>>({})

    const snapshot = () => ({ name: profile.value.name, phone: profile.value.phone })
    const form = ref(snapshot())

    const isDirty = computed(() => form.value.name.trim() !== profile.value.name || form.value.phone.trim() !== profile.value.phone)

    const localErrors = computed<Partial<Record<EditableField, string>>>(() => {
        const e: Partial<Record<EditableField, string>> = {}
        if (!form.value.name.trim()) e.name = 'El nombre es obligatorio.'
        else if (form.value.name.trim().length > 30) e.name = 'Máximo 30 caracteres.'
        if (!form.value.phone.trim()) e.phone = 'El teléfono es obligatorio.'
        else if (form.value.phone.trim().length > 20) e.phone = 'Máximo 20 caracteres.'
        return e
    })

    const canSave = computed(() => isDirty.value && Object.keys(localErrors.value).length === 0 && !saving.value)

    function startEdit() {
        form.value = snapshot()
        errors.value = {}
        editMode.value = true
    }

    function cancelEdit() {
        form.value = snapshot()
        errors.value = {}
        editMode.value = false
    }

    async function save(): Promise<boolean> {
        if (!canSave.value) return false

        saving.value = true
        errors.value = {}
        try {
            const payload = { name: form.value.name.trim(), phone: form.value.phone.trim() }
            await backendClient.put(ENDPOINTS[profile.value.role], {
                ...payload,
                target_user_id: targetUserId ?? undefined,
            })
            profile.value = { ...profile.value, ...payload }
            editMode.value = false
            showToast('Datos actualizados correctamente', 'success')

            justSaved.value = true
            setTimeout(() => { justSaved.value = false }, 2500)
            return true
        } catch (error: any) {
            const serverErrors = error?.response?.data?.errors as Record<string, string[]> | undefined
            if (serverErrors) {
                errors.value = {
                    name: serverErrors.name?.[0],
                    phone: serverErrors.phone?.[0],
                }
            }
            showToast(error?.response?.data?.message ?? 'No se pudo actualizar', 'error')
            return false
        } finally {
            saving.value = false
        }
    }

    return { profile, editMode, saving, justSaved, form, errors, localErrors, isDirty, canSave, startEdit, cancelEdit, save }
}

// ── Formateadores compartidos de la ficha ────────────────────────────────────

const amountFormatter = new Intl.NumberFormat('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const integerFormatter = new Intl.NumberFormat('es-VE', { maximumFractionDigits: 0 })
const dateFormatter = new Intl.DateTimeFormat('es-VE', { day: '2-digit', month: 'short', year: 'numeric' })

export function formatAmount(value: number): string {
    return amountFormatter.format(value)
}

export function formatInteger(value: number): string {
    return integerFormatter.format(value)
}

export function formatDate(iso: string): string {
    return dateFormatter.format(new Date(iso))
}

/** "5%" / "4,5%" — sin decimales sobrantes. */
export function formatRate(rate: number): string {
    return `${new Intl.NumberFormat('es-VE', { maximumFractionDigits: 2 }).format(rate)}%`
}

/** Tiempo relativo ("hace 3 días") — sin librería, suficiente para la ficha. */
export function timeAgo(iso: string): string {
    const rtf = new Intl.RelativeTimeFormat('es', { numeric: 'auto' })
    const seconds = Math.round((new Date(iso).getTime() - Date.now()) / 1000)
    const steps: Array<[Intl.RelativeTimeFormatUnit, number]> = [
        ['year', 31536000], ['month', 2592000], ['week', 604800], ['day', 86400], ['hour', 3600], ['minute', 60],
    ]
    for (const [unit, size] of steps) {
        if (Math.abs(seconds) >= size) return rtf.format(Math.round(seconds / size), unit)
    }
    return 'justo ahora'
}

export const STATUS_META: Record<string, { label: string; tone: 'success' | 'muted' | 'danger' | 'warning' }> = {
    ACTIVE:   { label: 'Activo',    tone: 'success' },
    INACTIVE: { label: 'Inactivo',  tone: 'muted' },
    BLOCKED:  { label: 'Bloqueado', tone: 'danger' },
    DELETED:  { label: 'Eliminado', tone: 'danger' },
}

export function statusMeta(status: string) {
    return STATUS_META[status] ?? { label: status, tone: 'warning' as const }
}

export const ROLE_META: Record<RoleProfileRole, { label: string; icon: string }> = {
    commerce:   { label: 'Comercializadora', icon: 'fa-store' },
    subsidiary: { label: 'Agencia',          icon: 'fa-code-branch' },
    seller:     { label: 'Taquilla',         icon: 'fa-user-tie' },
}
