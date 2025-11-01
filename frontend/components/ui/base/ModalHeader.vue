<template>
  <header
    :class="headerClasses"
    role="banner"
  >
    <!-- Dynamic Background -->
    <div :class="backgroundClasses" />

    <!-- Background Effects -->
    <div
      v-if="showEffects"
      :class="effectsClasses"
    />

    <!-- Content Container -->
    <div :class="contentClasses">
      <!-- Header Content -->
      <div :class="headerContentClasses">
        <slot
          name="header"
          :props="headerSlotProps"
        >
          <!-- SharedIcon Section -->
          <SharedIcon
            v-if="icon || iconName"
            :name="iconName"
            :size="iconSize"
            :class="iconContainerClasses"
          />

          <!-- Text Section -->
          <div
            v-if="title || subtitle || description"
            :class="textContainerClasses"
          >
            <h3
              v-if="title"
              class="text-lg font-semibold text-white"
            >
              {{ title }}
            </h3>
            <p
              v-if="subtitle"
              class="text-sm text-white/80"
            >
              {{ subtitle }}
            </p>
            <p
              v-if="description"
              class="text-xs text-white/60"
            >
              {{ description }}
            </p>
          </div>
        </slot>
      </div>

      <!-- Actions Section -->
      <div
        v-if="showActions"
        :class="actionsClasses"
      >
        <slot
          name="actions"
          :props="actionsSlotProps"
        >
          <!-- Custom Actions -->
          <BaseButton
            v-for="action in actions"
            :key="action.id"
            :disabled="action.disabled"
            variant="ghost"
            size="sm"
            :class="actionClasses(action)"
            @click="handleActionClick(action)"
          >
            {{ action.label }}
          </BaseButton>

          <!-- Close Button -->
          <BaseButton
            v-if="closable"
            variant="ghost"
            size="sm"
            :class="closeButtonClasses"
            aria-label="Cerrar"
            @click="handleClose"
          >
            <SharedIcon
              name="x-mark"
              size="sm"
            />
          </BaseButton>
        </slot>
      </div>
    </div>

    <!-- Progress Bar (if applicable) -->
    <div
      v-if="showProgress"
      :class="progressClasses"
    >
      <div class="w-full bg-gray-200 rounded-full h-2">
        <div
          class="bg-blue-600 h-2 rounded-full transition-all duration-300"
          :style="{ width: `${progress}%` }"
        />
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
// useResponsive auto-imported by Nuxt

export interface ModalHeaderAction {
  id: string
  label: string
  icon?: unknown
  iconName?: string
  variant?: 'primary' | 'secondary' | 'ghost' | 'danger'
  size?: 'sm' | 'md' | 'lg'
  disabled?: boolean
  loading?: boolean
  tooltip?: string
  onClick?: () => void
}

export interface ModalHeaderProps {
  // Content
  title?: string
  subtitle?: string
  description?: string

  // Icon
  icon?: unknown
  iconName?: string
  iconVariant?: 'default' | 'primary' | 'success' | 'warning' | 'danger'
  iconSize?: 'sm' | 'md' | 'lg' | 'xl'
  iconAnimated?: boolean

  // Styling
  variant?: 'default' | 'primary' | 'secondary' | 'success' | 'warning' | 'danger' | 'gaming' | 'premium' | 'minimal'
  size?: 'sm' | 'md' | 'lg' | 'xl'

  // Text styling
  textVariant?: 'default' | 'contrast' | 'muted'
  textSize?: 'sm' | 'md' | 'lg' | 'xl'
  textAlignment?: 'left' | 'center' | 'right'
  textTruncate?: boolean

  // Close button
  closable?: boolean
  closeButtonVariant?: 'default' | 'ghost' | 'minimal'
  closeButtonSize?: 'sm' | 'md' | 'lg'
  closeButtonPosition?: 'top-right' | 'inline'
  closeButtonAnimated?: boolean

  // Actions
  actions?: ModalHeaderAction[]

  // Progress
  showProgress?: boolean
  progress?: number
  progressVariant?: 'default' | 'primary' | 'success' | 'warning' | 'danger'
  progressAnimated?: boolean

  // Layout
  bordered?: boolean
  shadow?: boolean
  sticky?: boolean

