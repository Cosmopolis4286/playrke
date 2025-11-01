<template>
  <div class="relative">
    <!-- Label -->
    <label
      v-if="label"
      :for="inputId"
      :class="labelClasses"
    >
      {{ label }}
      <span
        v-if="required"
        class="text-error-500 ml-1"
        aria-label="requerido"
      >*</span>
    </label>

    <!-- Input Container -->
    <div class="relative">
      <!-- Left SharedIcon -->
      <div
        v-if="leftIcon"
        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
      >
        <SharedIcon
          :name="leftIcon"
          :class="iconClasses"
        />
      </div>

      <!-- Input Element -->
      <input
        :id="inputId"
        ref="inputRef"
        :type="currentType"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :required="required"
        :autocomplete="autocomplete"
        :aria-describedby="ariaDescribedby"
        :aria-invalid="hasError"
        :class="inputClasses"
        v-bind="$attrs"
        @input="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
        @keydown="handleKeydown"
      >

      <!-- Right Icons Container -->
      <div class="absolute inset-y-0 right-0 flex items-center">
        <!-- Password Toggle -->
        <button
          v-if="type === 'password'"
          type="button"
          class="px-3 text-secondary-400 hover:text-secondary-600 focus:outline-none focus:text-secondary-600 transition-colors"
          :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
          @click="togglePassword"
        >
          <SharedIcon
            :name="showPassword ? 'eyeOff' : 'eye'"
            size="sm"
          />
        </button>

        <!-- Clear Button -->
        <button
          v-if="clearable && modelValue && !disabled && !readonly"
          type="button"
          class="px-3 text-secondary-400 hover:text-secondary-600 focus:outline-none focus:text-secondary-600 transition-colors"
          aria-label="Limpiar campo"
          @click="clearInput"
        >
          <SharedIcon
            name="close"
            size="sm"
          />
        </button>

        <!-- Right SharedIcon -->
        <div
          v-if="rightIcon"
          class="px-3 pointer-events-none"
        >
          <SharedIcon
            :name="rightIcon"
            :class="iconClasses"
          />
        </div>

        <!-- Loading Spinner -->
        <div
          v-if="loading"
          class="px-3 pointer-events-none"
        >
          <div class="loading-spinner w-4 h-4 text-secondary-400" />
        </div>
      </div>
    </div>

    <!-- Help Text -->
    <p
      v-if="help && !hasError"
      :id="`${inputId}-help`"
      class="form-help"
    >
      {{ help }}
    </p>

    <!-- Error Message -->
    <p
      v-if="hasError"
      :id="`${inputId}-error`"
      class="form-error"
      role="alert"
      aria-live="polite"
    >
      {{ errorMessage }}
    </p>

    <!-- Success Message -->
    <p
      v-if="hasSuccess"
      :id="`${inputId}-success`"
      class="text-xs text-success-600 mt-1"
    >
      {{ successMessage }}
    </p>
  </div>
</template>

<script setup lang="ts">
/**
 * @fileoverview Input component - Campo de entrada optimizado
 * @description Input field con validación, iconos y estados múltiples
 */
import { computed, ref, nextTick } from 'vue'

interface Props {
  /** Valor del input */
  modelValue?: string | number
  /** Tipo de input */
  type?: 'text' | 'email' | 'password' | 'number' | 'tel' | 'url' | 'search' | 'date'
  /** Label del campo */
  label?: string
  /** Placeholder */
  placeholder?: string
  /** Texto de ayuda */
  help?: string
  /** Tamaño del input */
  size?: 'sm' | 'md' | 'lg'
  /** Estado del input */
  state?: 'default' | 'success' | 'warning' | 'error'
  /** Mensaje de error */
  error?: string
  /** Mensaje de éxito */
  success?: string
  /** Campo requerido */
  required?: boolean
  /** Campo deshabilitado */
  disabled?: boolean
  /** Campo de solo lectura */
  readonly?: boolean
  /** Icono izquierdo */
  leftIcon?: string
  /** Icono derecho */
  rightIcon?: string
  /** Botón para limpiar */
  clearable?: boolean
  /** Estado de carga */
  loading?: boolean
  /** Autocompletado */
  autocomplete?: string
  /** Validación en tiempo real */
  validateOnInput?: boolean
  /** Regex para validación */
  pattern?: string
}

