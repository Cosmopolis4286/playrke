import { ref, watch } from 'vue'

export interface LobbyPlayer {
  user_id: string
  name: string
  cards_purchased: number
  role: string
}

export function useLobbyPlayers(roomRouteKey: string | undefined, totalPlayers: () => number) {
  const showModal   = ref(false)
  const playersList = ref<LobbyPlayer[]>([])
  const loading     = ref(false)
  const fetchError  = ref(false)

  async function open() {
    showModal.value  = true
    fetchError.value = false
    if (playersList.value.length > 0) return
    loading.value = true
    try {
      const res = await fetch(`/bingo/${roomRouteKey}/lobby-players`, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      })
      if (!res.ok) throw new Error(`HTTP ${res.status}`)
      const data = await res.json()
      playersList.value = data.players ?? []
    } catch {
      playersList.value = []
      fetchError.value  = true
    } finally {
      loading.value = false
    }
  }

  function close() {
    showModal.value  = false
    fetchError.value = false
  }

  watch(totalPlayers, () => {
    playersList.value = []
    if (showModal.value) open()
  })

  return { showModal, playersList, loading, fetchError, open, close }
}
