<template>
  <Teleport to="body">
    <div
      v-if="visible"
      :class="containerClasses"
      :style="positionStyles"
    >
      <Transition
        :enter-active-class="enterActiveClass"
        :enter-from-class="enterFromClass"
        :enter-to-class="enterToClass"
        :leave-active-class="leaveActiveClass"
        :leave-from-class="leaveFromClass"
        :leave-to-class="leaveToClass"
      >
        <div
          v-if="visible"
          :class="notificationClasses"
          role="alert"
          :aria-live="ariaLive"
          :aria-atomic="true"
          @click="handleClick"
          @mouseenter="handleMouseEnter"
          @mouseleave="handleMouseLeave"
        >
          <!-- Background Effects -->
          <div
            v-if="animated"
            class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-500"
          >
            <div class="absolute inset-0 bg-gradient-shimmer animate-shimmer" />
          </div>

          <!-- Content Container -->
          <div class="relative flex items-start gap-3">
            <!-- SharedIcon Container -->
            <div
              v-if="showIcon"
              :class="iconContainerClasses"
            >
              <component
                :is="iconComponent"
                :class="iconClasses"
                aria-hidden="true"
              />
            </div>

            <!-- Content Area -->
            <div class="flex-1 min-w-0">
              <!-- Title -->
              <div
                v-if="title"
                :class="titleClasses"
              >
                {{ title }}
              </div>

              <!-- Message -->
              <div :class="messageClasses">
                {{ message }}
              </div>

              <!-- Actions -->
              <div
                v-if="$slots.actions"
                class="mt-2"
              >
                <slot name="actions" />
              </div>
            </div>

            <!-- Close Button -->
            <button
              v-if="dismissible"
              type="button"
              :class="closeButtonClasses"
              :aria-label="dismissLabel"
              @click="handleDismiss"
            >
              <SharedIcon
                name="close"
                class="w-4 h-4"
              />
            </button>
          </div>

          <!-- Progress Bar -->
          <div
            v-if="showProgress && duration"
            :class="progressBarClasses"
            :style="{ animationDuration: `${duration}ms` }"
          />
        </div>
      </Transition>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
/**
 * @fileoverview Notification - Sistema de notificaciones toast premium v2.0
 * @description Notificaciones toast con Heroicons, animaciones y posicionamiento flexible
 * @author Lotto38 Team
 * @version 2.0.0
 * @since 2025-09-16
 */

import { computed, onMounted, onUnmounted, ref } from 'vue'
// useResponsive auto-imported by Nuxt

type NotificationType = 'success' | 'error' | 'warning' | 'info'
type NotificationPosition = 'top-right' | 'top-left' | 'bottom-right' | 'bottom-left' | 'top-center' | 'bottom-center'
type NotificationVariant = 'filled' | 'outlined' | 'glass' | 'minimal'

interface Props {
  // Content
  message: string
  title?: string
  type?: NotificationType

  // Appearance
  variant?: NotificationVariant
  position?: NotificationPosition

  // Behavior
  visible?: boolean
  duration?: number
  dismissible?: boolean
  showProgress?: boolean
  animated?: boolean

  // Interaction
  clickable?: boolean
  pauseOnHover?: boolean

  // Accessibility
  ariaLive?: 'polite' | 'assertive'
  dismissLabel?: string
}

const props = withDefaults(defineProps<Props>(), {
  type: 'info',
  variant: 'glass',
  position: 'bottom-right',
  visible: false,
  duration: 5000,
  dismissible: true,
  showProgress: true,
  animated: true,
  clickable: false,
  pauseOnHover: true,
  ariaLive: 'polite',
  dismissLabel: 'Dismiss notification',
})

// Emits
const emit = defineEmits<{
  dismiss: []
  click: [event: MouseEvent]
}>()

// Composables
const { viewport } = useResponsive()

// Local state
const isHovered = ref(false)
const timeoutId = ref<ReturnType<typeof setTimeout>>()

// Computed properties
const showSharedIcon = computed(() => true)

const iconComponent = computed(() => {
  const icons = {
    success: 'success',
    error: 'error',
    warning: 'warning',
    info: 'info',
  }
  return icons[props.type]
})

const containerClasses = computed(() => [
  'fixed z-50 pointer-events-none',
].join(' '))

const positionStyles = computed(() => {
  const positions = {
    'top-right': { top: '1rem', right: '1rem' },
    'top-left': { top: '1rem', left: '1rem' },
    'bottom-right': { bottom: '1rem', right: '1rem' },
    'bottom-left': { bottom: '1rem', left: '1rem' },
    'top-center': { top: '1rem', left: '50%', transform: 'translateX(-50%)' },
    'bottom-center': { bottom: '1rem', left: '50%', transform: 'translateX(-50%)' },
  }
  return positions[props.position]
})

