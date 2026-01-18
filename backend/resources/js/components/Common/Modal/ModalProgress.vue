<template>
  <div
    :class="containerClasses"
    role="progressbar"
    :aria-valuenow="value"
    :aria-valuemin="min"
    :aria-valuemax="max"
    :aria-label="ariaLabel"
  >
    <!-- Progress Track -->
    <div :class="trackClasses">
      <!-- Progress Fill -->
      <div
        :class="fillClasses"
        :style="fillStyle"
      >
        <!-- Progress Shimmer Effect -->
        <div
          v-if="animated && variant === 'premium'"
          :class="shimmerClasses"
        />

        <!-- Gaming Glow Effect -->
        <div
          v-if="animated && variant === 'gaming'"
          :class="gamingGlowClasses"
        />
      </div>

      <!-- Progress Text Overlay -->
      <div
        v-if="showText"
        :class="textClasses"
      >
        <span :class="textContentClasses">
          {{ formattedText }}
        </span>
      </div>

      <!-- Progress Steps (for stepper mode) -->
      <div
        v-if="mode === 'steps'"
        :class="stepsContainerClasses"
      >
        <div
          v-for="step in steps"
          :key="step"
          :class="getStepClasses(step)"
        />
      </div>
    </div>

    <!-- Progress Label -->
    <div
      v-if="label || $slots.label"
      :class="labelClasses"
    >
      <slot name="label">
        {{ label }}
      </slot>
    </div>

    <!-- Progress Description -->
    <div
      v-if="description || $slots.description"
      :class="descriptionClasses"
    >
      <slot name="description">
        {{ description }}
      </slot>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * @fileoverview ModalProgress - Componente unificado de progreso para modales
 * @description Reemplaza ModalHeaderProgress y ModalFooterProgress con un solo
 * componente versátil que soporta múltiples variantes y modos
 * @author Lotto38 Team
 * @version 2.0.0
 * @since 2025-09-17
 */

import { computed } from 'vue'

type ProgressVariant = 'default' | 'primary' | 'success' | 'warning' | 'danger' | 'gaming' | 'premium'
type ProgressSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl'
type ProgressMode = 'linear' | 'circular' | 'steps'
type ProgressPosition = 'header' | 'footer' | 'inline'

interface Props {
  /** Valor del progreso (0-100) */
  value?: number
  /** Valor mínimo */
  min?: number
  /** Valor máximo */
  max?: number

  // Apariencia
  /** Variante visual */
  variant?: ProgressVariant
  /** Tamaño del progress */
  size?: ProgressSize
  /** Modo de visualización */
  mode?: ProgressMode
  /** Posición en el modal */
  position?: ProgressPosition

  // Contenido
  /** Etiqueta del progreso */
  label?: string
  /** Descripción adicional */
  description?: string
  /** Mostrar texto del porcentaje */
  showText?: boolean
  /** Formato personalizado del texto */
  textFormat?: string

  // Comportamiento
  /** Animaciones habilitadas */
  animated?: boolean
  /** Progreso indeterminado */
  indeterminate?: boolean
  /** Número de pasos (para mode='steps') */
  steps?: number
  /** Paso actual (para mode='steps') */
  currentStep?: number

  // Accesibilidad
  /** Etiqueta ARIA personalizada */
  ariaLabel?: string
}

const props = withDefaults(defineProps<Props>(), {
  value: 0,
  min: 0,
  max: 100,
  variant: 'primary',
  size: 'md',
  mode: 'linear',
  position: 'inline',
  showText: true,
  textFormat: '{value}%',
  animated: true,
  indeterminate: false,
  steps: 5,
  currentStep: 1,
  ariaLabel: 'Progreso',
})

// Computadas principales
const progressPercentage = computed(() => {
  if (props.indeterminate) { return 0 }

  const normalizedValue = Math.max(props.min, Math.min(props.max, props.value))
  return ((normalizedValue - props.min) / (props.max - props.min)) * 100
})

const formattedText = computed(() => {
  if (props.mode === 'steps') {
    return `${props.currentStep} / ${props.steps}`
  }

  return props.textFormat
    .replace('{value}', Math.round(progressPercentage.value).toString())
    .replace('{min}', props.min.toString())
    .replace('{max}', props.max.toString())
})

const fillStyle = computed(() => {
  if (props.mode === 'circular') {
    // Para modo circular, usar CSS custom properties
    return {
      '--progress-value': `${progressPercentage.value}%`,
    }
  }

  if (props.indeterminate) {
    return {
      width: '30%',
      animation: 'indeterminate 2s infinite',
    }
  }

  return {
    width: `${progressPercentage.value}%`,
    transition: props.animated ? 'width 0.3s ease-out' : 'none',
  }
})

// Clases computadas
const containerClasses = computed(() => [
  'relative w-full',
  {
    // Posicionamiento
    'mb-2': props.position === 'header',
    'mt-2': props.position === 'footer',
    'my-2': props.position === 'inline',
  },
])

