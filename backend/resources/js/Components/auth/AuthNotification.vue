<template>
  <teleport to="body">
    <div class="auth__notifications-container">
      <transition-group name="notification" tag="div">
        <div
          v-for="notification in notifications"
          :key="notification.id"
          :class="getNotificationClasses(notification)"
          @click="removeNotification(notification.id)"
        >
          <!-- Icon -->
          <div class="auth__notification-icon">
            <i :class="getNotificationIcon(notification.type)"></i>
          </div>

          <!-- Content -->
          <div class="auth__notification-content">
            <h4 v-if="notification.title" class="auth__notification-title">
              {{ notification.title }}
            </h4>
            <p class="auth__notification-message" v-html="notification.message"></p>
          </div>

          <!-- Close Button -->
          <button
            v-if="notification.closable !== false"
            type="button"
            class="auth__notification-close"
            @click.stop="removeNotification(notification.id)"
            aria-label="Cerrar notificación"
          >
            <i class="fas fa-times"></i>
          </button>

          <!-- Progress Bar -->
          <div
            v-if="notification.duration > 0"
            class="auth__notification-progress"
            :style="{ animationDuration: `${notification.duration}ms` }"
          ></div>
        </div>
      </transition-group>
    </div>
  </teleport>
</template>

<script setup lang="ts">
import { ref, onUnmounted } from 'vue'

// Types
interface Notification {
  id: string
  type: 'success' | 'error' | 'warning' | 'info'
  title?: string
  message: string
  duration?: number
  closable?: boolean
  ripple?: boolean
  position?: 'top' | 'bottom'
}

// State
const notifications = ref<Notification[]>([])
const timeouts = new Map<string, NodeJS.Timeout>()

// Methods
const addNotification = (notification: Omit<Notification, 'id'>) => {
  const id = Math.random().toString(36).substr(2, 9)
  const newNotification: Notification = {
    id,
    duration: 4000,
    closable: true,
    ripple: true,
    position: 'bottom',
    ...notification,
  }

  notifications.value.push(newNotification)

  // Auto remove after duration
  if (newNotification.duration > 0) {
    const timeout = setTimeout(() => {
      removeNotification(id)
    }, newNotification.duration)

    timeouts.set(id, timeout)
  }

  return id
}

const removeNotification = (id: string) => {
  const index = notifications.value.findIndex(n => n.id === id)
  if (index > -1) {
    notifications.value.splice(index, 1)

    // Clear timeout if exists
    const timeout = timeouts.get(id)
    if (timeout) {
      clearTimeout(timeout)
      timeouts.delete(id)
    }
  }
}

const removeAllNotifications = () => {
  notifications.value.forEach(notification => {
    removeNotification(notification.id)
  })
}

const getNotificationClasses = (notification: Notification) => {
  const classes = ['auth__notification', `auth__notification--${notification.type}`]

  if (notification.position === 'top') {
    classes.push('auth__notification--top')
  } else {
    classes.push('auth__notification--bottom')
  }

  return classes.join(' ')
}

const getNotificationIcon = (type: Notification['type']) => {
  const icons = {
    success: 'fas fa-check-circle',
    error: 'fas fa-times-circle',
    warning: 'fas fa-exclamation-triangle',
    info: 'fas fa-info-circle'
  }
  return icons[type]
}

// Public methods for external use
const showSuccess = (message: string, options?: Partial<Notification>) => {
  return addNotification({
    type: 'success',
    message,
    ...options,
  })
}

const showError = (message: string, options?: Partial<Notification>) => {
  return addNotification({
    type: 'error',
    message,
    duration: 6000, // Errors stay longer
    ...options,
  })
}

const showWarning = (message: string, options?: Partial<Notification>) => {
  return addNotification({
    type: 'warning',
    message,
    ...options,
  })
}

const showInfo = (message: string, options?: Partial<Notification>) => {
  return addNotification({
    type: 'info',
    message,
    ...options,
  })
}

// Validation specific notifications
const showValidationError = (message: string) => {
  return showError(`<span style="font-size: 0.91em;">${message}</span>`, {
    duration: 3450,
    ripple: true,
  })
}

// Cleanup on unmount
onUnmounted(() => {
  timeouts.forEach(timeout => clearTimeout(timeout))
  timeouts.clear()
})

// Expose methods
defineExpose({
  showSuccess,
  showError,
  showWarning,
  showInfo,
  showValidationError,
  removeNotification,
  removeAllNotifications,
})
</script>

<style scoped>
/* Container */
.auth__notifications-container {
  @apply fixed inset-x-0 z-50 pointer-events-none;
  bottom: 1rem;
  left: 1rem;
  right: 1rem;
  max-width: 420px;
  margin: 0 auto;
}

/* Notification Base */
.auth__notification {
  @apply relative flex items-start p-4 mb-3 rounded-xl shadow-lg pointer-events-auto;
  @apply cursor-pointer transition-all duration-300;
  backdrop-filter: blur(20px);
  border-left: 4px solid;
}

.auth__notification:hover {
  @apply transform -translate-y-1 shadow-xl;
}

/* Notification Types */
.auth__notification--success {
  @apply bg-green-600/90 border-green-400 text-white;
}

.auth__notification--error {
  @apply bg-red-600/90 border-red-400 text-white;
}

.auth__notification--warning {
  @apply bg-yellow-600/90 border-yellow-400 text-white;
}

.auth__notification--info {
  @apply bg-blue-600/90 border-blue-400 text-white;
}

/* Position variants */
.auth__notification--top {
  @apply mb-3;
}

.auth__notification--bottom {
  @apply mb-3;
}

/* Icon */
.auth__notification-icon {
  @apply flex-shrink-0 mr-3;
  font-size: 1.25rem;
}

/* Content */
.auth__notification-content {
  @apply flex-1 min-w-0;
}

.auth__notification-title {
  @apply font-semibold mb-1 text-sm;
}

.auth__notification-message {
  @apply text-sm leading-relaxed opacity-95;
}

/* Close Button */
.auth__notification-close {
  @apply flex-shrink-0 ml-3 p-1 rounded-full transition-all;
  @apply hover:bg-white/20 focus:outline-none focus:bg-white/20;
}

/* Progress Bar */
.auth__notification-progress {
  @apply absolute bottom-0 left-0 h-1 bg-white/30 rounded-b-xl;
  animation: progress linear forwards;
  width: 100%;
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
.notification-enter-active {
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.notification-leave-active {
  transition: all 0.3s ease-in;
}

.notification-enter-from {
  opacity: 0;
  transform: translateX(100%) scale(0.8);
}

.notification-leave-to {
  opacity: 0;
  transform: translateX(-100%) scale(0.8);
}

/* Move transition for other notifications */
.notification-move {
  transition: transform 0.3s ease;
}

/* Responsive */
@screen sm {
  .auth__notifications-container {
    max-width: 480px;
    right: 1.5rem;
    left: auto;
  }
}

/* Dark mode adjustments */
@media (prefers-color-scheme: dark) {
  .auth__notification {
    @apply bg-opacity-95;
  }
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
  .auth__notification,
  .notification-enter-active,
  .notification-leave-active,
  .notification-move {
    transition: none;
  }

  .auth__notification-progress {
    animation: none;
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .auth__notification {
    @apply border-2;
  }
}

/* Focus styles for accessibility */
.auth__notification:focus-within {
  @apply ring-2 ring-white ring-opacity-50;
}
</style>