const notificationClasses = computed(() => {
  const baseClasses = [
    'group relative overflow-hidden pointer-events-auto',
    'transition-all duration-300 ease-out',
    'transform-gpu will-change-transform',
  ]

  // Responsive sizing
  const responsiveClasses = viewport.isMobile
    ? [
        'mx-4 mb-3 px-4 py-3 rounded-xl text-sm max-w-sm',
      ]
    : []
  'mb-4 px-6 py-4 rounded-2xl max-w-md'

  // Variant-based styling
  const variantClasses = {
    filled: {
      success: 'bg-gradient-success text-white border border-success-400 shadow-success-medium',
      error: 'bg-gradient-danger text-white border border-error-400 shadow-error-medium',
      warning: 'bg-gradient-warning text-white border border-warning-400 shadow-warning-medium',
      info: 'bg-gradient-primary text-white border border-primary-400 shadow-blue-medium',
    },
    outlined: {
      success: 'bg-white border-2 border-success-300 text-success-700 shadow-success-soft',
      error: 'bg-white border-2 border-red-300 text-red-700 shadow-error-soft',
      warning: 'bg-white border-2 border-amber-300 text-amber-700 shadow-warning-soft',
      info: 'bg-white border-2 border-primary-300 text-primary-700 shadow-blue-soft',
    },
    glass: {
      success: 'bg-success-50/90 border border-success-200/60 text-success-800 shadow-success-soft backdrop-blur-xl',
      error: 'bg-red-50/90 border border-red-200/60 text-red-800 shadow-error-soft backdrop-blur-xl',
      warning: 'bg-amber-50/90 border border-amber-200/60 text-amber-800 shadow-warning-soft backdrop-blur-xl',
      info: 'bg-primary-50/90 border border-primary-200/60 text-primary-800 shadow-blue-soft backdrop-blur-xl',
    },
    minimal: {
      success: 'bg-success-50/50 border-l-4 border-l-success-500 text-success-800 shadow-sm',
      error: 'bg-red-50/50 border-l-4 border-l-red-500 text-red-800 shadow-sm',
      warning: 'bg-amber-50/50 border-l-4 border-l-amber-500 text-amber-800 shadow-sm',
      info: 'bg-primary-50/50 border-l-4 border-l-primary-500 text-primary-800 shadow-sm',
    },
  }

  // Interactive classes
  const interactiveClasses = props.clickable
    ? [
        'cursor-pointer hover:scale-[1.02] hover:-translate-y-0.5',
      ]
    : []

  return [
    ...baseClasses,
    ...(props.animated ? responsiveClasses : ['mb-4 px-6 py-4 rounded-2xl max-w-md']),
    variantClasses[props.variant][props.type],
    ...interactiveClasses,
  ].join(' ')
})

const iconContainerClasses = computed(() => {
  const size = viewport.isMobile ? 'p-1.5' : 'p-2'

  const colorClasses = {
    success: 'bg-success-100/80 text-success-600 border border-success-200/50',
    error: 'bg-red-100/80 text-red-600 border border-red-200/50',
    warning: 'bg-amber-100/80 text-amber-600 border border-amber-200/50',
    info: 'bg-primary-100/80 text-primary-600 border border-primary-200/50',
  }

  if (props.variant === 'filled') {
    return [
      'flex-shrink-0 rounded-xl transition-all duration-200',
      size,
      'bg-white/20 text-white border border-white/30',
    ].join(' ')
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
    props.variant === 'filled' ? 'text-white' : 'text-current',
  ].join(' ')
})

const messageClasses = computed(() => {
  const size = viewport.isMobile ? 'text-xs' : 'text-sm'
  return [
    'leading-relaxed',
    size,
    props.variant === 'filled' ? 'text-white/90' : 'text-current',
  ].join(' ')
})

const closeButtonClasses = computed(() => {
  const baseClasses = [
    'flex-shrink-0 p-1.5 rounded-lg',
    'transition-all duration-200',
    'focus:outline-none focus:ring-2 focus:ring-offset-1',
    'hover:scale-110',
  ]

  const colorClasses = props.variant === 'filled'
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
  props.variant === 'filled' ? 'bg-white/30' : 'bg-current opacity-30',
].join(' '))

// Animation classes
const enterActiveClass = computed(() => {
  const direction = props.position.includes('top') ? 'up' : 'down'
  return `transition-all duration-400 ease-out`
})

const enterFromClass = computed(() => {
  const direction = props.position.includes('top') ? '-translate-y-full' : 'translate-y-full'
  return `opacity-0 ${direction} scale-95`
})

const enterToClass = computed(() => 'opacity-100 translate-y-0 scale-100')

const leaveActiveClass = computed(() => 'transition-all duration-300 ease-in')

const leaveFromClass = computed(() => 'opacity-100 translate-y-0 scale-100')

const leaveToClass = computed(() => {
  const direction = props.position.includes('top') ? '-translate-y-full' : 'translate-y-full'
  return `opacity-0 ${direction} scale-95`
})

// Methods
const handleDismiss = () => {
  if (timeoutId.value) {
    clearTimeout(timeoutId.value)
  }
  emit('dismiss')
}

const handleClick = (event: MouseEvent) => {
  if (props.clickable) {
    emit('click', event)
  }
}

const handleMouseEnter = () => {
  if (props.pauseOnHover) {
    isHovered.value = true
    if (timeoutId.value) {
      clearTimeout(timeoutId.value)
    }
  }
}

const handleMouseLeave = () => {
  if (props.pauseOnHover) {
    isHovered.value = false
    startAutoHide()
  }
}

const startAutoHide = () => {
  if (props.duration && props.duration > 0) {
    timeoutId.value = setTimeout(() => {
      handleDismiss()
    }, props.duration)
  }
}

// Lifecycle
onMounted(() => {
  if (props.visible) {
    startAutoHide()
  }
})

onUnmounted(() => {
  if (timeoutId.value) {
    clearTimeout(timeoutId.value)
  }
})
</script>

<style scoped>
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

/* Performance optimizations */
.transform-gpu {
  transform: translateZ(0);
}

.will-change-transform {
  will-change: transform;
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
</style>
