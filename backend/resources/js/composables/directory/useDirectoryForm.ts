import { ref, watch, computed } from 'vue'
import type { DirectoryVEN } from '@/js/types/directory/directory-ven'

type PixKeyType = 'CPF' | 'CNPJ' | 'EMAIL' | 'PHONE' | 'RANDOM' | ''

/** Validaciones por tipo de clave PIX */
const PIX_RULES: Record<string, { pattern: RegExp; message: string; placeholder: string; maxlength: number }> = {
    CPF:    { pattern: /^\d{11}$/,                           message: 'CPF inválido (11 dígitos sin puntos).', placeholder: '00000000000',             maxlength: 11  },
    CNPJ:   { pattern: /^\d{14}$/,                           message: 'CNPJ inválido (14 dígitos sin puntos).', placeholder: '00000000000000',           maxlength: 14  },
    EMAIL:  { pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,         message: 'E-mail inválido.',                       placeholder: 'ejemplo@correo.com',        maxlength: 77  },
    PHONE:  { pattern: /^\+55\d{10,11}$/,                    message: 'Formato: +55XXXXXXXXXXX.',               placeholder: '+5511999999999',            maxlength: 14  },
    RANDOM: { pattern: /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i, message: 'UUID inválido.', placeholder: 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx', maxlength: 36  },
}

/**
 * Composable para manejar el formulario de directorio multi-país.
 *
 * VE → Pago Móvil (holder_name, document_type_id, document_number, phone, bank_id)
 * BR → PIX        (name, pix_key_type, pix_key_value, bank_id)
 */
export function useDirectoryForm(
    modelValue: Partial<DirectoryVEN> & { country_code?: string; name?: string; pix_key_type?: string; pix_key_value?: string },
    emit: (event: 'update:modelValue', value: any) => void
) {
    const countryCode = computed<string>(() => (modelValue as any).country_code ?? 'VE')
    const isBR = computed(() => countryCode.value === 'BR')

    /* =======================
     * State
     * ======================= */
    const form = ref({
        // VE fields
        holder_name: '',
        document_type_id: '',
        document_number: '',
        phone: '',
        // BR fields
        name: '',
        pix_key_type: '' as PixKeyType,
        pix_key_value: '',
        // shared
        bank_id: '',
    })

    const directoryId = ref((modelValue as any).id)

    const errors = ref<{
        holder_name?: string
        document_type_id?: string
        document_number?: string
        phone?: string
        name?: string
        pix_key_type?: string
        pix_key_value?: string
        bank_id?: string
    }>({})

    /* =======================
     * Computed
     * ======================= */

    const isEditMode = computed(() => !!(modelValue as any)?.id)

    const isValid = computed(() => {
        if (isBR.value) {
            return !errors.value.name &&
                !errors.value.pix_key_type &&
                !errors.value.pix_key_value &&
                !errors.value.bank_id
        }
        return !errors.value.holder_name &&
            !errors.value.document_type_id &&
            !errors.value.document_number &&
            !errors.value.phone &&
            !errors.value.bank_id
    })

    /** Metadatos del campo pix_key_value según tipo seleccionado */
    const pixFieldMeta = computed(() => {
        const type = form.value.pix_key_type
        return PIX_RULES[type] ?? { pattern: null, message: '', placeholder: 'CPF, e-mail, teléfono...', maxlength: 100 }
    })

    /* =======================
     * Methods
     * ======================= */

    const reset = () => {
        form.value = {
            holder_name: '',
            document_type_id: '',
            document_number: '',
            phone: '',
            name: '',
            pix_key_type: '',
            pix_key_value: '',
            bank_id: '',
        }
        errors.value = {}
    }

    const validate = () => {
        errors.value = {}

        if (isBR.value) {
            // --- Brasil (PIX) ---
            if (!form.value.name?.trim()) {
                errors.value.name = 'El nombre es requerido.'
            } else if (form.value.name.length > 35) {
                errors.value.name = 'Máximo 35 caracteres.'
            }

            if (!form.value.pix_key_type) {
                errors.value.pix_key_type = 'El tipo de clave PIX es requerido.'
            }

            if (!form.value.pix_key_value) {
                errors.value.pix_key_value = 'La clave PIX es requerida.'
            } else {
                const rule = PIX_RULES[form.value.pix_key_type]
                if (rule && !rule.pattern.test(form.value.pix_key_value)) {
                    errors.value.pix_key_value = rule.message
                }
            }
        } else {
            // --- Venezuela (Pago Móvil) ---
            if (!form.value.holder_name?.trim()) {
                errors.value.holder_name = 'El nombre del titular es requerido.'
            } else if (form.value.holder_name.length > 100) {
                errors.value.holder_name = 'Máximo 100 caracteres.'
            }

            if (!form.value.document_type_id) {
                errors.value.document_type_id = 'El tipo de documento es requerido.'
            }

            if (!form.value.document_number) {
                errors.value.document_number = 'El número de documento es requerido.'
            } else if (!/^\d{7,8}$/.test(form.value.document_number)) {
                errors.value.document_number = 'Debe tener 7-8 dígitos numéricos.'
            }

            if (!form.value.phone) {
                errors.value.phone = 'El teléfono es requerido.'
            } else if (!/^(04|02)\d{2}-?\d{7}$/.test(form.value.phone)) {
                errors.value.phone = 'Formato inválido (ej: 04241234567).'
            }
        }

        if (!form.value.bank_id) {
            errors.value.bank_id = 'El banco es requerido.'
        }
    }

    /* =======================
     * Watchers
     * ======================= */

    /** Sync modelValue → form (edit / create) */
    watch(
        () => modelValue,
        value => {
            const v = value as any
            form.value = {
                holder_name:      v.holder_name       ?? '',
                document_type_id: v.document_type_id?.toString() ?? '',
                document_number:  v.document_number   ?? '',
                phone:            v.phone             ?? '',
                name:             v.name              ?? '',
                pix_key_type:     v.pix_key_type      ?? '',
                pix_key_value:    v.pix_key_value      ?? '',
                bank_id:          v.bank_id?.toString() ?? '',
            }
        },
        { immediate: true, deep: true }
    )

    /** Sync form → modelValue */
    watch(
        form,
        value => {
            const base: any = { id: directoryId.value, bank_id: value.bank_id }

            if (isBR.value) {
                Object.assign(base, {
                    name:          value.name,
                    pix_key_type:  value.pix_key_type,
                    pix_key_value: value.pix_key_value,
                })
            } else {
                Object.assign(base, {
                    holder_name:      value.holder_name,
                    document_type_id: value.document_type_id,
                    document_number:  value.document_number,
                    phone:            value.phone,
                })
            }

            emit('update:modelValue', base)
        },
        { deep: true }
    )

    /** Validación reactiva */
    watch(
        () => [
            form.value.holder_name,
            form.value.document_type_id,
            form.value.document_number,
            form.value.phone,
            form.value.name,
            form.value.pix_key_type,
            form.value.pix_key_value,
            form.value.bank_id,
        ],
        validate,
        { immediate: true }
    )

    return {
        form,
        errors,
        isEditMode,
        isValid,
        pixFieldMeta,
        validate,
        reset,
    }
}
