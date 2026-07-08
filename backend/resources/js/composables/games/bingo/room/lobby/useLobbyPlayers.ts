import { ref, watch, onUnmounted } from 'vue'
import backendClient from '@/js/plugins/axios'

export interface LobbyPlayer {
  user_id: string
  name: string
  cards_purchased: number
  role: string
}

const STALE_MS = 120_000

export function useLobbyPlayers(roomRouteKey: string | undefined, totalPlayers: () => number) {
  const showModal   = ref(false)
  const playersList = ref<LobbyPlayer[]>([])
  const loading     = ref(false)
  const fetchError  = ref(false)
  let debounceTimer: ReturnType<typeof setTimeout> | null = null
  let lastFetchedAt = 0

  async function open() {
    showModal.value  = true
    fetchError.value = false
    const isStale = Date.now() - lastFetchedAt > STALE_MS
    if (playersList.value.length > 0 && !isStale) return
    loading.value = true
    try {
      const { data } = await backendClient.get(`/bingo/${roomRouteKey}/lobby-players`)
      playersList.value = data.players ?? []
      lastFetchedAt     = Date.now()
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
    if (!showModal.value) return
    if (debounceTimer) clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
      debounceTimer = null
      open()
    }, 400)
  })

  onUnmounted(() => { if (debounceTimer) clearTimeout(debounceTimer) })

  return { showModal, playersList, loading, fetchError, open, close }
}
