<template>
  <teleport to="body">
    <div class="auth__toast-container" :class="containerClasses">
      <transition-group name="toast" tag="div" class="auth__toast-wrapper">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="getToastClasses(toast)"
          role="alert"
          :aria-live="toast.type === 'error' ? 'assertive' : 'polite'"
          @click="handleToastClick(toast)"
          @mouseenter="pauseTimer(toast.id)"
          @mouseleave="resumeTimer(toast.id)"
        >
          <!-- Icon -->
          <div class="auth__toast-icon" :style="getIconStyle(toast)">
            <i :class="getToastIcon(toast.type).name"></i>
          </div>

          <!-- Content -->
          <div class="auth__toast-content">
            <h4 v-if="toast.title" class="auth__toast-title">
              {{ toast.title }}
            </h4>
            <p class="auth__toast-message" v-html="toast.message"></p>
            <div v-if="toast.actions?.length" class="auth__toast-actions">
              <button
                v-for="action in toast.actions"
                :key="action.label"
                type="button"
                :class="getActionClasses(action, toast)"
                @click.stop="handleAction(action, toast)"
              >
                <i v-if="action.icon" :class="action.icon" class="mr-1"></i>
                {{ action.label }}
              </button>
            </div>
          </div>

          <!-- Close Button -->
          <button
            v-if="toast.closable !== false"
            type="button"
            class="auth__toast-close"
            @click.stop="removeToast(toast.id)"
            :aria-label="`Cerrar ${toast.type}`"
          >
            <i :class="getIcon('close').name"></i>
          </button>

          <!-- Progress Bar -->
          <div
            v-if="toast.duration > 0 && !toast.persistent"
            class="auth__toast-progress"
            :class="`auth__toast-progress--${toast.type}`"
            :style="getProgressStyle(toast)"
          ></div>

          <!-- Ripple Effect -->
          <div v-if="toast.ripple" class="auth__toast-ripple"></div>
        </div>
      </transition-group>
    </div>
  </teleport>
</template>

<script setup lang="ts">
import { ref, computed, onUnmounted, nextTick } from 'vue'
import { useIcons } from '../../composables/useIcons'

const { getToastIcon, getIcon } = useIcons()

// Types
interface ToastAction {
  label: string
  icon?: string
  handler: () => void
  variant?: 'primary' | 'secondary' | 'ghost'
}

interface Toast {
  id: string
  type: 'success' | 'error' | 'warning' | 'info' | 'loading'
  title?: string
  message: string
  duration?: number
  closable?: boolean
  persistent?: boolean
  ripple?: boolean
  position?: 'top-right' | 'top-left' | 'bottom-right' | 'bottom-left' | 'top-center' | 'bottom-center'
  actions?: ToastAction[]
  onClose?: () => void
  pausedTime?: number
  startTime?: number
}

// Props
interface Props {
  position?: Toast['position']
  maxToasts?: number
  stackable?: boolean
  groupSimilar?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  position: 'bottom-right',
  maxToasts: 5,
  stackable: true,
  groupSimilar: false,
})

// State
const toasts = ref<Toast[]>([])
const timers = new Map<string, NodeJS.Timeout>()
const pausedToasts = new Set<string>()

// Computed
const containerClasses = computed(() => {
  const classes = [`auth__toast-container--${props.position}`]
  if (props.stackable) classes.push('auth__toast-container--stackable')
  return classes
})

// Methods
const addToast = (toastOptions: Omit<Toast, 'id'>): string => {
  // Check for similar toasts if groupSimilar is enabled
  if (props.groupSimilar) {
    const similar = toasts.value.find(
      t => t.type === toastOptions.type && t.message === toastOptions.message
    )
    if (similar) {
      // Update existing toast
      similar.duration = toastOptions.duration || 4000
      resetTimer(similar.id)
      return similar.id
    }
  }

  const id = `toast-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`
  const toast: Toast = {
    id,
    duration: 4000,
    closable: true,
    persistent: false,
    ripple: true,
    position: props.position,
    startTime: Date.now(),
    ...toastOptions,
  }

  // Add to beginning for top positions, end for bottom
  if (props.position.includes('top')) {
    toasts.value.unshift(toast)
  } else {
    toasts.value.push(toast)
  }

  // Remove excess toasts
  if (toasts.value.length > props.maxToasts) {
    const excess = toasts.value.length - props.maxToasts
    if (props.position.includes('top')) {
      toasts.value.splice(-excess)
    } else {
      toasts.value.splice(0, excess)
    }
  }

  // Set auto-remove timer
  if (toast.duration > 0 && !toast.persistent) {
    setTimer(toast)
  }

  return id
}

