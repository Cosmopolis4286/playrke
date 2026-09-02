import { computed } from 'vue'
import type { Seller } from '@/js/types/roles/seller/seller'

/**
 * Composable para formulario de vendedores
 *
 * Responsabilidades:
 * - Adaptar modelValue al formulario
 * - Validación reactiva
 * - Exponer estado de edición
 *
 * ❌ Sin watchers
 * ✅ v-model computado
 */
export function useManagerSellerForm(
    modelValueGetter: () => Partial<Seller>,
    emit: (event: 'update:modelValue', value: Partial<Seller>) => void,
    roleUserGetter: () => string = () => ''
) {
    /* =======================
     * v-model computado
     * ======================= */

    const form = computed<Partial<Seller>>({
        get: () => modelValueGetter(),
        set: value => emit('update:modelValue', value)
    })

    /* =======================
     * Computed
     * ======================= */

    /**
     * Modo edición
     */
    const isEditMode = computed(() => !!form.value.id)

    /**
     * Validaciones reactivas
     */
    const errors = computed<Record<string, string>>(() => {
        const e: Record<string, string> = {}
        const isAdmin = roleUserGetter() === 'admin'

        // admin: manager obligatorio (sistema completo, sin manager
        // implícito posible). commerce: opcional — omitirlo mantiene el
        // comportamiento actual (reporta directo a sí mismo), el select solo
        // existe para permitir mover el seller hacia una de sus propias
        // subsidiaries. subsidiary: nunca ve el select (no puede reasignar).
        if (isAdmin && !form.value.manager_user_id) {
            e.manager_user_id = 'El manager es requerido.'
            return e
        }

        if (!form.value.name) {
            e.name = 'El nombre es requerido.'
        }

        if (!form.value.username || form.value.username.length < 5) {
            e.username = 'El username debe tener al menos 5 caracteres.'
        }

        if (!form.value.email) {
            e.email = 'El email es requerido.'
        }

        if (!form.value.commission) {
            e.commission = 'La comisión es requerida.'
        } else {
            const commissionValue = parseFloat(form.value.commission)
            if (isNaN(commissionValue) || commissionValue < 0 || commissionValue > 100) {
                e.commission = 'La comisión debe estar entre 0 y 100.'
            }
        }

        if (!form.value.country_id) {
            e.country_id = 'El país es requerido.'
        }

        if (!form.value.phone?.trim()) {
            e.phone = 'El teléfono es requerido.'
        }

        if (!form.value.currency_id) {
            e.currency_id = 'La moneda principal es requerida.'
        }

        if (!form.value.allowed_games?.length) {
            e.allowed_games = 'Debes seleccionar al menos un juego permitido.'
        }

        return e
    })

    /**
     * Estado de validez
     */
    const isValid = computed(() => Object.keys(errors.value).length === 0)

    return {
        form,
        errors,
        isValid
    }
}
