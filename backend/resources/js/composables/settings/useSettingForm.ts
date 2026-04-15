import { ref, watch, computed } from 'vue'
import type { Setting } from '@/js/types/setting'

export function useSettingForm(
    modelValue: Partial<Setting>,
    emit: (event: 'update:modelValue', value: Partial<Setting>) => void
) {
    const form = ref<Partial<Setting>>({
        param:  '',
        value:  null,
        status: 'ACTIVE',
    })

    const errors = ref<Partial<Record<keyof Setting, string>>>({})

    const isValid = computed(() =>
        !errors.value.param &&
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

        if (form.value.value && form.value.value.length > 100) {
            errors.value.value = 'Máximo 100 caracteres.'
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

    return { form, errors, isValid, validate }
}
