<template>
  <div class="relative">
    <!-- Botón de campana -->
    <button
      :class="buttonClasses"
      :aria-label="ariaLabel"
      :aria-expanded="showDropdown"
      aria-haspopup="true"
      @click="toggleDropdown"
    >
      <!-- Icono de campana con efecto pulse -->
      <div class="relative">
        <SharedIcon
          name="notification"
          :class="bellIconClasses"
        />

        <!-- Efecto pulse para notificaciones nuevas -->
        <div
          v-if="hasNewNotifications"
          class="absolute inset-0 rounded-full animate-ping bg-primary-400 opacity-30"
        />
      </div>

      <!-- Badge de contador premium -->
      <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 scale-0"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-0"
      >
        <BaseBadge
          v-if="unreadCount > 0"
          :class="badgeClasses"
          variant="error"
          size="sm"
          :pulse="hasNewNotifications"
        >
          {{ unreadCount > 99 ? '99+' : unreadCount }}
        </BaseBadge>
      </Transition>

      <!-- Indicador de conexión -->
      <div
        :class="connectionIndicatorClasses"
        :title="connectionTooltip"
        :aria-label="connectionTooltip"
      />
    </button>

    <!-- Dropdown de notificaciones -->
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="showDropdown"
        class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 z-50"
      >
        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900">
            Notificaciones
          </h3>
          <div class="flex items-center space-x-2">
            <!-- Estado de conexión -->
            <span
              class="text-xs px-2 py-1 rounded-full"
              :class="isConnected
                ? 'bg-green-100 text-green-800'
                : 'bg-red-100 text-red-800'"
            >
              {{ isConnected ? 'En línea' : 'Desconectado' }}
            </span>
            <!-- Botón limpiar -->
            <button
              v-if="notifications.length > 0"
              class="text-xs text-blue-600 hover:text-blue-800"
              @click="clearAll"
            >
              Limpiar
            </button>
          </div>
        </div>

        <!-- Lista de notificaciones -->
        <div class="max-h-96 overflow-y-auto">
          <div
            v-if="notifications.length === 0"
            class="px-4 py-8 text-center text-gray-500"
          >
            <SharedIcon
              name="notification"
              class="w-12 h-12 mx-auto mb-2 text-primary-300"
            />
            <p>No hay notificaciones</p>
          </div>

          <div
            v-for="(notification, index) in notifications"
            :key="`${notification.timestamp}-${index}`"
            class="px-4 py-3 border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors"
            :class="{ 'bg-blue-50': !notification.data.read }"
            @click="markAsRead(notification.id)"
          >
            <div class="flex items-start space-x-3">
              <!-- Icono según tipo -->
              <div
                class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center"
                :class="getNotificationIconClass(notification.type)"
              >
                <component
                  :is="getNotificationIcon(notification.type)"
                  class="w-4 h-4"
                />
              </div>

              <!-- Contenido -->
              <div class="flex-1 min-w-0">
                <p
                  class="text-sm font-medium text-gray-900"
                  :class="{ 'font-bold': !notification.data.read }"
                >
                  {{ notification.data.message }}
                </p>

                <!-- Datos adicionales -->
                <div
                  v-if="notification.data?.eventType === 'draw_finished'"
                  class="mt-1 text-xs text-gray-600"
                >
                  <span>Números: {{ notification.data.winningNumbers?.join(', ') }}</span>
                  <span class="ml-2">Ganadores: {{ notification.data.totalWinners }}</span>
                </div>

                <div
                  v-if="notification.data?.eventType === 'user_win'"
                  class="mt-1 text-xs text-green-600 font-semibold"
                >
                  🎉 Premio: {{ formatCurrency(notification.data.amount) }}
                </div>

                <!-- Timestamp -->
                <p class="mt-1 text-xs text-gray-500">
                  {{ formatTime(notification.timestamp?.toString() || '') }}
                </p>
              </div>

              <!-- Indicador no leído -->
              <div
                v-if="!notification.data.read"
                class="flex-shrink-0 w-2 h-2 bg-blue-500 rounded-full"
              />
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div
          v-if="notifications.length > 0"
          class="px-4 py-3 border-t border-gray-200 text-center"
        >
          <button
            class="text-sm text-blue-600 hover:text-blue-800"
            @click="requestPermissions"
          >
            {{ hasNotificationPermission ? 'Notificaciones habilitadas' : 'Habilitar notificaciones del navegador' }}
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
/**
 * @fileoverview NotificationBell - Campana de notificaciones optimizada v2.0
 * @description Componente premium de notificaciones con Heroicons y animaciones
 * @author Lotto38 Team
 * @version 2.0.0
 * @since 2025-09-16
 */

import { ref, computed, onMounted, onUnmounted } from 'vue'
// useNotifications auto-imported by Nuxt
// useResponsive auto-imported by Nuxt

// Composables
const notificationService = useNotifications()
const { viewport } = useResponsive()

// Estado local
const showDropdown = ref(false)
const hasNotificationPermission = ref(false)
const lastNotificationCount = ref(0)

