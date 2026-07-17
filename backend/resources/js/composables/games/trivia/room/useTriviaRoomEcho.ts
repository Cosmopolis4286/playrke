import { ref } from 'vue'
import { getEchoInstance } from '@js/plugins/echo'
import type { Player, WsPlayerJoined, WsPlayerLeft, WsGameStarted } from '@js/types/trivia'

interface EchoDeps {
  roomId: string
  roomCode: string
  onPlayerJoined: (player: Player) => void
  onPlayerLeft: (playerId: string) => void
  onGameStarted: (gameId: string) => void
}

/**
 * Suscripción al canal de la sala de espera de Trivia (Echo/Reverb).
 * Laravel Echo antepone '.' al broadcastAs() — los nombres deben coincidir
 * exactamente con broadcastAs() del evento PHP.
 */
export function useTriviaRoomEcho(deps: EchoDeps) {
  const { roomId, roomCode, onPlayerJoined, onPlayerLeft, onGameStarted } = deps

  const connectionStatus = ref<'connected' | 'disconnected' | 'reconnecting'>('disconnected')
  const echo = getEchoInstance()
  const channelName = `trivia.room.${roomId}`
  let channel: any = null

  function subscribe() {
    try {
      channel = echo.channel(channelName)

      channel
        .listen('.trivia.player.joined', (data: WsPlayerJoined) => {
          onPlayerJoined({
            id:       data.player.id,
            name:     data.player.name,
            roomCode: roomCode,
            teamId:   data.player.teamId ?? null,
            joinedAt: new Date().toISOString(),
          })
        })
        .listen('.trivia.player.left', (data: WsPlayerLeft) => {
          onPlayerLeft(data.player_id)
        })
        .listen('.trivia.game.started', (data: WsGameStarted) => {
          onGameStarted(data.game_id)
        })

      const conn = (echo.connector as any).pusher?.connection
      if (conn) {
        conn.bind('connected',    () => { connectionStatus.value = 'connected' })
        conn.bind('disconnected', () => { connectionStatus.value = 'disconnected' })
        conn.bind('unavailable',  () => { connectionStatus.value = 'disconnected' })
        conn.bind('connecting',   () => { connectionStatus.value = 'reconnecting' })

        const state: string = conn.state ?? ''
        if (state === 'connected') connectionStatus.value = 'connected'
        else if (['connecting', 'reconnecting'].includes(state)) connectionStatus.value = 'reconnecting'
      }
    } catch (err) {
      console.warn('[WaitingRoom] Echo no disponible:', err)
      connectionStatus.value = 'disconnected'
    }
  }

  function unsubscribe() {
    try { echo.leaveChannel(channelName) } catch {}
    channel = null
  }

  return { connectionStatus, subscribe, unsubscribe }
}
