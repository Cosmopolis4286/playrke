<template>
  <div class="fixed top-4 right-4 z-50 flex flex-col space-y-2">
    <!-- Notificaciones persistentes -->
    <TransitionGroup
      name="notification"
      tag="div"
      class="flex flex-col space-y-2"
    >
      <div
        v-for="notification in displayNotifications"
        :key="notification.id"
        :class="notificationClasses(notification)"
        class="notification-item"
      >
        <!-- Icono -->
        <div
          class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-white"
          :style="{ backgroundColor: notification.type_color }"
        >
          <span class="text-lg">{{ notification.type_icon }}</span>
        </div>

        <!-- Contenido -->
        <div class="flex-1 min-w-0">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <h4 class="text-sm font-semibold text-gray-900">
                {{ notification.title }}
              </h4>
              <p class="text-sm text-gray-600 mt-1">
                {{ notification.message }}
              </p>
              
              <!-- Datos adicionales para billetera -->
              <div
                v-if="notification.type === 'WALLET_UPDATED'"
                class="mt-2 text-xs bg-gray-50 rounded p-2"
              >
                <div class="flex items-center justify-between">
                  <span class="text-gray-600">Nuevo saldo:</span>
                  <span class="font-semibold text-green-600">
                    {{ notification.data.currency_symbol }}{{ notification.data.balance }}
                  </span>
                </div>
              </div>
              
              <p class="text-xs text-gray-500 mt-1">
                {{ notification.formatted_time }}
              </p>
            </div>

            <!-- Botones de acción -->
            <div class="flex items-center space-x-1 ml-2">
              <button
                v-if="!notification.is_read"
                @click="markAsRead(notification.id)"
                class="text-xs text-blue-600 hover:text-blue-800"
                title="Marcar como leída"
              >
                <SharedIcon name="check" class="w-4 h-4" />
              </button>
              <button
                @click="removeNotification(notification.id)"
                class="text-xs text-gray-400 hover:text-gray-600"
                title="Cerrar"
              >
                <SharedIcon name="x" class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </TransitionGroup>

    <!-- Indicador de carga -->
    <div
      v-if="isLoading"
      class="bg-white rounded-lg shadow-lg border border-gray-200 p-3 flex items-center space-x-2"
    >
      <LoadingSpinner size="sm" />
      <span class="text-sm text-gray-600">Cargando notificaciones...</span>
    </div>

    <!-- Error -->
    <div
      v-if="error"
      class="bg-red-50 border border-red-200 rounded-lg p-3 flex items-center space-x-2"
    >
      <SharedIcon name="warning" class="w-4 h-4 text-red-500" />
      <span class="text-sm text-red-700">{{ error }}</span>
      <button
        @click="loadNotifications"
        class="text-xs text-red-600 hover:text-red-800"
      >
        Reintentar
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Contenedor de notificaciones persistentes
 * Muestra notificaciones almacenadas en BD que no se pierden al refrescar
 */

import type { NotificationData } from '~/composables/usePersistentNotifications'

// Composables
const {
  notifications,
  unreadCount,
  isLoading,
  error,
  loadNotifications,
  markAsRead,
  deleteNotification,
} = usePersistentNotifications()

// Notificaciones a mostrar (solo las no leídas + últimas leídas)
const displayNotifications = computed(() => {
  const unread = notifications.value.filter(n => !n.is_read)
  const recentRead = notifications.value
    .filter(n => n.is_read)
    .slice(0, 2) // Máximo 2 leídas recientes
  
  return [...unread, ...recentRead].slice(0, 5) // Máximo 5 visibles
})

// Eliminar notificación de la vista
const removeNotification = async (id: string) => {
  const notification = notifications.value.find(n => n.id === id)
  
  if (notification && !notification.is_read) {
    // Si no está leída, marcarla primero
    await markAsRead(id)
  }
  
  // Eliminar de la vista (se mantiene en BD)
  const index = notifications.value.findIndex(n => n.id === id)
  if (index !== -1) {
    notifications.value.splice(index, 1)
  }
}

// Clases CSS para notificaciones
const notificationClasses = (notification: NotificationData) => {
  const baseClasses = [
    'bg-white rounded-lg shadow-lg border border-gray-200 p-3',
    'flex items-start space-x-3 transition-all duration-300',
    'transform hover:scale-105 hover:shadow-xl',
  ]
  
  const unreadClasses = notification.is_read
    ? ['opacity-75']
    : [
        'border-l-4',
        'animate-slide-in-right',
      ]
  
  return [...baseClasses, ...unreadClasses].join(' ')
}

// Auto-eliminar notificaciones leídas después de 10 segundos
const scheduleAutoRemove = () => {
  setInterval(() => {
    notifications.value.forEach(notification => {
      if (notification.is_read) {
        const createdAt = new Date(notification.created_at)
        const now = new Date()
        const diffMinutes = (now.getTime() - createdAt.getTime()) / (1000 * 60)
        
        if (diffMinutes > 10) {
          removeNotification(notification.id)
        }
      }
    })
  }, 30000) // Verificar cada 30 segundos
}

onMounted(() => {
  scheduleAutoRemove()
})
</script>

<style scoped>
/* Animaciones */
.notification-enter-active {
  transition: all 0.3s ease-out;
}

.notification-leave-active {
  transition: all 0.2s ease-in;
}

.notification-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.notification-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

.notification-move {
  transition: transform 0.3s ease;
}

/* Animación personalizada para notificaciones no leídas */
@keyframes slide-in-right {
  from {
    opacity: 0;
    transform: translateX(100%);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.animate-slide-in-right {
  animation: slide-in-right 0.3s ease-out;
}

/* Border color dinámico según tipo */
.notification-item {
  border-left-color: v-bind('notification.type_color');
}
</style>
