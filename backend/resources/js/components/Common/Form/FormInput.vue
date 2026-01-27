<script setup lang="ts">
  import { computed, useSlots } from 'vue'

  /**
   * Props del componente FormInput
   */
  const props = defineProps({
    container: { type: Boolean, default: false },
    id: { type: String, default: null },
    label: { type: String, default: null },
    name: { type: String, required: true },
    type: { type: String, default: 'text' },
    modelValue: { type: [String, Number], default: '' },
    required: { type: Boolean, default: false },
    readonly: { type: Boolean, default: false },
    minlength: { type: [String, Number], default: null },
    maxlength: { type: [String, Number], default: null },
    placeholder: { type: String, default: null },
    col: { type: String, default: 'col-sm-6' },
    className: { type: String, default: '' },
    inputGroup: { type: Boolean, default: false }
  })

  /**
   * Emite el evento update:modelValue para el v-model
   */
  const emit = defineEmits(['update:modelValue'])

  /**
   * Obtener slots (para el input group append)
   */
  const slots = useSlots()

  /**
   * ID calculado: si no se pasa, usa el name
   */
  const inputId = computed(() => props.id ?? props.name)

  /**
   * Función para emitir el cambio del input
   * @param {Event} event
   */
  function onInput(event: Event) {
    const target = event.target as HTMLInputElement
    emit('update:modelValue', target.value)
  }
</script>

<template>
  <div v-if="container" :id="`container-${inputId}`" :class="['form-group', col, className]">
    <label v-if="label" :for="inputId"> {{ label }}<span v-if="required">*</span> </label>

    <div v-if="inputGroup" class="input-group">
      <input
        :type="type"
        :id="inputId"
        :name="name"
        :value="modelValue"
        class="form-control"
        :readonly="readonly"
        :required="required"
        :placeholder="placeholder"
        :minlength="minlength"
        :maxlength="maxlength"
        @input="onInput"
      />
      <div class="input-group-append" v-if="slots.default">
        <slot />
      </div>
    </div>

    <input
      v-else
      :type="type"
      :id="inputId"
      :name="name"
      :value="modelValue"
      class="form-control"
      :readonly="readonly"
      :required="required"
      :placeholder="placeholder"
      :minlength="minlength"
      :maxlength="maxlength"
      @input="onInput"
    />

    <!-- Aquí puedes agregar manejo de errores si usas validación externa -->
  </div>

  <!-- Si container es falso, solo renderiza input simple -->
  <div v-else :class="className">
    <input
      :type="type"
      :id="inputId"
      :name="name"
      :value="modelValue"
      class="form-control"
      :readonly="readonly"
      :required="required"
      :placeholder="placeholder"
      :minlength="minlength"
      :maxlength="maxlength"
      @input="onInput"
    />
  </div>
</template>
