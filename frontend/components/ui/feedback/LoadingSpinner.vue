<template>
  <div
    :class="containerClasses"
    :aria-label="ariaLabel"
    role="status"
  >
    <!-- Spinner SVG -->
    <svg
      v-if="type === 'spinner'"
      :class="spinnerClasses"
      viewBox="0 0 24 24"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
    >
      <circle
        cx="12"
        cy="12"
        r="10"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-dasharray="31.416"
        stroke-dashoffset="31.416"
        class="opacity-25"
      />
      <circle
        cx="12"
        cy="12"
        r="10"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-dasharray="31.416"
        stroke-dashoffset="7.854"
        class="animate-spin-slow"
      />
    </svg>

    <!-- Dots Loading -->
    <div
      v-else-if="type === 'dots'"
      :class="dotsContainerClasses"
    >
      <div
        v-for="i in 3"
        :key="i"
        :class="[dotClasses, `animate-bounce-${i}`]"
        :style="{ animationDelay: `${(i - 1) * 0.15}s` }"
      />
    </div>

    <!-- Pulse Circle -->
    <div
      v-else-if="type === 'pulse'"
      :class="pulseClasses"
    >
      <div class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" />
      <div class="relative inline-flex rounded-full" />
    </div>

    <!-- Bars Loading -->
    <div
      v-else-if="type === 'bars'"
      :class="barsContainerClasses"
    >
      <div
        v-for="i in 4"
        :key="i"
        :class="[barClasses, `animate-pulse-${i}`]"
        :style="{ animationDelay: `${(i - 1) * 0.1}s` }"
      />
    </div>

    <!-- Progress Ring -->
    <div
      v-else-if="type === 'ring'"
      class="relative"
    >
      <svg
        :class="ringClasses"
        viewBox="0 0 24 24"
      >
        <circle
          cx="12"
          cy="12"
          r="10"
          stroke="currentColor"
          stroke-width="2"
          fill="none"
          class="opacity-25"
        />
        <circle
          cx="12"
          cy="12"
          r="10"
          stroke="currentColor"
          stroke-width="2"
          fill="none"
          stroke-linecap="round"
          :stroke-dasharray="`${progress * 0.628} 62.8`"
          transform="rotate(-90 12 12)"
          class="transition-all duration-300"
        />
      </svg>
      <div
        v-if="showPercentage"
        :class="percentageClasses"
      >
        {{ Math.round(progress) }}%
      </div>
    </div>

    <!-- Loading Text -->
    <div
      v-if="text && showText"
      :class="textClasses"
    >
      {{ text }}
    </div>

    <!-- Screen Reader Text -->
    <span class="sr-only">{{ screenReaderText }}</span>
  </div>
</template>

<script setup lang="ts">
/**
 * @fileoverview LoadingSpinner component - Indicador de carga
 * @description Spinner de carga con múltiples variantes y estilos
 */

interface Props {
  /** Tipo de animación de carga */
  type?: 'spinner' | 'dots' | 'pulse' | 'bars' | 'ring'
  /** Tamaño del spinner */
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl'
  /** Color del spinner */
  color?: 'primary' | 'secondary' | 'success' | 'warning' | 'error' | 'info' | 'white'
  /** Texto de carga */
  text?: string
  /** Mostrar texto */
  showText?: boolean
  /** Posición del texto */
  textPosition?: 'bottom' | 'top' | 'right' | 'left'
  /** Centrar el spinner */
  centered?: boolean
  /** Overlay completo */
  fullscreen?: boolean
  /** Progreso para tipo 'ring' (0-100) */
  progress?: number
  /** Mostrar porcentaje en ring */
  showPercentage?: boolean
  /** Velocidad de animación */
  speed?: 'slow' | 'normal' | 'fast'
  /** Texto para lectores de pantalla */
  ariaLabel?: string
}

defineOptions({
  name: 'LoadingSpinner',
})

const props = withDefaults(defineProps<Props>(), {
  type: 'spinner',
  size: 'md',
  color: 'primary',
  showText: true,
  textPosition: 'bottom',
  centered: false,
  fullscreen: false,
  progress: 0,
  showPercentage: false,
  speed: 'normal',
  ariaLabel: 'Cargando...',
})

