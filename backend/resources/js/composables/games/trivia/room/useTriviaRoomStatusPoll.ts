import { ref, watch, onUnmounted, type Ref } from 'vue'
import backendClient from '@js/plugins/axios'

/**
 * Respaldo del evento en tiempo real de inicio de partida (useTriviaRoomEcho):
 * si la conexión se cae o el jugador llega tarde, el evento GameStarted no le
 * llega y se quedaba en la sala de espera. Mientras `enabled` (jugador
 * inscrito), consulta GET /trivia/room/{code}/state cada `intervalMs`, al
 * volver a la pestaña y al reconectar, y avisa cuando la partida empezó o la
 * sala se canceló.
 */
export function useTriviaRoomStatusPoll(options: {
  roomCode: string
  enabled: Ref<boolean>
  connectionStatus: Ref<'connected' | 'disconnected' | 'reconnecting'>
  onPlaying: (gameId: string) => void
  onCancelled: () => void
  intervalMs?: number
}) {
  const scheduledAt = ref<string | null>(null)
  let timer: ReturnType<typeof setInterval> | null = null
  let inFlight = false
  let done = false

  async function check() {
    if (done || inFlight || !options.enabled.value) return
    inFlight = true
    try {
      const { data } = await backendClient.get(`/trivia/room/${options.roomCode}/state`)
      scheduledAt.value = data.scheduledAt ?? scheduledAt.value
      if (data.gameId && ['playing', 'paused'].includes(data.status)) {
        done = true
        options.onPlaying(String(data.gameId))
      } else if (['cancelling', 'cancelled', 'closed', 'finished'].includes(data.status)) {
        done = true
        options.onCancelled()
      }
    } catch {
      // Sin red: se reintenta en el próximo ciclo.
    } finally {
      inFlight = false
    }
  }

  function onVisible() {
    if (document.visibilityState === 'visible') check()
  }

  function start() {
    if (timer) return
    timer = setInterval(check, options.intervalMs ?? 8000)
    document.addEventListener('visibilitychange', onVisible)
    check()
  }

  function stop() {
    if (timer) clearInterval(timer)
    timer = null
    document.removeEventListener('visibilitychange', onVisible)
  }

  watch(options.enabled, (on) => (on ? start() : stop()), { immediate: true })
  // Al reconectar puede haberse perdido el evento de inicio: consultar ya.
  watch(options.connectionStatus, (status) => { if (status === 'connected') check() })
  onUnmounted(stop)

  return { scheduledAt, check }
}
