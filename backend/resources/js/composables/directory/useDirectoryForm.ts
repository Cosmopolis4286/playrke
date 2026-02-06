import { ref, watch, computed } from 'vue'
import type { DirectoryVEN } from '@/js/types/directory/directory-ven'

/**
 * Composable para manejar el formulario de directorio de Pago Móvil.
 *
 * Responsabilidades:
 * - Gestión del estado del formulario
 * - Validaciones en tiempo real
 * - Sincronización bidireccional con v-model
 *
 * @param modelValue Valor inicial del formulario (create / edit)
 * @param emit Emit de Vue para sync con v-model
 */
export function useDirectoryForm(
    modelValue: Partial<DirectoryVEN>,
    emit: (event: 'update:modelValue', value: Partial<DirectoryVEN>) => void
) {
    /* =======================
     * State
     * ======================= */
    const form = ref({
        holder_name: '',
        document_type_id: '',
        document_number: '',
        phone: '',
        bank_id: ''
    })

    const directoryId = ref(modelValue.id)

    const errors = ref<{
        holder_name?: string
        document_type_id?: string
        document_number?: string
        phone?: string
        bank_id?: string
    }>({})

    /* =======================
     * Computed
     * ======================= */

    /**
     * Indica si el formulario está en modo edición.
     */
    const isEditMode = computed(() => !!modelValue?.id)

    /**
     * Estado de validez del formulario.
     */
    const isValid = computed(() =>
        !errors.value.holder_name &&
        !errors.value.document_type_id &&
        !errors.value.document_number &&
        !errors.value.phone &&
        !errors.value.bank_id
    )

    /* =======================
     * Methods
     * ======================= */

    /**
     * Resetea el formulario a su estado inicial.
     */
    const reset = () => {
        form.value = {
            holder_name: '',
            document_type_id: '',
            document_number: '',
            phone: '',
            bank_id: ''
        }
        errors.value = {}
    }

    /**
     * Valida los campos del formulario.
     */
    const validate = () => {
        errors.value = {}

        // Validar nombre del titular
        if (!form.value.holder_name?.trim()) {
            errors.value.holder_name = 'El nombre del titular es requerido.'
        } else if (form.value.holder_name.length > 100) {
            errors.value.holder_name = 'Máximo 100 caracteres.'
        }

        // Validar tipo de documento
        if (!form.value.document_type_id) {
            errors.value.document_type_id = 'El tipo de documento es requerido.'
        }

        // Validar número de documento
        if (!form.value.document_number) {
            errors.value.document_number = 'El número de documento es requerido.'
        } else if (!/^\d{7,8}$/.test(form.value.document_number)) {
            errors.value.document_number = 'Debe tener 7-8 dígitos numéricos.'
        }

        // Validar teléfono
        if (!form.value.phone) {
            errors.value.phone = 'El teléfono es requerido.'
        } else if (!/^(04|02)\d{2}-?\d{7}$/.test(form.value.phone)) {
            errors.value.phone = 'Formato inválido (ej: 04241234567).'
        }

        // Validar banco
        if (!form.value.bank_id) {
            errors.value.bank_id = 'El banco es requerido.'
        }
    }

    /* =======================
     * Watchers
     * ======================= */

    /**
     * Sync modelValue → form (edit / create).
     */
    watch(
        () => modelValue,
        value => {
            form.value = {
                holder_name: value.holder_name ?? '',
                document_type_id: value.document_type_id?.toString() ?? '',
                document_number: value.document_number ?? '',
                phone: value.phone ?? '',
                bank_id: value.bank_id?.toString() ?? ''
            }
        },
        { immediate: true, deep: true }
    )

    /**
     * Sync form → modelValue.
     */
    watch(
        form,
        value => {
            emit('update:modelValue', {
                id: directoryId.value,
                holder_name: value.holder_name,
                document_type_id: value.document_type_id,
                document_number: value.document_number,
                phone: value.phone,
                bank_id: value.bank_id
            })
        },
        { deep: true }
    )

    /**
     * Validación reactiva.
     */
    watch(
        () => [
            form.value.holder_name,
            form.value.document_type_id,
            form.value.document_number,
            form.value.phone,
            form.value.bank_id
        ],
        validate,
        { immediate: true }
    )

    return {
        form,
        errors,
        isEditMode,
        isValid,
        validate,
        reset
    }
}
