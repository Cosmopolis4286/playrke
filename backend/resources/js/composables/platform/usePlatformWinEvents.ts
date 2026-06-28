import { ref, computed, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import echo from '@/js/plugins/echo'

/**
 * Evento de ganancia genérico — cualquier juego (Bingo, Trivia, futuros
 * motores) o premio (dinero, objeto, servicio), digital o físico.
 */
export interface PlatformWinEvent {
  id: string
  game: string
  game_slug: string
  winner_name: string
  prize_kind: 'money' | 'item' | 'service'
  prize_label: string
  amount: number | null
  currency: string
  currency_symbol: string
  is_usd: boolean
  local_amount: number | null
  decimal_places: number
  timestamp: string
  /** Momento (epoch ms) en que el cliente recibió/cargó este evento, usado para el TTL del ticker. */
  addedAt: number
}

const MAX_EVENTS = 20

/** Tiempo máximo (ms) que un evento permanece visible en el ticker. */
const TTL_MS = 15_000
/** Frecuencia (ms) con la que se revisa si hay eventos vencidos. */
const TICK_MS = 1_000

export function usePlatformWinEvents() {
  const allEvents = ref<PlatformWinEvent[]>([])
  const now = ref(Date.now())
  const channel = echo.channel('platform.win-events')

  const events = computed(() =>
    allEvents.value.filter((e) => now.value - e.addedAt < TTL_MS)
  )

  channel.listen('.NewWin', (payload: Omit<PlatformWinEvent, 'addedAt'>) => {
    const event: PlatformWinEvent = {
      ...payload,
      addedAt: Date.now(),
    }
    allEvents.value = [event, ...allEvents.value].slice(0, MAX_EVENTS)
  })

  onMounted(async () => {
    try {
      const { data } = await axios.get<{ wins: Omit<PlatformWinEvent, 'addedAt'>[] }>('/api/win-events/recent')
      const addedAt = Date.now()
      allEvents.value = (data.wins ?? []).map((e) => ({ ...e, addedAt }))
    } catch (err) {
      console.error('❌ Error cargando eventos de ganancia recientes:', err)
    }
  })

  const tick = window.setInterval(() => {
    now.value = Date.now()
  }, TICK_MS)

  onUnmounted(() => {
    echo.leaveChannel('platform.win-events')
    window.clearInterval(tick)
  })

  /** Texto del premio listo para mostrar: monto formateado o descripción libre. */
  function formatPrize(e: PlatformWinEvent): string {
    if (e.prize_kind !== 'money' || e.amount === null) {
      return e.prize_label
    }
    if (e.is_usd) {
      return `$${e.amount.toFixed(2)} USD`
    }
    const local = e.local_amount && e.local_amount > 0 ? e.local_amount : e.amount
    return `${e.currency_symbol}${local.toFixed(e.decimal_places)}`
  }

  function maskName(name: string): string {
    if (!name) return '***'
    if (name.length <= 3) return `${name.slice(0, 1)}***`
    return `${name.slice(0, 3)}${'*'.repeat(Math.min(name.length - 3, 4))}`
  }

  return { events, formatPrize, maskName }
}
