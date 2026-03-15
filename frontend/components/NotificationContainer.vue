<template>
  <div class="fixed top-4 right-4 z-50 flex flex-col space-y-2">
    <!-- DEBUG: Elemento de prueba -->
    <div class="bg-red-500 text-white p-2 rounded text-xs">
      🔔 NotificationContainer ({{ notifications.length }} notificaciones)
    </div>
    
    <!-- Notificaciones globales -->
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
          :style="{ backgroundColor: getNotificationColor(notification.type) }"
        >
          <span class="text-lg">{{ getNotificationIcon(notification.type) }}</span>
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
              
              <p class="mt-1 text-xs text-gray-500">
                {{ formatTime(notification.created_at) }}
              </p>
            </div>

            <!-- Botones de acción -->
            <div class="flex items-center space-x-1 ml-2">
              <button
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
 * Contenedor para notificaciones toast globales
 * Consume datos globales compartidos desde el backend
 */

interface NotificationData {
  id: string
  type: string
  title: string
  message: string
  data: Record<string, any>
  is_read: boolean
  read_at?: string
  created_at: string
}

// Estado
const notifications = ref<NotificationData[]>([])
const isLoading = ref(false)
const error = ref<string | null>(null)

// Notificaciones a mostrar (solo las no leídas)
const displayNotifications = computed(() => {
  return notifications.value.filter(n => !n.is_read).slice(0, 5)
})

// Cargar notificaciones desde datos globales
const loadNotifications = () => {
  try {
    isLoading.value = true
    error.value = null

    // Usar datos globales si están disponibles
    if (process.client && window.notificationsData) {
      notifications.value = window.notificationsData.notifications || []
      console.log('Notificaciones cargadas desde datos globales:', notifications.value.length)
    } else {
      console.log('No hay datos globales disponibles')
    }
  } catch (err) {
    console.error('Error cargando notificaciones:', err)
    error.value = 'Error al cargar notificaciones'
  } finally {
    isLoading.value = false
  }
}

// Eliminar notificación de la vista
const removeNotification = (id: string) => {
  const index = notifications.value.findIndex(n => n.id === id)
  if (index !== -1) {
    notifications.value.splice(index, 1)
  }
}

// Marcar como leída (simulado)
const markAsRead = (id: string) => {
  const notification = notifications.value.find(n => n.id === id)
  if (notification) {
    notification.is_read = true
    notification.read_at = new Date().toISOString()
  }
}

// Funciones helper
const getNotificationColor = (type: string): string => {
  const colors = {
    WALLET_UPDATED: '#28a745',
    GAME_ALERT: '#007bff',
    SYSTEM: '#dc3545',
    PROMOTION: '#ffc107',
  }
  return colors[type] || '#6c757d'
}

const getNotificationIcon = (type: string): string => {
  const icons = {
    WALLET_UPDATED: '💰',
    GAME_ALERT: '🎮',
    SYSTEM: '⚠️',
    PROMOTION: '🎁',
  }
  return icons[type] || '📢'
}

const formatTime = (timestamp: string): string => {
  const date = new Date(timestamp)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / 60000)

  if (diffMins < 1) return 'Ahora'
  if (diffMins < 60) return `Hace ${diffMins}m`
  
  return date.toLocaleDateString('es-ES', {
    day: '2-digit',
    month: '2-digit',
  })
}

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

// Lifecycle
onMounted(() => {
  console.log('🔔 NotificationContainer montado')
  
  // Debug inmediato
  if (process.client && window.notificationsData) {
    console.log('📦 Datos encontrados inmediatamente:', window.notificationsData)
    notifications.value = window.notificationsData.notifications || []
  } else {
    console.log('⏳ Esperando datos globales...')
  }
  
  loadNotifications()
  
  // Escuchar evento de actualización
  if (process.client) {
    window.addEventListener('notifications-loaded', (event: any) => {
      console.log('🎉 Evento recibido:', event.detail)
      notifications.value = event.detail.notifications || []
      console.log('📊 Notificaciones actualizadas:', notifications.value.length)
    })
  }
  
  // Verificar después de un momento
  setTimeout(() => {
    if (process.client && window.notificationsData) {
      console.log('✅ Verificación tardía - datos encontrados:', window.notificationsData)
      notifications.value = window.notificationsData.notifications || []
    } else {
      console.log('❌ Verificación tardía - sin datos')
    }
  }, 1000)
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

/* Animación personalizada */
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

.notification-item {
  border-left-color: v-bind('getNotificationColor(notification.type)');
}
</style>
