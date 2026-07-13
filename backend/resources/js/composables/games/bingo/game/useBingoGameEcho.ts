import { onUnmounted, ref, type Ref } from 'vue'
import echo from '@js/plugins/echo'
import type { PlayerProgress } from '@js/Components/Games/Bingo/views/inGame/types'

export type { PlayerProgress }

export interface GameEndedEvent {
  has_winner:  boolean
  total_drawn: number
  prize:       number
  end_time:    string
  winners: {
    user_id:     number | string
    user_name:   string
    card_id:     number | string
    card_serial: string
    prize:       number
    pattern?:    string
    round?:      number
  }[]
}

export interface UseBingoGameEchoOptions {
  roomId:            Ref<string>
  userId:            string | null | undefined
  syncTime:          (serverTimestampMs?: number) => void
  updateRoomTime:    (roomId: string, unixDate: number) => void
  currentUnixDate:   Ref<number>
  clearDrawTimer:    () => void
  playNext:          () => void
  playWinner:        () => void
  onRoomUpdated:     (e: { prize_pool: number; total_players: number }) => void
  onPlayerJoined:    (e: { current_players: number }) => void
  onNumberCalled:    (e: { number: number }) => void
  onPlayersProgress: (e: { players: PlayerProgress[] }) => void
  onGameStarted:     () => void
  onWinnerDeclared:  (e: { winner: { id: number | string } }) => void
  onGameEnded:       (e: GameEndedEvent) => void
  /** Se dispara al recuperar la conexión tras una desconexión (no en el
   * connect inicial) — usado para resincronizar estado que pudo perderse
   * mientras el socket estuvo caído, ya que Reverb/Pusher no reenvía
   * eventos pasados. */
  onReconnected?:    () => void
}

const ROOM_EVENTS = [
  '.RoomUpdated', '.PlayerJoined', '.BingoNumberCalled', '.PlayersProgress',
  '.GameStarted', '.WinnerDeclared', '.GameEnded', '.RoomTimeSync',
] as const

export function useBingoGameEcho(options: UseBingoGameEchoOptions) {
  const {
    roomId, userId,
    syncTime, updateRoomTime, currentUnixDate,
    clearDrawTimer, playNext, playWinner,
    onRoomUpdated, onPlayerJoined, onNumberCalled,
    onPlayersProgress, onGameStarted, onWinnerDeclared,
    onGameEnded, onReconnected,
  } = options

  let roomChannelName: string | null = null
  const calledNumbers    = new Set<number>()
  let wasDisconnected    = false
  const isReconnecting   = ref(false)

  function resetCalledNumbers() {
    calledNumbers.clear()
  }

  /** Marca números como ya vistos sin re-emitirlos — usado tras un resync
   * por HTTP (ej. draw-state) para que el listener de WS no los duplique
   * si llegan de nuevo por socket. */
  function markNumbersAsSeen(numbers: number[]) {
    for (const n of numbers) calledNumbers.add(n)
  }

  function handleConnectionStateChange(states: { current: string }) {
    if (states.current === 'disconnected' || states.current === 'unavailable') {
      wasDisconnected      = true
      isReconnecting.value = true
    } else if (states.current === 'connected') {
      if (wasDisconnected) {
        wasDisconnected = false
        onReconnected?.()
      }
      isReconnecting.value = false
    }
  }

  function subscribe() {
    if (!roomId.value) return
    // unbind previo a modo defensivo: si subscribe() llegara a invocarse dos
    // veces sin pasar por unmount, evita apilar el mismo handler N veces y
    // que onReconnected se dispare N veces por cada reconexión real.
    echo.connector.pusher.connection.unbind('state_change', handleConnectionStateChange)
    echo.connector.pusher.connection.bind('state_change', handleConnectionStateChange)
    roomChannelName = `bingo.room.${roomId.value}`
    const channel   = echo.channel(roomChannelName)

    channel.listen('.RoomUpdated', (e: { prize_pool: number; total_players: number }) => {
      onRoomUpdated(e)
    })

    channel.listen('.PlayerJoined', (e: { current_players: number }) => {
      onPlayerJoined(e)
    })

    channel.listen('.BingoNumberCalled', (e: { number: number }) => {
      if (e.number !== null && e.number !== undefined && !calledNumbers.has(e.number)) {
        calledNumbers.add(e.number)
        onNumberCalled(e)
        playNext()
      }
    })

    channel.listen('.PlayersProgress', (e: { players: PlayerProgress[] }) => {
      onPlayersProgress(e)
    })

    channel.listen('.GameStarted', () => {
      onGameStarted()
      clearDrawTimer()
    })

    channel.listen('.WinnerDeclared', (e: { winner: { id: number | string } }) => {
      if (e.winner && String(e.winner.id) === String(userId)) {
        onWinnerDeclared(e)
      }
    })

    channel.listen('.GameEnded', (e: GameEndedEvent) => {
      onGameEnded(e)
      playWinner()
    })

    channel.listen('.RoomTimeSync', (e: { server_timestamp: number; unix_date: number }) => {
      if (e.server_timestamp) {
        syncTime(e.server_timestamp * 1000)
      }
      if (e.unix_date && e.unix_date !== currentUnixDate.value) {
        currentUnixDate.value = e.unix_date
        updateRoomTime(roomId.value, e.unix_date)
      }
    })
  }

  function unsubscribe() {
    echo.connector.pusher.connection.unbind('state_change', handleConnectionStateChange)
    if (!roomChannelName) return
    const channel = echo.channel(roomChannelName)
    for (const event of ROOM_EVENTS) channel.stopListening(event)
    echo.leaveChannel(roomChannelName)
    roomChannelName = null
  }

  onUnmounted(() => unsubscribe())

  return { subscribe, unsubscribe, resetCalledNumbers, markNumbersAsSeen, isReconnecting }
}
