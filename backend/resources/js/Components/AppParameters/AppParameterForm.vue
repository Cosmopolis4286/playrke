<script setup lang="ts" name="AppParameterForm">
  import { computed } from 'vue'
  import { useAppParameterForm } from '@/js/composables/app-parameters/useAppParameterForm'
  import type { AppParameter } from '@/js/types/app-parameter'
  import FormInput  from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'

  const props = defineProps<{
    modelValue: Partial<AppParameter>
  }>()

  const emit = defineEmits<{
    (e: 'update:modelValue', value: Partial<AppParameter>): void
  }>()

  const { form, errors, isValid, validate, meta } = useAppParameterForm(props.modelValue, emit)

  const isEditMode = computed(() => !!props.modelValue.id)

  const statusOptions = [
    { value: 'ACTIVE',   label: 'Activo' },
    { value: 'INACTIVE', label: 'Inactivo' },
  ]

  function handleParam(val: string) {
    form.value.param = val.toLowerCase().replace(/[^a-z0-9_]/g, '_').slice(0, 50)
  }

  /**
   * El campo "Valor" boolean sigue guardando 'ACTIVE'/'INACTIVE' (no
   * 'true'/'false') para no romper compatibilidad con los valores ya
   * sembrados por AppParameterSeeder — solo cambia el input visual, no el
   * formato de almacenamiento.
   */
  const isValueActive = computed({
    get: () => form.value.value === 'ACTIVE',
    set: (checked: boolean) => { form.value.value = checked ? 'ACTIVE' : 'INACTIVE' }
  })

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

      <!-- Valor: input según el tipo conocido del parámetro (ver appParameterCatalog.ts) -->
      <div v-if="meta.type === 'boolean'" class="form-group col-12">
        <label class="d-block">Valor</label>
        <div class="custom-control custom-switch">
          <input
            id="app-parameter-value-switch"
            type="checkbox"
            class="custom-control-input"
            :checked="isValueActive"
            @change="isValueActive = ($event.target as HTMLInputElement).checked"
          />
          <label class="custom-control-label" for="app-parameter-value-switch">
            {{ isValueActive ? 'Activo' : 'Inactivo' }}
          </label>
        </div>
      </div>

      <FormInput
        v-else-if="meta.type === 'integer' || meta.type === 'decimal'"
        name="value"
        label="Valor"
        col="col-12"
        type="number"
        :step="meta.type === 'decimal' ? 0.01 : 1"
        placeholder="Valor del parámetro"
        :error="errors.value"
        :modelValue="form.value ?? ''"
        @update:modelValue="form.value = ($event === '' ? null : String($event))"
      >
        <template #hint>Opcional</template>
      </FormInput>

      <FormInput
        v-else
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
