<script setup lang="ts">
  import { computed, useSlots } from 'vue'
  import { IMaskDirective } from 'vue-imask'
  import type { PropType } from 'vue'
  import type { FactoryArg } from 'imask'

  const vImask = IMaskDirective

  const props = defineProps({
    id: { type: String, default: null },
    error: {
      type: [String, Object] as PropType<string | Record<string, string> | null>,
      default: null
    },
    label: { type: String, default: null },
    name: { type: String, required: true },
    type: { type: String, default: 'text' },
    modelValue: { type: [String, Number], default: '' },
    required: { type: Boolean, default: false },
    readonly: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    minlength: { type: [String, Number], default: null },
    maxlength: { type: [String, Number], default: null },
    min: { type: [String, Number], default: null },
    max: { type: [String, Number], default: null },
    step: { type: [String, Number], default: null },
    placeholder: { type: String, default: null },
    col: { type: String, default: 'col-sm-6' },
    className: { type: String, default: '' },
    inputClass: { type: String, default: '' },
    inputGroup: { type: Boolean, default: false },
    inputGroupClass: { type: String, default: '' },
    mask: { type: Object as () => FactoryArg, default: null }
  })

  const emit = defineEmits(['update:modelValue'])
  const slots = useSlots()

  const inputId = computed(() => props.id ?? props.name)

  /**
   * Valida y corrige valores numéricos dentro de los límites min/max
   */
  function clampNumericValue(value: string | number): number {
    if (props.type !== 'number') {
      return Number(value) || 0
    }

    const num = Number(value) || 0
    const min = props.min !== null ? Number(props.min) : null
    const max = props.max !== null ? Number(props.max) : null

    if (min !== null && num < min) {
      return min
    }
    if (max !== null && num > max) {
      return max
    }

    return num
  }

  /**
   * Maneja el evento @input
   */
  function onInput(event: Event) {
    const target = event.target as HTMLInputElement
    const value = target.value

    if (props.type === 'number') {
      if (value === '') {
        emit('update:modelValue', '')
        return
      }

      const adjusted = clampNumericValue(value)
      target.value = String(adjusted)
      emit('update:modelValue', adjusted)
      return
    }

    // ✅ Para date, time, text, etc
    emit('update:modelValue', value)
  }

  /**
   * Maneja el @blur
   */
  function onBlur(event: FocusEvent) {
    const target = event.target as HTMLInputElement
    const value = target.value

    if (props.type === 'number') {
      if (value === '') {
        emit('update:modelValue', '')
        return
      }

      const adjusted = clampNumericValue(value)
      target.value = String(adjusted)
      emit('update:modelValue', adjusted)
      return
    }

    emit('update:modelValue', value)
  }

  /**
   * Valida el valor cuando aceptan la máscara
   */
  function onAccept(event: CustomEvent) {
    // For Number masks, unmaskedValue is the clean numeric string (e.g. "1234.56")
    // detail.value is the formatted display string (e.g. "1,234.56") — Number() of that returns NaN
    const raw = event.detail.unmaskedValue ?? event.detail.value
    emit('update:modelValue', raw)
  }
</script>

<template>
  <div :class="['form-group', col, className]">
    <!-- LABEL (igual que Blade) -->
    <label v-if="label" :for="inputId">
      {{ label }}
      <span v-if="required" class="text-danger">*</span>
    </label>

    <!-- INPUT GROUP -->
    <div v-if="inputGroup" :class="['input-group', inputGroupClass]">
      <div v-if="slots.prepend" class="input-group-prepend">
        <slot name="prepend" />
      </div>

      <input
        v-if="mask"
        v-imask="mask"
        :type="type"
        :id="inputId"
        :name="name"
        :value="modelValue"
        :class="['form-control', { 'is-invalid': !!error }]"
        :readonly="readonly"
        :disabled="disabled"
        :required="required"
        :placeholder="placeholder"
        :minlength="minlength"
        :maxlength="maxlength"
        :min="min"
        :max="max"
        :step="step"
        @accept="onAccept"
      />

      <input
        v-else
        :type="type"
        :id="inputId"
        :name="name"
        :value="modelValue"
        :class="['form-control', { 'is-invalid': !!error }]"
        :readonly="readonly"
        :disabled="disabled"
        :required="required"
        :placeholder="placeholder"
        :minlength="minlength"
        :maxlength="maxlength"
        :min="min"
        :max="max"
        :step="step"
        @input="onInput"
        @blur="onBlur"
      />

      <div v-if="slots.append" class="input-group-append">
        <slot name="append" />
      </div>
    </div>

    <!-- INPUT CON MÁSCARA -->
    <input
      v-else-if="mask"
      v-imask="mask"
      :type="type"
      :id="inputId"
      :name="name"
      :value="modelValue"
      :class="['form-control', inputClass, { 'is-invalid': !!error }]"
      :readonly="readonly"
      :disabled="disabled"
      :required="required"
      :placeholder="placeholder"
      :minlength="minlength"
      :maxlength="maxlength"
      :min="min"
      :max="max"
      :step="step"
      @accept="onAccept"
    />

    <!-- INPUT SIMPLE -->
    <input
      v-else
      :type="type"
      :id="inputId"
      :name="name"
      :value="modelValue"
      :class="['form-control', inputClass, { 'is-invalid': !!error }]"
      :readonly="readonly"
      :disabled="disabled"
      :required="required"
      :placeholder="placeholder"
      :minlength="minlength"
      :maxlength="maxlength"
      :min="min"
      :max="max"
      :step="step"
      @input="onInput"
      @blur="onBlur"
    />

    <!-- ERROR MESSAGE -->
    <small v-if="error" class="text-danger d-block mt-1">
      {{ error }}
    </small>
  </div>
</template>
