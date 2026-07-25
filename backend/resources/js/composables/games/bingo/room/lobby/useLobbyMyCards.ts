import { ref } from 'vue'
import backendClient from '@/js/plugins/axios'

export interface LobbyCard {
  id: string
  serialNumber: number
  /** Matriz del cartón por columna (B/I/N/G/O) — ver LobbyMyCardsModal::colNumbers(). */
  cardMatrix: Record<string, number[]>
  isActive: boolean
  isCustomCard: boolean
  purchaseDate: string | null
}

export function useLobbyMyCards(roomRouteKey: string | undefined) {
  const showModal  = ref(false)
  const loading    = ref(false)
  const fetchError = ref(false)
  const cards      = ref<LobbyCard[]>([])

  async function open() {
    showModal.value  = true
    fetchError.value = false

    if (cards.value.length > 0) return

    loading.value = true
    try {
      const res = await backendClient.get<{ success: boolean; data: LobbyCard[] }>(
        `/bingo/${roomRouteKey}/user-cards`
      )
      cards.value = res.data?.data ?? []
    } catch {
      fetchError.value = true
    } finally {
      loading.value = false
    }
  }

  function close() {
    showModal.value = false
  }

  function refresh() {
    cards.value = []
    open()
  }

  return { showModal, loading, fetchError, cards, open, close, refresh }
}
