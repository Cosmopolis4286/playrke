<template>
  <component
    :is="component"
    :type="component === 'button' ? type : undefined"
    :href="href"
    :to="to"
    :disabled="disabled || loading"
    :class="buttonClasses"
    @click="handleClick"
    @mouseenter="handleMouseEnter"
    @mouseleave="handleMouseLeave"
  >
    <!-- Icon (Left) -->
    <div
      v-if="(iconLeft || leftIcon) && !loading"
      class="auth__button-icon auth__button-icon--left"
      :style="getIconStyle(getLeftIcon())"
    >
      <i :class="buildIconClass(getLeftIcon(), 'transition-all duration-200')"></i>
    </div>

    <!-- Loading Spinner -->
    <div v-if="loading" class="auth__button-loading">
      <i :class="buildIconClass(getLoadingIcon(), 'animate-spin')"></i>
    </div>

    <!-- Success Icon (temporary) -->
    <div v-if="state === 'success' && !loading" class="auth__button-success-icon">
      <i :class="buildIconClass(getIcon('checkCircle'))" :style="buildIconStyle(getIcon('checkCircle'))"></i>
    </div>

    <!-- Error Icon (temporary) -->
    <div v-if="state === 'error' && !loading" class="auth__button-error-icon">
      <i :class="buildIconClass(getIcon('xCircle'))" :style="buildIconStyle(getIcon('xCircle'))"></i>
    </div>

    <!-- Button Text -->
    <span v-if="!iconOnly" class="auth__button-text">
      {{ getCurrentText() }}
    </span>

    <!-- Icon (Right) -->
    <div
      v-if="(iconRight || rightIcon) && !loading && state === 'idle'"
      class="auth__button-icon auth__button-icon--right"
      :style="getIconStyle(getRightIcon())"
    >
      <i :class="buildIconClass(getRightIcon(), 'transition-all duration-200')"></i>
    </div>

    <!-- Ripple Effect -->
    <span
      v-if="ripple"
      ref="rippleRef"
      class="auth__button-ripple"
      :style="rippleStyle"
    ></span>

    <!-- Glow Effect -->
    <div v-if="glow" class="auth__button-glow"></div>
  </component>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue'
import { useIcons } from '@js/composables/utils/useIcons'

const { getIcon, buildIconClass, buildIconStyle } = useIcons()

// Types
interface Props {
  type?: 'button' | 'submit' | 'reset'
  variant?: 'primary' | 'secondary' | 'outline' | 'ghost' | 'danger' | 'success' | 'warning' | 'info' | 'gradient'
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl'
  text?: string
  loadingText?: string
  successText?: string
  errorText?: string
  disabled?: boolean
  loading?: boolean
  state?: 'idle' | 'loading' | 'success' | 'error'
  iconLeft?: string
  iconRight?: string
  leftIcon?: string // Tabler icon name
  rightIcon?: string // Tabler icon name
  iconOnly?: boolean
  ripple?: boolean
  glow?: boolean
  fullWidth?: boolean
  rounded?: boolean
  elevated?: boolean
  animate?: boolean
  // Link/Router support
  href?: string
  to?: string | object
  component?: string
}

// Props
const props = withDefaults(defineProps<Props>(), {
  type: 'button',
  variant: 'primary',
  size: 'md',
  text: '',
  loadingText: '',
  successText: '',
  errorText: '',
  disabled: false,
  loading: false,
  state: 'idle',
  iconLeft: '',
  iconRight: '',
  leftIcon: '',
  rightIcon: '',
  iconOnly: false,
  ripple: true,
  glow: false,
  fullWidth: false,
  rounded: false,
  elevated: false,
  animate: true,
  component: 'button',
})

// Emits
const emit = defineEmits<{
  click: [event: MouseEvent]
  mousedown: [event: MouseEvent]
  mouseup: [event: MouseEvent]
}>()

// State
const rippleRef = ref<HTMLElement>()
const rippleStyle = ref<Record<string, string>>({})
const isHovered = ref(false)
const isPressed = ref(false)
const tempState = ref<'success' | 'error' | null>(null)

// Computed component type
const component = computed(() => {
  if (props.href) return 'a'
  if (props.to) return 'router-link'
  return props.component || 'button'
})

