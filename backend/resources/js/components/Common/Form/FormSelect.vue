<script setup lang="ts" name="FormSelect">
  import { computed, useSlots } from 'vue'
  import Multiselect from '@vueform/multiselect'
  import type { PropType } from 'vue'

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
    /** ID explícito del input */
    id: { type: String, default: null },

    /** Mensaje de error */
    error: {
      type: [String, Object] as PropType<string | Record<string, string> | null>,
      default: null
    },

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
    className: { type: String, default: '' },

    inputGroup: { type: Boolean, default: false },

    inputGroupClass: { type: String, default: '' },

    /** Ocultar label (para uso inline) */
    hideLabel: { type: Boolean, default: false },

    /** Solo borde rojo + icono, sin mensaje de error debajo */
    inlineError: { type: Boolean, default: false }
  })

  /**
   * Emits
   */
  const emit = defineEmits<{
    (e: 'update:modelValue', value: any): void
  }>()

  const slots = useSlots()

  /**
   * ID efectivo
   */
  const inputId = computed(() => props.id ?? props.name)

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
  <div :class="['form-group', col, className]">
    <!-- Label SIEMPRE visible (como Blade) -->
    <label v-if="!hideLabel" :for="inputId" class="form-label fw-semibold">
      {{ label }}
      <span v-if="required" class="text-danger">*</span>
    </label>

    <!-- INPUT GROUP -->
    <div v-if="inputGroup" :class="['input-group', inputGroupClass]">
      <div v-if="slots.prepend" class="input-group-prepend">
        <slot name="prepend" />
      </div>

      <div style="flex: 1; min-width: 0; position: relative">
        <Multiselect
          v-model="value"
          :id="inputId"
          :options="options"
          :mode="multiple ? 'tags' : 'single'"
          :value-prop="optionValue"
          :label="optionLabel"
          :placeholder="placeholder"
          :close-on-select="!multiple"
          :searchable="true"
          :clearable="!required"
          :can-clear="!required"
          class="multiselect-bootstrap"
          :class="{ 'ms-invalid': !!error }"
          style="width: 100%"
        />
        <span v-if="!!error && inlineError" class="ms-invalid-icon" aria-hidden="true" />
      </div>

      <div v-if="slots.append" class="input-group-append">
        <slot name="append" />
      </div>
    </div>

    <!-- MULTISELECT SIMPLE -->
    <Multiselect
      v-else
      v-model="value"
      :id="inputId"
      :options="options"
      :mode="multiple ? 'tags' : 'single'"
      :value-prop="optionValue"
      :label="optionLabel"
      :placeholder="placeholder"
      :close-on-select="!multiple"
      :searchable="true"
      :clearable="!required"
      :can-clear="!required"
      class="multiselect-bootstrap"
      :class="{ 'ms-invalid': !!error }"
    />

    <small v-if="error && !inlineError" class="text-danger d-block mt-1">
      {{ error }}
    </small>
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

    /* Altura consistente con .form-control */
    --ms-py: 0.375rem;
    --ms-px: 0.75rem;

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

    /* Dropdown */
    --ms-max-height: 200px;

    /* Tags (multiple) */
    --ms-tag-bg: #007bff;
    --ms-tag-color: #fff;
    --ms-tag-radius: 0.2rem;
    --ms-tag-font-size: 0.75rem;
    --ms-tag-py: 0.125rem;
    --ms-tag-px: 0.5rem;
    --ms-tag-my: 0.125rem;
    --ms-tag-mx: 0.125rem;
  }

  /* Altura mínima igual a form-control (38px aprox) */
  .multiselect-bootstrap :deep(.multiselect-wrapper) {
    min-height: calc(1.5em + 0.75rem + 2px);
  }

  /* Estado inválido: borde rojo en todo el control */
  .multiselect-bootstrap.ms-invalid {
    --ms-border-color: #dc3545;
    --ms-border-color-active: #dc3545;
    --ms-ring-color: rgba(220, 53, 69, 0.25);
  }

  .multiselect-bootstrap.ms-invalid :deep(.multiselect-wrapper) {
    border-color: #dc3545;
  }

  /* Icono de error idéntico al is-invalid de Bootstrap */
  .ms-invalid-icon {
    position: absolute;
    top: 50%;
    right: 0.5rem;
    transform: translateY(-50%);
    width: 1rem;
    height: 1rem;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23dc3545' viewBox='0 0 12 12'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-size: contain;
    pointer-events: none;
  }

  /* Altura igual a form-control-sm dentro de input-group-sm */
  .input-group-sm .multiselect-bootstrap {
    --ms-py: 0.125rem;
    --ms-px: 0.5rem;
    --ms-font-size: 0.875rem;
    --ms-line-height: 1.5;
  }

  .input-group-sm .multiselect-bootstrap :deep(.multiselect-wrapper) {
    min-height: calc(1.5em + 0.3rem + 2px);
    max-height: calc(1.5em + 0.3rem + 2px);
  }

  /* Placeholder con texto largo */
  .multiselect-bootstrap :deep(.multiselect-placeholder) {
    font-size: 0.8rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    padding-right: 1.5rem;
  }

  /* Disabled */
  .multiselect-bootstrap.is-disabled {
    background-color: #e9ecef;
    opacity: 1;
  }

  .multiselect-bootstrap :deep(.multiselect-option) {
    min-height: 32px; /* altura */
    padding: 6px 12px; /* espacio interno */
    display: flex;
    align-items: center; /* centra vertical */
  }

  .multiselect-bootstrap :deep(.multiselect-option) {
    font-size: 0.9rem;
  }
</style>
