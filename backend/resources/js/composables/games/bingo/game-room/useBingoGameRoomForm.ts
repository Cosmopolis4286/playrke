import { computed } from 'vue'
import type { Commerce } from '@/js/types/roles/commerce/commerce'

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
export function useBingoGameRoomForm(
    modelValueGetter: () => Partial<Commerce>,
    emit: (event: 'update:modelValue', value: Partial<Commerce>) => void
) {
    /* =======================
     * v-model computado
     * ======================= */

    const form = computed<Partial<Commerce>>({
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

        if (!form.value.currency_id) {
            e.currency_id = 'La moneda principal es requerida.'
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