interface Emits {
  /** Actualizar valor */
  'update:modelValue': [value: string | number]
  /** Evento blur */
  'blur': [event: FocusEvent]
  /** Evento focus */
  'focus': [event: FocusEvent]
  /** Evento enter */
  'enter': [event: KeyboardEvent]
  /** Evento validación */
  'validate': [value: string | number, isValid: boolean]
}

defineOptions({
  name: 'Input',
  inheritAttrs: false,
})

const props = withDefaults(defineProps<Props>(), {
  type: 'text',
  size: 'md',
  state: 'default',
  validateOnInput: true,
})

const emit = defineEmits<Emits>()

// Refs
const inputRef = ref<HTMLInputElement>()
const showPassword = ref(false)

// Unique ID
const inputId = computed(() => `input-${Math.random().toString(36).substr(2, 9)}`)

// Password toggle
const currentType = computed(() => {
  if (props.type === 'password') {
    return showPassword.value ? 'text' : 'password'
  }
  return props.type
})

// Validation
const errorMessage = computed(() => props.error)
const successMessage = computed(() => props.success)
const hasError = computed(() => props.state === 'error' || Boolean(props.error))
const hasSuccess = computed(() => props.state === 'success' || Boolean(props.success))

// ARIA
const ariaDescribedby = computed(() => {
  const ids: string[] = []

  if (props.help && !hasError.value) {
    ids.push(`${inputId.value}-help`)
  }
  if (hasError.value) {
    ids.push(`${inputId.value}-error`)
  }
  if (hasSuccess.value) {
    ids.push(`${inputId.value}-success`)
  }

  return ids.length > 0 ? ids.join(' ') : undefined
})

// Classes
const labelClasses = computed(() => 'form-label')

const inputClasses = computed(() => {
  const classes = ['input-base']

  // Size
  classes.push(`input-${props.size}`)

  // State
  if (hasError.value) {
    classes.push('input-error')
  } else if (hasSuccess.value) {
    classes.push('input-success')
  }

  // Icons padding
  if (props.leftIcon) {
    classes.push('pl-10')
  }
  if (props.rightSharedIcon || props.clearable || props.loading || props.type === 'password') {
    classes.push('pr-10')
  }

  return classes.join(' ')
})

const iconClasses = computed(() => {
  const baseClasses = 'w-4 h-4'
  if (hasError.value) {
    return `${baseClasses} text-error-400`
  } else if (hasSuccess.value) {
    return `${baseClasses} text-success-400`
  }
  return `${baseClasses} text-secondary-400`
})

// Methods
const togglePassword = () => {
  showPassword.value = !showPassword.value
}

const clearInput = () => {
  emit('update:modelValue', '')
  nextTick(() => {
    inputRef.value?.focus()
  })
}

const validateInput = (value: string | number) => {
  if (!props.validateOnInput) { return }

  let isValid = true

  // Required validation
  if (props.required && !value) {
    isValid = false
  }

  // Pattern validation
  if (props.pattern && value) {
    const regex = new RegExp(props.pattern)
    isValid = regex.test(String(value))
  }

  // Email validation
  if (props.type === 'email' && value) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    isValid = emailRegex.test(String(value))
  }

  emit('validate', value, isValid)
}

// Event handlers
const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  const value = props.type === 'number' ? Number(target.value) : target.value
  emit('update:modelValue', value)

  validateInput(value)
}

const handleBlur = (event: FocusEvent) => {
  emit('blur', event)
  if (props.modelValue) {
    validateInput(props.modelValue)
  }
}

const handleFocus = (event: FocusEvent) => {
  emit('focus', event)
}

const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Enter') {
    emit('enter', event)
  }
}

// Public methods
const focus = () => {
  inputRef.value?.focus()
}

const blur = () => {
  inputRef.value?.blur()
}

const select = () => {
  inputRef.value?.select()
}

defineExpose({
  focus,
  blur,
  select,
  inputRef,
})
</script>
