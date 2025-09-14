<template>
  <div class="auth__form-item" :class="itemClasses">
    <!-- Left Icon -->
    <div v-if="props.icon || props.fieldType || fieldIcon" class="auth__input-icon auth__input-icon--left" :style="iconStyle">
      <i :class="buildIconClass(getCurrentIcon, 'transition-all duration-200')"></i>
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
    <div v-if="showRightIcons" class="auth__input-icons-right">
      <!-- Toggle Password Visibility -->
      <button
        v-if="showPasswordToggle && type === 'password'"
        type="button"
        class="auth__input-toggle"
        @click="togglePasswordVisibility"
        :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
        tabindex="-1"
      >
        <i :class="buildIconClass(getPasswordToggleIcon())"></i>
      </button>

      <!-- Loading Spinner -->
      <div v-if="loading" class="auth__input-loading">
        <i :class="buildIconClass(getIcon('loader'), 'animate-spin')"></i>
      </div>

      <!-- Validation Status Icons -->
      <div v-else-if="showValidationIcon && (isValid || hasError)" class="auth__input-status">
        <i v-if="isValid" :class="buildIconClass(getValidationIcon('valid'))" :style="buildIconStyle(getValidationIcon('valid'))"></i>
        <i v-else-if="hasError" :class="buildIconClass(getValidationIcon('invalid'))" :style="buildIconStyle(getValidationIcon('invalid'))"></i>
      </div>
    </div>
  </div>

  <!-- Error Messages -->
  <transition name="slide-down">
    <div v-if="errorMessage" class="auth__error">
      <i :class="buildIconClass(getValidationIcon('invalid'))" :style="buildIconStyle(getValidationIcon('invalid'))"></i>
      <span>{{ errorMessage }}</span>
    </div>
  </transition>

  <!-- Help Text -->
  <transition name="slide-down">
    <div v-if="helpText && !hasError" class="auth__help-text">
      <i :class="buildIconClass(getIcon('info'))" :style="buildIconStyle(getIcon('info'))"></i>
      <span>{{ helpText }}</span>
    </div>
  </transition>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { useIcons } from '../../composables/useIcons'

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
  const classes = ['auth__input']

  // State classes
  if (hasError.value) {
    classes.push('auth__input--error')
  } else if (isValid.value) {
    classes.push('auth__input--success')
  }

  if (isFocused.value) {
    classes.push('auth__input--focused')
  }

  if (props.readonly) {
    classes.push('auth__input--readonly')
  }

  // Variant classes
  classes.push(`auth__input--${props.variant}`)

  // Size classes
  classes.push(`auth__input--${props.size}`)

  // Icon padding
  if (props.icon || fieldIcon.value) {
    classes.push('auth__input--with-left-icon')
  }

  if (showRightIcons.value) {
    classes.push('auth__input--with-right-icon')
  }

  return classes.join(' ')
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
/* Component-specific animations */
.auth__input--focused {
  transform: translateY(-1px);
}

.auth__input-loading {
  @apply absolute right-3 top-1/2 transform -translate-y-1/2 text-blue-500;
}

.auth__input-success {
  @apply absolute right-3 top-1/2 transform -translate-y-1/2;
}

.auth__help-text {
  @apply text-xs mt-1 px-1 text-gray-400;
}

/* Transitions */
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease-out;
}

.slide-down-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}

.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-5px);
}

/* Input focus animation */
.auth__input {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.auth__input:focus {
  transform: translateY(-1px);
}

/* Icon animation */
.auth__input-icon {
  transition: all 0.2s ease-in-out;
}

/* Password toggle animation */
.auth__input-toggle {
  transition: all 0.2s ease-in-out;
}

.auth__input-toggle:hover {
  transform: scale(1.1);
}

.auth__input-toggle:active {
  transform: scale(0.95);
}
</style>