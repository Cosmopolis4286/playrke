<template>
  <div class="fixed top-20 right-4 z-[9999] flex flex-col space-y-2">
    <!-- DEBUG: Elemento de prueba -->
    <div class="bg-blue-500 text-white p-3 rounded text-sm font-bold shadow-lg">
      🔔 SimpleContainer ({{ notificationCount }} notificaciones)
    </div>
    
    <!-- Notificaciones simples -->
    <div
      v-for="(notification, index) in windowNotifications"
      :key="notification.id"
      class="bg-white rounded-lg shadow-lg border border-gray-200 p-3 mb-2"
    >
      <div class="flex items-center space-x-3">
        <div class="flex-shrink-0">
          <span class="text-2xl">💰</span>
        </div>
        <div class="flex-1">
          <h4 class="font-semibold text-gray-900">{{ notification.title }}</h4>
          <p class="text-sm text-gray-600">{{ notification.message }}</p>
          <p class="text-xs text-gray-500">{{ formatTime(notification.created_at) }}</p>
        </div>
        <button
          @click="removeNotification(index)"
          class="text-gray-400 hover:text-gray-600"
        >
          ✕
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface NotificationData {
  id: string
  type: string
  title: string
  message: string
  data: Record<string, any>
  is_read: boolean
  created_at: string
}

const windowNotifications = ref<NotificationData[]>([])
const notificationCount = computed(() => windowNotifications.value.length)

const loadNotifications = () => {
  console.log('🔔 SimpleNotificationContainer: Cargando notificaciones')
  console.log('🔍 Verificando window:', typeof window)
  console.log('🔍 Verificando notificationsData:', (window as any).notificationsData)
  
  if (typeof window !== 'undefined' && (window as any).notificationsData) {
    const data = (window as any).notificationsData
    console.log('📦 Datos encontrados:', data)
    windowNotifications.value = data.notifications || []
    console.log('✅ Notificaciones cargadas:', windowNotifications.value.length)
  } else {
    console.log('❌ No hay datos globales')
    console.log('🔍 window.notificationsData:', (window as any).notificationsData)
  }
}

const removeNotification = (index: number) => {
  windowNotifications.value.splice(index, 1)
}

const formatTime = (timestamp: string): string => {
  const date = new Date(timestamp)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffSecs = Math.floor(diffMs / 1000)
  const diffMins = Math.floor(diffSecs / 60)
  const diffHours = Math.floor(diffMins / 60)
  const diffDays = Math.floor(diffHours / 24)

  if (diffSecs < 60) return 'hace unos segundos'
  if (diffMins < 60) return `hace ${diffMins} ${diffMins === 1 ? 'minuto' : 'minutos'}`
  if (diffHours < 24) return `hace ${diffHours} ${diffHours === 1 ? 'hora' : 'horas'}`
  if (diffDays < 30) return `hace ${diffDays} ${diffDays === 1 ? 'día' : 'días'}`
  
  return date.toLocaleDateString('es-ES', {
    day: '2-digit',
    month: '2-digit',
  })
}

onMounted(() => {
  console.log('🚀 SimpleNotificationContainer montado')
  loadNotifications()
  
  // Escuchar evento
  if (process.client) {
    window.addEventListener('notifications-loaded', (event: any) => {
      console.log('🎉 Evento recibido en SimpleContainer:', event.detail)
      windowNotifications.value = event.detail.notifications || []
    })
  }
  
  // Intentar cargar después de un momento
  setTimeout(() => {
    if (windowNotifications.value.length === 0) {
      console.log('🔄 Reintentando carga...')
      loadNotifications()
    }
  }, 500)
})
</script>
