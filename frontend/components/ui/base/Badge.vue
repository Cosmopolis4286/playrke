<template>
  <span :class="badgeClasses">
    <SharedIcon
      v-if="icon"
      :name="icon"
      :class="iconClasses"
    />
    <slot />
  </span>
</template>

<script setup lang="ts">
/**
 * @fileoverview Badge component - Etiqueta de estado
 * @description Componente para mostrar estados, categorías o etiquetas
 */

interface Props {
  /** Variante visual del badge */
  variant?: 'primary' | 'secondary' | 'success' | 'warning' | 'error' | 'info' | 'danger' | 'default'
  /** Tamaño del badge */
  size?: 'sm' | 'md' | 'lg'
  /** Icono opcional */
  icon?: string
  /** Badge con borde */
  outline?: boolean
  /** Badge removible */
  removable?: boolean
}

interface Emits {
  /** Evento de eliminación */
  remove: []
}

defineOptions({
  name: 'Badge',
})

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'md',
  outline: false,
  removable: false,
})

const emit = defineEmits<Emits>()

// Computed classes
const badgeClasses = computed(() => {
  const classes = ['badge-base']

  // Size classes
  switch (props.size) {
    case 'sm':
      classes.push('badge-sm')
      break
    case 'lg':
      classes.push('badge-lg')
      break
    default:
      classes.push('badge-md')
  }

  // Variant classes
  if (props.outline) {
    switch (props.variant) {
      case 'secondary':
      case 'default': // Alias para secondary
        classes.push('badge-outline-secondary')
        break
      case 'success':
        classes.push('badge-outline-success')
        break
      case 'warning':
        classes.push('badge-outline-warning')
        break
      case 'error':
      case 'danger': // Alias para error
        classes.push('badge-outline-error')
        break
      case 'info':
        classes.push('badge-outline-info')
        break
      default:
        classes.push('badge-outline-primary')
    }
  } else {
    switch (props.variant) {
      case 'secondary':
      case 'default': // Alias para secondary
        classes.push('badge-secondary')
        break
      case 'success':
        classes.push('badge-success')
        break
      case 'warning':
        classes.push('badge-warning')
        break
      case 'error':
      case 'danger': // Alias para error
        classes.push('badge-error')
        break
      case 'info':
        classes.push('badge-info')
        break
      default:
        classes.push('badge-primary')
    }
  }

  return classes
})

const iconClasses = computed(() => {
  const classes = ['badge-icon']

  switch (props.size) {
    case 'sm':
      classes.push('w-3 h-3')
      break
    case 'lg':
      classes.push('w-5 h-5')
      break
    default:
      classes.push('w-4 h-4')
  }

  return classes
})

// Methods
const handleRemove = () => {
  emit('remove')
}
</script>

<style scoped>
/* Base badge styles */
.badge-base {
  @apply inline-flex items-center gap-1 font-medium rounded-full transition-colors duration-200;
}

/* Size variations */
.badge-sm {
  @apply px-2 py-0.5 text-xs;
}

.badge-md {
  @apply px-2.5 py-1 text-sm;
}

.badge-lg {
  @apply px-3 py-1.5 text-base;
}

/* Solid variants */
.badge-primary {
  @apply bg-primary-100 text-primary-800;
}

.badge-secondary {
  @apply bg-secondary-100 text-secondary-800;
}

.badge-success {
  @apply bg-success-100 text-success-800;
}

.badge-warning {
  @apply bg-warning-100 text-warning-800;
}

.badge-error {
  @apply bg-error-100 text-error-800;
}

.badge-info {
  @apply bg-info-100 text-info-800;
}

/* Outline variants */
.badge-outline-primary {
  @apply bg-transparent border border-primary-300 text-primary-700;
}

.badge-outline-secondary {
  @apply bg-transparent border border-secondary-300 text-secondary-700;
}

.badge-outline-success {
  @apply bg-transparent border border-success-300 text-success-700;
}

.badge-outline-warning {
  @apply bg-transparent border border-warning-300 text-warning-700;
}

.badge-outline-error {
  @apply bg-transparent border border-error-300 text-error-700;
}

.badge-outline-info {
  @apply bg-transparent border border-info-300 text-info-700;
}

/* SharedIcon spacing */
.badge-icon {
  @apply flex-shrink-0;
}
</style>
