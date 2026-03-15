/**
 * Composable para notificaciones globales compartidas desde el backend
 * Lee los datos inyectados en window.notificationsData
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

export interface GlobalNotificationsData {
  unreadCount: number
  notifications: NotificationData[]
  userId: number
}

export const useGlobalNotifications = () => {
  // Estado inicial
  const notifications = ref<NotificationData[]>([])
  const unreadCount = ref(0)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // Cargar datos globales si están disponibles
  const loadGlobalNotifications = () => {
    try {
      isLoading.value = true
      error.value = null

      // Verificar si hay datos globales disponibles
      if (process.client && window.notificationsData) {
        const globalData: GlobalNotificationsData = window.notificationsData
        
        notifications.value = globalData.notifications || []
        unreadCount.value = globalData.unreadCount || 0
        
        console.log('Global notifications loaded:', {
          count: notifications.value.length,
          unreadCount: unreadCount.value,
          userId: globalData.userId
        })
        
        // Limpiar datos globales después de cargarlos
        delete window.notificationsData
      } else {
        console.log('No global notifications data found, falling back to API')
        // Si no hay datos globales, hacer fallback a la API
        loadNotificationsFromAPI()
      }
    } catch (err) {
      console.error('Error loading global notifications:', err)
      error.value = 'Error al cargar notificaciones globales'
      // Fallback a API
      loadNotificationsFromAPI()
    } finally {
      isLoading.value = false
    }
  }

  // Fallback a API si no hay datos globales
  const loadNotificationsFromAPI = async () => {
    try {
      const response = await $fetch('/notifications/unread', {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        }
      })
      
      notifications.value = response.data || []
      unreadCount.value = response.unread_count || 0
      
      console.log('API notifications loaded:', {
        count: notifications.value.length,
        unreadCount: unreadCount.value
      })
    } catch (err) {
      console.error('Error loading API notifications:', err)
      error.value = 'Error al cargar notificaciones desde API'
    }
  }

  // Escuchar evento de notificaciones cargadas
  const listenForNotificationsEvent = () => {
    if (process.client) {
      window.addEventListener('notifications-loaded', (event: any) => {
        const data = event.detail as GlobalNotificationsData
        
        notifications.value = data.notifications || []
        unreadCount.value = data.unreadCount || 0
        
        console.log('Notifications loaded via event:', {
          count: notifications.value.length,
          unreadCount: unreadCount.value,
          userId: data.userId
        })
      })
    }
  }

  // Integración con WebSocket para actualizaciones en tiempo real
  const listenToWebSocketUpdates = () => {
    if (process.client && window.Echo) {
      // Escuchar eventos de billetera
      window.Echo.private(`App.Models.User.${useAuth().user?.id}`)
        .listen('wallet.updated', (event: any) => {
          console.log('WebSocket wallet update received:', event)
          
          // Crear notificación local
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
          
          // Agregar al principio
          notifications.value.unshift(notification)
          unreadCount.value++
          
          // Notificación del navegador
          if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(notification.title, {
              body: notification.message,
              icon: '/favicon.ico',
              tag: notification.id,
            })
          }
        })
    }
  }

  // Funciones helper
  const getWalletTitle = (movementType: string): string => {
    const titles = {
      DEPOSIT: '💰 Depósito Recibido',
      WITHDRAWAL: '💸 Retiro Realizado',
      CARD_PURCHASE: '🎫 Compra de Cartones',
    }
    return titles[movementType] || '💳 Movimiento de Billetera'
  }

  const getWalletMessage = (event: any): string => {
    const { movement_type, amount, currency_symbol } = event
    const messages = {
      DEPOSIT: `Has recibido ${currency_symbol}${amount}`,
      WITHDRAWAL: `Has retirado ${currency_symbol}${amount}`,
      CARD_PURCHASE: `Has gastado ${currency_symbol}${amount} en cartones`,
    }
    return messages[movement_type] || `Movimiento de ${currency_symbol}${amount}`
  }

  // Lifecycle
  onMounted(() => {
    listenForNotificationsEvent()
    loadGlobalNotifications()
    listenToWebSocketUpdates()
  })

  return {
    // Estado
    notifications: readonly(notifications),
    unreadCount: readonly(unreadCount),
    isLoading: readonly(isLoading),
    error: readonly(error),
    
    // Acciones
    loadGlobalNotifications,
    loadNotificationsFromAPI,
  }
}
