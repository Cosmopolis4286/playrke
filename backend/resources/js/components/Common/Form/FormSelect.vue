<script setup lang="ts">
  import { computed } from 'vue'

  /**
   * Props del componente FormSelect
   */
  const props = defineProps({
    container: { type: Boolean, default: false },
    id: { type: String, default: null },
    label: { type: String, default: null },
    name: { type: String, required: true },
    modelValue: { type: [String, Number, Array], default: '' },
    required: { type: Boolean, default: false },
    readonly: { type: Boolean, default: false },
    multiple: { type: Boolean, default: false },
    placeholder: { type: String, default: null },
    col: { type: String, default: 'col-sm-6' },
    className: { type: String, default: '' },
    options: {
      type: Array as () => Array<Record<string, any>>,
      default: () => []
    },
    optionValue: { type: String, default: 'id' },
    optionLabel: { type: String, default: 'name' },
    inputGroup: { type: Boolean, default: false }
  })

  const emit = defineEmits(['update:modelValue'])

  const inputId = computed(() => props.id ?? props.name)

  /**
   * Emitir el cambio en el select
   */
  function onChange(event: Event) {
    const target = event.target as HTMLSelectElement
    let value: any = target.value

    if (props.multiple) {
      value = Array.from(target.selectedOptions).map(option => option.value)
    }

    emit('update:modelValue', value)
  }
</script>

<template>
  <div v-if="container" :id="`container-${inputId}`" :class="['form-group', col, className]">
    <label v-if="label" :for="inputId"> {{ label }}<span v-if="required">*</span> </label>

    <div v-if="inputGroup" class="input-group">
      <select
        :id="inputId"
        :name="name"
        class="form-select"
        :required="required"
        :readonly="readonly"
        :multiple="multiple"
        @change="onChange"
        :value="modelValue"
      >
        <option v-if="placeholder && !multiple" disabled value="">{{ placeholder }}</option>

        <option v-for="option in options" :key="option[optionValue]" :value="option[optionValue]">
          {{ option[optionLabel] }}
        </option>
      </select>
      <div class="input-group-append">
        <slot />
      </div>
    </div>

    <select
      v-else
      :id="inputId"
      :name="name"
      class="form-select"
      :required="required"
      :readonly="readonly"
      :multiple="multiple"
      @change="onChange"
      :value="modelValue"
    >
      <option v-if="placeholder && !multiple" disabled value="">{{ placeholder }}</option>

      <option v-for="option in options" :key="option[optionValue]" :value="option[optionValue]">
        {{ option[optionLabel] }}
      </option>
    </select>

    <!-- Puedes agregar manejo de errores si usas validación externa -->
  </div>

  <select
    v-else
    :id="inputId"
    :name="name"
    class="form-select"
    :required="required"
    :readonly="readonly"
    :multiple="multiple"
    @change="onChange"
    :value="modelValue"
  >
    <option v-if="placeholder && !multiple" disabled value="">{{ placeholder }}</option>

    <option v-for="option in options" :key="option[optionValue]" :value="option[optionValue]">
      {{ option[optionLabel] }}
    </option>
  </select>
</template>
