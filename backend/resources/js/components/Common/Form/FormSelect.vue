<script setup lang="ts" name="FormSelect">
  import { computed } from 'vue'
  import Multiselect from '@vueform/multiselect'

  /**
   * Opción genérica para selects
   * Ej:
   * { id: 1, name: 'Argentina' }
   * { id: 2, label: 'UTC -3' }
   */
  interface SelectOption {
    [key: string]: any
  }

  /**
   * Props del componente
   */
  const props = defineProps({
    /** Mostrar id en el contenedor (container-{id|name}) */
    container: { type: Boolean, default: false },

    /** ID explícito del input */
    id: { type: String, default: null },

    /** Label del campo (obligatorio) */
    label: { type: String, required: true },

    /** Name del campo */
    name: { type: String, required: true },

    /** Valor del v-model (id o array de ids) */
    modelValue: {
      type: [String, Number, Array],
      default: null
    },

    /** Opciones del select */
    options: {
      type: Array as () => SelectOption[],
      default: () => []
    },

    /** Key usada como value */
    optionValue: { type: String, default: 'id' },

    /** Key usada como label */
    optionLabel: { type: String, default: 'name' },

    /** Campo requerido */
    required: { type: Boolean, default: false },

    /** Multiselect */
    multiple: { type: Boolean, default: false },

    /** Placeholder */
    placeholder: { type: String, default: 'Seleccionar...' },

    /** Layout */
    col: { type: String, default: 'col-sm-6' },
    className: { type: String, default: '' }
  })

  /**
   * Emits
   */
  const emit = defineEmits<{
    (e: 'update:modelValue', value: any): void
  }>()

  /**
   * ID efectivo
   */
  const inputId = computed(() => props.id ?? props.name)

  /**
   * ID del contenedor
   */
  const containerId = computed(() => (props.container ? `container-${inputId.value}` : undefined))

  /**
   * v-model proxy
   * Multiselect trabaja perfecto con ids
   */
  const value = computed({
    get: () => props.modelValue,
    set: v => emit('update:modelValue', v)
  })
</script>

<template>
  <div :id="containerId" :class="['form-group', col, className]">
    <!-- Label SIEMPRE visible (como Blade) -->
    <label :for="inputId" class="form-label fw-semibold">
      {{ label }}
      <span v-if="required" class="text-danger">*</span>
    </label>

    <Multiselect
      v-model="value"
      :id="inputId"
      :options="options"
      :mode="multiple ? 'multiple' : 'single'"
      :value-prop="optionValue"
      :label="optionLabel"
      :placeholder="placeholder"
      :close-on-select="!multiple"
      :searchable="true"
      :clearable="!required"
      :can-clear="!required"
      class="multiselect-bootstrap"
    />
  </div>
</template>
<style scoped>
  /* ==========================================================
   Vueform Multiselect → Bootstrap 4 look & feel
   ========================================================== */

  .multiselect-bootstrap {
    /* Input */
    --ms-bg: #fff;
    --ms-border-color: #ced4da;
    --ms-border-width: 1px;
    --ms-border-radius: 0.25rem;
    --ms-placeholder-color: #6c757d;
    --ms-color: #495057;
    --ms-font-size: 0.875rem;
    --ms-line-height: 1.5;

    /* Focus */
    --ms-ring-width: 0.2rem;
    --ms-ring-color: rgba(0, 123, 255, 0.25);
    --ms-border-color-active: #80bdff;

    /* Options */
    --ms-option-bg-selected: #007bff;
    --ms-option-color-selected: #fff;
    --ms-option-bg-selected-pointed: #0069d9;

    --ms-option-bg-pointed: #f8f9fa;
    --ms-option-color-pointed: #16181b;

    /* Tags (multiple) */
    --ms-tag-bg: #007bff;
    --ms-tag-color: #fff;
    --ms-tag-radius: 0.2rem;
  }

  /* Altura y padding como .form-control */
  .multiselect-bootstrap .multiselect {
    min-height: calc(1.5em + 0.75rem + 2px);
  }

  .multiselect-bootstrap .multiselect-input,
  .multiselect-bootstrap .multiselect-single-label {
    padding: 0.375rem 0.75rem;
  }

  /* Disabled */
  .multiselect-bootstrap.is-disabled {
    background-color: #e9ecef;
    opacity: 1;
  }
</style>
