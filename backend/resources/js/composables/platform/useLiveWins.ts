import { ref, computed, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import echo from '@/js/plugins/echo'

export interface LiveWin {
  id: string
  game: string
  game_slug: string
  winner_name: string
  amount: number
  currency: string
  currency_symbol: string
  is_usd: boolean
  local_amount: number
  decimal_places: number
  timestamp: string
}

const MAX_WINS = 20

/** Tiempo máximo (ms) que un premio permanece visible en el ticker. */
const TTL_MS = 15_000
/** Frecuencia (ms) con la que se revisa si hay premios vencidos. */
const TICK_MS = 1_000

export function useLiveWins(maxItems = 8) {
  const allWins = ref<LiveWin[]>([])
  const now = ref(Date.now())
  const channel = echo.channel('live-wins')

  const wins = computed(() =>
    allWins.value.filter((win) => now.value - new Date(win.timestamp).getTime() < TTL_MS)
  )

  channel.listen('.NewWin', (payload: Omit<LiveWin, 'id'>) => {
    const win: LiveWin = {
      ...payload,
      id: `${Date.now()}-${Math.random()}`,
    }
    allWins.value = [win, ...allWins.value].slice(0, MAX_WINS)
  })

  onMounted(async () => {
    try {
      const { data } = await axios.get<{ wins: Omit<LiveWin, 'id'>[] }>('/api/live-wins/recent')
      allWins.value = (data.wins ?? []).map((win, i) => ({
        ...win,
        id: `seed-${i}-${win.timestamp}`,
      }))
    } catch (err) {
      console.error('❌ Error cargando ganadores recientes:', err)
    }
  })

  const tick = window.setInterval(() => {
    now.value = Date.now()
  }, TICK_MS)

  onUnmounted(() => {
    echo.leaveChannel('live-wins')
    window.clearInterval(tick)
  })

  function formatWinAmount(win: LiveWin): string {
    if (win.is_usd) {
      return `$${win.amount.toFixed(2)} USD`
    }
    const local = win.local_amount > 0 ? win.local_amount : win.amount
    return `${win.currency_symbol}${local.toFixed(win.decimal_places)}`
  }

  function maskName(name: string): string {
    if (!name || name.length <= 3) return `${name}***`
    return `${name.slice(0, 3)}${'*'.repeat(Math.min(name.length - 3, 4))}`
  }

  return { wins, formatWinAmount, maskName }
}
