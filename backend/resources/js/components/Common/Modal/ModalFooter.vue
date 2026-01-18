<template>
  <footer
    :class="footerClasses"
    role="contentinfo"
  >
    <!-- Background Effects -->
    <div
      v-if="showEffects"
      :class="backgroundClasses"
    />

    <!-- Content Container -->
    <div :class="contentClasses">
      <!-- Left Section -->
      <div
        v-if="showLeftSection"
        :class="leftSectionClasses"
      >
        <slot
          name="left"
          :props="leftSlotProps"
        >
          <!-- Progress Indicator - TODO: Create ModalFooterProgress component -->
          <!-- <ModalFooterProgress
            v-if="showProgress"
            :current="currentStep"
            :total="totalSteps"
            :variant="progressVariant"
            :size="size"
          /> -->

          <!-- Info Text - TODO: Create ModalFooterInfo component -->
          <!-- <ModalFooterInfo
            v-if="infoText || errorText || warningText"
            :info="infoText"
            :error="errorText"
            :warning="warningText"
            :variant="variant"
            :size="size"
          /> -->

          <!-- Temporary inline info display -->
          <div v-if="infoText || errorText || warningText" class="text-sm">
            <p v-if="infoText" class="text-blue-600 dark:text-blue-400">
              {{ infoText }}
            </p>
            <p v-if="errorText" class="text-red-600 dark:text-red-400">
              {{ errorText }}
            </p>
            <p v-if="warningText" class="text-yellow-600 dark:text-yellow-400">
              {{ warningText }}
            </p>
          </div>
        </slot>
      </div>

      <!-- Main Actions -->
      <div :class="actionsClasses">
        <slot
          name="actions"
          :props="actionsSlotProps"
        >
          <!-- Custom Actions - TODO: Create ModalFooterAction component -->
          <!-- <ModalFooterAction
            v-for="action in customActions"
            :key="action.id"
            v-bind="action"
            :class="actionClasses(action)"
            @click="handleActionClick(action)"
          /> -->

          <!-- Default Button Group -->
          <div
            v-if="showDefaultButtons"
            :class="buttonGroupClasses"
          >
            <!-- Cancel Button -->
            <BaseButton
              v-if="showCancelButton"
              :variant="cancelVariant"
              :size="buttonSize"
              :disabled="disabled || cancelDisabled"
              :loading="cancelLoading"
              :class="cancelButtonClasses"
              @click="handleCancel"
            >
              {{ cancelText }}
            </BaseButton>

            <!-- Confirm Button -->
            <BaseButton
              v-if="showConfirmButton"
              :variant="confirmVariant"
              :size="buttonSize"
              :disabled="disabled || confirmDisabled"
              :loading="loading || confirmLoading"
              :class="confirmButtonClasses"
              @click="handleConfirm"
            >
              {{ confirmText }}
            </BaseButton>
          </div>
        </slot>
      </div>

      <!-- Right Section -->
      <div
        v-if="showRightSection"
        :class="rightSectionClasses"
      >
        <slot
          name="right"
          :props="rightSlotProps"
        >
          <!-- Additional Actions - TODO: Create ModalFooterAction component -->
          <!-- <ModalFooterAction
            v-for="action in rightActions"
            :key="action.id"
            v-bind="action"
            :class="actionClasses(action)"
            @click="handleActionClick(action)"
          /> -->
        </slot>
      </div>
    </div>

    <!-- Status Bar - TODO: Create ModalFooterStatusBar component -->
    <!-- <ModalFooterStatusBar
      v-if="showStatusBar"
      :status="status"
      :message="statusMessage"
      :variant="statusVariant"
      :progress="statusProgress"
      :class="statusBarClasses"
    /> -->
  </footer>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
// useResponsive auto-imported by Nuxt

export interface ModalFooterAction {
  id: string
  label: string
  icon?: unknown
  iconName?: string
  variant?: 'primary' | 'secondary' | 'ghost' | 'danger' | 'success' | 'warning'
  size?: 'sm' | 'md' | 'lg'
  disabled?: boolean
  loading?: boolean
  position?: 'left' | 'center' | 'right'
  onClick?: () => void
}

export interface ModalFooterProps {
  // Layout
  variant?: 'default' | 'primary' | 'secondary' | 'minimal' | 'gaming' | 'premium'
  size?: 'sm' | 'md' | 'lg' | 'xl'
  layout?: 'simple' | 'complex' | 'compact'

  // Default buttons
  showDefaultButtons?: boolean
  showCancelButton?: boolean
  showConfirmButton?: boolean

  // Button properties
  cancelText?: string
  confirmText?: string
  cancelVariant?: 'secondary' | 'ghost' | 'danger'
  confirmVariant?: 'primary' | 'success' | 'danger' | 'warning'
  buttonSize?: 'sm' | 'md' | 'lg'

  // Button states
  disabled?: boolean
  loading?: boolean
  cancelDisabled?: boolean
  confirmDisabled?: boolean
  cancelLoading?: boolean
  confirmLoading?: boolean