const removeToast = (id: string) => {
  const index = toasts.value.findIndex(t => t.id === id)
  if (index > -1) {
    const toast = toasts.value[index]
    toast.onClose?.()
    toasts.value.splice(index, 1)
    clearTimer(id)
  }
}

const removeAllToasts = () => {
  toasts.value.forEach(toast => {
    toast.onClose?.()
    clearTimer(toast.id)
  })
  toasts.value = []
}

const setTimer = (toast: Toast) => {
  if (timers.has(toast.id)) {
    clearTimer(toast.id)
  }

  const timeout = setTimeout(() => {
    removeToast(toast.id)
  }, toast.duration)

  timers.set(toast.id, timeout)
}

const resetTimer = (id: string) => {
  const toast = toasts.value.find(t => t.id === id)
  if (toast && toast.duration > 0 && !toast.persistent) {
    toast.startTime = Date.now()
    setTimer(toast)
  }
}

const clearTimer = (id: string) => {
  const timer = timers.get(id)
  if (timer) {
    clearTimeout(timer)
    timers.delete(id)
  }
  pausedToasts.delete(id)
}

const pauseTimer = (id: string) => {
  const toast = toasts.value.find(t => t.id === id)
  if (toast && !toast.persistent && toast.duration > 0) {
    clearTimer(id)
    toast.pausedTime = Date.now()
    pausedToasts.add(id)
  }
}

const resumeTimer = (id: string) => {
  const toast = toasts.value.find(t => t.id === id)
  if (toast && pausedToasts.has(id) && toast.pausedTime) {
    const elapsed = toast.pausedTime - (toast.startTime || Date.now())
    const remaining = Math.max(0, toast.duration - elapsed)

    if (remaining > 0) {
      const timeout = setTimeout(() => {
        removeToast(id)
      }, remaining)
      timers.set(id, timeout)
    }

    pausedToasts.delete(id)
    delete toast.pausedTime
  }
}

const handleToastClick = (toast: Toast) => {
  if (toast.closable !== false && !toast.actions?.length) {
    removeToast(toast.id)
  }
}

const handleAction = (action: ToastAction, toast: Toast) => {
  action.handler()
  removeToast(toast.id)
}

const getToastClasses = (toast: Toast) => {
  const classes = [
    'auth__toast',
    `auth__toast--${toast.type}`,
  ]

  if (toast.ripple) classes.push('auth__toast--ripple')
  if (toast.persistent) classes.push('auth__toast--persistent')
  if (toast.actions?.length) classes.push('auth__toast--with-actions')

  return classes
}

const getIconStyle = (toast: Toast) => {
  const iconConfig = getToastIcon(toast.type)
  return {
    color: iconConfig.color || 'currentColor'
  }
}

const getActionClasses = (action: ToastAction, toast: Toast) => {
  const variant = action.variant || 'secondary'
  return [
    'auth__toast-action',
    `auth__toast-action--${variant}`,
    `auth__toast-action--${toast.type}`
  ]
}

const getProgressStyle = (toast: Toast) => {
  const iconConfig = getToastIcon(toast.type)
  return {
    animationDuration: `${toast.duration}ms`,
    backgroundColor: iconConfig.color || 'currentColor'
  }
}

// Public API
const showSuccess = (message: string, options?: Partial<Toast>) => {
  return addToast({ type: 'success', message, ...options })
}

