<template>
  <Transition
    enter-active-class="transition-all duration-400 ease-out"
    enter-from-class="opacity-0 -translate-y-4 scale-95"
    enter-to-class="opacity-100 translate-y-0 scale-100"
    leave-active-class="transition-all duration-300 ease-in"
    leave-from-class="opacity-100 translate-y-0 scale-100"
    leave-to-class="opacity-0 -translate-y-4 scale-95"
  >
    <div
      v-if="visible"
      :class="alertClasses"
      :role="role"
      :aria-live="ariaLive"
      :aria-atomic="true"
    >
      <!-- Background effects -->
      <div
        v-if="animated"
        class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-500"
      >
        <div class="absolute inset-0 bg-gradient-shimmer animate-shimmer" />
      </div>

      <!-- Main content -->
      <div class="relative flex items-start gap-4">
        <!-- SharedIcon container -->
        <div
          v-if="showIcon"
          :class="iconContainerClasses"
        >
          <SharedIcon
            :name="iconName"
            :size="iconSize"
            :class="iconColorClass"
            aria-hidden="true"
          />
        </div>

        <!-- Content area -->
        <div class="flex-1 min-w-0">
          <!-- Title -->
          <div
            v-if="hasTitle"
            :class="titleClasses"
          >
            <slot name="title">
              {{ title }}
            </slot>
          </div>

          <!-- Message -->
          <div :class="messageClasses">
            <slot>{{ message }}</slot>
          </div>

          <!-- Actions slot -->
          <div
            v-if="$slots.actions"
            class="mt-3"
          >
            <slot name="actions" />
          </div>
        </div>

        <!-- Close button -->
        <button
          v-if="dismissible"
          type="button"
          :class="closeButtonClasses"
          :aria-label="dismissLabel"
          @click="handleDismiss"
        >
          <svg
            class="w-4 h-4"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>

      <!-- Progress bar for auto-dismiss -->
      <div
        v-if="showProgress && duration"
        :class="progressBarClasses"
        :style="{ animationDuration: `${duration}ms` }"
      />
    </div>
  </Transition>
</template>

<script setup lang="ts">
/**
 * @fileoverview Alert - Componente de alerta optimizado con nueva paleta azul
 * @description Alerta responsive con animaciones y estados mejorados
 * @author Lotto38 Team
 * @version 2.0.0
 * @since 2025-09-16
 */

import { computed, ref, onMounted, onUnmounted, useSlots, type Component } from 'vue'
// useResponsive auto-imported by Nuxt

// Alert types y variants
type AlertType = 'success' | 'error' | 'warning' | 'info'
type AlertStyleVariant = 'solid' | 'soft' | 'glass' | 'outline' | 'minimal'
type AlertVariant = AlertStyleVariant | AlertType

interface Props {
  // Contenido
  message?: string
  title?: string

  // Apariencia
  type?: AlertType
  variant?: AlertVariant

  // Comportamiento
  dismissible?: boolean
  duration?: number
  showIcon?: boolean
  animated?: boolean

  // Responsive
  responsive?: boolean

  // Accesibilidad
  role?: string
  ariaLive?: 'polite' | 'assertive' | 'off'
  dismissLabel?: string
}

const props = withDefaults(defineProps<Props>(), {
  type: 'info',
  variant: 'soft',
  dismissible: true,
  showIcon: true,
  animated: true,
  responsive: true,
  role: 'alert',
  ariaLive: 'polite',
  dismissLabel: 'Dismiss alert',
})

// Emits
const emit = defineEmits<{
  dismiss: []
  expired: []
}>()

// Composables
const { viewport } = useResponsive()

// State
const visible = ref(true)
const timeoutId = ref<ReturnType<typeof setTimeout>>()

// Computed properties
const hasTitle = computed(() => !!props.title || !!slots.title)
const showProgress = computed(() => !!props.duration && props.duration > 0)

const iconName = computed(() => {
  const iconMap = {
    success: 'success',
    error: 'error',
    warning: 'warning',
    info: 'info',
  }
  return iconMap[props.type]
})

const iconSize = computed(() => viewport.isMobile ? 'sm' : 'md')

const iconColor = computed(() => {
  const colorMap = {
    success: props.variant === 'solid' ? 'white' : 'success',
    error: props.variant === 'solid' ? 'white' : 'error',
    warning: props.variant === 'solid' ? 'white' : 'warning',
    info: props.variant === 'solid' ? 'white' : 'info',
  }
  return colorMap[props.type]
})

