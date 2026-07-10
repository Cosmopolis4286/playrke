<template>
  <div class="relative">
    <!-- Input Container -->
    <div class="relative">
      <!-- Left Icon -->
      <div v-if="props.icon || props.fieldType || fieldIcon"
           class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
        <i :class="buildIconClass(getCurrentIcon, 'text-gray-400 text-lg transition-all duration-200')"
           :style="iconStyle"></i>
      </div>

      <input
        :id="inputId"
        :ref="inputId"
        v-model="inputValue"
        :type="computedType"
        :placeholder="placeholder"
        :aria-label="ariaLabel"
        :class="inputClasses"
        :maxlength="maxlength"
        :readonly="readonly"
        :required="required"
        :autocomplete="autocomplete"
        @input="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
        @keydown.enter="handleEnter"
      />

      <!-- Right Icons -->
      <div v-if="showRightIcons" class="absolute inset-y-0 right-0 pr-4 flex items-center space-x-2">
        <!-- Loading Spinner -->
        <div v-if="loading" class="text-indigo-500">
          <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        </div>

        <!-- Validation Status Icons -->
        <div v-else-if="showValidationIcon && (isValid || hasError)">
          <i v-if="isValid" class="fas fa-check-circle text-green-500 text-lg"></i>
          <i v-else-if="hasError" class="fas fa-exclamation-circle text-red-500 text-lg"></i>
        </div>

        <!-- Toggle Password Visibility -->
        <button
          v-if="showPasswordToggle && type === 'password'"
          type="button"
          class="text-gray-400 hover:text-white transition-colors duration-200 focus:outline-none"
          @click="togglePasswordVisibility"
          :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
          tabindex="-1"
        >
          <i class="fas text-lg" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
        </button>
      </div>
    </div>

    <!-- Error Messages -->
    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 transform -translate-y-2"
      enter-to-class="opacity-100 transform translate-y-0"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 transform translate-y-0"
      leave-to-class="opacity-0 transform -translate-y-2">
      <div v-if="errorMessage" class="mt-2 text-red-400 text-sm flex items-center space-x-1">
        <i class="fas fa-exclamation-circle"></i>
        <span>{{ errorMessage }}</span>
      </div>
    </transition>

    <!-- Help Text -->
    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 transform -translate-y-2"
      enter-to-class="opacity-100 transform translate-y-0"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 transform translate-y-0"
      leave-to-class="opacity-0 transform -translate-y-2">
      <div v-if="helpText && !hasError" class="mt-2 text-gray-400 text-sm flex items-center space-x-1">
        <i class="fas fa-info-circle"></i>
        <span>{{ helpText }}</span>
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { useIcons } from '@js/composables/utils/useIcons'

const { getIcon, getFieldIcon, getValidationIcon, buildIconClass, buildIconStyle } = useIcons()

// Types
interface Props {
  modelValue?: string | number
  type?: 'text' | 'email' | 'password' | 'tel' | 'url' | 'search'
  placeholder?: string
  ariaLabel?: string
  icon?: string
  fieldType?: string // For automatic icon detection
  variant?: 'default' | 'filled' | 'outlined' | 'underlined'
  size?: 'sm' | 'md' | 'lg'
  maxlength?: number
  readonly?: boolean
  required?: boolean
  autocomplete?: string
  showPasswordToggle?: boolean
  showValidationIcon?: boolean
  loading?: boolean
  error?: string | null
  helpText?: string
  validation?: (value: string) => boolean | string
  debounceMs?: number
  autofocus?: boolean
  animate?: boolean
}

// Props
const props = withDefaults(defineProps<Props>(), {
  type: 'text',
  placeholder: '',
  ariaLabel: '',
  icon: '',
  fieldType: '',
  variant: 'default',
  size: 'md',
  maxlength: 524288,
  readonly: false,
  required: false,
  autocomplete: 'off',
  showPasswordToggle: false,
  showValidationIcon: true,
  loading: false,
  error: null,
  helpText: '',
  debounceMs: 300,
  autofocus: false,
  animate: true,
})

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: string]
  'focus': [event: FocusEvent]
  'blur': [event: FocusEvent]
  'enter': [event: KeyboardEvent]
  'validate': [isValid: boolean, message?: string]
}>()

// State
const inputValue = ref<string>(String(props.modelValue || ''))
const showPassword = ref(false)
const isFocused = ref(false)
const isValidated = ref(false)
const validationMessage = ref<string>('')
const debounceTimer = ref<NodeJS.Timeout>()

// Computed
const inputId = computed(() => `auth-input-${Math.random().toString(36).substr(2, 9)}`)

const computedType = computed(() => {
  if (props.type === 'password' && showPassword.value) {
    return 'text'
  }
  return props.type
})

const hasError = computed(() => {
  return !!(props.error || (isValidated.value && validationMessage.value && typeof validationMessage.value === 'string' && validationMessage.value !== ''))
})

const isValid = computed(() => {
  return isValidated.value && !hasError.value && inputValue.value.trim() !== ''
})

const errorMessage = computed(() => {
  if (props.error) return props.error
  if (isValidated.value && typeof validationMessage.value === 'string' && validationMessage.value !== '') {
    return validationMessage.value
  }
  return ''
})

const fieldIcon = computed(() => {
  if (props.icon) return null
  if (props.fieldType) return getFieldIcon(props.fieldType)
  if (props.type) return getFieldIcon(props.type)
  return null
})