const showError = (message: string, options?: Partial<Toast>) => {
  return addToast({
    type: 'error',
    message,
    duration: 6000,
    ...options
  })
}

const showWarning = (message: string, options?: Partial<Toast>) => {
  return addToast({ type: 'warning', message, ...options })
}

const showInfo = (message: string, options?: Partial<Toast>) => {
  return addToast({ type: 'info', message, ...options })
}

const showLoading = (message: string, options?: Partial<Toast>) => {
  return addToast({
    type: 'loading',
    message,
    persistent: true,
    closable: false,
    ...options
  })
}

// Specialized notifications
const showValidationError = (message: string) => {
  return showError(`<strong>Error de validación:</strong> ${message}`, {
    duration: 4000,
    ripple: true,
  })
}

const showApiError = (message: string, retry?: () => void) => {
  return showError(message, {
    duration: 0,
    actions: retry ? [{
      label: 'Reintentar',
      icon: getIcon('refresh').name,
      handler: retry,
      variant: 'primary'
    }, {
      label: 'Cerrar',
      icon: getIcon('close').name,
      handler: () => {},
      variant: 'ghost'
    }] : undefined
  })
}

const showConfirmation = (message: string, onConfirm: () => void) => {
  return showInfo(message, {
    duration: 0,
    actions: [{
      label: 'Confirmar',
      icon: getIcon('check').name,
      handler: onConfirm,
      variant: 'primary'
    }, {
      label: 'Cancelar',
      icon: getIcon('x').name,
      handler: () => {},
      variant: 'secondary'
    }]
  })
}

// Cleanup
onUnmounted(() => {
  timers.forEach(timer => clearTimeout(timer))
  timers.clear()
  pausedToasts.clear()
})

// Expose public methods
defineExpose({
  showSuccess,
  showError,
  showWarning,
  showInfo,
  showLoading,
  showValidationError,
  showApiError,
  showConfirmation,
  removeToast,
  removeAllToasts,
})
</script>

<style scoped>
/* Container positioning */
.auth__toast-container {
  @apply fixed z-50 pointer-events-none;
  max-width: 420px;
}

.auth__toast-container--top-right {
  @apply top-4 right-4;
}

.auth__toast-container--top-left {
  @apply top-4 left-4;
}

.auth__toast-container--bottom-right {
  @apply bottom-4 right-4;
}

.auth__toast-container--bottom-left {
  @apply bottom-4 left-4;
}

.auth__toast-container--top-center {
  @apply top-4 left-1/2 transform -translate-x-1/2;
}

.auth__toast-container--bottom-center {
  @apply bottom-4 left-1/2 transform -translate-x-1/2;
}

.auth__toast-wrapper {
  @apply space-y-2;
}

/* Toast base */
.auth__toast {
  @apply relative flex items-start p-4 rounded-xl shadow-lg pointer-events-auto;
  @apply cursor-pointer transition-all duration-300;
  backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  min-width: 300px;
  overflow: hidden;
}

.auth__toast:hover {
  @apply transform scale-105 shadow-xl;
}

.auth__toast--ripple {
  position: relative;
  overflow: hidden;
}

.auth__toast-ripple {
  position: absolute;
  inset: 0;
  pointer-events: none;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, transparent 70%);
  transform: scale(0);
  animation: ripple 0.8s ease-out;
}

@keyframes ripple {
  0% {
    transform: scale(0);
    opacity: 1;
  }
  100% {
    transform: scale(2);
    opacity: 0;
  }
}

/* Toast variants */
.auth__toast--success {
  @apply bg-emerald-600/90 text-white;
  border-color: rgba(16, 185, 129, 0.3);
}

.auth__toast--error {
  @apply bg-red-600/90 text-white;
  border-color: rgba(239, 68, 68, 0.3);
}

.auth__toast--warning {
  @apply bg-amber-600/90 text-white;
  border-color: rgba(245, 158, 11, 0.3);
}

.auth__toast--info {
  @apply bg-blue-600/90 text-white;
  border-color: rgba(59, 130, 246, 0.3);
}

