<template>
  <div
    class="form-field"
    :class="fieldClasses"
  >
    <!-- Label -->
    <label
      v-if="label"
      :for="fieldId"
      class="@apply block text-sm font-medium text-gray-700 mb-2"
      :class="{ 'text-red-600': hasError }"
    >
      {{ label }}
      <span
        v-if="required"
        class="text-red-500 ml-1"
      >*</span>
    </label>

    <!-- Field Container -->
    <div class="relative">
      <!-- Input Types -->
      <input
        v-if="isInputType"
        :id="fieldId"
        :value="modelValue"
        :type="actualInputType"
        :name="name"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :required="required"
        :autocomplete="autocomplete"
        :min="min"
        :max="max"
        :step="step"
        :pattern="pattern"
        :maxlength="maxlength"
        :class="inputClasses"
        @input="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
        @keydown="handleKeydown"
      >

      <!-- Textarea -->
      <textarea
        v-else-if="type === 'textarea'"
        :id="fieldId"
        :value="modelValue"
        :name="name"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :required="required"
        :rows="rows"
        :maxlength="maxlength"
        :class="inputClasses"
        @input="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
      />

      <!-- Select -->
      <select
        v-else-if="type === 'select'"
        :id="fieldId"
        :value="modelValue"
        :name="name"
        :disabled="disabled"
        :required="required"
        :class="inputClasses"
        @change="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
      >
        <option
          v-if="placeholder"
          value=""
          disabled
        >
          {{ placeholder }}
        </option>
        <option
          v-for="option in selectOptions"
          :key="getOptionValue(option)"
          :value="getOptionValue(option)"
        >
          {{ getOptionLabel(option) }}
        </option>
      </select>

      <!-- Checkbox -->
      <div
        v-else-if="type === 'checkbox'"
        class="flex items-center"
      >
        <input
          :id="fieldId"
          :checked="modelValue"
          type="checkbox"
          :name="name"
          :disabled="disabled"
          :required="required"
          class="@apply h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
          @change="handleInput"
        >
        <label
          v-if="checkboxLabel"
          :for="fieldId"
          class="@apply ml-2 text-sm text-gray-700"
        >
          {{ checkboxLabel }}
        </label>
      </div>

      <!-- Radio Group -->
      <div
        v-else-if="type === 'radio'"
        class="space-y-2"
      >
        <div
          v-for="option in radioOptions"
          :key="getOptionValue(option)"
          class="flex items-center"
        >
          <input
            :id="`${fieldId}-${getOptionValue(option)}`"
            :checked="modelValue === getOptionValue(option)"
            type="radio"
            :name="name"
            :value="getOptionValue(option)"
            :disabled="disabled"
            :required="required"
            class="@apply h-4 w-4 text-primary-600 border-gray-300 focus:ring-primary-500"
            @change="handleInput"
          >
          <label
            :for="`${fieldId}-${getOptionValue(option)}`"
            class="@apply ml-2 text-sm text-gray-700"
          >
            {{ getOptionLabel(option) }}
          </label>
        </div>
      </div>

      <!-- SharedIcon Slots -->
      <div
        v-if="$slots.prefix || prefixIcon"
        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
      >
        <slot name="prefix">
          <SharedIcon
            v-if="prefixIcon"
            :name="prefixIcon"
            class="w-4 h-4 text-gray-400"
          />
        </slot>
      </div>

      <div
        v-if="$slots.suffix || suffixSharedIcon || showPasswordToggle"
        class="absolute inset-y-0 right-0 pr-3 flex items-center"
      >
        <slot name="suffix">
          <!-- Password Toggle -->
          <button
            v-if="showPasswordToggle"
            type="button"
            class="@apply text-gray-400 hover:text-gray-600 focus:outline-none"
            @click="togglePasswordVisibility"
          >
            <SharedIcon
              :name="passwordVisible ? 'eye-slash' : 'eye'"
              class="w-4 h-4"
            />
          </button>

          <!-- Suffix SharedIcon -->
          <SharedIcon
            v-else-if="suffixIcon"
            :name="suffixIcon"
            class="w-4 h-4 text-gray-400"
          />
        </slot>
      </div>

      <!-- Loading Spinner -->
      <div
        v-if="loading"
        class="absolute inset-y-0 right-0 pr-3 flex items-center"
      >
        <UiLoadingSpinner size="sm" />
      </div>
    </div>

    <!-- Help Text -->
    <p
      v-if="helpText && !hasError"
      class="@apply mt-1 text-xs text-gray-500"
    >
      {{ helpText }}
    </p>

    <!-- Error Message -->
    <UiFormValidation
      v-if="hasError"
      :errors="errorMessages"
      class="mt-1"
    />

    <!-- Character Count -->
    <div
      v-if="showCharCount && maxlength"
      class="@apply mt-1 text-xs text-gray-500 text-right"
    >
      {{ characterCount }}/{{ maxlength }}
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * @fileoverview FormField - Campo de formulario inteligente
 * @description Componente universal para todos los tipos de campos de formulario
 */

