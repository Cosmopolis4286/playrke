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
    /** Solo con type="number": descarta decimales y bloquea "." / ",". */
    integer: { type: Boolean, default: false },
    placeholder: { type: String, default: null },
    accept: { type: String, default: null },
    col: { type: String, default: 'col-sm-6' },
    className: { type: String, default: '' },
    inputClass: { type: String, default: '' },
    inputGroup: { type: Boolean, default: false },
    inputGroupClass: { type: String, default: '' },
    mask: { type: [String, Object, Array] as PropType<FactoryArg>, default: null },
    /**
     * Si true, emite el valor con formato completo (event.detail.value).
     * Usar para máscaras con prefijos fijos (ej: teléfonos +58, +55).
     * Si false (default), emite unmaskedValue (solo los dígitos/caracteres sin máscara).
     */
    maskEmitFormatted: { type: Boolean, default: false },
    /** Texto de ayuda que se muestra debajo del campo (si no hay error) */
    hint: { type: String, default: null }
  })

  const emit = defineEmits(['update:modelValue', 'focus', 'blur'])
  const slots = useSlots()

  const inputId = computed(() => props.id ?? props.name)

  /**
   * Clases de estado: error prop tiene prioridad, luego inputValidState.
   * Dentro de input-group Bootstrap suprime el ícono de validación pero
   * mantiene el padding-right extra → se añade no-validation-icon para resetearlo.
   */
  const stateClasses = computed(() => ({
    'is-invalid': !!props.error || props.inputValidState === 'invalid',
    'is-valid': !props.error && props.inputValidState === 'valid',
    'no-validation-icon': useInputGroup.value
  }))

  /** Hay slot prepend o append (para mostrar input-group automáticamente) */
  const hasPrepend = computed(() => !!slots.prepend)
  const hasAppend = computed(() => !!slots.append)
  const useInputGroup = computed(() => props.inputGroup || hasPrepend.value || hasAppend.value)

  const imaskOptions = computed(() => {
    if (!props.mask) return null
    return props.mask
  })

  /**
   * Ajusta un valor numérico a [min, max] (y a entero si `integer`).
   * `clampMin` en false solo recorta el máximo: mientras se escribe no se
   * fuerza el mínimo, o con min=10 teclear "1" saltaría a 10 y sería imposible
   * escribir "15". El mínimo se aplica al pegar y al salir del campo (blur).
   */
  function clampNumericValue(value: string | number, clampMin: boolean): number {
    let num = Number(value) || 0
    if (props.integer) num = Math.trunc(num)
    const min = props.min !== null ? Number(props.min) : null
    const max = props.max !== null ? Number(props.max) : null
    if (max !== null && num > max) return max
    if (clampMin && min !== null && num < min) return min
    return num
  }

  function emitNumeric(target: HTMLInputElement, clampMin: boolean) {
    const value = target.value
    if (value === '') {
      emit('update:modelValue', '')
      return
    }
    const adjusted = clampNumericValue(value, clampMin)
    // Solo reescribir el DOM si el valor cambió: reescribirlo siempre borra
    // estados intermedios válidos mientras se teclea (ej. "10." camino a "10.5").
    if (adjusted !== Number(value)) target.value = String(adjusted)
    emit('update:modelValue', adjusted)
  }

  function onInput(event: Event) {
    const target = event.target as HTMLInputElement
    if (props.type === 'number') {
      emitNumeric(target, (event as InputEvent).inputType === 'insertFromPaste')
      return
    }
    emit('update:modelValue', target.value)
  }

  /** Bloquea teclas que <input type="number"> acepta pero nunca son válidas aquí (e, +, -, y "." en enteros). */
  function onKeydown(event: KeyboardEvent) {
    if (props.type !== 'number') return
    const blocked = props.integer ? ['e', 'E', '+', '-', '.', ','] : ['e', 'E', '+', '-']
    if (blocked.includes(event.key)) event.preventDefault()
  }

  function onBlur(event: FocusEvent) {
    const target = event.target as HTMLInputElement
    if (props.type === 'number') {
      emitNumeric(target, true)
    } else {
      emit('update:modelValue', target.value)
    }
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
        v-if="imaskOptions"
        v-imask="imaskOptions"
        :value="modelValue"
        :type="type"
        :id="inputId"
        :name="name"
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
        @focus="onFocus"
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
        @keydown="onKeydown"
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
      v-else-if="imaskOptions"
      v-imask="imaskOptions"
      :value="modelValue"
      :type="type"
      :id="inputId"
      :name="name"
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
        @keydown="onKeydown"
      @blur="onBlur"
      @focus="onFocus"
    />

    <!-- ERROR MESSAGE -->
    <small v-if="error" class="text-danger d-block mt-1">
      {{ error }}
    </small>
    <!-- HINT (solo si no hay error) -->
    <small v-else-if="hint" class="text-muted d-block mt-1">
      <i class="fa-solid fa-circle-info fa-fw"></i> {{ hint }}
    </small>
  </div>
</template>

<style scoped>
  /* Bootstrap agrega padding-right + background-image para el ícono de validación
   en .is-valid / .is-invalid, pero dentro de .input-group ese ícono no se muestra.
   Reseteamos solo el padding extra para evitar que el texto se corte. */
  .no-validation-icon.is-valid,
  .no-validation-icon.is-invalid {
    padding-right: 0.75rem;
    background-image: none;
  }
</style>