.auth__toast--loading {
  @apply bg-indigo-600/90 text-white;
  border-color: rgba(99, 102, 241, 0.3);
}

.auth__toast--loading .auth__toast-icon i {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Persistent toast */
.auth__toast--persistent {
  @apply border-2;
  box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.3);
}

/* Toast components */
.auth__toast-icon {
  @apply flex-shrink-0 mr-3;
  font-size: 1.5rem;
}

.auth__toast-content {
  @apply flex-1 min-w-0;
}

.auth__toast-title {
  @apply font-semibold mb-1 text-sm;
}

.auth__toast-message {
  @apply text-sm leading-relaxed opacity-95;
}

.auth__toast-actions {
  @apply flex items-center space-x-2 mt-3 pt-3 border-t border-white/20;
}

.auth__toast-action {
  @apply px-3 py-1 rounded-md text-xs font-medium transition-all duration-200;
  @apply flex items-center space-x-1;
}

.auth__toast-action--primary {
  @apply bg-white/20 hover:bg-white/30 text-white;
}

.auth__toast-action--secondary {
  @apply bg-white/10 hover:bg-white/20 text-white/90;
}

.auth__toast-action--ghost {
  @apply hover:bg-white/10 text-white/80 hover:text-white;
}

.auth__toast-close {
  @apply flex-shrink-0 ml-3 p-1 rounded-full transition-all duration-200;
  @apply hover:bg-white/20 focus:outline-none focus:bg-white/20;
}

/* Progress bar */
.auth__toast-progress {
  @apply absolute bottom-0 left-0 h-1 rounded-bl-xl;
  animation: progress linear forwards;
  width: 100%;
  background: rgba(255, 255, 255, 0.4);
}

.auth__toast-progress--success {
  background: rgba(16, 185, 129, 0.8);
}

.auth__toast-progress--error {
  background: rgba(239, 68, 68, 0.8);
}

.auth__toast-progress--warning {
  background: rgba(245, 158, 11, 0.8);
}

.auth__toast-progress--info {
  background: rgba(59, 130, 246, 0.8);
}

.auth__toast-progress--loading {
  background: rgba(99, 102, 241, 0.8);
}

@keyframes progress {
  from {
    width: 100%;
  }
  to {
    width: 0%;
  }
}

/* Transitions */
.toast-enter-active {
  transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.toast-leave-active {
  transition: all 0.3s ease-in;
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%) scale(0.8) rotateY(45deg);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(-100%) scale(0.8) rotateY(-45deg);
}

/* Move transition */
.toast-move {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Stackable effect */
.auth__toast-container--stackable .auth__toast:nth-child(n+2) {
  margin-top: -8px;
  transform: scale(0.95) translateY(-4px);
  opacity: 0.8;
  z-index: -1;
}

.auth__toast-container--stackable .auth__toast:nth-child(n+3) {
  margin-top: -12px;
  transform: scale(0.9) translateY(-8px);
  opacity: 0.6;
  z-index: -2;
}

/* Responsive */
@media (max-width: 640px) {
  .auth__toast-container {
    @apply left-2 right-2;
    max-width: none;
  }

  .auth__toast-container--top-right,
  .auth__toast-container--top-left,
  .auth__toast-container--top-center {
    @apply top-2 left-2 right-2 transform-none;
  }

  .auth__toast-container--bottom-right,
  .auth__toast-container--bottom-left,
  .auth__toast-container--bottom-center {
    @apply bottom-2 left-2 right-2 transform-none;
  }

  .auth__toast {
    min-width: auto;
  }

  .auth__toast-actions {
    @apply flex-col space-x-0 space-y-2;
  }

  .auth__toast-action {
    @apply w-full justify-center;
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .auth__toast {
    @apply border-2;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .auth__toast,
  .toast-enter-active,
  .toast-leave-active,
  .toast-move,
  .auth__toast-progress,
  .auth__toast--loading .auth__toast-icon i {
    transition: none;
    animation: none;
  }
}

/* Focus styles */
.auth__toast:focus-within {
  @apply ring-2 ring-white/50;
}
</style>