// Computed
const buttonClasses = computed(() => {
  const classes = ['auth__button']

  // Variant classes
  const currentVariant = tempState.value || props.state !== 'idle' ? props.state : props.variant
  classes.push(`auth__button--${currentVariant}`)

  // Size classes
  classes.push(`auth__button--${props.size}`)

  // State classes
  if (props.disabled) classes.push('auth__button--disabled')
  if (props.loading || props.state === 'loading') classes.push('auth__button--loading')
  if (props.state === 'success') classes.push('auth__button--success-state')
  if (props.state === 'error') classes.push('auth__button--error-state')
  if (props.fullWidth) classes.push('auth__button--full-width')
  if (props.rounded) classes.push('auth__button--rounded')
  if (props.elevated) classes.push('auth__button--elevated')
  if (props.glow) classes.push('auth__button--glow')
  if (props.iconOnly) classes.push('auth__button--icon-only')
  if (props.animate) classes.push('auth__button--animated')
  if (isHovered.value) classes.push('auth__button--hovered')
  if (isPressed.value) classes.push('auth__button--pressed')

  return classes.join(' ')
})

const getCurrentText = () => {
  if (props.state === 'loading' && props.loadingText) return props.loadingText
  if (props.state === 'success' && props.successText) return props.successText
  if (props.state === 'error' && props.errorText) return props.errorText
  return props.text
}

const getLeftIcon = () => {
  if (props.iconLeft) {
    return { name: props.iconLeft, type: 'fontawesome' as const }
  }
  if (props.leftIcon) {
    return getIcon(props.leftIcon as any)
  }
  return null
}

const getRightIcon = () => {
  if (props.iconRight) {
    return { name: props.iconRight, type: 'fontawesome' as const }
  }
  if (props.rightIcon) {
    return getIcon(props.rightIcon as any)
  }
  return null
}

const getLoadingIcon = () => {
  return getIcon('loader')
}

const getIconStyle = (iconConfig: any) => {
  if (!iconConfig) return {}
  return buildIconStyle(iconConfig)
}


// Methods
const handleClick = (event: MouseEvent) => {
  if (props.disabled || props.loading) return

  // Create ripple effect
  if (props.ripple) {
    createRipple(event)
  }

  emit('click', event)
  emit('mousedown', event)
}

const handleMouseEnter = () => {
  isHovered.value = true
}

const handleMouseLeave = () => {
  isHovered.value = false
}

const handleMouseDown = () => {
  isPressed.value = true
}

const handleMouseUp = () => {
  isPressed.value = false
}

// Temporary state helpers
const showSuccess = (duration = 2000) => {
  tempState.value = 'success'
  setTimeout(() => {
    tempState.value = null
  }, duration)
}

const showError = (duration = 2000) => {
  tempState.value = 'error'
  setTimeout(() => {
    tempState.value = null
  }, duration)
}

// Expose methods
defineExpose({
  showSuccess,
  showError,
})

const createRipple = (event: MouseEvent) => {
  if (!rippleRef.value) return

  const button = event.currentTarget as HTMLButtonElement
  const rect = button.getBoundingClientRect()
  const size = Math.max(rect.width, rect.height)
  const x = event.clientX - rect.left - size / 2
  const y = event.clientY - rect.top - size / 2

  rippleStyle.value = {
    width: `${size}px`,
    height: `${size}px`,
    left: `${x}px`,
    top: `${y}px`,
    transform: 'scale(0)',
    opacity: '0.6',
  }

  // Trigger animation
  nextTick(() => {
    rippleStyle.value = {
      ...rippleStyle.value,
      transform: 'scale(2)',
      opacity: '0',
      transition: 'all 0.6s ease-out',
    }
  })

  // Clean up
  setTimeout(() => {
    rippleStyle.value = {}
  }, 600)
}
</script>

<style scoped>
/* Base button styles */
.auth__button {
  @apply relative overflow-hidden font-semibold text-sm uppercase tracking-wider transition-all duration-300;
  @apply flex items-center justify-center space-x-2;
  @apply focus:outline-none focus:ring-2 focus:ring-offset-2;
  border-radius: 12px;
  min-height: 48px;
}

/* Variants */
.auth__button--primary {
  @apply text-white focus:ring-blue-500;
  background: linear-gradient(135deg, #0066ff 0%, #4c1d95 100%);
  box-shadow: 0 10px 25px -5px rgba(0, 102, 255, 0.1), 0 4px 6px -2px rgba(0, 102, 255, 0.05);
}

.auth__button--primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 15px 35px -5px rgba(0, 102, 255, 0.2), 0 8px 10px -2px rgba(0, 102, 255, 0.1);
}

