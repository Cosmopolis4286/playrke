import { ref, watch } from 'vue'

export interface LobbyPlayer {
  user_id: string
  name: string
  cards_purchased: number
  role: string
}

export function useLobbyPlayers(roomRouteKey: string | undefined, totalPlayers: () => number) {
  const showModal    = ref(false)
  const playersList  = ref<LobbyPlayer[]>([])
  const loading      = ref(false)

  async function open() {
    showModal.value = true
    if (playersList.value.length > 0) return
    loading.value = true
    try {
      const res  = await fetch(`/bingo/${roomRouteKey}/lobby-players`, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      const data = await res.json()
      playersList.value = data.players ?? []
    } catch {
      playersList.value = []
    } finally {
      loading.value = false
    }
  }

  function close() { showModal.value = false }

  // Invalida la caché cuando entra un jugador nuevo (fuerza recarga en próxima apertura)
  watch(totalPlayers, () => {
    if (showModal.value) playersList.value = []
  })

  return { showModal, playersList, loading, open, close }
}