// Props computadas del servicio
const {
  isConnected,
  notifications,
  unreadCount,
  clearNotifications,
  markAsRead,
} = notificationService

// Computed properties optimizadas
const hasNewNotifications = computed(() => {
  const currentCount = unreadCount.value
  const hasNew = currentCount > lastNotificationCount.value
  lastNotificationCount.value = currentCount
  return hasNew && currentCount > 0
})

const ariaLabel = computed(() => {
  if (unreadCount.value === 0) { return 'Notificaciones, no hay nuevas' }
  return `Notificaciones, ${unreadCount.value} sin leer`
})

const buttonClasses = computed(() => {
  const baseClasses = [
    'relative p-2 rounded-xl transition-all duration-300 ease-out',
    'focus:outline-none focus:ring-2 focus:ring-primary-500',
    'hover:bg-primary-50 active:bg-primary-100',
    'transform-gpu will-change-transform',
  ]

  const stateClasses = unreadCount.value > 0
    ? [
        'text-primary-600 hover:text-primary-700',
        'bg-primary-50/50 hover:bg-primary-100/80',
      ]
    : [
        'text-gray-600 hover:text-gray-900',
      ]

  const responsiveClasses = viewport.isMobile
    ? [
        'p-3',
      ]
    : []

  return [...baseClasses, ...stateClasses, ...responsiveClasses].join(' ')
})

const bellIconClasses = computed(() => {
  const size = viewport.isMobile ? 'w-6 h-6' : 'w-6 h-6'
  const animationClasses = hasNewNotifications.value
    ? [
        'animate-gentle-bounce',
      ]
    : []

  return [size, 'transition-all duration-300', ...animationClasses].join(' ')
})

const badgeClasses = computed(() => [
  'absolute -top-1 -right-1',
  'transform-gpu transition-all duration-300',
].join(' '))

const connectionIndicatorClasses = computed(() => {
  const baseClasses = [
    'absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full',
    'border-2 border-white transition-all duration-300',
  ]

  const statusClasses = isConnected.value
    ? [
        'bg-success-500 shadow-success-soft',
      ]
    : [
        'bg-gray-400 shadow-sm',
      ]

  return [...baseClasses, ...statusClasses].join(' ')
})

const connectionTooltip = computed(() =>
  isConnected.value ? 'Conectado en tiempo real' : 'Desconectado',
)

/**
 * Toggle del dropdown
 */
const toggleDropdown = () => {
  showDropdown.value = !showDropdown.value
}

/**
 * Limpia todas las notificaciones
 */
const clearAll = () => {
  clearNotifications()
  showDropdown.value = false
}

/**
 * Solicita permisos de notificación
 */
const requestPermissions = async () => {
  if ('Notification' in window) {
    const permission = await Notification.requestPermission()
    hasNotificationPermission.value = permission === 'granted'
  }
}

/**
 * Obtiene la clase del icono según el tipo de notificación
 */
const getNotificationIconClass = (type: string | undefined): string => {
  const classes = {
    draw_started: 'bg-blue-100 text-blue-600',
    draw_finished: 'bg-green-100 text-green-600',
    user_win: 'bg-yellow-100 text-yellow-600',
    admin_notification: 'bg-purple-100 text-purple-600',
    system_alert: 'bg-red-100 text-red-600',
    default: 'bg-gray-100 text-gray-600',
  } as const
  const normalizedType = type ?? 'default'
  return (classes as any)[normalizedType] ?? classes.default
}

/**
 * Obtiene el icono según el tipo de notificación usando Heroicons
 */
const getNotificationSharedIcon = (type: string | undefined) => {
  const icons: Record<string, string> = {
    draw_started: 'play',
    draw_finished: 'trophy',
    user_win: 'heart',
    admin_notification: 'user',
    system_alert: 'warning',
    default: 'info',
  }
  return icons[type ?? 'default'] ?? icons.default
}

/**
 * Formatea timestamp a tiempo relativo
 */
const formatTime = (timestamp: string): string => {
  const date = new Date(timestamp)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMins / 60)
  const diffDays = Math.floor(diffHours / 24)

  if (diffMins < 1) { return 'Ahora' }
  if (diffMins < 60) { return `${diffMins}m` }
  if (diffHours < 24) { return `${diffHours}h` }
  if (diffDays < 7) { return `${diffDays}d` }

  return date.toLocaleDateString('es-ES', {
    day: '2-digit',
    month: '2-digit',
  })
}

/**
 * Formatea moneda
 */
const formatCurrency = (amount: number): string => new Intl.NumberFormat('es-VE', {
  style: 'currency',
  currency: 'VES',
  minimumFractionDigits: 0,
}).format(amount)

/**
 * Cierra dropdown al hacer click fuera
 */
const handleClickOutside = (event: Event) => {
  const target = event.target as HTMLElement
  if (!target.closest('.relative')) {
    showDropdown.value = false
  }
}

// Lifecycle
onMounted(() => {
  document.addEventListener('click', handleClickOutside)

  // Verificar permisos de notificación
  if ('Notification' in window) {
    hasNotificationPermission.value = Notification.permission === 'granted'
  }
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>