const iconColorClass = computed(() => {
  const colorMap = {
    success: props.variant === 'solid' ? 'text-white' : 'text-success-600',
    error: props.variant === 'solid' ? 'text-white' : 'text-error-600',
    warning: props.variant === 'solid' ? 'text-white' : 'text-warning-600',
    info: props.variant === 'solid' ? 'text-white' : 'text-info-600',
  }
  return colorMap[props.type]
})

// Classes computadas optimizadas
const alertClasses = computed(() => {
  const baseClasses = [
    'relative overflow-hidden group',
    'transition-all duration-300 ease-out',
    'transform-gpu will-change-transform',
  ]

  // Responsive sizing
  const responsiveClasses = viewport.isMobile
    ? [
        'mx-2 mb-3 px-4 py-3 rounded-xl text-sm',
      ]
    : [
        'mb-4 px-6 py-4 rounded-2xl',
      ]

  // Variant-based styling con nueva paleta azul
  const variantClasses = {
    solid: {
      success: 'bg-gradient-success text-white border border-success-400 shadow-success-medium',
      error: 'bg-gradient-danger text-white border border-error-400 shadow-error-medium',
      warning: 'bg-gradient-warning text-white border border-warning-400 shadow-warning-medium',
      info: 'bg-gradient-primary text-white border border-primary-400 shadow-blue-medium',
    },
    soft: {
      success: 'bg-success-50/90 border border-success-200/60 text-success-800 shadow-success-soft backdrop-blur-sm',
      error: 'bg-red-50/90 border border-red-200/60 text-red-800 shadow-error-soft backdrop-blur-sm',
      warning: 'bg-amber-50/90 border border-amber-200/60 text-amber-800 shadow-warning-soft backdrop-blur-sm',
      info: 'bg-primary-50/90 border border-primary-200/60 text-primary-800 shadow-blue-soft backdrop-blur-sm',
    },
    glass: {
      success: 'bg-gradient-glass border border-success-300/40 text-success-700 shadow-success-soft backdrop-blur-xl',
      error: 'bg-gradient-glass border border-red-300/40 text-red-700 shadow-error-soft backdrop-blur-xl',
      warning: 'bg-gradient-glass border border-amber-300/40 text-amber-700 shadow-warning-soft backdrop-blur-xl',
      info: 'bg-gradient-glass border border-primary-300/40 text-primary-700 shadow-blue-soft backdrop-blur-xl',
    },
    outline: {
      success: 'bg-white/95 border-2 border-success-300 text-success-700 shadow-success-soft hover:bg-success-50/60',
      error: 'bg-white/95 border-2 border-red-300 text-red-700 shadow-error-soft hover:bg-red-50/60',
      warning: 'bg-white/95 border-2 border-amber-300 text-amber-700 shadow-warning-soft hover:bg-amber-50/60',
      info: 'bg-white/95 border-2 border-primary-300 text-primary-700 shadow-blue-soft hover:bg-primary-50/60',
    },
    minimal: {
      success: 'bg-success-50/50 border-l-4 border-l-success-500 text-success-800',
      error: 'bg-red-50/50 border-l-4 border-l-red-500 text-red-800',
      warning: 'bg-amber-50/50 border-l-4 border-l-amber-500 text-amber-800',
      info: 'bg-primary-50/50 border-l-4 border-l-primary-500 text-primary-800',
    },
  }

  // Hover effects
  const hoverClasses = props.animated
    ? [
        'hover:scale-[1.02] hover:-translate-y-0.5',
      ]
    : []

  // Manejo inteligente de variant: si variant es un color, usarlo como type
  const actualVariant = (['error', 'success', 'warning', 'info'] as const).includes(props.variant as AlertType) ? 'soft' : props.variant as AlertStyleVariant
  const actualType = (['error', 'success', 'warning', 'info'] as const).includes(props.variant as AlertType) ? props.variant as AlertType : props.type

  return [
    ...baseClasses,
    ...(props.responsive ? responsiveClasses : ['mb-4 px-6 py-4 rounded-2xl']),
    variantClasses[actualVariant as AlertStyleVariant]?.[actualType as AlertType] || '',
    ...hoverClasses,
  ].join(' ')
})

