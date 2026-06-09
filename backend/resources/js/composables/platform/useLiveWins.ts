import { ref, onUnmounted } from 'vue'
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

export function useLiveWins(maxItems = 8) {
  const wins = ref<LiveWin[]>([])
  const channel = echo.channel('live-wins')

  channel.listen('.NewWin', (payload: Omit<LiveWin, 'id'>) => {
    const win: LiveWin = {
      ...payload,
      id: `${Date.now()}-${Math.random()}`,
    }
    wins.value = [win, ...wins.value].slice(0, MAX_WINS)
  })

  onUnmounted(() => {
    echo.leaveChannel('live-wins')
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