const trackClasses = computed(() => [
  'relative overflow-hidden',
  'bg-gray-200/50 dark:bg-gray-700/50',

  // Tamaños
  {
    'h-1 rounded-full': props.size === 'xs',
    'h-1.5 rounded-full': props.size === 'sm',
    'h-2 rounded-full': props.size === 'md',
    'h-3 rounded-lg': props.size === 'lg',
    'h-4 rounded-lg': props.size === 'xl',
  },

  // Modo circular
  {
    'rounded-full aspect-square w-16 h-16': props.mode === 'circular' && props.size === 'sm',
    'rounded-full aspect-square w-20 h-20': props.mode === 'circular' && props.size === 'md',
    'rounded-full aspect-square w-24 h-24': props.mode === 'circular' && props.size === 'lg',
  },

  // Variantes del track
  {
    'shadow-inner': props.variant === 'premium',
    'border border-purple-300/20': props.variant === 'gaming',
  },
])

const fillClasses = computed(() => [
  'relative h-full transition-all duration-300 ease-out',

  // Variantes de color
  {
    'bg-gray-400': props.variant === 'default',
    'bg-gradient-to-r from-primary-500 to-primary-600': props.variant === 'primary',
    'bg-gradient-to-r from-emerald-500 to-emerald-600': props.variant === 'success',
    'bg-gradient-to-r from-amber-500 to-amber-600': props.variant === 'warning',
    'bg-gradient-to-r from-red-500 to-red-600': props.variant === 'danger',
    'bg-gradient-to-r from-purple-500 via-pink-500 to-purple-600': props.variant === 'gaming',
    'bg-gradient-to-r from-yellow-400 via-amber-500 to-yellow-600': props.variant === 'premium',
  },

  // Forma
  {
    'rounded-full': props.size === 'xs' || props.size === 'sm' || props.size === 'md',
    'rounded': props.size === 'lg' || props.size === 'xl',
  },

  // Efectos especiales
  {
    'shadow-lg': props.variant === 'gaming' || props.variant === 'premium',
    'shadow-primary-500/50': props.variant === 'primary',
    'shadow-emerald-500/50': props.variant === 'success',
    'shadow-amber-500/50': props.variant === 'warning',
    'shadow-red-500/50': props.variant === 'danger',
  },
])

const textClasses = computed(() => [
  'absolute inset-0 flex items-center justify-center',
  'pointer-events-none',
])

const textContentClasses = computed(() => [
  'text-xs font-medium',
  {
    'text-white': props.variant !== 'default',
    'text-gray-700': props.variant === 'default',
    'drop-shadow-sm': props.variant === 'gaming' || props.variant === 'premium',
  },
])

const labelClasses = computed(() => [
  'flex items-center justify-between mb-1',
  'text-sm font-medium',
  {
    'text-white': props.position === 'header',
    'text-gray-700 dark:text-gray-300': props.position !== 'header',
  },
])

const descriptionClasses = computed(() => [
  'mt-1 text-xs',
  {
    'text-white/80': props.position === 'header',
    'text-gray-500 dark:text-gray-400': props.position !== 'header',
  },
])

const shimmerClasses = computed(() => [
  'absolute inset-0 rounded-inherit',
  'bg-gradient-to-r from-transparent via-white/30 to-transparent',
  'animate-shimmer',
])

const gamingGlowClasses = computed(() => [
  'absolute inset-0 rounded-inherit',
  'bg-gradient-to-r from-purple-400/0 via-pink-400/20 to-purple-400/0',
  'animate-pulse',
])

const stepsContainerClasses = computed(() => [
  'absolute inset-0 flex items-center justify-between px-2',
])

// Métodos
const getStepClasses = (step: number) => [
  'w-2 h-2 rounded-full border-2 transition-all duration-200',
  {
    'bg-current border-current': step <= props.currentStep,
    'bg-transparent border-gray-300': step > props.currentStep,
    'scale-125': step === props.currentStep && props.animated,
  },
]
</script>

<style scoped>
/* Animación indeterminada */
@keyframes indeterminate {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(400%);
  }
}

/* Animación shimmer */
@keyframes shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

.animate-shimmer {
  animation: shimmer 2s infinite;
}

/* Progreso circular */
.circular-progress {
  background: conic-gradient(
    from 0deg,
    currentColor 0deg,
    currentColor calc(var(--progress-value) * 3.6deg),
    transparent calc(var(--progress-value) * 3.6deg),
    transparent 360deg
  );
  mask: radial-gradient(
    circle at center,
    transparent 60%,
    black 60%,
    black 100%
  );
}

/* Soporte para reduced motion */
@media (prefers-reduced-motion: reduce) {
  * {
    animation: none !important;
    transition: none !important;
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .bg-gradient-to-r {
    background: currentColor !important;
  }
}
</style>