  // Icons
  cancelIcon?: unknown
  confirmIcon?: unknown

  // Custom actions
  customActions?: ModalFooterAction[]
  rightActions?: ModalFooterAction[]

  // Progress
  showProgress?: boolean
  currentStep?: number
  totalSteps?: number
  progressVariant?: 'default' | 'primary' | 'success' | 'warning' | 'danger'

  // Info messages
  infoText?: string
  errorText?: string
  warningText?: string

  // Status bar
  showStatusBar?: boolean
  status?: 'idle' | 'loading' | 'success' | 'error' | 'warning'
  statusMessage?: string
  statusVariant?: 'default' | 'primary' | 'success' | 'warning' | 'danger'
  statusProgress?: number

  // Visual effects
  showEffects?: boolean
  bordered?: boolean
  shadow?: boolean
  sticky?: boolean

  // Responsive
  responsive?: boolean
  mobileStack?: boolean
}

defineOptions({ name: 'ModalFooter' })

const props = withDefaults(defineProps<ModalFooterProps>(), {
  variant: 'default',
  size: 'md',
  layout: 'simple',
  showDefaultButtons: true,
  showCancelButton: true,
  showConfirmButton: true,
  cancelText: 'Cancelar',
  confirmText: 'Confirmar',
  cancelVariant: 'secondary',
  confirmVariant: 'primary',
  buttonSize: 'md',
  disabled: false,
  loading: false,
  cancelDisabled: false,
  confirmDisabled: false,
  cancelLoading: false,
  confirmLoading: false,
  customActions: () => [],
  rightActions: () => [],
  showProgress: false,
  currentStep: 1,
  totalSteps: 1,
  progressVariant: 'primary',
  showStatusBar: false,
  status: 'idle',
  statusVariant: 'default',
  statusProgress: 0,
  showEffects: true,
  bordered: true,
  shadow: false,
  sticky: false,
  responsive: true,
  mobileStack: true,
})

const emit = defineEmits<{
  confirm: []
  cancel: []
  action: [action: ModalFooterAction]
}>()

// Composables
const { isMobile, isTablet } = useResponsive()

// State
const isHovered = ref(false)

// Computed properties
const showLeftSection = computed(() =>
  props.showProgress || props.infoText || props.errorText || props.warningText,
)

const showRightSection = computed(() =>
  props.rightActions.length > 0,
)

// Computed Classes
const footerClasses = computed(() => [
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
    'border-t border-white/10': props.bordered && props.variant !== 'minimal',
    'border-t border-gray-200 dark:border-gray-700': props.bordered && props.variant === 'minimal',
  },

  // Rounded corners
  {
    'rounded-b-xl': props.size === 'sm',
    'rounded-b-2xl': props.size === 'md' || props.size === 'lg',
    'rounded-b-3xl': props.size === 'xl',
  },

  // Shadow
  {
    'shadow-lg': props.shadow,
    'shadow-xl': props.shadow && props.size === 'xl',
  },

  // Sticky
  {
    'sticky bottom-0 z-30': props.sticky,
  },

  // Responsive
  {
    'px-2 py-2': isMobile.value && props.responsive,
    'px-3 py-2': isTablet.value && props.responsive,
  },
])

const backgroundClasses = computed(() => [
  'absolute inset-0 transition-all duration-300',

  // Variant backgrounds
  {
    'bg-gray-50/80 dark:bg-gray-800/80': props.variant === 'default',
    'bg-blue-50/80 dark:bg-blue-900/20': props.variant === 'primary',
    'bg-gray-100/80 dark:bg-gray-700/80': props.variant === 'secondary',
    'bg-white dark:bg-gray-800': props.variant === 'minimal',
    'bg-gradient-to-r from-purple-900/20 to-pink-900/20': props.variant === 'gaming',
    'bg-gradient-to-r from-yellow-50/80 to-amber-50/80 dark:from-yellow-900/20 dark:to-amber-900/20': props.variant === 'premium',
  },

  // Effects
  {
    'backdrop-blur-sm': props.showEffects,
  },
])

const contentClasses = computed(() => [
  'relative z-10 flex items-center',

  // Layout variations
  {
    'justify-between': props.layout === 'complex' && !isMobile.value,
    'justify-end': props.layout === 'simple' && !isMobile.value,
    'justify-center': props.layout === 'compact' && !isMobile.value,
    'flex-col space-y-3': isMobile.value && props.mobileStack,
    'flex-row': !isMobile.value || !props.mobileStack,
  },

  // Spacing
  {
    'gap-2': props.size === 'sm',
    'gap-3': props.size === 'md',
    'gap-4': props.size === 'lg' || props.size === 'xl',
  },
])

const leftSectionClasses = computed(() => [
  'flex items-center',
  {
    'gap-2': props.size === 'sm',
    'gap-3': props.size === 'md',
    'gap-4': props.size === 'lg' || props.size === 'xl',
  },
  {
    'order-2 w-full justify-center': isMobile.value && props.mobileStack,
    'order-1': !isMobile.value || !props.mobileStack,
  },
])

