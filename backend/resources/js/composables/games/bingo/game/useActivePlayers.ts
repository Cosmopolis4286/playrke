import { ref, watch, type Ref } from 'vue'
import backendClient from '@/js/plugins/axios'

export interface ActivePlayer { user_id: string; name: string; cards_purchased: number }

/**
 * Lista de jugadores activos en la sala — se carga cada vez que el modal de
 * jugadores se abre y aún no hay ganadores (partida en curso).
 */
export function useActivePlayers(deps: {
  show: Ref<boolean>
  totalWinners: Ref<number>
  roomRouteKey: Ref<string>
  roomId: Ref<string>
}) {
  const { show, totalWinners, roomRouteKey, roomId } = deps

  const activePlayers  = ref<ActivePlayer[]>([])
  const loadingPlayers = ref(false)
  const playersError   = ref(false)

  async function fetchActivePlayers(): Promise<void> {
    if (loadingPlayers.value) return
    loadingPlayers.value = true
    playersError.value   = false
    try {
      const routeKey = roomRouteKey.value || roomId.value
      const { data } = await backendClient.get(`/bingo/${routeKey}/lobby-players`)
      activePlayers.value = (data.players ?? []).sort(
        (a: ActivePlayer, b: ActivePlayer) => b.cards_purchased - a.cards_purchased,
      )
    } catch {
      playersError.value = true
    } finally {
      loadingPlayers.value = false
    }
  }

  // Cargar jugadores cada vez que el modal se abre y aún no hay fin de partida
  watch(show, (val) => {
    if (val && totalWinners.value === 0) fetchActivePlayers()
  })

  return { activePlayers, loadingPlayers, playersError, fetchActivePlayers }
}
