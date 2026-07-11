/**
 * Composable para manejar notificaciones persistentes
 * Integra notificaciones WebSocket con notificaciones almacenadas en BD
 */

export interface NotificationData {
  id: string
  type: string
  title: string
  message: string
  data: Record<string, any>
  is_read: boolean
  read_at?: string
  created_at: string
  formatted_time: string
  type_color: string
  type_icon: string
}

export const usePersistentNotifications = () => {
  // Estado
  const notifications = ref<NotificationData[]>([])
  const unreadCount = ref(0)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // Cargar notificaciones persistentes al montar
  const loadNotifications = async () => {
    try {
      isLoading.value = true
      error.value = null
      
      const response = await $fetch('/notifications/unread', {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        }
      })
      
      notifications.value = response.data || []
      unreadCount.value = response.unread_count || 0
      
      console.log('Notifications loaded:', {
        count: notifications.value.length,
        unreadCount: unreadCount.value,
        data: response
      })
    } catch (err) {
      console.error('Error loading notifications:', err)
      error.value = 'Error al cargar notificaciones'
    } finally {
      isLoading.value = false
    }
  }

  // Marcar notificación como leída
  const markAsRead = async (notificationId: string) => {
    try {
      await $fetch(`/notifications/${notificationId}/read`, {
        method: 'PATCH',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        }
      })
      
      // Actualizar estado local
      const notification = notifications.value.find(n => n.id === notificationId)
      if (notification) {
        notification.is_read = true
        notification.read_at = new Date().toISOString()
        unreadCount.value = Math.max(0, unreadCount.value - 1)
      }
    } catch (err) {
      console.error('Error marking notification as read:', err)
      error.value = 'Error al marcar notificación como leída'
    }
  }

  // Marcar todas como leídas
  const markAllAsRead = async () => {
    try {
      await $fetch('/notifications/read-all', {
        method: 'PATCH',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        }
      })
      
      // Actualizar estado local
      notifications.value.forEach(notification => {
        notification.is_read = true
        notification.read_at = new Date().toISOString()
      })
      unreadCount.value = 0
    } catch (err) {
      console.error('Error marking all notifications as read:', err)
      error.value = 'Error al marcar todas las notificaciones como leídas'
    }
  }

  // Eliminar notificación
  const deleteNotification = async (notificationId: string) => {
    try {
      await $fetch(`/notifications/${notificationId}`, {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        }
      })
      
      // Actualizar estado local
      const index = notifications.value.findIndex(n => n.id === notificationId)
      if (index !== -1) {
        const notification = notifications.value[index]
        if (!notification.is_read) {
          unreadCount.value = Math.max(0, unreadCount.value - 1)
        }
        notifications.value.splice(index, 1)
      }
    } catch (err) {
      console.error('Error deleting notification:', err)
      error.value = 'Error al eliminar notificación'
    }
  }

  // Agregar notificación nueva (recibida por WebSocket)
  const addNotification = (notification: NotificationData) => {
    // Agregar al principio de la lista
    notifications.value.unshift(notification)
    
    if (!notification.is_read) {
      unreadCount.value++
    }
    
    // Mostrar notificación del navegador si está permitido
    if ('Notification' in window && Notification.permission === 'granted') {
      new Notification(notification.title, {
        body: notification.message,
        icon: '/favicon.ico',
        tag: notification.id,
      })
    }
  }

  // Integración con WebSocket existente
  const { $echo } = useNuxtApp()
  
  // Escuchar eventos de billetera
  const listenToWalletUpdates = () => {
    if (process.client && $echo) {
      const userId = useAuth().user?.id
      
      if (userId) {
        $echo.private(`App.Models.User.${userId}`)
          .listen('wallet.updated', (event: any) => {
            // Crear notificación persistente local
            const notification: NotificationData = {
              id: `wallet-${Date.now()}`,
              type: 'WALLET_UPDATED',
              title: getWalletTitle(event.movement_type),
              message: getWalletMessage(event),
              data: event,
              is_read: false,
              created_at: new Date().toISOString(),
              formatted_time: 'Ahora',
              type_color: '#28a745',
              type_icon: '💰',
            }
            
            addNotification(notification)
          })
      }
    }
  }

  // Funciones helper
  const getWalletTitle = (movementType: string): string => {
    const titles: Record<string, string> = {
      DEPOSIT: '💰 Depósito Recibido',
      WITHDRAWAL: '💸 Retiro Realizado',
      PURCHASE: '🎫 Compra de Cartones',
      CARD_PURCHASE: '🎫 Compra de Cartones',
      PRIZE: '🏆 ¡Premio Ganado!',
      REFUND: '↩️ Reembolso Recibido',
      TRIVIA_ENTRY: '🧠 Entrada a Trivia',
      RKE_COMMISSION: '💼 Comisión Recibida',
    }
    return titles[movementType] || '💳 Movimiento de Billetera'
  }

  const getWalletMessage = (event: any): string => {
    const { movement_type, amount, currency_symbol } = event
    const messages: Record<string, string> = {
      DEPOSIT: `Has recibido ${currency_symbol}${amount}`,
      WITHDRAWAL: `Has retirado ${currency_symbol}${amount}`,
      PURCHASE: `Has gastado ${currency_symbol}${amount} en cartones`,
      CARD_PURCHASE: `Has gastado ${currency_symbol}${amount} en cartones`,
      PRIZE: `Ganaste ${currency_symbol}${amount}`,
      REFUND: `Se reembolsaron ${currency_symbol}${amount} a tu billetera`,
      TRIVIA_ENTRY: `Has gastado ${currency_symbol}${amount} en una entrada de trivia`,
      RKE_COMMISSION: `Has recibido ${currency_symbol}${amount} por comisión`,
    }
    return messages[movement_type] || `Movimiento de ${currency_symbol}${amount}`
  }

  // Recargar contador de notificaciones no leídas
  const refreshUnreadCount = async () => {
    try {
      const response = await $fetch('/notifications/unread-count', {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        }
      })
      
      unreadCount.value = response.unread_count || 0
    } catch (err) {
      console.error('Error refreshing unread count:', err)
    }
  }

  // Lifecycle
  onMounted(() => {
    loadNotifications()
    listenToWalletUpdates()
    
    // Refrescar contador cada 30 segundos
    const interval = setInterval(refreshUnreadCount, 30000)
    
    onUnmounted(() => {
      clearInterval(interval)
    })
  })

  return {
    // Estado
    notifications: readonly(notifications),
    unreadCount: readonly(unreadCount),
    isLoading: readonly(isLoading),
    error: readonly(error),
    
    // Acciones
    loadNotifications,
    markAsRead,
    markAllAsRead,
    deleteNotification,
    addNotification,
    refreshUnreadCount,
  }
}