.auth__button--secondary {
  @apply bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500;
  box-shadow: 0 4px 14px -2px rgba(107, 114, 128, 0.1);
}

.auth__button--secondary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 8px 20px -2px rgba(107, 114, 128, 0.2);
}

.auth__button--outline {
  @apply bg-transparent border-2 border-blue-600 text-blue-600 hover:bg-blue-50 focus:ring-blue-500;
  box-shadow: inset 0 0 0 1px rgba(59, 130, 246, 0.1);
}

.auth__button--outline:hover:not(:disabled) {
  @apply bg-blue-600 text-white;
  box-shadow: 0 4px 14px -2px rgba(59, 130, 246, 0.3);
}

.auth__button--ghost {
  @apply bg-transparent text-blue-600 hover:bg-blue-50 focus:ring-blue-500;
}

.auth__button--ghost:hover:not(:disabled) {
  @apply bg-blue-600 text-white;
  box-shadow: 0 4px 14px -2px rgba(59, 130, 246, 0.2);
}

.auth__button--danger {
  @apply bg-red-600 text-white hover:bg-red-700 focus:ring-red-500;
  box-shadow: 0 4px 14px -2px rgba(239, 68, 68, 0.2);
}

.auth__button--danger:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 8px 20px -2px rgba(239, 68, 68, 0.3);
}

.auth__button--success {
  @apply bg-emerald-600 text-white focus:ring-emerald-500;
  box-shadow: 0 4px 14px -2px rgba(16, 185, 129, 0.2);
}

.auth__button--success:hover:not(:disabled) {
  @apply bg-emerald-700;
  transform: translateY(-1px);
  box-shadow: 0 8px 20px -2px rgba(16, 185, 129, 0.3);
}

.auth__button--warning {
  @apply bg-amber-600 text-white focus:ring-amber-500;
  box-shadow: 0 4px 14px -2px rgba(245, 158, 11, 0.2);
}

.auth__button--warning:hover:not(:disabled) {
  @apply bg-amber-700;
  transform: translateY(-1px);
  box-shadow: 0 8px 20px -2px rgba(245, 158, 11, 0.3);
}

.auth__button--info {
  @apply bg-blue-500 text-white focus:ring-blue-400;
  box-shadow: 0 4px 14px -2px rgba(59, 130, 246, 0.2);
}

.auth__button--info:hover:not(:disabled) {
  @apply bg-blue-600;
  transform: translateY(-1px);
  box-shadow: 0 8px 20px -2px rgba(59, 130, 246, 0.3);
}

.auth__button--gradient {
  @apply text-white focus:ring-purple-500;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  box-shadow: 0 10px 25px -5px rgba(102, 126, 234, 0.2);
}

.auth__button--gradient:hover:not(:disabled) {
  transform: translateY(-1px) scale(1.02);
  box-shadow: 0 15px 35px -5px rgba(102, 126, 234, 0.3);
}

/* Sizes */
.auth__button--xs {
  @apply text-xs px-3 py-1;
  min-height: 32px;
  border-radius: 8px;
}

.auth__button--sm {
  @apply text-xs px-4;
  min-height: 36px;
  border-radius: 10px;
}

.auth__button--md {
  @apply text-sm px-6;
  min-height: 48px;
  border-radius: 12px;
}

.auth__button--lg {
  @apply text-base px-8;
  min-height: 56px;
  border-radius: 14px;
}

.auth__button--xl {
  @apply text-lg px-10;
  min-height: 64px;
  border-radius: 16px;
}

/* States */
.auth__button--full-width {
  @apply w-full;
}

.auth__button--rounded {
  @apply rounded-full;
}

.auth__button--disabled {
  @apply opacity-60 cursor-not-allowed;
  transform: none !important;
  box-shadow: none !important;
}

.auth__button--loading {
  @apply cursor-wait;
}

.auth__button--hovered {
  @apply transform -translate-y-0.5;
}

/* Icons */
.auth__button-icon {
  @apply transition-transform duration-200;
}

.auth__button-icon--left {
  @apply mr-2 -ml-1;
}

.auth__button-icon--right {
  @apply ml-2 -mr-1;
}

