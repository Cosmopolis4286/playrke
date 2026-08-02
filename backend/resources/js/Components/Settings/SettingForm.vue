<script setup lang="ts" name="SettingForm">
  import { computed } from 'vue'
  import { useSettingForm } from '@/js/composables/settings/useSettingForm'
  import type { Setting } from '@/js/types/setting'
  import FormInput  from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'

  const props = defineProps<{
    modelValue: Partial<Setting>
  }>()

  const emit = defineEmits<{
    (e: 'update:modelValue', value: Partial<Setting>): void
  }>()

  const { form, errors, isValid, validate } = useSettingForm(props.modelValue, emit)

  const isEditMode = computed(() => !!props.modelValue.id)

  const statusOptions = [
    { value: 'ACTIVE',   label: 'Activo' },
    { value: 'INACTIVE', label: 'Inactivo' },
  ]

  function handleParam(val: string) {
    form.value.param = val.toLowerCase().replace(/[^a-z0-9_]/g, '_').slice(0, 50)
  }

  function submit(): boolean {
    validate()
    return isValid.value
  }

  defineExpose({ submit, isValid })
</script>

<template>
  <form @submit.prevent novalidate>
    <div class="row">
      <!-- Parámetro -->
      <FormInput
        name="param"
        label="Parámetro"
        col="col-md-6"
        :required="true"
        placeholder="Ej: app_name"
        :maxlength="50"
        :error="errors.param"
        :modelValue="form.param"
        :disabled="isEditMode"
        @update:modelValue="handleParam($event as string)"
        @blur="validate"
      >
        <template #hint>Snake_case, letras minúsculas y números</template>
      </FormInput>

      <!-- Estado -->
      <FormSelect
        name="status"
        label="Estado"
        col="col-md-6"
        :required="true"
        :options="statusOptions"
        option-value="value"
        option-label="label"
        placeholder="Seleccionar estado"
        :error="errors.status"
        :modelValue="form.status"
        @update:modelValue="form.status = $event"
      />

      <!-- Valor -->
      <FormInput
        name="value"
        label="Valor"
        col="col-12"
        placeholder="Valor del parámetro"
        :maxlength="100"
        :error="errors.value"
        :modelValue="form.value ?? ''"
        @update:modelValue="form.value = ($event as string) || null"
      >
        <template #hint>Opcional, máximo 100 caracteres</template>
      </FormInput>
    </div>
  </form>
</template>