// Computed classes
const containerClasses = computed(() => {
  const classes = ['loading-spinner']

  // Layout
  if (props.fullscreen) {
    classes.push('fixed inset-0 bg-white/80 backdrop-blur-sm z-50')
  }

  if (props.centered || props.fullscreen) {
    classes.push('flex items-center justify-center')
  }

  // Text position
  if (props.text && props.showText) {
    switch (props.textPosition) {
      case 'top':
        classes.push('flex flex-col-reverse gap-3')
        break
      case 'right':
        classes.push('flex items-center gap-3')
        break
      case 'left':
        classes.push('flex items-center flex-row-reverse gap-3')
        break
      default:
        classes.push('flex flex-col gap-3')
    }
  }

  return classes
})

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'xs': return 'w-3 h-3'
    case 'sm': return 'w-4 h-4'
    case 'lg': return 'w-8 h-8'
    case 'xl': return 'w-12 h-12'
    default: return 'w-6 h-6'
  }
})

const colorClasses = computed(() => {
  switch (props.color) {
    case 'secondary': return 'text-secondary-600'
    case 'success': return 'text-success-600'
    case 'warning': return 'text-warning-600'
    case 'error': return 'text-error-600'
    case 'info': return 'text-info-600'
    case 'white': return 'text-white'
    default: return 'text-primary-600'
  }
})

const speedClasses = computed(() => {
  switch (props.speed) {
    case 'slow': return 'animate-spin-slow'
    case 'fast': return 'animate-spin-fast'
    default: return 'animate-spin'
  }
})

// Spinner specific classes
const spinnerClasses = computed(() => [
  sizeClasses.value,
  colorClasses.value,
  speedClasses.value,
].join(' '))

// Dots specific classes
const dotsContainerClasses = computed(() => [
  'flex space-x-1',
  colorClasses.value,
].join(' '))

const dotClasses = computed(() => {
  const baseSize = props.size === 'xs'
    ? 'w-1 h-1'
    : props.size === 'sm'
      ? 'w-1.5 h-1.5'
      : props.size === 'lg'
        ? 'w-3 h-3'
        : props.size === 'xl'
          ? 'w-4 h-4'
          : 'w-2 h-2'

  return [
    baseSize,
    'rounded-full bg-current animate-bounce',
  ].join(' ')
})

// Pulse specific classes
const pulseClasses = computed(() => [
  'relative',
  sizeClasses.value,
  colorClasses.value,
].join(' '))

// Bars specific classes
const barsContainerClasses = computed(() => [
  'flex items-end space-x-1',
  colorClasses.value,
].join(' '))

const barClasses = computed(() => {
  const width = props.size === 'xs'
    ? 'w-0.5'
    : props.size === 'sm'
      ? 'w-1'
      : props.size === 'lg'
        ? 'w-2'
        : props.size === 'xl'
          ? 'w-3'
          : 'w-1.5'

  const height = props.size === 'xs'
    ? 'h-3'
    : props.size === 'sm'
      ? 'h-4'
      : props.size === 'lg'
        ? 'h-8'
        : props.size === 'xl'
          ? 'h-12'
          : 'h-6'

  return [
    width,
    height,
    'bg-current animate-pulse',
  ].join(' ')
})

// Ring specific classes
const ringClasses = computed(() => [
  sizeClasses.value,
  colorClasses.value,
].join(' '))

const percentageClasses = computed(() => [
  'absolute inset-0 flex items-center justify-center text-xs font-medium',
  colorClasses.value,
].join(' '))

// Text classes
const textClasses = computed(() => {
  const sizeClass = props.size === 'xs' || props.size === 'sm'
    ? 'text-xs'
    : props.size === 'lg'
      ? 'text-base'
      : props.size === 'xl'
        ? 'text-lg'
        : 'text-sm'

  return [
    sizeClass,
    'font-medium',
    colorClasses.value,
    'animate-pulse',
  ].join(' ')
})

// Screen reader text
const screenReaderText = computed(() => {
  if (props.text) {
    return props.text
  }
  return props.ariaLabel || 'Cargando contenido...'
})
</script>

<style scoped>
/* Custom animations */
@keyframes spin-slow {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@keyframes spin-fast {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.animate-spin-slow {
  animation: spin-slow 2s linear infinite;
}

.animate-spin-fast {
  animation: spin-fast 0.5s linear infinite;
}

/* Bounce delays for dots */
.animate-bounce-1 {
  animation-delay: 0s;
}

.animate-bounce-2 {
  animation-delay: 0.15s;
}

.animate-bounce-3 {
  animation-delay: 0.3s;
}

/* Pulse delays for bars */
.animate-pulse-1 {
  animation-delay: 0s;
}

.animate-pulse-2 {
  animation-delay: 0.1s;
}

.animate-pulse-3 {
  animation-delay: 0.2s;
}

.animate-pulse-4 {
  animation-delay: 0.3s;
}

/* Accessibility */
.loading-spinner {
  @apply focus:outline-none;
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .loading-spinner * {
    animation-duration: 0s !important;
    animation-iteration-count: 1 !important;
  }
}
</style>
