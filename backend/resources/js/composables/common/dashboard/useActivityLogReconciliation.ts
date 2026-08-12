import { onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'
import echo from '@/js/plugins/echo'
import type { ActivityItem } from '@/js/types/common/dashboard/dashboard'

interface ActivityLogResponse {
  items: ActivityItem[]
}

/**
 * Reconcilia el feed de actividad reciente tras una caída del WebSocket.
 * El realtime (useAdminActivityRealtime) sigue siendo la vía principal —
 * esto solo rellena el hueco de eventos ocurridos mientras la conexión
 * estuvo caída, consultando `/dashboard/activity-log?since=<último visto>`
 * (query federada sobre las tablas de origen, sin tabla de log propia).
 *
 * Dispara la reconciliación:
 * - una vez al montar (por si la página se abrió con el socket ya caído).
 * - cada vez que Pusher pasa de un estado no-conectado a "connected" de
 *   nuevo (reconexión real, no la conexión inicial — se ignora el primer
 *   evento "connected" para no duplicar la carga inicial de props Inertia).
 */
export function useActivityLogReconciliation(onItems: (items: ActivityItem[]) => void) {
  let hasConnectedBefore = false
  let lastSeenTimestamp: string | null = null

  function trackTimestamp(items: ActivityItem[]) {
    for (const item of items) {
      if (!lastSeenTimestamp || item.timestamp > lastSeenTimestamp) {
        lastSeenTimestamp = item.timestamp
      }
    }
  }

  async function reconcile() {
    try {
      const { data } = await axios.get<ActivityLogResponse>('/dashboard/activity-log', {
        params: lastSeenTimestamp ? { since: lastSeenTimestamp } : undefined,
      })
      if (data.items.length) {
        onItems(data.items)
        trackTimestamp(data.items)
      }
    } catch {
      // Silencioso: el feed realtime sigue funcionando igual, esto es solo
      // un best-effort de reconciliación.
    }
  }

  const onConnected = () => {
    if (hasConnectedBefore) {
      reconcile()
    }
    hasConnectedBefore = true
  }

  onMounted(() => {
    reconcile()
    echo.connector.pusher.connection.bind('connected', onConnected)
  })

  onBeforeUnmount(() => {
    echo.connector.pusher.connection.unbind('connected', onConnected)
  })

  /** Permite que el consumidor registre timestamps de items que llegaron por realtime (no por esta reconciliación), para que el próximo `since` no repita eventos ya vistos en vivo. */
  function noteRealtimeItem(item: ActivityItem) {
    if (!lastSeenTimestamp || item.timestamp > lastSeenTimestamp) {
      lastSeenTimestamp = item.timestamp
    }
  }

  return { noteRealtimeItem }
}
