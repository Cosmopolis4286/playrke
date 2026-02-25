import { computed } from 'vue'
import type { Customer } from '@/js/types/roles/customer/customer'

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

function isValidUrl(val: string): boolean {
    try {
        const url = new URL(val)
        return url.protocol === 'https:' || url.protocol === 'http:'
    } catch {
        return false
    }
}

/**
 * Composable para formulario de Comercializadoras
 *
 * Responsabilidades:
 * - Adaptar modelValue al formulario
 * - Validación reactiva
 * - Exponer estado de edición
 *
 * ❌ Sin watchers
 * ✅ v-model computado
 */
export function useAdminCustomerForm(
    modelValueGetter: () => Partial<Customer>,
    emit: (event: 'update:modelValue', value: Partial<Customer>) => void
) {
    /* =======================
     * v-model computado
     * ======================= */

    const form = computed<Partial<Customer>>({
        get: () => modelValueGetter(),
        set: value => emit('update:modelValue', value)
    })

    /* =======================
     * Computed
     * ======================= */

    const isEditMode = computed(() => !!form.value.id)

    const errors = computed<Record<string, string>>(() => {
        const e: Record<string, string> = {}

        if (!form.value.name?.trim()) {
            e.name = 'La razón social es requerida.'
        }

        const username = form.value.username ?? ''
        if (!username || username.length < 5) {
            e.username = 'El username debe tener al menos 5 caracteres.'
        }

        const email = form.value.email ?? ''
        if (!email) {
            e.email = 'El email es requerido.'
        } else if (!EMAIL_RE.test(email)) {
            e.email = 'Ingresa un email válido.'
        }

        const apiRoute = form.value.api_route?.trim() ?? ''
        if (!apiRoute) {
            e.api_route = 'La ruta API es requerida.'
        } else if (!isValidUrl(apiRoute)) {
            e.api_route = 'Ingresa una URL válida (ej: https://api.ejemplo.com/).'
        }

        if (form.value.has_commission) {
            const commission = form.value.commission ?? ''
            if (!commission) {
                e.commission = 'La comisión es requerida.'
            } else {
                const v = parseFloat(commission)
                if (isNaN(v) || v < 0 || v > 100) {
                    e.commission = 'La comisión debe estar entre 0 y 100.'
                }
            }
        }

        if (!form.value.country_id) {
            e.country_id = 'El país es requerido.'
        }

        if (!form.value.currency_id) {
            e.currency_id = 'La moneda principal es requerida.'
        }

        if (!form.value.allowed_games?.length) {
            e.allowed_games = 'Debes seleccionar al menos un juego permitido.'
        }

        return e
    })

    const isValid = computed(() => Object.keys(errors.value).length === 0)

    return {
        form,
        errors,
        isValid,
        isEditMode
    }
}