.auth__button-icon--animated {
  transition: transform 0.2s ease-in-out;
}

.auth__button:hover .auth__button-icon--animated {
  transform: scale(1.1);
}

/* Loading */
.auth__button-loading {
  @apply mr-2;
}

/* Text */
.auth__button-text {
  @apply relative z-10;
}

/* Ripple effect */
.auth__button-ripple {
  @apply absolute bg-white rounded-full pointer-events-none;
  z-index: 0;
}

/* Focus styles */
.auth__button:focus-visible {
  @apply ring-2 ring-offset-2;
}

/* Active state */
.auth__button:active:not(:disabled) {
  transform: translateY(0) !important;
}

/* Pulse animation for loading */
.auth__button--loading {
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.8;
  }
}

/* Gradient animation for primary button */
.auth__button--primary::before {
  content: '';
  @apply absolute inset-0 bg-white opacity-0 transition-opacity duration-300;
  border-radius: inherit;
}

.auth__button--primary:hover::before:not(:disabled) {
  opacity: 0.1;
}

/* Success state animation */
.auth__button--success {
  @apply bg-green-600 text-white;
  animation: successPulse 0.6s ease-out;
}

@keyframes successPulse {
  0% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
  100% {
    transform: scale(1);
  }
}

/* Error state animation */
.auth__button--error {
  @apply bg-red-600 text-white;
  animation: shake 0.5s ease-in-out;
}

@keyframes shake {
  0%, 100% {
    transform: translateX(0);
  }
  25% {
    transform: translateX(-2px);
  }
  75% {
    transform: translateX(2px);
  }
}

/* Additional button states */
.auth__button--pressed {
  @apply transform translate-y-0.5;
}

.auth__button--elevated {
  box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.3);
}

.auth__button--elevated:hover:not(:disabled) {
  box-shadow: 0 25px 50px -15px rgba(0, 0, 0, 0.4);
}

.auth__button--glow {
  position: relative;
  overflow: visible;
}

.auth__button-glow {
  position: absolute;
  inset: -2px;
  background: linear-gradient(45deg, #ff006e, #ff5722, #ffc107, #4caf50, #2196f3, #9c27b0);
  border-radius: inherit;
  z-index: -1;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.auth__button--glow:hover .auth__button-glow {
  opacity: 0.8;
  animation: glowRotate 2s linear infinite;
}

@keyframes glowRotate {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.auth__button--icon-only {
  @apply aspect-square p-0;
  min-width: var(--button-height);
}

.auth__button--icon-only.auth__button--xs {
  --button-height: 32px;
}

.auth__button--icon-only.auth__button--sm {
  --button-height: 36px;
}

.auth__button--icon-only.auth__button--md {
  --button-height: 48px;
}

.auth__button--icon-only.auth__button--lg {
  --button-height: 56px;
}

.auth__button--icon-only.auth__button--xl {
  --button-height: 64px;
}

.auth__button--animated {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.auth__button--success-state {
  animation: successPulse 0.8s ease-out;
}

.auth__button--error-state {
  animation: shake 0.5s ease-in-out;
}

.auth__button-success-icon,
.auth__button-error-icon {
  @apply flex items-center justify-center;
  animation: iconBounce 0.6s ease-out;
}

@keyframes iconBounce {
  0%, 20%, 50%, 80%, 100% {
    transform: translateY(0);
  }
  40% {
    transform: translateY(-8px);
  }
  60% {
    transform: translateY(-4px);
  }
}

/* Responsive */
@media (max-width: 640px) {
  .auth__button {
    min-height: 44px;
  }

  .auth__button--xs {
    @apply text-xs px-2;
    min-height: 28px;
  }

  .auth__button--sm {
    min-height: 36px;
  }

  .auth__button--lg,
  .auth__button--xl {
    min-height: 52px;
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .auth__button {
    @apply border-2;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .auth__button,
  .auth__button-icon,
  .auth__button-ripple,
  .auth__button--loading,
  .auth__button-glow {
    transition: none;
    animation: none;
  }

  .auth__button--animated {
    transition: none;
  }

  .auth__button--success-state,
  .auth__button--error-state {
    animation: none;
  }
}

/* Focus trap for keyboard navigation */
.auth__button:focus-visible {
  @apply outline-none ring-2 ring-offset-2 ring-offset-gray-800;
}
</style>