  // Effects
  showEffects?: boolean
  gradient?: boolean
  blur?: boolean
  animated?: boolean

  // Responsive
  responsive?: boolean
  mobileCollapse?: boolean
}

defineOptions({ name: 'ModalHeader' })

const props = withDefaults(defineProps<ModalHeaderProps>(), {
  variant: 'default',
  size: 'md',
  iconVariant: 'default',
  iconSize: 'md',
  iconAnimated: true,
  textVariant: 'default',
  textSize: 'md',
  textAlignment: 'left',
  textTruncate: false,
  closable: true,
  closeButtonVariant: 'default',
  closeButtonSize: 'md',
  closeButtonPosition: 'inline',
  closeButtonAnimated: true,
  actions: () => [],
  showProgress: false,
  progress: 0,
  progressVariant: 'primary',
  progressAnimated: true,
  bordered: true,
  shadow: false,
  sticky: false,
  showEffects: true,
  gradient: true,
  blur: false,
  animated: true,
  responsive: true,
  mobileCollapse: false,
})

const emit = defineEmits<{
  close: []
  action: [action: ModalHeaderAction]
}>()

// Composables
const { isMobile, isTablet } = useResponsive()

// State
const isHovered = ref(false)

// Computed Classes
const headerClasses = computed(() => [
  // Base
  'relative overflow-hidden transition-all duration-300',

  // Responsive sizing
  {
    'px-3 py-2': props.size === 'sm',
    'px-4 py-3': props.size === 'md',
    'px-6 py-4': props.size === 'lg',
    'px-8 py-6': props.size === 'xl',
  },

  // Borders
  {
    'border-b border-white/10': props.bordered && props.variant !== 'minimal',
    'border-b border-gray-200 dark:border-gray-700': props.bordered && props.variant === 'minimal',
  },

  // Rounded corners
  {
    'rounded-t-xl': props.size === 'sm',
    'rounded-t-2xl': props.size === 'md' || props.size === 'lg',
    'rounded-t-3xl': props.size === 'xl',
  },

  // Shadow
  {
    'shadow-lg': props.shadow,
    'shadow-xl': props.shadow && props.size === 'xl',
  },

  // Sticky
  {
    'sticky top-0 z-30': props.sticky,
  },

  // Responsive
  {
    'px-2 py-2': isMobile.value && props.responsive,
    'px-3 py-2': isTablet.value && props.responsive,
  },

  // Hover effects
  {
    'hover:shadow-md': props.animated,
    'cursor-pointer': props.animated,
  },
])

const backgroundClasses = computed(() => [
  'absolute inset-0 transition-all duration-500',

  // Variant backgrounds
  {
    // Default variants
    'bg-gradient-to-r from-blue-600 to-purple-600': props.variant === 'default' && props.gradient,
    'bg-blue-600': props.variant === 'default' && !props.gradient,

    // Primary
    'bg-gradient-to-r from-blue-500 to-blue-700': props.variant === 'primary' && props.gradient,
    'bg-blue-500': props.variant === 'primary' && !props.gradient,

    // Secondary
    'bg-gradient-to-r from-gray-600 to-gray-800': props.variant === 'secondary' && props.gradient,
    'bg-gray-700': props.variant === 'secondary' && !props.gradient,

    // Success
    'bg-gradient-to-r from-green-500 to-green-700': props.variant === 'success' && props.gradient,
    'bg-green-600': props.variant === 'success' && !props.gradient,

    // Warning
    'bg-gradient-to-r from-yellow-500 to-orange-600': props.variant === 'warning' && props.gradient,
    'bg-yellow-600': props.variant === 'warning' && !props.gradient,

    // Danger
    'bg-gradient-to-r from-red-500 to-red-700': props.variant === 'danger' && props.gradient,
    'bg-red-600': props.variant === 'danger' && !props.gradient,

    // Gaming
    'bg-gradient-to-r from-purple-600 via-pink-600 to-blue-600': props.variant === 'gaming' && props.gradient,
    'bg-purple-600': props.variant === 'gaming' && !props.gradient,

    // Premium
    'bg-gradient-to-r from-yellow-400 via-yellow-500 to-amber-600': props.variant === 'premium' && props.gradient,
    'bg-yellow-500': props.variant === 'premium' && !props.gradient,

    // Minimal
    'bg-white dark:bg-gray-800': props.variant === 'minimal',
  },

  // Blur effect
  {
    'backdrop-blur-sm': props.blur,
  },

  // Hover effects
  {
    'scale-105': isHovered.value && props.animated,
  },
])

