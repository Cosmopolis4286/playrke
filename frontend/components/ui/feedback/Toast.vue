<template>
  <div class="fixed top-4 right-4 z-50 space-y-2 max-w-sm">
    <TransitionGroup
      name="toast"
      tag="div"
      class="space-y-2"
    >
      <div
        v-for="notification in notifications"
        :key="notification.id"
        :class="toastClasses(notification.type)"
        class="toast-item"
      >
        <!-- SharedIcon -->
        <div class="flex-shrink-0">
          <component
            :is="getIcon(notification.type)"
            class="h-5 w-5"
          />
        </div>

        <!-- Content -->
        <div class="flex-1 ml-3">
          <h4 class="text-sm font-medium text-white">
            {{ notification.title }}
          </h4>
          <p
            v-if="notification.message"
            class="text-sm opacity-90 mt-1"
          >
            {{ notification.message }}
          </p>
        </div>

        <!-- Action Button -->
        <div
          v-if="notification.action"
          class="flex-shrink-0 ml-3"
        >
          <button
            class="text-sm font-medium underline hover:no-underline focus:outline-none"
            @click="notification.action.handler"
          >
            {{ notification.action.label }}
          </button>
        </div>

        <!-- Close Button -->
        <div class="flex-shrink-0 ml-3">
          <button
            class="rounded-md p-1 hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/20 transition-colors"
            @click="removeNotification(notification.id)"
          >
            <SharedIcon
              name="close"
              class="h-4 w-4 text-white/70"
            />
          </button>
        </div>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import type { Notification } from '~/composables/ui/feedback/useNotifications'

// Composable de notificaciones (con fallback)
const notifications = ref<Notification[]>([])
let removeNotification = (id: string) => {}

try {
  const notificationService = await import('../../../composables/ui/feedback/useNotifications').then(m => m.useNotifications())
  notifications.value = notificationService.notifications
  removeNotification = notificationService.removeNotification
} catch (error) {
  console.warn('Toast: useNotifications no disponible:', error)
}

// Función para obtener el icono según el tipo
const getSharedIcon = (type: Notification['type']) => {
  const icons = {
    success: 'success',
    error: 'error',
    warning: 'warning',
    info: 'info',
  }
  return icons[type]
}

// Función para obtener las clases CSS según el tipo
const toastClasses = (type: Notification['type']) => {
  const baseClasses = 'relative flex items-start p-4 rounded-xl backdrop-blur-sm border shadow-lg transform transition-all duration-300 ease-in-out'

  const typeClasses = {
    success: 'bg-green-500/90 border-green-400/50 text-white',
    error: 'bg-red-500/90 border-red-400/50 text-white',
    warning: 'bg-yellow-500/90 border-yellow-400/50 text-white',
    info: 'bg-blue-500/90 border-blue-400/50 text-white',
  }

  return `${baseClasses} ${typeClasses[type]}`
}
</script>

<style scoped>
.toast-item {
  min-width: 320px;
  max-width: 400px;
}

/* Transiciones para la entrada y salida */
.toast-enter-active {
  transition: all 0.3s ease-out;
}

.toast-leave-active {
  transition: all 0.3s ease-in;
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100%) scale(0.9);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100%) scale(0.9);
}

.toast-move {
  transition: transform 0.3s ease;
}

/* Hover effects */
.toast-item:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
}

/* Responsive */
@media (max-width: 640px) {
  .toast-item {
    min-width: 280px;
    max-width: calc(100vw - 2rem);
  }
}
</style>