import { computed, ref, useId, useSlots, watch } from 'vue'

interface SelectOption {
  label: string
  value: string | number
  disabled?: boolean
}

interface Props {
  // Core
  type?: 'text' | 'email' | 'password' | 'number' | 'tel' | 'url' | 'search' | 'textarea' | 'select' | 'checkbox' | 'radio' | 'date' | 'time' | 'datetime-local'
  modelValue?: unknown
  name?: string
  label?: string
  placeholder?: string
  helpText?: string

  // Validation
  required?: boolean
  disabled?: boolean
  readonly?: boolean
  errors?: string[]

  // Input specific
  autocomplete?: string
  min?: string | number
  max?: string | number
  step?: string | number
  pattern?: string
  maxlength?: number

  // Textarea specific
  rows?: number

  // Select specific
  options?: SelectOption[] | string[]

  // Checkbox specific
  checkboxLabel?: string

  // Radio specific
  radioOptions?: SelectOption[] | string[]

  // UI
  size?: 'sm' | 'md' | 'lg'
  variant?: 'default' | 'filled' | 'outlined'
  prefixIcon?: string
  suffixIcon?: string
  showCharCount?: boolean
  loading?: boolean

  // Advanced
  debounce?: number
  validateOnBlur?: boolean
  validateOnInput?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  type: 'text',
  size: 'md',
  variant: 'default',
  rows: 3,
  debounce: 0,
  validateOnBlur: true,
  validateOnInput: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: unknown]
  'input': [event: Event]
  'blur': [event: Event]
  'focus': [event: Event]
  'keydown': [event: KeyboardEvent]
  'validation': [isValid: boolean, errors: string[]]
}>()

// Slots
const slots = useSlots()

// State
const fieldId = useId()
const passwordVisible = ref(false)
const isFocused = ref(false)
const internalErrors = ref<string[]>([])
let debounceTimer: NodeJS.Timeout | null = null

// Computed
const isInputType = computed(() => ['text', 'email', 'password', 'number', 'tel', 'url', 'search', 'date', 'time', 'datetime-local'].includes(props.type))

const actualInputType = computed(() => {
  if (props.type === 'password') {
    return passwordVisible.value ? 'text' : 'password'
  }
  return props.type
})

const selectOptions = computed(() => {
  if (!props.options) { return [] }

  return props.options.map(option =>
    typeof option === 'string'
      ? { label: option, value: option }
      : option,
  )
})

const radioOptions = computed(() => {
  if (!props.radioOptions) { return [] }

  return props.radioOptions.map(option =>
    typeof option === 'string'
      ? { label: option, value: option }
      : option,
  )
})

const errorMessages = computed(() => [...(props.errors || []), ...internalErrors.value])

const hasError = computed(() => errorMessages.value.length > 0)

const showPasswordToggle = computed(() => props.type === 'password')

const characterCount = computed(() => String(props.modelValue || '').length)

const fieldClasses = computed(() => [
  'form-field',
  `form-field--${props.size}`,
  `form-field--${props.variant}`,
  {
    'form-field--error': hasError.value,
    'form-field--disabled': props.disabled,
    'form-field--focused': isFocused.value,
    'form-field--required': props.required,
  },
])

const inputClasses = computed(() => {
  const baseClasses = [
    '@apply w-full border rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-0',
  ]

  // Size classes
  const sizeClasses = {
    sm: 'px-2 py-1 text-sm',
    md: 'px-3 py-2 text-sm',
    lg: 'px-4 py-3 text-base',
  }

  // State classes
  if (hasError.value) {
    baseClasses.push('border-red-300 focus:border-red-500 focus:ring-red-500')
  } else {
    baseClasses.push('border-gray-300 focus:border-primary-500 focus:ring-primary-500')
  }

  if (props.disabled) {
    baseClasses.push('bg-gray-50 text-gray-500 cursor-not-allowed')
  } else if (props.readonly) {
    baseClasses.push('bg-gray-50')
  } else {
    baseClasses.push('bg-white')
  }

  // SharedIcon padding
  if (props.prefixSharedIcon || slots.prefix) {
    baseClasses.push('pl-9')
  }

  if (props.suffixSharedIcon || slots.suffix || showPasswordToggle.value || props.loading) {
    baseClasses.push('pr-9')
  }

  return [
    ...baseClasses,
    sizeClasses[props.size],
  ].join(' ')
})

