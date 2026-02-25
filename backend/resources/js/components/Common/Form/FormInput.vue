<script setup lang="ts" name="FormInput">
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
    /** Fuerza is-valid / is-invalid desde fuera sin depender solo del error prop */
    inputValidState: {
      type: String as PropType<'valid' | 'invalid' | null>,
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
    accept: { type: String, default: null },
    col: { type: String, default: 'col-sm-6' },
    className: { type: String, default: '' },
    inputClass: { type: String, default: '' },
    inputGroup: { type: Boolean, default: false },
    inputGroupClass: { type: String, default: '' },
    mask: { type: [String, Object, Array] as PropType<FactoryArg>, default: null },
    /** Si true, emite el valor con formato (value) en lugar del valor sin máscara (unmaskedValue) */
    maskEmitFormatted: { type: Boolean, default: false }
  })

  const emit = defineEmits(['update:modelValue', 'focus', 'blur'])
  const slots = useSlots()

  const inputId = computed(() => props.id ?? props.name)

  /** Clases de estado: error prop tiene prioridad, luego inputValidState */
  const stateClasses = computed(() => ({
    'is-invalid': !!props.error || props.inputValidState === 'invalid',
    'is-valid':   !props.error && props.inputValidState === 'valid'
  }))

  /** Hay slot prepend o append (para mostrar input-group automáticamente) */
  const hasPrepend = computed(() => !!slots.prepend)
  const hasAppend  = computed(() => !!slots.append)
  const useInputGroup = computed(() => props.inputGroup || hasPrepend.value || hasAppend.value)

  function clampNumericValue(value: string | number): number {
    if (props.type !== 'number') return Number(value) || 0

    const num = Number(value) || 0
    const min = props.min !== null ? Number(props.min) : null
    const max = props.max !== null ? Number(props.max) : null

    if (min !== null && num < min) return min
    if (max !== null && num > max) return max
    return num
  }

  function onInput(event: Event) {
    const target = event.target as HTMLInputElement
    const value  = target.value

    if (props.type === 'number') {
      if (value === '') { emit('update:modelValue', ''); return }
      const adjusted = clampNumericValue(value)
      target.value = String(adjusted)
      emit('update:modelValue', adjusted)
      return
    }

    emit('update:modelValue', value)
  }

  function onBlur(event: FocusEvent) {
    const target = event.target as HTMLInputElement
    const value  = target.value

    if (props.type === 'number') {
      if (value === '') { emit('update:modelValue', ''); return }
      const adjusted = clampNumericValue(value)
      target.value = String(adjusted)
      emit('update:modelValue', adjusted)
      return
    }

    emit('update:modelValue', value)
    emit('blur', event)
  }

  function onFocus(event: FocusEvent) {
    emit('focus', event)
  }

  function onAccept(event: CustomEvent) {
    const val = props.maskEmitFormatted
      ? event.detail.value
      : (event.detail.unmaskedValue ?? event.detail.value)
    emit('update:modelValue', val)
  }

  function onFileChange(event: Event) {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0] ?? null
    emit('update:modelValue', file)
  }
</script>

<template>
  <div :class="['form-group', col, className]">
    <!-- LABEL -->
    <label v-if="label" :for="inputId">
      {{ label }}
      <span v-if="required" class="text-danger">*</span>
    </label>

    <!-- INPUT GROUP (explicit prop OR prepend/append slots present) -->
    <div v-if="useInputGroup" :class="['input-group', inputGroupClass]">
      <div v-if="hasPrepend" class="input-group-prepend">
        <slot name="prepend" />
      </div>

      <input
        v-if="mask"
        v-imask="mask"
        :type="type"
        :id="inputId"
        :name="name"
        :value="modelValue"
        :class="['form-control', stateClasses]"
        :readonly="readonly"
        :disabled="disabled"
        :required="required"
        :placeholder="placeholder"
        :minlength="minlength"
        :maxlength="maxlength"
        :min="min"
        :max="max"
        :step="step"
        :accept="accept"
        @accept="onAccept"
      />

      <input
        v-else
        :type="type"
        :id="inputId"
        :name="name"
        :value="modelValue"
        :class="['form-control', stateClasses]"
        :readonly="readonly"
        :disabled="disabled"
        :required="required"
        :placeholder="placeholder"
        :minlength="minlength"
        :maxlength="maxlength"
        :min="min"
        :max="max"
        :step="step"
        :accept="accept"
        @input="onInput"
        @blur="onBlur"
        @focus="onFocus"
      />

      <div v-if="hasAppend" class="input-group-append">
        <slot name="append" />
      </div>
    </div>

    <!-- FILE INPUT -->
    <input
      v-else-if="type === 'file'"
      :type="type"
      :id="inputId"
      :name="name"
      :class="['form-control-file', inputClass, stateClasses]"
      :disabled="disabled"
      :required="required"
      :accept="accept"
      @change="onFileChange"
    />

    <!-- INPUT CON MÁSCARA -->
    <input
      v-else-if="mask"
      v-imask="mask"
      :type="type"
      :id="inputId"
      :name="name"
      :value="modelValue"
      :class="['form-control', inputClass, stateClasses]"
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
      @focus="onFocus"
    />

    <!-- INPUT SIMPLE -->
    <input
      v-else
      :type="type"
      :id="inputId"
      :name="name"
      :value="modelValue"
      :class="['form-control', inputClass, stateClasses]"
      :readonly="readonly"
      :disabled="disabled"
      :required="required"
      :placeholder="placeholder"
      :minlength="minlength"
      :maxlength="maxlength"
      :min="min"
      :max="max"
      :step="step"
      :accept="accept"
      @input="onInput"
      @blur="onBlur"
      @focus="onFocus"
    />

    <!-- ERROR MESSAGE -->
    <small v-if="error" class="text-danger d-block mt-1">
      {{ error }}
    </small>
  </div>
</template>
