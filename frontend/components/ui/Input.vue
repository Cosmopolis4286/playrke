<template>
  <div class="space-y-2">
    <!-- Label -->
    <label 
      v-if="label" 
      :for="inputId"
      :class="labelClasses"
    >
      {{ label }}
      <span v-if="required" class="text-red-500 ml-1">*</span>
    </label>

    <!-- Input Container -->
    <div class="relative">
      <!-- Icon Left -->
      <div 
        v-if="iconLeft" 
        class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none z-10"
      >
        <component :is="iconLeft" class="w-5 h-5" />
      </div>

      <!-- Input Element -->
      <input
        :id="inputId"
        v-model="inputValue"
        :type="inputType"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :class="inputClasses"
        v-bind="$attrs"
        @focus="handleFocus"
        @blur="handleBlur"
        @input="handleInput"
      />

      <!-- Icon Right / Clear / Password Toggle -->
      <div 
        v-if="iconRight || clearable || isPassword" 
        class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-2"
      >
        <!-- Clear Button -->
        <button
          v-if="clearable && inputValue && !disabled"
          type="button"
          class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-full hover:bg-gray-100"
          @click="clearInput"
        >
          <XMarkIcon class="w-4 h-4" />
        </button>

        <!-- Password Toggle -->
        <button
          v-if="isPassword"
          type="button"
          class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-full hover:bg-gray-100"
          @click="togglePassword"
        >
          <EyeIcon v-if="showPassword" class="w-4 h-4" />
          <EyeSlashIcon v-else class="w-4 h-4" />
        </button>

        <!-- Icon Right -->
        <component 
          v-if="iconRight" 
          :is="iconRight" 
          class="w-5 h-5 text-gray-400" 
        />
      </div>

      <!-- Loading Spinner -->
      <div 
        v-if="loading" 
        class="absolute right-3 top-1/2 -translate-y-1/2"
      >
        <div class="w-5 h-5 border-2 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
      </div>

      <!-- Focus Ring -->
      <div 
        v-if="focused && !error"
        class="absolute inset-0 rounded-lg border-2 border-blue-500 pointer-events-none animate-pulse-border"
      ></div>

      <!-- Error Ring -->
      <div 
        v-if="error"
        class="absolute inset-0 rounded-lg border-2 border-red-500 pointer-events-none"
      ></div>
    </div>

    <!-- Help Text / Error -->
    <div v-if="helpText || error" class="flex items-start gap-2">
      <!-- Error Icon -->
      <ExclamationCircleIcon 
        v-if="error" 
        class="w-4 h-4 text-red-500 mt-0.5 flex-shrink-0" 
      />
      
      <!-- Success Icon -->
      <CheckCircleIcon 
        v-else-if="success" 
        class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" 
      />

      <!-- Text -->
      <p :class="helpTextClasses">
        {{ error || helpText }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch, type Component } from 'vue'
import { 
  XMarkIcon, 
  EyeIcon, 
  EyeSlashIcon, 
  ExclamationCircleIcon, 
  CheckCircleIcon 
} from '@heroicons/vue/24/outline'

interface Props {
  modelValue?: string | number
  type?: 'text' | 'email' | 'password' | 'number' | 'tel' | 'url' | 'search'
  label?: string
  placeholder?: string
  helpText?: string
  error?: string
  success?: boolean
  required?: boolean
  disabled?: boolean
  readonly?: boolean
  loading?: boolean
  clearable?: boolean
  iconLeft?: Component
  iconRight?: Component
  size?: 'sm' | 'md' | 'lg'
  variant?: 'default' | 'filled' | 'outlined'
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  type: 'text',
  size: 'md',
  variant: 'default'
})

const emit = defineEmits<{
  'update:modelValue': [value: string | number]
  focus: [event: FocusEvent]
  blur: [event: FocusEvent]
  input: [event: InputEvent]
}>()

const focused = ref(false)
const showPassword = ref(false)
const inputId = `input-${Math.random().toString(36).substr(2, 9)}`

const isPassword = computed(() => props.type === 'password')
const inputType = computed(() => {
  if (isPassword.value) {
    return showPassword.value ? 'text' : 'password'
  }
  return props.type
})

const inputValue = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const labelClasses = computed(() => [
  'block text-sm font-medium',
  props.error ? 'text-red-700' : 'text-gray-700',
  props.disabled ? 'opacity-50' : ''
])

const inputClasses = computed(() => {
  const base = [
    'w-full transition-all duration-200 outline-none',
    'placeholder:text-gray-400',
    'disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-50',
    'read-only:bg-gray-50 read-only:cursor-default'
  ]

  // Tamaños
  const sizes = {
    sm: 'px-3 py-2 text-sm min-h-[36px]',
    md: 'px-4 py-2.5 text-base min-h-[42px]',
    lg: 'px-5 py-3 text-lg min-h-[48px]'
  }

  // Variantes
  const variants = {
    default: [
      'bg-white border border-gray-300 rounded-lg',
      'hover:border-gray-400',
      'focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500'
    ],
    filled: [
      'bg-gray-50 border border-transparent rounded-lg',
      'hover:bg-gray-100',
      'focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500'
    ],
    outlined: [
      'bg-transparent border-2 border-gray-200 rounded-lg',
      'hover:border-gray-300',
      'focus:border-blue-500'
    ]
  }

  // Estados
  const states = []
  if (props.error) {
    states.push('border-red-500 focus:border-red-500 focus:ring-red-500/20')
  } else if (props.success) {
    states.push('border-green-500 focus:border-green-500 focus:ring-green-500/20')
  }

  // Padding para iconos
  const iconPadding = []
  if (props.iconLeft) iconPadding.push('pl-11')
  if (props.iconRight || props.clearable || isPassword.value || props.loading) {
    iconPadding.push('pr-11')
  }

  return [
    ...base,
    sizes[props.size],
    ...variants[props.variant],
    ...states,
    ...iconPadding
  ].filter(Boolean).join(' ')
})

const helpTextClasses = computed(() => [
  'text-sm',
  props.error ? 'text-red-600' : props.success ? 'text-green-600' : 'text-gray-500'
])

const handleFocus = (event: FocusEvent) => {
  focused.value = true
  emit('focus', event)
}

const handleBlur = (event: FocusEvent) => {
  focused.value = false
  emit('blur', event)
}

const handleInput = (event: InputEvent) => {
  emit('input', event)
}

const clearInput = () => {
  inputValue.value = ''
}

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

// Watchers
watch(() => props.error, (newError) => {
  if (newError) {
    focused.value = false
  }
})
</script>

<style scoped>
@keyframes pulse-border {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse-border {
  animation: pulse-border 2s ease-in-out infinite;
}
</style>