const getCurrentIcon = computed(() => {
  try {
    if (props.icon) {
      return { name: props.icon, type: 'fontawesome' as const }
    }
    const fallback = getIcon('help')
    return fieldIcon.value || fallback
  } catch (error) {
    console.error('Error in getCurrentIcon:', error)
    return { name: 'ti ti-help', type: 'tabler' as const }
  }
})

const showRightIcons = computed(() => {
  return props.showPasswordToggle || props.loading || props.showValidationIcon
})

const getPasswordToggleIcon = () => {
  return showPassword.value ? getIcon('eyeOff') : getIcon('eye')
}

const itemClasses = computed(() => {
  const classes = ['auth__form-item']

  // State classes
  if (hasError.value) classes.push('auth__form-item--error')
  if (isValid.value) classes.push('auth__form-item--success')
  if (isFocused.value) classes.push('auth__form-item--focused')
  if (props.loading) classes.push('auth__form-item--loading')

  // Variant classes
  classes.push(`auth__form-item--${props.variant}`)

  // Size classes
  classes.push(`auth__form-item--${props.size}`)

  // Animation
  if (props.animate) classes.push('auth__form-item--animated')

  return classes
})


const iconStyle = computed(() => {
  const icon = getCurrentIcon.value
  const style = buildIconStyle(icon)

  if (props.animate && (isFocused.value || inputValue.value)) {
    style.transform = 'scale(1.1)'
    style.color = 'var(--color-primary-electric)'
  }

  return style
})

const inputClasses = computed(() => {
  const baseClasses = 'w-full py-4 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-400 focus:outline-none transition-all duration-200 backdrop-blur-sm'

  let classes = baseClasses

  // Padding based on icons
  if (props.icon || fieldIcon.value) {
    classes += ' pl-12'
  } else {
    classes += ' pl-4'
  }

  if (showRightIcons.value) {
    classes += ' pr-12'
  } else {
    classes += ' pr-4'
  }

  // State classes
  if (hasError.value) {
    classes += ' border-red-400 focus:ring-red-500 focus:border-red-400'
  } else if (isValid.value) {
    classes += ' border-green-400 focus:ring-green-500 focus:border-green-400'
  } else {
    classes += ' focus:ring-2 focus:ring-indigo-500 focus:border-transparent'
  }

  if (isFocused.value) {
    classes += ' transform scale-[1.02]'
  }

  if (props.readonly) {
    classes += ' opacity-60 cursor-not-allowed'
  }

  // Size variants
  if (props.size === 'sm') {
    classes += ' py-3 text-sm'
  } else if (props.size === 'lg') {
    classes += ' py-5 text-lg'
  }

  return classes
})


// Methods
const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value
}

const validateInput = (value: string) => {
  if (!props.validation || !value.trim()) {
    validationMessage.value = ''
    isValidated.value = false
    return
  }

  const result = props.validation(value)

  if (typeof result === 'boolean') {
    isValidated.value = true
    validationMessage.value = result ? '' : 'Valor inválido'
    emit('validate', result, result ? '' : 'Valor inválido')
  } else {
    isValidated.value = true
    validationMessage.value = result
    emit('validate', result === '', result)
  }
}

const debouncedValidate = (value: string) => {
  if (debounceTimer.value) {
    clearTimeout(debounceTimer.value)
  }

  debounceTimer.value = setTimeout(() => {
    validateInput(value)
  }, props.debounceMs)
}

// Event Handlers
const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  let value = target.value

  // Apply maxlength for certain input types
  if (props.maxlength && value.length > props.maxlength) {
    value = value.slice(0, props.maxlength)
    target.value = value
  }

  inputValue.value = value
  emit('update:modelValue', value)

  // Debounced validation
  debouncedValidate(value)
}

const handleFocus = (event: FocusEvent) => {
  isFocused.value = true
  emit('focus', event)
}

const handleBlur = (event: FocusEvent) => {
  isFocused.value = false

  // Validate immediately on blur
  if (inputValue.value.trim()) {
    validateInput(inputValue.value)
  }

  emit('blur', event)
}

const handleEnter = (event: KeyboardEvent) => {
  emit('enter', event)
}

// Focus method for external use
const focus = async () => {
  await nextTick()
  const input = document.getElementById(inputId.value) as HTMLInputElement
  input?.focus()
}

// Watch for external model changes
watch(() => props.modelValue, (newValue) => {
  inputValue.value = String(newValue || '')
})

// Watch for external error changes
watch(() => props.error, (newError) => {
  if (newError) {
    isValidated.value = true
  }
})

// Lifecycle
onMounted(() => {
  if (props.autofocus) {
    focus()
  }
})

// Expose focus method
defineExpose({
  focus,
})
</script>

<style scoped>
/* Custom focus ring for better visibility */
.focus\:ring-2:focus {
  box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.5);
}

.focus\:ring-red-500:focus {
  box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.5);
}

.focus\:ring-green-500:focus {
  box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.5);
}

/* Enhanced backdrop blur for input */
.backdrop-blur-sm {
  backdrop-filter: blur(4px);
}

/* Smooth transitions */
input {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Icon animations */
i {
  transition: all 0.2s ease-in-out;
}

/* Hover effects for interactive elements */
button:hover i {
  transform: scale(1.1);
}

button:active i {
  transform: scale(0.95);
}

/* Reduced motion preferences */
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
</style>