// Methods
const getOptionValue = (option: SelectOption | string) => typeof option === 'string' ? option : option.value

const getOptionLabel = (option: SelectOption | string) => typeof option === 'string' ? option : option.label

const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  let value: string | number | boolean = target.value

  // Type conversion
  if (props.type === 'number') {
    value = target.valueAsNumber
  } else if (props.type === 'checkbox') {
    value = target.checked
  }

  if (props.debounce > 0) {
    if (debounceTimer) {
      clearTimeout(debounceTimer)
    }
    debounceTimer = setTimeout(() => {
      emit('update:modelValue', value)
      if (props.validateOnInput) {
        validateField()
      }
    }, props.debounce)
  } else {
    emit('update:modelValue', value)
    if (props.validateOnInput) {
      validateField()
    }
  }

  emit('input', event)
}

const handleBlur = (event: Event) => {
  isFocused.value = false

  if (props.validateOnBlur) {
    validateField()
  }

  emit('blur', event)
}

const handleFocus = (event: Event) => {
  isFocused.value = true
  emit('focus', event)
}

const handleKeydown = (event: KeyboardEvent) => {
  emit('keydown', event)
}

/**
 * Validadores individuales para FormField
 * @description Reduce complejidad mediante extracción de validadores
 */
const fieldValidators = {
  required: (value: any, type: string) => {
    if (type === 'checkbox' && !value) {
      return 'Este campo es obligatorio'
    }
    if (!value && value !== 0) {
      return 'Este campo es obligatorio'
    }
    return null
  },

  email: (value: any) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (value && !emailRegex.test(value)) {
      return 'Formato de email inválido'
    }
    return null
  },

  numberRange: (value: any, min?: number, max?: number) => {
    if (value === null || value === undefined || value === '') { return null }

    const numValue = Number(value)
    if (min !== undefined && numValue < min) {
      return `Valor mínimo: ${min}`
    }
    if (max !== undefined && numValue > max) {
      return `Valor máximo: ${max}`
    }
    return null
  },

  pattern: (value: any, pattern: string) => {
    if (value && pattern) {
      const regex = new RegExp(pattern)
      if (!regex.test(value)) {
        return 'Formato inválido'
      }
    }
    return null
  },

  maxLength: (value: any, maxlength?: number) => {
    if (maxlength && value && String(value).length > maxlength) {
      return `Máximo ${maxlength} caracteres`
    }
    return null
  },
}

/**
 * Valida el campo usando validadores individuales
 * @complexity Reducida de 23 a ~6 mediante extracción de validadores
 */
const validateField = () => {
  const errors: string[] = []
  const value = props.modelValue

  // Ejecutar validadores y agregar errores
  if (props.required) {
    const error = fieldValidators.required(value, props.type)
    if (error) { errors.push(error) }
  }

  if (props.type === 'email') {
    const error = fieldValidators.email(value)
    if (error) { errors.push(error) }
  }

  if (props.type === 'number') {
    const error = fieldValidators.numberRange(value, props.min, props.max)
    if (error) { errors.push(error) }
  }

  if (props.pattern) {
    const error = fieldValidators.pattern(value, props.pattern)
    if (error) { errors.push(error) }
  }

  if (props.maxlength) {
    const error = fieldValidators.maxLength(value, props.maxlength)
    if (error) { errors.push(error) }
  }

  internalErrors.value = errors
  const isValid = errors.length === 0

  emit('validation', isValid, errors)
  return isValid
}

const togglePasswordVisibility = () => {
  passwordVisible.value = !passwordVisible.value
}

// Watch for external validation
watch(() => props.errors, () => {
  const isValid = !props.errors || props.errors.length === 0
  emit('validation', isValid, props.errors || [])
}, { immediate: true })

// Expose validation method
defineExpose({
  validate: validateField,
  focus: () => {
    const input = document.getElementById(fieldId) as HTMLElement
    input?.focus()
  },
  blur: () => {
    const input = document.getElementById(fieldId) as HTMLElement
    input?.blur()
  },
})
</script>

<style scoped>
.form-field {
  @apply mb-4;
}

.form-field--sm {
  @apply mb-3;
}

.form-field--lg {
  @apply mb-6;
}

/* Hide number input spinners */
.form-field input[type="number"]::-webkit-outer-spin-button,
.form-field input[type="number"]::-webkit-inner-spin-button {
  -webkit-appearance: none;
  appearance: none;
  margin: 0;
}

.form-field input[type="number"] {
  -moz-appearance: textfield;
  appearance: textfield;
}

/* Focus states */
.form-field--focused {
  /* Additional focus styles can be added here */
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
  .form-field * {
    transition: none !important;
  }
}
</style>
