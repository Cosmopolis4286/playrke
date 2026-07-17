import { computed, type ComputedRef } from 'vue'
import type { BingoRoom } from '@js/types/games/bingo/room/bingo-room'
import { fmt } from './useBingoRoomFormatters'

type CurrencyOption = { value: string; label: string }
type PrizePoolTypeOption = { value: string; code: string; label: string }

/**
 * Datos financieros derivados de una sala: modo de inicio, precios por
 * moneda, desglose de métricas por moneda, configuración de rondas y
 * distribución de porcentajes.
 */
export function useBingoRoomFinancials(
  room: ComputedRef<BingoRoom>,
  allowedCurrenciesOptions: ComputedRef<CurrencyOption[]>,
  prizePoolTypeOptions: ComputedRef<PrizePoolTypeOption[]>,
) {
  const startModeLabel = computed(() => {
    const m = room.value.config?.start_mode
    if (m === 'MANUAL') return 'Manual (sin horario fijo)'
    if (m === 'SCHEDULED') return 'Programado'
    return m ?? '-'
  })

  const scheduleInfo = computed(() => {
    if (room.value.config?.start_mode === 'MANUAL') return 'Sin horario — inicio manual'
    return fmt.date(room.value.config?.date, room.value.config?.time)
  })

  const cardPricesList = computed(() => {
    const prices = room.value.card_price_by_currency
    if (!prices || !Object.keys(prices).length) return null
    return Object.entries(prices).map(([currencyId, price]) => {
      const label = allowedCurrenciesOptions.value.find(c => String(c.value) === String(currencyId))?.label ?? currencyId
      return { label, price: fmt.localAmount(price) }
    })
  })

  // metrics_by_currency viene en moneda local de cada currency_id (no USD) —
  // ver RoomMetricsService::updateRoomMetricsWithCommissions().
  const metricsByCurrencyList = computed(() => {
    const metrics = room.value.metrics_by_currency
    if (!metrics || !Object.keys(metrics).length) return null

    // Monedas permitidas de la sala — si solo hay una, sirve de fallback cuando
    // el currencyId no resuelve (datos antiguos: un bug de serialización en
    // metrics_by_currency podía perder la clave real cuando había una sola
    // moneda con ventas; ya corregido en el backend para registros nuevos).
    const allowedIds = (room.value as any).allowed_currencies ?? []
    const singleAllowedId = Array.isArray(allowedIds) && allowedIds.length === 1 ? String(allowedIds[0]) : null

    return Object.entries(metrics).map(([currencyId, m]) => {
      const resolvedId = allowedCurrenciesOptions.value.some(c => String(c.value) === String(currencyId))
        ? currencyId
        : (singleAllowedId ?? currencyId)
      const label = allowedCurrenciesOptions.value.find(c => String(c.value) === String(resolvedId))?.label ?? `Moneda #${currencyId}`
      return {
        currencyId: resolvedId,
        label,
        totalSales: fmt.localAmount(m.total_sales),
        totalCards: fmt.number(m.total_cards),
        prizePool: fmt.localAmount(m.prize_pool),
        totalCommissions: fmt.localAmount(m.rke_commission + m.room_commission + m.seller_commission),
      }
    })
  })

  /** Resuelve el código ISO de una moneda por su ID, para mostrar junto a montos locales. */
  function currencyIso(currencyId?: string | number | null): string {
    if (currencyId == null) return ''
    return allowedCurrenciesOptions.value.find(c => String(c.value) === String(currencyId))?.label ?? ''
  }

  const prizePoolTypeCode = computed(() => {
    const id = room.value.config?.prize_pool_type_id
    return id ? (prizePoolTypeOptions.value as any[]).find(o => String(o.value) === String(id))?.code : null
  })

  const isAccumulative = computed(() => prizePoolTypeCode.value === 'ACCUMULATIVE')

  const roundsConfig = computed(() => room.value.rounds_config ?? [])

  const roundPrizeLabel = (round: NonNullable<BingoRoom['rounds_config']>[number]) => {
    if (round.prizeType === 'percentage') return fmt.pct(round.prizeValue)
    if (round.prizeType === 'prize') return round.prizeName ?? `Premio #${round.prizeId}`
    if (round.prizeType === 'fixed' && round.prizeValueByCurrency) {
      return Object.entries(round.prizeValueByCurrency)
        .map(([cur, val]) => {
          const label = allowedCurrenciesOptions.value.find(c => String(c.value) === String(cur))?.label ?? cur
          return `${label}: ${fmt.currency(val)}`
        }).join(' · ')
    }
    return fmt.currency(round.prizeValue)
  }

  // El %RKE lo cobra la plataforma sobre el bote bruto antes de distribuir.
  // El 100% distribuible es solo: %Sala + %Jackpot + %Seriales + %Rondas.
  const totalPct = computed(() => {
    const commn  = parseFloat(String(room.value.config?.room_commission ?? 0)) || 0
    const jk     = parseFloat(String(room.value.config?.jackpot_pct ?? 0)) || 0
    const ser    = room.value.config?.use_serials ? (parseFloat(String(room.value.config?.serials_pct ?? 0)) || 0) : 0
    const rounds = parseFloat(String(room.value.config?.rounds_pct ?? 0)) || 0
    return Math.round((commn + jk + ser + rounds) * 100) / 100
  })

  return {
    startModeLabel, scheduleInfo, cardPricesList, metricsByCurrencyList,
    currencyIso, prizePoolTypeCode, isAccumulative,
    roundsConfig, roundPrizeLabel, totalPct,
  }
}
