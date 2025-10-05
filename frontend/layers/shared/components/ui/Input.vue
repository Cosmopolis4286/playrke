<!--
  Componente Input reutilizable.

  Campo de entrada con validación, estados de error,
  soporte para iconos y diferentes variantes.
-->

<template>
  <div class="input-wrapper">
    <!-- Label -->
    <label
      v-if="label"
      :for="inputId"
      :class="labelClasses"
    >
      {{ label }}
      <span v-if="required" class="text-destructive ml-1">*</span>
    </label>

    <!-- Contenedor del input -->
    <div class="relative" :class="{ 'mt-1': label }">
      <!-- Icono izquierdo -->
      <div
        v-if="iconLeft"
        class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none"
      >
        <Icon
          :name="iconLeft"
          :class="iconClasses"
        />
      </div>

      <!-- Input -->
      <input
        :id="inputId"
        :type="type"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :class="inputClasses"
        :value="modelValue"
        v-bind="$attrs"
        @input="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
      />

      <!-- Icono derecho -->
      <div
        v-if="iconRight || isPassword"
        class="absolute inset-y-0 right-0 flex items-center pr-3"
      >
        <!-- Botón toggle para password -->
        <button
          v-if="isPassword"
          type="button"
          class="text-muted-foreground hover:text-foreground transition-colors"
          @click="togglePasswordVisibility"
        >
          <Icon
            :name="showPassword ? 'lucide:eye-off' : 'lucide:eye'"
            class="w-4 h-4"
          />
        </button>

        <!-- Icono derecho normal -->
        <Icon
          v-else-if="iconRight"
          :name="iconRight"
          :class="iconClasses"
        />
      </div>
    </div>

    <!-- Mensaje de ayuda o error -->
    <p
      v-if="helpText || errorMessage"
      :class="messageClasses"
    >
      {{ errorMessage || helpText }}
    </p>
  </div>
</template>

<script setup>
// Props
const props = defineProps({
  // Modelo de datos
  modelValue: {
    type: [String, Number],
    default: ''
  },

  // Configuración básica
  type: {
    type: String,
    default: 'text'
  },

  label: String,
  placeholder: String,
  helpText: String,

  // Estados
  disabled: {
    type: Boolean,
    default: false
  },

  readonly: {
    type: Boolean,
    default: false
  },

  required: {
    type: Boolean,
    default: false
  },

  // Validación
  error: {
    type: Boolean,
    default: false
  },

  errorMessage: String,

  // Iconos
  iconLeft: String,
  iconRight: String,

  // Variante
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'admin', 'player', 'gaming'].includes(value)
  },

  // Tamaño
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'blur', 'focus'])

// Estado interno
const showPassword = ref(false)
const inputId = computed(() => `input-${Math.random().toString(36).substr(2, 9)}`)

// Computed
const isPassword = computed(() => props.type === 'password')

const currentType = computed(() => {
  if (isPassword.value) {
    return showPassword.value ? 'text' : 'password'
  }
  return props.type
})

const labelClasses = computed(() => {
  const classes = ['block text-sm font-medium']

  if (props.variant === 'gaming' || props.variant === 'player') {
    classes.push('text-white')
  } else {
    classes.push('text-foreground')
  }

  return classes.join(' ')
})

const inputClasses = computed(() => {
  const classes = [
    'block w-full rounded-md border transition-colors',
    'focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2'
  ]

  // Tamaños
  switch (props.size) {
    case 'sm':
      classes.push('px-3 py-1.5 text-sm')
      break
    case 'lg':
      classes.push('px-4 py-3 text-lg')
      break
    default:
      classes.push('px-3 py-2')
  }

  // Padding para iconos
  if (props.iconLeft) classes.push('pl-10')
  if (props.iconRight || isPassword.value) classes.push('pr-10')

  // Estados
  if (props.error) {
    classes.push('border-destructive focus:border-destructive focus:ring-destructive')
  } else {
    classes.push('border-border focus:border-ring')
  }

  if (props.disabled) {
    classes.push('opacity-50 cursor-not-allowed bg-muted')
  } else if (props.readonly) {
    classes.push('bg-muted')
  } else {
    classes.push('bg-background')
  }

  // Variantes
  switch (props.variant) {
    case 'gaming':
    case 'player':
      classes.push('bg-slate-800 border-slate-600 text-white placeholder-slate-400')
      classes.push('focus:border-blue-500 focus:ring-blue-500')
      break
    case 'admin':
      classes.push('border-slate-300 dark:border-slate-600')
      break
    default:
      classes.push('text-foreground placeholder-muted-foreground')
  }

  return classes.join(' ')
})

const iconClasses = computed(() => {
  const classes = ['w-4 h-4']

  if (props.error) {
    classes.push('text-destructive')
  } else if (props.variant === 'gaming' || props.variant === 'player') {
    classes.push('text-slate-400')
  } else {
    classes.push('text-muted-foreground')
  }

  return classes.join(' ')
})

const messageClasses = computed(() => {
  const classes = ['mt-1 text-sm']

  if (props.errorMessage) {
    classes.push('text-destructive')
  } else if (props.variant === 'gaming' || props.variant === 'player') {
    classes.push('text-slate-400')
  } else {
    classes.push('text-muted-foreground')
  }

  return classes.join(' ')
})

// Métodos
const handleInput = (event) => {
  emit('update:modelValue', event.target.value)
}

const handleBlur = (event) => {
  emit('blur', event)
}

const handleFocus = (event) => {
  emit('focus', event)
}

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value
}
</script>