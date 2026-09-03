import { computed } from 'vue'
import type { Subsidiary } from '@/js/types/roles/subsidiary/subsidiary'

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

/**
 * Composable para formulario de Sucursales
 *
 * Responsabilidades:
 * - Adaptar modelValue al formulario
 * - Validación reactiva
 * - Exponer estado de edición
 *
 * ❌ Sin watchers
 * ✅ v-model computado
 */
export function useAdminSubsidiaryForm(
    modelValueGetter: () => Partial<Subsidiary>,
    emit: (event: 'update:modelValue', value: Partial<Subsidiary>) => void
) {
    /* =======================
     * v-model computado
     * ======================= */

    const form = computed<Partial<Subsidiary>>({
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
            e.name = 'El nombre de la sucursal es requerido.'
        }

        // username/email: obligatorios solo al crear — en edición el backend
        // los marca 'sometimes' (AdminSubsidiaryRequest::rules()), un admin
        // puede editar solo comisión/país/etc. sin tocar la cuenta de acceso.
        // Si se envían, igual deben cumplir formato/longitud.
        const username = form.value.username ?? ''
        if (!isEditMode.value && !username) {
            e.username = 'El username es requerido.'
        } else if (username && username.length < 5) {
            e.username = 'El username debe tener al menos 5 caracteres.'
        }

        const email = form.value.email ?? ''
        if (!isEditMode.value && !email) {
            e.email = 'El email es requerido.'
        } else if (email && !EMAIL_RE.test(email)) {
            e.email = 'Ingresa un email válido.'
        }

        if (!form.value.commerce_id) {
            e.commerce_id = 'El comercio al que pertenece es requerido.'
        }

        if (!form.value.country_id) {
            e.country_id = 'El país es requerido.'
        }

        if (!form.value.phone?.trim()) {
            e.phone = 'El teléfono es requerido.'
        }

        if (!form.value.currency_id) {
            e.currency_id = 'La moneda es requerida.'
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