const effectsClasses = computed(() => [
  'absolute inset-0 pointer-events-none',
  'bg-gradient-to-r from-white/10 via-transparent to-white/10',
  'animate-pulse opacity-50',
  {
    'animate-bounce': props.variant === 'gaming',
    'animate-pulse': props.variant === 'premium',
  },
])

const contentClasses = computed(() => [
  'relative z-10 flex items-center justify-between',
  {
    'flex-col space-y-3': props.mobileCollapse && isMobile.value,
    'flex-row': !props.mobileCollapse || !isMobile.value,
  },
])

const headerContentClasses = computed(() => [
  'flex items-center',
  {
    'gap-2': props.size === 'sm',
    'gap-3': props.size === 'md',
    'gap-4': props.size === 'lg' || props.size === 'xl',
  },
  {
    'flex-col items-start space-y-2': props.mobileCollapse && isMobile.value,
    'flex-row': !props.mobileCollapse || !isMobile.value,
  },
])

const showActions = computed(() =>
  props.closable || props.actions.length > 0,
)

const actionsClasses = computed(() => [
  'flex items-center',
  {
    'gap-1': props.size === 'sm',
    'gap-2': props.size === 'md',
    'gap-3': props.size === 'lg' || props.size === 'xl',
  },
  {
    'mt-2 w-full justify-end': props.mobileCollapse && isMobile.value,
    'ml-auto': !props.mobileCollapse || !isMobile.value,
  },
])

const iconContainerClasses = computed(() => [
  'shrink-0',
  {
    'order-1': props.mobileCollapse && isMobile.value,
  },
])

const textContainerClasses = computed(() => [
  {
    'order-2 min-w-0': props.mobileCollapse && isMobile.value,
    'min-w-0': !props.mobileCollapse || !isMobile.value,
  },
])

const closeButtonClasses = computed(() => [
  'shrink-0',
  {
    'order-3': props.mobileCollapse && isMobile.value,
  },
])

const progressClasses = computed(() => [
  'absolute bottom-0 left-0 right-0',
  'h-1 overflow-hidden',
])

const actionClasses = (action: ModalHeaderAction) => [
  'shrink-0',
  {
    'opacity-50 cursor-not-allowed': action.disabled,
  },
]

// Slot props
const headerSlotProps = computed(() => ({
  variant: props.variant,
  size: props.size,
  isMobile: isMobile.value,
  isTablet: isTablet.value,
}))

const actionsSlotProps = computed(() => ({
  variant: props.variant,
  size: props.size,
  actions: props.actions,
  closable: props.closable,
}))

// Methods
const handleClose = () => {
  emit('close')
}

const handleActionClick = (action: ModalHeaderAction) => {
  if (action.disabled) { return }

  if (action.onClick) {
    action.onClick()
  }

  emit('action', action)
}

// Lifecycle
onMounted(() => {
  // Any initialization logic
})
</script>

<style scoped>
/* Header Animations */
@keyframes header-glow {
  0%, 100% { box-shadow: 0 0 20px rgba(59, 130, 246, 0.3); }
  50% { box-shadow: 0 0 30px rgba(59, 130, 246, 0.5); }
}

@keyframes header-pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.02); }
}

@keyframes shimmer {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}

/* Gaming Effects */
.gaming-glow {
  animation: header-glow 2s ease-in-out infinite;
}

.premium-pulse {
  animation: header-pulse 3s ease-in-out infinite;
}

/* Shimmer effect for premium variant */
.shimmer-effect::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  animation: shimmer 2s infinite;
}

/* Focus and accessibility */
header:focus-within {
  outline: 2px solid rgb(59 130 246);
  outline-offset: 2px;
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  header {
    border: 2px solid;
  }
}

/* Print styles */
@media print {
  header {
    background: white !important;
    color: black !important;
    box-shadow: none !important;
  }
}
</style>
