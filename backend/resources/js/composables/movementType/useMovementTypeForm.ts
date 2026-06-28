import { ref, watch, computed } from 'vue'
import type { MovementType } from '@/js/types/movementType'

/**
 * Composable para manejar el formulario de MovementType
 *
 * @param modelValue Valor inicial del formulario (create / edit)
 * @param emit Emit de Vue para sync con v-model
 */
export function useMovementTypeForm(
    modelValue: Partial<MovementType>,
    emit: (event: 'update:modelValue', value: Partial<MovementType>) => void
) {
    /* =======================
     * State
     * ======================= */
    const form = ref<Partial<MovementType>>({
        code: '',
        label: '',
        description: null,
        icon: 'fas fa-circle',
        color: '#718096',
        is_credit: true,
        requires_approval: false,
        status: 'ACTIVE'
    })

    const movementTypeId = ref(modelValue.id)

    const errors = ref<Partial<Record<keyof MovementType, string>>>({})

    /* =======================
     * Computed
     * ======================= */

    const isEditMode = computed(() => !!modelValue?.id)

    /** code es inmutable para tipos del sistema o con movimientos asociados */
    const isCodeLocked = computed(() => isEditMode.value && !!modelValue?.is_system)

    const isValid = computed(() => {
        return (
            !errors.value.code &&
            !errors.value.label &&
            !errors.value.color &&
            !!form.value.label &&
            (!!form.value.code || isCodeLocked.value) &&
            form.value.is_credit != null &&
            form.value.requires_approval != null
        )
    })

    /* =======================
     * Methods
     * ======================= */

    const validate = () => {
        errors.value = {}

        if (!isCodeLocked.value) {
            if (!form.value.code?.trim()) {
                errors.value.code = 'El código es requerido.'
            } else if (!/^[A-Z_]{2,30}$/.test(form.value.code)) {
                errors.value.code = 'Debe tener 2-30 caracteres en mayúsculas y guion bajo.'
            }
        }

        if (!form.value.label?.trim()) {
            errors.value.label = 'El nombre es requerido.'
        } else if (form.value.label.length < 2) {
            errors.value.label = 'Debe tener al menos 2 caracteres.'
        }

        if (form.value.color && !/^#[0-9A-Fa-f]{6}$/.test(form.value.color)) {
            errors.value.color = 'Debe ser un color hexadecimal válido (ej: #16a34a).'
        }
    }

    const reset = () => {
        form.value = {
            code: '',
            label: '',
            description: null,
            icon: 'fas fa-circle',
            color: '#718096',
            is_credit: true,
            requires_approval: false,
            status: 'ACTIVE'
        }
        errors.value = {}
    }

    /* =======================
     * Watchers
     * ======================= */

    /**
     * Sync modelValue → form (edit / create)
     */
    watch(
        () => modelValue,
        value => {
            movementTypeId.value = value.id
            form.value = {
                id: value.id,
                code: value.code ?? '',
                label: value.label ?? '',
                description: value.description ?? null,
                icon: value.icon ?? 'fas fa-circle',
                color: value.color ?? '#718096',
                is_credit: value.is_credit ?? true,
                requires_approval: value.requires_approval ?? false,
                is_system: value.is_system,
                status: value.status ?? 'ACTIVE'
            }
        },
        { immediate: true, deep: true }
    )

    /**
     * Sync form → modelValue
     */
    watch(
        form,
        value => {
            emit('update:modelValue', { ...value })
        },
        { deep: true }
    )

    /**
     * Validación reactiva
     */
    watch(
        () => [form.value.code, form.value.label, form.value.color],
        validate,
        { immediate: true }
    )

    return {
        form,
        errors,
        isEditMode,
        isCodeLocked,
        isValid,
        validate,
        reset
    }
}
