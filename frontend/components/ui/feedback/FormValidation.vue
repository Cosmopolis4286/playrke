<template>
  <div
    v-if="hasErrors || hasSuccess"
    class="form-validation space-y-2"
  >
    <!-- Success Messages -->
    <div
      v-if="hasSuccess && showSuccess"
      class="success-group"
    >
      <div
        v-for="(message, index) in successMessages"
        :key="`success-${index}`"
        :class="successClasses"
        role="status"
        aria-live="polite"
      >
        <SharedIcon
          name="success"
          :class="iconClasses"
        />
        <span>{{ message }}</span>
      </div>
    </div>

    <!-- Error Messages -->
    <div
      v-if="hasErrors && showErrors"
      class="error-group"
    >
      <div
        v-for="(error, index) in errorMessages"
        :key="`error-${index}`"
        :class="errorClasses"
        role="alert"
        aria-live="assertive"
      >
        <SharedIcon
          name="error"
          :class="iconClasses"
        />
        <span>{{ error }}</span>
      </div>
    </div>

    <!-- Warning Messages -->
    <div
      v-if="hasWarnings && showWarnings"
      class="warning-group"
    >
      <div
        v-for="(warning, index) in warningMessages"
        :key="`warning-${index}`"
        :class="warningClasses"
        role="status"
        aria-live="polite"
      >
        <SharedIcon
          name="warning"
          :class="iconClasses"
        />
        <span>{{ warning }}</span>
      </div>
    </div>

    <!-- Info Messages -->
    <div
      v-if="hasInfo && showInfo"
      class="info-group"
    >
      <div
        v-for="(info, index) in infoMessages"
        :key="`info-${index}`"
        :class="infoClasses"
        role="status"
        aria-live="polite"
      >
        <SharedIcon
          name="info"
          :class="iconClasses"
        />
        <span>{{ info }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * @fileoverview FormValidation component - Feedback de validación
 * @description Componente para mostrar mensajes de validación, errores y feedback
 */

interface Props {
  /** Errores de validación */
  errors?: string | string[] | Record<string, string | string[]>
  /** Mensajes de éxito */
  success?: string | string[]
  /** Mensajes de advertencia */
  warnings?: string | string[]
  /** Mensajes informativos */
  info?: string | string[]
  /** Mostrar errores */
  showErrors?: boolean
  /** Mostrar éxitos */
  showSuccess?: boolean
  /** Mostrar advertencias */
  showWarnings?: boolean
  /** Mostrar información */
  showInfo?: boolean
  /** Tamaño del componente */
  size?: 'sm' | 'md' | 'lg'
  /** Variante visual */
  variant?: 'default' | 'compact' | 'inline'
  /** Posición de los iconos */
  iconPosition?: 'left' | 'right' | 'none'
  /** Auto-dismiss después de X ms */
  autoDismiss?: number
}

defineOptions({
  name: 'FormValidation',
})

const props = withDefaults(defineProps<Props>(), {
  showErrors: true,
  showSuccess: true,
  showWarnings: true,
  showInfo: true,
  size: 'md',
  variant: 'default',
  iconPosition: 'left',
})

// Normalizar mensajes
const normalizeMessages = (messages: string | string[] | Record<string, string | string[]> | undefined): string[] => {
  if (!messages) { return [] }

  if (typeof messages === 'string') {
    return [messages]
  }

  if (Array.isArray(messages)) {
    return messages
  }

  if (typeof messages === 'object' && messages !== null) {
    const result: string[] = []

    for (const [key, value] of Object.entries(messages)) {
      if (typeof value === 'string') {
        result.push(value)
      } else if (Array.isArray(value)) {
        result.push(...value)
      }
    }

    return result
  }

  return []
}

// Computed messages
const errorMessages = computed(() => normalizeMessages(props.errors))
const successMessages = computed(() => normalizeMessages(props.success))
const warningMessages = computed(() => normalizeMessages(props.warnings))
const infoMessages = computed(() => normalizeMessages(props.info))

// Computed states
const hasErrors = computed(() => errorMessages.value.length > 0)
const hasSuccess = computed(() => successMessages.value.length > 0)
const hasWarnings = computed(() => warningMessages.value.length > 0)
const hasInfo = computed(() => infoMessages.value.length > 0)

// Base classes
const baseMessageClasses = computed(() => {
  const classes = ['flex items-start transition-all duration-200']

  // Size classes
  switch (props.size) {
    case 'sm':
      classes.push('text-xs gap-1 py-1 px-2')
      break
    case 'lg':
      classes.push('text-base gap-3 py-3 px-4')
      break
    default:
      classes.push('text-sm gap-2 py-2 px-3')
  }

  // Variant classes
  switch (props.variant) {
    case 'compact':
      classes.push('rounded')
      break
    case 'inline':
      classes.push('border-0 p-0 bg-transparent')
      break
    default:
      classes.push('rounded-md border')
  }

  // SharedIcon positioning
  if (props.iconPosition === 'right') {
    classes.push('flex-row-reverse')
  } else if (props.iconPosition === 'none') {
    classes.push('pl-0')
  }

  return classes
})

// Message-specific classes
const errorClasses = computed(() => [
  ...baseMessageClasses.value,
  props.variant !== 'inline'
    ? 'bg-error-50 border-error-200 text-error-700'
    : 'text-error-600',
].join(' '))

const successClasses = computed(() => [
  ...baseMessageClasses.value,
  props.variant !== 'inline'
    ? 'bg-success-50 border-success-200 text-success-700'
    : 'text-success-600',
].join(' '))

const warningClasses = computed(() => [
  ...baseMessageClasses.value,
  props.variant !== 'inline'
    ? 'bg-warning-50 border-warning-200 text-warning-700'
    : 'text-warning-600',
].join(' '))

const infoClasses = computed(() => [
  ...baseMessageClasses.value,
  props.variant !== 'inline'
    ? 'bg-info-50 border-info-200 text-info-700'
    : 'text-info-600',
].join(' '))

// SharedIcon classes
const iconClasses = computed(() => {
  const classes = ['flex-shrink-0']

  switch (props.size) {
    case 'sm':
      classes.push('w-3 h-3')
      break
    case 'lg':
      classes.push('w-6 h-6')
      break
    default:
      classes.push('w-4 h-4')
  }

  return classes.join(' ')
})

// Auto-dismiss functionality
if (props.autoDismiss && props.autoDismiss > 0) {
  const emit = defineEmits<{
    dismiss: [type: 'error' | 'success' | 'warning' | 'info']
  }>()

  onMounted(() => {
    setTimeout(() => {
      if (hasSuccess.value) { emit('dismiss', 'success') }
      if (hasInfo.value) { emit('dismiss', 'info') }
      if (hasWarnings.value) { emit('dismiss', 'warning') }
    }, props.autoDismiss)
  })
}
</script>

<style scoped>
.form-validation {
  @apply space-y-2;
}

/* Animation for messages */
.error-group>div,
.success-group>div,
.warning-group>div,
.info-group>div {
  animation: slideInFade 0.3s ease-out;
}

@keyframes slideInFade {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Focus management for screen readers */
[role="alert"],
[role="status"] {
  @apply focus:outline-none;
}
</style>