const iconContainerClasses = computed(() => {
  const size = viewport.isMobile ? 'p-1.5' : 'p-2'

  const colorClasses = {
    success: 'bg-success-100/80 text-success-600 border border-success-200/50 shadow-success-soft/30',
    error: 'bg-red-100/80 text-red-600 border border-red-200/50 shadow-error-soft/30',
    warning: 'bg-amber-100/80 text-amber-600 border border-amber-200/50 shadow-warning-soft/30',
    info: 'bg-primary-100/80 text-primary-600 border border-primary-200/50 shadow-blue-soft/30',
  }

  return [
    'flex-shrink-0 rounded-xl transition-all duration-200',
    size,
    colorClasses[props.type],
  ].join(' ')
})

const iconClasses = computed(() => {
  const size = viewport.isMobile ? 'w-4 h-4' : 'w-5 h-5'
  return size
})

const titleClasses = computed(() => {
  const size = viewport.isMobile ? 'text-sm' : 'text-base'
  return [
    'font-semibold mb-1',
    size,
    props.variant === 'solid' ? 'text-white' : 'text-current',
  ].join(' ')
})

const messageClasses = computed(() => {
  const size = viewport.isMobile ? 'text-xs' : 'text-sm'
  return [
    'leading-relaxed',
    size,
    props.variant === 'solid' ? 'text-white/90' : 'text-current',
  ].join(' ')
})

const closeButtonClasses = computed(() => {
  const baseClasses = [
    'flex-shrink-0 p-1.5 rounded-lg',
    'transition-all duration-200',
    'focus:outline-none focus:ring-2 focus:ring-offset-1',
    'hover:scale-110',
  ]

  const colorClasses = props.variant === 'solid'
    ? [
        'text-white/80 hover:text-white hover:bg-white/20',
        'focus:ring-white/50',
      ]
    : {
        success: 'text-success-600 hover:bg-success-100/80 focus:ring-success-500',
        error: 'text-red-600 hover:bg-red-100/80 focus:ring-red-500',
        warning: 'text-amber-600 hover:bg-amber-100/80 focus:ring-amber-500',
        info: 'text-primary-600 hover:bg-primary-100/80 focus:ring-primary-500',
      }[props.type]

  return [...baseClasses, ...(Array.isArray(colorClasses) ? colorClasses : [colorClasses])].join(' ')
})

const progressBarClasses = computed(() => [
  'absolute bottom-0 left-0 h-1 rounded-b-inherit',
  'animate-shrink transform-gpu',
  props.variant === 'solid' ? 'bg-white/30' : 'bg-current opacity-30',
].join(' '))

// Methods
const handleDismiss = () => {
  if (timeoutId.value) {
    clearTimeout(timeoutId.value)
  }
  visible.value = false
  emit('dismiss')
}

const handleExpiry = () => {
  visible.value = false
  emit('expired')
}

// Auto-dismiss logic
onMounted(() => {
  if (props.duration && props.duration > 0) {
    timeoutId.value = setTimeout(handleExpiry, props.duration)
  }
})

onUnmounted(() => {
  if (timeoutId.value) {
    clearTimeout(timeoutId.value)
  }
})

// Access slots
const slots = useSlots()
</script>

<style scoped>
/* Performance optimizations */
.transform-gpu {
  transform: translateZ(0);
}

.will-change-transform {
  will-change: transform;
}

/* Shimmer effect gradient */
.bg-gradient-shimmer {
  background: linear-gradient(90deg,
      transparent,
      rgba(255, 255, 255, 0.2),
      transparent);
  background-size: 200% 100%;
}

/* Progress bar animation */
@keyframes shrink {
  from {
    width: 100%;
  }

  to {
    width: 0%;
  }
}

.animate-shrink {
  animation: shrink linear;
}

/* Optimización para dispositivos con prefiere-reduced-motion */
@media (prefers-reduced-motion: reduce) {
  .group:hover {
    transform: none !important;
  }

  .animate-shimmer,
  .animate-shrink {
    animation: none !important;
  }

  .transition-all {
    transition: none !important;
  }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  div[role="alert"] {
    border: 2px solid currentColor !important;
  }
}

/* Dark mode adjustments */
@media (prefers-color-scheme: dark) {
  .bg-white\/95 {
    background-color: rgba(31, 41, 55, 0.95);
  }

  .backdrop-blur-sm {
    backdrop-filter: blur(4px) brightness(0.8);
  }
}
</style>
