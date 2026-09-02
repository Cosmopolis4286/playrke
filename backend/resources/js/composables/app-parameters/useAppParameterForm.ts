import { ref, watch, computed } from 'vue'
import type { AppParameter } from '@/js/types/app-parameter'
import { getParamMeta } from './appParameterCatalog'

export function useAppParameterForm(
    modelValue: Partial<AppParameter>,
    emit: (event: 'update:modelValue', value: Partial<AppParameter>) => void
) {
    const form = ref<Partial<AppParameter>>({
        param:  '',
        value:  null,
        status: 'ACTIVE',
    })

    const errors = ref<Partial<Record<keyof AppParameter, string>>>({})

    const meta = computed(() => getParamMeta(form.value.param ?? ''))

    const isValid = computed(() =>
        !errors.value.param &&
        !errors.value.value &&
        !!form.value.param?.trim() &&
        !!form.value.status
    )

    function validate() {
        errors.value = {}

        if (!form.value.param?.trim()) {
            errors.value.param = 'El parámetro es requerido.'
        } else if (!/^[a-z0-9_]{1,50}$/.test(form.value.param.trim())) {
            errors.value.param = 'Solo letras minúsculas, números y guiones bajos (máx. 50).'
        }

        const value = form.value.value

        if (value && value.length > 100) {
            errors.value.value = 'Máximo 100 caracteres.'
            return
        }

        // Validación adicional por tipo conocido (ver appParameterCatalog.ts) —
        // no reemplaza la validación de longitud de arriba, se suma a ella.
        if (value) {
            if (meta.value.type === 'integer' && !/^-?\d+$/.test(value)) {
                errors.value.value = 'Debe ser un número entero.'
            } else if (meta.value.type === 'decimal' && !/^-?\d+(\.\d+)?$/.test(value)) {
                errors.value.value = 'Debe ser un número (puede tener decimales).'
            }
        }
    }

    // Sync modelValue → form
    watch(
        () => modelValue,
        value => {
            form.value = {
                id:     value.id,
                param:  value.param  ?? '',
                value:  value.value  ?? null,
                status: value.status ?? 'ACTIVE',
            }
        },
        { immediate: true, deep: true }
    )

    // Sync form → parent
    watch(form, value => emit('update:modelValue', { ...value }), { deep: true })

    // Validación reactiva
    watch(() => [form.value.param, form.value.value], validate, { immediate: true })

    return { form, errors, isValid, validate, meta }
}