const actionsClasses = computed(() => [
  'flex items-center',
  {
    'gap-2': props.size === 'sm',
    'gap-3': props.size === 'md',
    'gap-4': props.size === 'lg' || props.size === 'xl',
  },
  {
    'order-1 w-full justify-center': isMobile.value && props.mobileStack,
    'order-2': !isMobile.value || !props.mobileStack,
  },
])

const rightSectionClasses = computed(() => [
  'flex items-center',
  {
    'gap-2': props.size === 'sm',
    'gap-3': props.size === 'md',
    'gap-4': props.size === 'lg' || props.size === 'xl',
  },
  {
    'order-3 w-full justify-center': isMobile.value && props.mobileStack,
    'order-3': !isMobile.value || !props.mobileStack,
  },
])

const buttonGroupClasses = computed(() => [
  'flex items-center',
  {
    'gap-2': props.size === 'sm',
    'gap-3': props.size === 'md',
    'gap-4': props.size === 'lg' || props.size === 'xl',
  },
  {
    'w-full': isMobile.value && props.mobileStack,
  },
])

const cancelButtonClasses = computed(() => [
  'transition-all duration-200',
  {
    'flex-1': isMobile.value && props.mobileStack,
  },
])

const confirmButtonClasses = computed(() => [
  'transition-all duration-200',
  {
    'flex-1': isMobile.value && props.mobileStack,
  },
])

const statusBarClasses = computed(() => [
  'absolute bottom-0 left-0 right-0',
])

const actionClasses = (action: ModalFooterAction) => [
  'transition-all duration-200',
  {
    'opacity-50 cursor-not-allowed': action.disabled,
    'flex-1': isMobile.value && props.mobileStack && action.position !== 'right',
  },
]

// Slot props
const leftSlotProps = computed(() => ({
  variant: props.variant,
  size: props.size,
  isMobile: isMobile.value,
}))

const actionsSlotProps = computed(() => ({
  variant: props.variant,
  size: props.size,
  buttonSize: props.buttonSize,
  disabled: props.disabled,
  loading: props.loading,
  isMobile: isMobile.value,
}))

const rightSlotProps = computed(() => ({
  variant: props.variant,
  size: props.size,
  isMobile: isMobile.value,
}))

// Methods
const handleConfirm = () => {
  emit('confirm')
}

const handleCancel = () => {
  emit('cancel')
}

const handleActionClick = (action: ModalFooterAction) => {
  if (action.disabled) { return }

  if (action.onClick) {
    action.onClick()
  }

  emit('action', action)
}
</script>

<style scoped>
/* Footer Animations */
@keyframes footer-slide-up {
  0% { transform: translateY(100%); opacity: 0; }
  100% { transform: translateY(0); opacity: 1; }
}

@keyframes footer-glow {
  0%, 100% { box-shadow: 0 -4px 20px rgba(59, 130, 246, 0.1); }
  50% { box-shadow: 0 -4px 30px rgba(59, 130, 246, 0.2); }
}

@keyframes status-pulse {
  0%, 100% { opacity: 0.8; }
  50% { opacity: 1; }
}

/* Base transition */
footer {
  transition: all 0.3s ease;
}

/* Gaming effects */
.gaming-footer {
  animation: footer-glow 3s ease-in-out infinite;
}

.gaming-footer::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(168, 85, 247, 0.5), transparent);
  animation: shimmer 2s infinite;
}

/* Premium effects */
.premium-footer {
  background: linear-gradient(135deg, rgba(255, 215, 0, 0.1), rgba(255, 193, 7, 0.1));
}

.premium-footer::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(90deg, transparent, rgba(255, 193, 7, 0.8), transparent);
  animation: shimmer 3s infinite;
}

/* Sticky footer enhancement */
.sticky-footer {
  backdrop-filter: blur(10px);
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

/* Button group animations */
.button-group-enter-active {
  transition: all 0.3s ease-out;
}

.button-group-enter-from {
  opacity: 0;
  transform: translateY(20px);
}

.button-group-enter-to {
  opacity: 1;
  transform: translateY(0);
}

/* Mobile responsive enhancements */
@media (max-width: 640px) {
  .mobile-stack {
    flex-direction: column;
    gap: 0.75rem;
  }

  .mobile-stack .button-group {
    width: 100%;
    display: flex;
    gap: 0.5rem;
  }

  .mobile-stack .button-group > * {
    flex: 1;
  }
}

/* Focus and accessibility */
footer:focus-within {
  outline: 2px solid rgb(59 130 246);
  outline-offset: -2px;
}

/* High contrast mode */
@media (prefers-contrast: high) {
  footer {
    border-top: 2px solid;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* Print styles */
@media print {
  footer {
    background: white !important;
    color: black !important;
    box-shadow: none !important;
    border-top: 1px solid black !important;
  }
}

/* Dark mode enhancements */
@media (prefers-color-scheme: dark) {
  .dark-footer {
    background: rgba(17, 24, 39, 0.9);
    border-top-color: rgba(75, 85, 99, 0.3);
  }
}
</style>
