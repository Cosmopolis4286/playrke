<template>
  <div class="fixed inset-y-0 right-0 z-50 w-80 bg-white shadow-2xl overflow-hidden">
    <div class="flex flex-col h-full">
      <!-- Header -->
      <div class="flex items-center justify-between p-4 border-b border-gray-200 bg-gray-50">
        <div>
          <h3 class="text-lg font-semibold text-gray-900">Notificaciones</h3>
          <p class="text-sm text-gray-500">{{ notifications.length }} nuevas</p>
        </div>
        <button
          @click="$emit('close')"
          class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
        >
          <XMarkIcon class="w-5 h-5" />
        </button>
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-2 p-4 border-b border-gray-200">
        <Button size="sm" variant="outline" @click="markAllAsRead">
          Marcar todas como leídas
        </Button>
        <Button size="sm" variant="ghost" @click="clearAll">
          Limpiar
        </Button>
      </div>

      <!-- Notifications List -->
      <div class="flex-1 overflow-y-auto">
        <div v-if="notifications.length === 0" class="flex flex-col items-center justify-center h-full p-6 text-center">
          <BellSlashIcon class="w-12 h-12 text-gray-300 mb-4" />
          <h4 class="text-lg font-medium text-gray-900 mb-2">No hay notificaciones</h4>
          <p class="text-sm text-gray-500">Todas las notificaciones aparecerán aquí</p>
        </div>

        <div v-else class="divide-y divide-gray-200">
          <div
            v-for="notification in notifications"
            :key="notification.id"
            :class="[
              'p-4 hover:bg-gray-50 transition-colors cursor-pointer relative',
              !notification.read ? 'bg-blue-50/50' : ''
            ]"
            @click="markAsRead(notification.id)"
          >
            <!-- Unread Indicator -->
            <div
              v-if="!notification.read"
              class="absolute top-4 right-4 w-2 h-2 bg-blue-500 rounded-full"
            ></div>

            <div class="flex gap-3">
              <!-- Icon -->
              <div
                :class="[
                  'w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0',
                  getNotificationStyle(notification.type).bg
                ]"
              >
                <component
                  :is="getNotificationIcon(notification.type)"
                  :class="['w-5 h-5', getNotificationStyle(notification.type).text]"
                />
              </div>

              <!-- Content -->
              <div class="flex-1 min-w-0">
                <h4 class="text-sm font-medium text-gray-900 mb-1 pr-6">
                  {{ notification.title }}
                </h4>
                <p class="text-sm text-gray-600 mb-2">
                  {{ notification.message }}
                </p>
                <div class="flex items-center justify-between">
                  <p class="text-xs text-gray-400">
                    {{ formatTime(notification.time) }}
                  </p>
                  <button
                    v-if="notification.action"
                    class="text-xs text-blue-600 hover:text-blue-800 font-medium"
                    @click.stop="handleAction(notification.action, notification.id)"
                  >
                    {{ notification.action.label }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import {
  XMarkIcon,
  BellSlashIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  CreditCardIcon,
  TrophyIcon
} from '@heroicons/vue/24/outline'

// Components
import Button from '../ui/Button.vue'

interface NotificationAction {
  label: string
  url?: string
  method?: 'GET' | 'POST'
}

interface Notification {
  id: string
  type: 'success' | 'warning' | 'info' | 'payment' | 'game'
  title: string
  message: string
  time: Date
  read: boolean
  action?: NotificationAction
}

const emit = defineEmits<{
  close: []
}>()

// Mock notifications
const notifications = ref<Notification[]>([
  {
    id: '1',
    type: 'payment',
    title: 'Pago Procesado',
    message: 'Tu depósito de $50.000 se ha procesado exitosamente.',
    time: new Date(Date.now() - 5 * 60 * 1000),
    read: false,
    action: {
      label: 'Ver detalles',
      url: '/transactions'
    }
  },
  {
    id: '2',
    type: 'game',
    title: '¡Ganaste en Bingo!',
    message: 'Felicitaciones, ganaste $25.000 en la sala Premium.',
    time: new Date(Date.now() - 30 * 60 * 1000),
    read: false,
    action: {
      label: 'Ver premios',
      url: '/games/bingo'
    }
  },
  {
    id: '3',
    type: 'info',
    title: 'Nueva Sala de Bingo',
    message: 'Se ha abierto una nueva sala con premios especiales.',
    time: new Date(Date.now() - 2 * 60 * 60 * 1000),
    read: true
  }
])

const getNotificationIcon = (type: string) => {
  const icons = {
    success: CheckCircleIcon,
    warning: ExclamationTriangleIcon,
    info: InformationCircleIcon,
    payment: CreditCardIcon,
    game: TrophyIcon
  }
  return icons[type as keyof typeof icons] || InformationCircleIcon
}

const getNotificationStyle = (type: string) => {
  const styles = {
    success: { bg: 'bg-green-100', text: 'text-green-600' },
    warning: { bg: 'bg-yellow-100', text: 'text-yellow-600' },
    info: { bg: 'bg-blue-100', text: 'text-blue-600' },
    payment: { bg: 'bg-purple-100', text: 'text-purple-600' },
    game: { bg: 'bg-orange-100', text: 'text-orange-600' }
  }
  return styles[type as keyof typeof styles] || styles.info
}

const formatTime = (time: Date) => {
  const now = new Date()
  const diffMs = now.getTime() - time.getTime()
  const diffMins = Math.floor(diffMs / (1000 * 60))
  const diffHours = Math.floor(diffMs / (1000 * 60 * 60))
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))

  if (diffMins < 1) return 'Ahora'
  if (diffMins < 60) return `${diffMins}m`
  if (diffHours < 24) return `${diffHours}h`
  return `${diffDays}d`
}

const markAsRead = (id: string) => {
  const notification = notifications.value.find(n => n.id === id)
  if (notification) {
    notification.read = true
  }
}

const markAllAsRead = () => {
  notifications.value.forEach(n => {
    n.read = true
  })
}

const clearAll = () => {
  notifications.value = []
}

const handleAction = (action: NotificationAction, id: string) => {
  if (action.url) {
    window.open(action.url, '_self')
  }
  markAsRead(id)
}
</script>