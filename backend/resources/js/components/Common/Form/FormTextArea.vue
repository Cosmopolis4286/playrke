<script setup lang="ts" name="FormTextArea">
  import { computed } from 'vue'

  const props = defineProps({
    /** ID explícito del textarea */
    id: { type: String, default: null },

    /** Mensaje de error */
    error: { type: String, default: null },

    /** Label del campo */
    label: { type: String, default: null },

    /** Name del campo */
    name: { type: String, required: true },

    /** v-model */
    modelValue: { type: String, default: '' },

    /** Campo requerido */
    required: { type: Boolean, default: false },

    /** Solo lectura */
    readonly: { type: Boolean, default: false },

    /** Deshabilitar */
    disabled: { type: Boolean, default: false },

    /** Placeholder */
    placeholder: { type: String, default: null },

    /** Máximo de caracteres */
    maxlength: { type: [String, Number], default: null },

    /** Filas visibles */
    rows: { type: [String, Number], default: 3 },

    /** Redimensionable */
    resize: {
      type: String,
      default: 'vertical',
      validator: (v: string) => ['none', 'vertical', 'horizontal', 'both'].includes(v)
    },

    /** Layout */
    col: { type: String, default: 'col-sm-6' },
    className: { type: String, default: '' },
    inputClass: { type: String, default: '' }
  })

  const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void
  }>()

  const inputId = computed(() => props.id ?? props.name)

  function onInput(event: Event) {
    emit('update:modelValue', (event.target as HTMLTextAreaElement).value)
  }
</script>

<template>
  <div :class="['form-group', col, className]">
    <label v-if="label" :for="inputId">
      {{ label }}
      <span v-if="required" class="text-danger">*</span>
    </label>

    <textarea
      :id="inputId"
      :name="name"
      :value="modelValue"
      :class="['form-control', inputClass, { 'is-invalid': !!error }]"
      :placeholder="placeholder"
      :readonly="readonly"
      :disabled="disabled"
      :required="required"
      :maxlength="maxlength"
      :rows="rows"
      :style="{ resize }"
      @input="onInput"
    />

    <small v-if="error" class="text-danger d-block mt-1">
      {{ error }}
    </small>
  </div>
</template>
