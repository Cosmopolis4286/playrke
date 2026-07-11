<script setup lang="ts">
import { computed, ref } from 'vue'
import backendClient from '@/js/plugins/axios'
import type { BingoRoom } from '@/js/types/games/bingo/room/bingo-room'

interface Props {
  room: BingoRoom
  roomTypeOptions: { value: string; label: string }[]
  gameModeOptions: { value: string; label: string }[]
  prizePoolTypeOptions: { value: string; code: string; label: string }[]
  allowedCurrenciesOptions: { value: string; label: string }[]
}

const props = defineProps<Props>()

// ── Ganadores / Card viewer ────────────────────────────────────────────────

const PRIZE_STATUS_LABELS: Record<string, string> = {
  waiting:   'Pendiente',
  claimed:   'Pagado',
  cancelled: 'Cancelado',
  suspended: 'Suspendido',
}

type WinnerEntry = NonNullable<BingoRoom['winners']>[number]

const loadingCard    = ref<string | null>(null)
const cardViewerOpen = ref(false)
const cardViewerError = ref(false)
const cardViewerData = ref<{ serialNumber: string; cardMatrix: Record<string, (number | null)[]> } | null>(null)
const cardViewerWinner = ref<WinnerEntry | null>(null)

// Variant column defs (reused from game page — mirror of BingoVariant.php)
const CARD_VIEWER_COLS: Record<string, { label: string; min: number; max: number; freeRow: number | null; bg: string }[]> = {
  BINGO_75: [
    { label: 'B', min:  1, max: 15, freeRow: null, bg: '#1a5fff' },
    { label: 'I', min: 16, max: 30, freeRow: null, bg: '#cc1010' },
    { label: 'N', min: 31, max: 45, freeRow: 2,    bg: '#b08000' },
    { label: 'G', min: 46, max: 60, freeRow: null, bg: '#0a8040' },
    { label: 'O', min: 61, max: 75, freeRow: null, bg: '#8010d0' },
  ],
  BINGO_90: [
    { label: '1', min:  1, max:  9, freeRow: null, bg: '#1a5fff' },
    { label: '2', min: 10, max: 19, freeRow: null, bg: '#cc1010' },
    { label: '3', min: 20, max: 29, freeRow: null, bg: '#b08000' },
    { label: '4', min: 30, max: 39, freeRow: null, bg: '#0a8040' },
    { label: '5', min: 40, max: 49, freeRow: null, bg: '#8010d0' },
    { label: '6', min: 50, max: 59, freeRow: null, bg: '#1a5fff' },
    { label: '7', min: 60, max: 69, freeRow: null, bg: '#cc1010' },
    { label: '8', min: 70, max: 79, freeRow: null, bg: '#b08000' },
    { label: '9', min: 80, max: 90, freeRow: null, bg: '#0a8040' },
  ],
  BINGO_80: [
    { label: '1', min:  1, max: 20, freeRow: null, bg: '#1a5fff' },
    { label: '2', min: 21, max: 40, freeRow: null, bg: '#cc1010' },
    { label: '3', min: 41, max: 60, freeRow: null, bg: '#b08000' },
    { label: '4', min: 61, max: 80, freeRow: null, bg: '#0a8040' },
  ],
  BINGO_30: [
    { label: '1', min:  1, max: 10, freeRow: null, bg: '#1a5fff' },
    { label: '2', min: 11, max: 20, freeRow: null, bg: '#cc1010' },
    { label: '3', min: 21, max: 30, freeRow: null, bg: '#b08000' },
  ],
}
const CARD_VIEWER_ROWS: Record<string, number> = { BINGO_75: 5, BINGO_90: 3, BINGO_80: 4, BINGO_30: 3 }

const cardViewerVariant = computed(() => props.room.config?.game_variant ?? 'BINGO_75')
const cardViewerCols = computed(() => CARD_VIEWER_COLS[cardViewerVariant.value] ?? CARD_VIEWER_COLS.BINGO_75)
const cardViewerRows = computed(() => CARD_VIEWER_ROWS[cardViewerVariant.value] ?? 5)

function isFreeViewerCell(col: { freeRow: number | null }, r: number): boolean {
  return col.freeRow !== null && col.freeRow === r - 1
}

function viewerNum(colLabel: string, r: number): number | null {
  const m = cardViewerData.value?.cardMatrix
  if (!m) return null
  const val = m[colLabel]?.[r - 1]
  return (val === undefined || val === null) ? null : val
}

async function openCardViewer(w: WinnerEntry): Promise<void> {
  loadingCard.value = w.id
  cardViewerOpen.value = true
  cardViewerError.value = false
  cardViewerData.value = null
  cardViewerWinner.value = w

  try {
    // room.id as route key — the controller uses route model binding
    const routeKey = props.room.id
    const { data: json } = await backendClient.get(`/bingo/${routeKey}/card/${w.winning_card_id}`)
    const card = json.card ?? json.data?.card
    if (card) {
      cardViewerData.value = { serialNumber: card.serialNumber, cardMatrix: card.cardMatrix }
    } else {
      cardViewerError.value = true
    }
  } catch {
    cardViewerError.value = true
  } finally {
    loadingCard.value = null
  }
}

const VARIANT_LABELS: Record<string, string> = {
  BINGO_75: 'Bingo 75 bolas (5×5)',
  BINGO_90: 'Bingo 90 bolas (3×9)',
  BINGO_80: 'Bingo 80 bolas (4×4)',
  BINGO_30: 'Speed Bingo 30 bolas (3×3)',
}

const fmt = {
  date(dateStr?: string | null, timeStr?: string | null): string {
    if (!dateStr) return '-'
    const dateTime = timeStr ? `${dateStr}T${timeStr}` : dateStr
    const d = new Date(dateTime)
    if (isNaN(d.getTime())) return '-'
    return d.toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
  },

  currency(value: number | string | null | undefined): string {
    if (value === null || value === undefined || value === '') return '-'
    const n = typeof value === 'string' ? parseFloat(value) : value
    if (isNaN(n)) return '-'
    return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'USD' }).format(n)
  },

  /**
   * Monto en moneda local (no USD) — se usa para campos que ya vienen
   * convertidos a la moneda de cada currency_id (ej. metrics_by_currency,
   * card_price_by_currency, local_amount de premios). Se formatea sin
   * símbolo de moneda para no aparentar ser dólares; el ISO code se
   * muestra aparte (ej. "VES 780,00").
   */
  localAmount(value: number | string | null | undefined): string {
    if (value === null || value === undefined || value === '') return '-'
    const n = typeof value === 'string' ? parseFloat(value) : value
    if (isNaN(n)) return '-'
    return new Intl.NumberFormat('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n)
  },

  number(value: number | string | null | undefined): string {
    if (value === null || value === undefined || value === '') return '-'
    const n = typeof value === 'string' ? parseFloat(value) : value
    if (isNaN(n)) return '-'
    return new Intl.NumberFormat('es-ES').format(n)
  },

  pct(value: number | string | null | undefined): string {
    if (value === null || value === undefined || value === '') return '-'
    const n = typeof value === 'string' ? parseFloat(value) : value
    if (isNaN(n)) return '-'
    return `${n}%`
  }
}

const statusLabel = (status?: string) => ({
  ACTIVE: 'Disponible', IN_PROGRESS: 'En Juego', FINISHED: 'Finalizada',
  REFUND: 'Reembolso', OFFLINE: 'Fuera de Línea', INACTIVE: 'Inactiva',
  DELETED: 'Eliminada', CANCELLED: 'Cancelada'
}[status ?? ''] ?? status ?? '-')

const statusClass = (status?: string) => ({
  ACTIVE: 'badge-success', IN_PROGRESS: 'badge-primary', FINISHED: 'badge-secondary',
  REFUND: 'badge-warning', OFFLINE: 'badge-dark', INACTIVE: 'badge-light',
  DELETED: 'badge-danger', CANCELLED: 'badge-danger'
}[status ?? ''] ?? 'badge-light')

const resolveOption = (opts: { value: string; label: string }[], id?: string | number | null) =>
  id ? opts.find(o => String(o.value) === String(id))?.label ?? String(id) : '-'

const startModeLabel = computed(() => {
  const m = props.room.config?.start_mode
  if (m === 'MANUAL') return 'Manual (sin horario fijo)'
  if (m === 'SCHEDULED') return 'Programado'
  return m ?? '-'
})

const scheduleInfo = computed(() => {
  if (props.room.config?.start_mode === 'MANUAL') return 'Sin horario — inicio manual'
  return fmt.date(props.room.config?.date, props.room.config?.time)
})

const cardPricesList = computed(() => {
  const prices = props.room.card_price_by_currency
  if (!prices || !Object.keys(prices).length) return null
  return Object.entries(prices).map(([currencyId, price]) => {
    const label = props.allowedCurrenciesOptions.find(c => String(c.value) === String(currencyId))?.label ?? currencyId
    return { label, price: fmt.localAmount(price) }
  })
})

// metrics_by_currency viene en moneda local de cada currency_id (no USD) —
// ver RoomMetricsService::updateRoomMetricsWithCommissions().
const metricsByCurrencyList = computed(() => {
  const metrics = props.room.metrics_by_currency
  if (!metrics || !Object.keys(metrics).length) return null

  // Monedas permitidas de la sala — si solo hay una, sirve de fallback cuando
  // el currencyId no resuelve (datos antiguos: un bug de serialización en
  // metrics_by_currency podía perder la clave real cuando había una sola
  // moneda con ventas; ya corregido en el backend para registros nuevos).
  const allowedIds = (props.room as any).allowed_currencies ?? []
  const singleAllowedId = Array.isArray(allowedIds) && allowedIds.length === 1 ? String(allowedIds[0]) : null

  return Object.entries(metrics).map(([currencyId, m]) => {
    const resolvedId = props.allowedCurrenciesOptions.some(c => String(c.value) === String(currencyId))
      ? currencyId
      : (singleAllowedId ?? currencyId)
    const label = props.allowedCurrenciesOptions.find(c => String(c.value) === String(resolvedId))?.label ?? `Moneda #${currencyId}`
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
  return props.allowedCurrenciesOptions.find(c => String(c.value) === String(currencyId))?.label ?? ''
}

/** Username del ganador, o "Sin usuario" si el usuario fue eliminado/no resuelve. */
function winnerUsername(w: WinnerEntry): string {
  return w.username ? `@${w.username}` : 'Sin usuario'
}

/**
 * true si el premio se pagó sin conversión real: cuando no hay tasa de cambio
 * activa al momento del sorteo, BingoDrawService::convertToLocal() devuelve
 * el monto USD tal cual como si fuera moneda local (usd_amount === local_amount).
 * En ese caso el "≈ $X USD" sería engañoso (no es una conversión, es el mismo
 * número sin convertir) — se muestra una advertencia en su lugar.
 */
function isUnconvertedPrize(w: WinnerEntry): boolean {
  if (currencyIso(w.currency_id) === 'USD') return false
  if (!w.usd_amount || w.usd_amount <= 0) return false
  return Math.abs(w.usd_amount - w.local_amount) < 0.005
}

const prizePoolTypeCode = computed(() => {
  const id = props.room.config?.prize_pool_type_id
  return id ? (props.prizePoolTypeOptions as any[]).find(o => String(o.value) === String(id))?.code : null
})

const isAccumulative = computed(() => prizePoolTypeCode.value === 'ACCUMULATIVE')

const roundsConfig = computed(() => props.room.rounds_config ?? [])

const roundPrizeLabel = (round: typeof roundsConfig.value[0]) => {
  if (round.prizeType === 'percentage') return fmt.pct(round.prizeValue)
  if (round.prizeType === 'prize') return round.prizeName ?? `Premio #${round.prizeId}`
  if (round.prizeType === 'fixed' && round.prizeValueByCurrency) {
    return Object.entries(round.prizeValueByCurrency)
      .map(([cur, val]) => {
        const label = props.allowedCurrenciesOptions.find(c => String(c.value) === String(cur))?.label ?? cur
        return `${label}: ${fmt.currency(val)}`
      }).join(' · ')
  }
  return fmt.currency(round.prizeValue)
}

// El %RKE lo cobra la plataforma sobre el bote bruto antes de distribuir.
// El 100% distribuible es solo: %Sala + %Jackpot + %Seriales + %Rondas.
const totalPct = computed(() => {
  const commn  = parseFloat(String(props.room.config?.room_commission ?? 0)) || 0
  const jk     = parseFloat(String(props.room.config?.jackpot_pct ?? 0)) || 0
  const ser    = props.room.config?.use_serials ? (parseFloat(String(props.room.config?.serials_pct ?? 0)) || 0) : 0
  const rounds = parseFloat(String(props.room.config?.rounds_pct ?? 0)) || 0
  return Math.round((commn + jk + ser + rounds) * 100) / 100
})
</script>

<template>
  <div class="room-details">

    <!-- ── Encabezado de estado ───────────────────────── -->
    <div class="details-header mb-3">
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="badge badge-lg" :class="statusClass(room.status)">{{ statusLabel(room.status) }}</span>
        <span class="text-muted small">ID: <strong>{{ room.id }}</strong></span>
        <span v-if="room.config?.in_live === 'Y'" class="badge badge-success">En Vivo</span>
        <span v-if="room.paid_prizes === 'Y'" class="badge badge-info">Premios Pagados</span>
        <span v-if="room.is_closed" class="badge badge-danger">Sala Cerrada</span>
      </div>
    </div>

    <!-- ── Configuración General ─────────────────────── -->
    <div class="details-section">
      <h6 class="section-title">Configuración General</h6>
      <div class="details-grid">
        <div class="detail-item">
          <span class="detail-label">Tipo de Sala</span>
          <span class="detail-value">{{ resolveOption(roomTypeOptions, room.config?.room_type_id) }}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Modo de Juego</span>
          <span class="detail-value">{{ resolveOption(gameModeOptions, room.config?.game_mode_id) }}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Variante</span>
          <span class="detail-value">{{ VARIANT_LABELS[room.config?.game_variant ?? 'BINGO_75'] ?? room.config?.game_variant ?? 'Bingo 75' }}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Tipo de Bote</span>
          <span class="detail-value">{{ resolveOption(prizePoolTypeOptions, room.config?.prize_pool_type_id) }}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Modo de Inicio</span>
          <span class="detail-value">{{ startModeLabel }}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Programación</span>
          <span class="detail-value">{{ scheduleInfo }}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Tiempo por Ficha</span>
          <span class="detail-value">{{ fmt.number(room.config?.time_chip) }} seg</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Rondas</span>
          <span class="detail-value">{{ fmt.number(room.config?.rounds) }}</span>
        </div>
        <div v-if="room.config?.min_cards_to_start" class="detail-item highlight-yellow">
          <span class="detail-label">Min. Cartones para Iniciar</span>
          <span class="detail-value font-weight-bold">{{ fmt.number(room.config?.min_cards_to_start) }}</span>
        </div>
      </div>
    </div>

    <!-- ── Participación ─────────────────────────────── -->
    <div class="details-section">
      <h6 class="section-title">Participación</h6>
      <div class="details-grid">
        <div class="detail-item highlight">
          <span class="detail-label">Jugadores</span>
          <span class="detail-value font-weight-bold text-primary">{{ fmt.number(room.total_players) }}</span>
        </div>
        <div class="detail-item highlight">
          <span class="detail-label">Cartones Vendidos</span>
          <span class="detail-value font-weight-bold text-info">{{ fmt.number(room.total_cards) }}</span>
        </div>
        <div v-if="room.config?.min_cards_to_start" class="detail-item">
          <span class="detail-label">Mínimo Requerido</span>
          <span class="detail-value">
            {{ fmt.number(room.config?.min_cards_to_start) }}
            <span class="ml-1" :class="(room.total_cards ?? 0) >= (room.config?.min_cards_to_start ?? 0) ? 'text-success' : 'text-warning'">
              <i :class="(room.total_cards ?? 0) >= (room.config?.min_cards_to_start ?? 0) ? 'fas fa-check-circle' : 'fas fa-clock'"></i>
              {{ (room.total_cards ?? 0) >= (room.config?.min_cards_to_start ?? 0) ? 'Alcanzado' : 'Pendiente' }}
            </span>
          </span>
        </div>
      </div>
    </div>

    <!-- ── Financiero ─────────────────────────────────── -->
    <div class="details-section">
      <h6 class="section-title">Financiero</h6>
      <p class="section-hint">Montos normalizados en USD. Para valores en la moneda local de cada venta, ver "Desglose por Moneda" más abajo.</p>
      <div class="details-grid">
        <div class="detail-item highlight">
          <span class="detail-label">Ventas Totales</span>
          <span class="detail-value font-weight-bold text-success">{{ fmt.currency(room.total_sales) }}</span>
        </div>
        <div class="detail-item highlight">
          <span class="detail-label">Bote Acumulado</span>
          <span class="detail-value font-weight-bold text-warning">{{ fmt.currency(room.prize_pool) }}</span>
        </div>
        <div v-if="!isAccumulative && room.config?.amount_added_prize_pool" class="detail-item">
          <span class="detail-label">Valor Fijo/Extra al Bote</span>
          <span class="detail-value">{{ fmt.currency(room.config?.amount_added_prize_pool) }}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Total Comisiones</span>
          <span class="detail-value">{{ fmt.currency(room.total_commissions) }}</span>
        </div>
        <div v-if="room.config?.use_base_usd" class="detail-item">
          <span class="detail-label">Precio Cartón (USD base)</span>
          <span class="detail-value">
            {{ fmt.currency(room.config?.card_price_usd) }}
            <span v-if="room.config?.usd_exchange_round_step" class="text-muted small ml-1">
              (redondeo {{ room.config?.usd_exchange_round_step }})
            </span>
          </span>
        </div>
        <div v-if="cardPricesList" class="detail-item" style="grid-column: 1 / -1">
          <span class="detail-label">Precios por Moneda</span>
          <div class="d-flex flex-wrap gap-2 mt-1">
            <span v-for="cp in cardPricesList" :key="cp.label" class="badge badge-light border">
              {{ cp.label }}: {{ cp.price }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Desglose por Moneda ───────────────────────── -->
    <div v-if="metricsByCurrencyList" class="details-section">
      <h6 class="section-title">Desglose por Moneda</h6>
      <div class="currency-breakdown">
        <div v-for="m in metricsByCurrencyList" :key="m.currencyId" class="currency-breakdown-item">
          <div class="currency-breakdown-header">{{ m.label }}</div>
          <div class="currency-breakdown-grid">
            <div class="detail-item">
              <span class="detail-label">Ventas</span>
              <span class="detail-value">{{ m.totalSales }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Cartones</span>
              <span class="detail-value">{{ m.totalCards }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Bote</span>
              <span class="detail-value">{{ m.prizePool }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Comisiones</span>
              <span class="detail-value">{{ m.totalCommissions }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Distribución de Porcentajes ───────────────── -->
    <div class="details-section">
      <h6 class="section-title">Distribución de Porcentajes</h6>
      <div class="details-grid">
        <div class="detail-item">
          <span class="detail-label">%Sala (comisión)</span>
          <span class="detail-value">{{ fmt.pct(room.config?.room_commission) }}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">%RKE</span>
          <span class="detail-value">{{ fmt.pct(room.config?.rke_commission) }}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">%Jackpot</span>
          <span class="detail-value">{{ room.config?.jackpot_pct ? fmt.pct(room.config?.jackpot_pct) : 'No aplica' }}</span>
        </div>
        <div v-if="(room.config?.rounds ?? 1) > 1" class="detail-item">
          <span class="detail-label">%Rondas</span>
          <span class="detail-value">{{ fmt.pct(room.config?.rounds_pct) }}</span>
        </div>
        <div v-if="room.config?.use_serials" class="detail-item">
          <span class="detail-label">%Seriales</span>
          <span class="detail-value">{{ fmt.pct(room.config?.serials_pct) }}</span>
        </div>
        <div class="detail-item" :class="totalPct > 100 ? 'highlight-danger' : 'highlight'">
          <span class="detail-label">Total distribuido <small class="text-muted">(sin RKE)</small></span>
          <span class="detail-value font-weight-bold" :class="totalPct > 100 ? 'text-danger' : 'text-success'">{{ totalPct }}%</span>
        </div>
      </div>
    </div>

    <!-- ── Rondas ─────────────────────────────────────── -->
    <div v-if="roundsConfig.length" class="details-section">
      <h6 class="section-title">Configuración de Rondas</h6>
      <div class="rounds-list">
        <div v-for="(round, i) in roundsConfig" :key="i" class="round-item">
          <span class="round-number">R{{ i + 1 }}</span>
          <div class="round-info">
            <span class="detail-label">Premio: <strong>{{ roundPrizeLabel(round) }}</strong></span>
            <span class="detail-label ml-2">Tipo: {{ round.prizeType }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Seriales ───────────────────────────────────── -->
    <div v-if="room.config?.use_serials" class="details-section">
      <h6 class="section-title">Seriales</h6>
      <div class="details-grid">
        <div class="detail-item">
          <span class="detail-label">% Seriales</span>
          <span class="detail-value">{{ fmt.pct(room.config?.serials_pct) }}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Cantidad de Seriales</span>
          <span class="detail-value">{{ fmt.number(room.config?.num_serials) }}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Premio Serial</span>
          <span class="detail-value">{{ fmt.currency(room.serial_prize) }}</span>
        </div>
      </div>
    </div>

    <!-- ── Premio Full House ──────────────────────────── -->
    <div v-if="room.full_house_prize" class="details-section">
      <h6 class="section-title">Premio Full House</h6>
      <div class="details-grid">
        <div class="detail-item highlight">
          <span class="detail-label">Monto Full House</span>
          <span class="detail-value font-weight-bold text-success">{{ fmt.currency(room.full_house_prize) }}</span>
        </div>
      </div>
    </div>

    <!-- ── Ganadores ─────────────────────────────────── -->
    <div v-if="room.winners && room.winners.length" class="details-section">
      <h6 class="section-title">Ganadores ({{ room.winners.length }})</h6>
      <div class="winners-list">
        <div v-for="w in room.winners" :key="w.id" class="winner-item">
          <div class="winner-main">
            <span class="winner-badge"><i class="fas fa-trophy" /></span>
            <div class="winner-info">
              <span class="winner-name">
                {{ winnerUsername(w) }}
                <span class="winner-id">#{{ w.user_id }}</span>
              </span>
              <span class="winner-meta">
                <span v-if="w.round_number">Ronda {{ w.round_number }}</span>
                <span v-if="w.winning_pattern"> · {{ w.winning_pattern }}</span>
              </span>
            </div>
            <div class="winner-prize">
              <span class="winner-amount">
                {{ currencyIso(w.currency_id) }} {{ fmt.localAmount(w.local_amount) }}
              </span>
              <span v-if="isUnconvertedPrize(w)" class="winner-amount-warning" title="No había tasa de cambio activa al momento del sorteo: el monto no se convirtió de USD a la moneda local">
                <i class="fas fa-exclamation-triangle"></i> Sin tasa activa, no convertido
              </span>
              <span v-else class="winner-amount-usd">≈ ${{ w.usd_amount.toFixed(2) }} USD</span>
              <span class="winner-status" :class="`winner-status--${w.prize_status}`">{{ PRIZE_STATUS_LABELS[w.prize_status] ?? w.prize_status }}</span>
            </div>
            <button
              class="winner-card-btn"
              :class="{ 'winner-card-btn--loading': loadingCard === w.id }"
              title="Ver cartón ganador"
              @click="openCardViewer(w)"
            >
              <i v-if="loadingCard === w.id" class="fas fa-spinner fa-spin" />
              <i v-else class="fas fa-id-card" />
            </button>
          </div>
          <div v-if="w.serial_number" class="winner-serial">{{ w.serial_number }}</div>
        </div>
      </div>
    </div>

    <!-- ── Modal Cartón Ganador ──────────────────────── -->
    <Teleport to="body">
      <Transition name="wd-modal">
        <div v-if="cardViewerOpen" class="wd-overlay" @click.self="cardViewerOpen = false">
          <div class="wd-modal">
            <div class="wd-modal__header">
              <span class="wd-modal__title"><i class="fas fa-id-card" /> Cartón Ganador</span>
              <button class="wd-modal__close" @click="cardViewerOpen = false" aria-label="Cerrar"><i class="fas fa-times" /></button>
            </div>

            <!-- Resumen del ganador: visible en todos los estados (loading/error/ok) -->
            <div v-if="cardViewerWinner" class="wd-winner-summary">
              <div class="wd-winner-summary__name">
                {{ winnerUsername(cardViewerWinner) }}
                <span class="winner-id">#{{ cardViewerWinner.user_id }}</span>
              </div>
              <div class="wd-winner-summary__meta">
                <span v-if="cardViewerWinner.round_number" class="wd-chip"><i class="fas fa-flag-checkered"></i> Ronda {{ cardViewerWinner.round_number }}</span>
                <span v-if="cardViewerWinner.winning_pattern" class="wd-chip"><i class="fas fa-shapes"></i> {{ cardViewerWinner.winning_pattern }}</span>
                <span class="wd-chip wd-chip--prize">
                  <i class="fas fa-coins"></i>
                  {{ currencyIso(cardViewerWinner.currency_id) }} {{ fmt.localAmount(cardViewerWinner.local_amount) }}
                </span>
              </div>
            </div>

            <div v-if="cardViewerData" class="wd-modal__body">
              <div class="wd-card-serial">
                <i class="fas fa-barcode"></i>
                <span class="wd-card-serial__label">Serial</span>
                <span class="wd-card-serial__value">{{ cardViewerData.serialNumber }}</span>
              </div>
              <!-- card grid -->
              <div class="wd-card">
                <div class="wd-card__head" :style="{ gridTemplateColumns: `repeat(${cardViewerCols.length}, 1fr)` }">
                  <span v-for="col in cardViewerCols" :key="col.label" class="wd-col-hdr" :style="{ background: col.bg }">{{ col.label }}</span>
                </div>
                <div class="wd-card__body" :style="{ gridTemplateColumns: `repeat(${cardViewerCols.length}, 1fr)` }">
                  <template v-for="col in cardViewerCols" :key="col.label">
                    <div
                      v-for="r in cardViewerRows" :key="`${col.label}${r}`"
                      class="wd-cell"
                      :class="{
                        'wd-cell--free': isFreeViewerCell(col, r),
                        'wd-cell--num': !isFreeViewerCell(col, r) && viewerNum(col.label, r) !== null,
                        'wd-cell--empty': viewerNum(col.label, r) === null && !isFreeViewerCell(col, r),
                      }"
                    >
                      <span v-if="isFreeViewerCell(col, r)" class="wd-free">★</span>
                      <span v-else-if="viewerNum(col.label, r) !== null">{{ viewerNum(col.label, r) }}</span>
                    </div>
                  </template>
                </div>
              </div>
            </div>
            <div v-else-if="cardViewerError" class="wd-modal__body wd-modal__body--state">
              <i class="fas fa-exclamation-circle wd-state-icon wd-state-icon--error"></i>
              <p class="wd-state-text">No se pudo cargar el cartón ganador.</p>
              <button class="wd-retry-btn" @click="cardViewerWinner && openCardViewer(cardViewerWinner)">
                <i class="fas fa-rotate-right"></i> Reintentar
              </button>
            </div>
            <div v-else class="wd-modal__body wd-modal__body--state">
              <i class="fas fa-spinner fa-spin wd-state-icon"></i>
              <p class="wd-state-text">Cargando cartón...</p>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

  </div>
</template>

<style scoped>
.room-details {
  max-height: 65vh;
  overflow-y: auto;
  padding-right: 0.5rem;
}

.details-header {
  padding: 0.5rem 0.5rem 0.75rem;
  border-bottom: 1px solid #e9ecef;
}

.details-section {
  margin-bottom: 1.25rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #e9ecef;
}

.details-section:last-child {
  border-bottom: none;
  margin-bottom: 0;
}

.section-title {
  color: #495057;
  font-weight: 600;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  margin-bottom: 0.65rem;
  padding-bottom: 0.4rem;
  border-bottom: 2px solid #007bff;
  display: inline-block;
}

.section-hint {
  font-size: 0.72rem;
  color: #6c757d;
  margin: -0.3rem 0 0.6rem;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
  gap: 0.6rem;
}

/* Desglose por Moneda */
.currency-breakdown {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.currency-breakdown-item {
  border: 1px solid #e9ecef;
  border-radius: 0.375rem;
  padding: 0.5rem 0.6rem;
}

.currency-breakdown-header {
  font-size: 0.75rem;
  font-weight: 700;
  color: #495057;
  margin-bottom: 0.4rem;
}

.currency-breakdown-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 0.5rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  padding: 0.45rem 0.6rem;
  background: #f8f9fa;
  border-radius: 0.375rem;
  border-left: 3px solid transparent;
}

.detail-item.highlight {
  background: #e8f4fd;
  border-left-color: #007bff;
}

.detail-item.highlight-yellow {
  background: #fff8e1;
  border-left-color: #ffc107;
}

.detail-item.highlight-danger {
  background: #fdecea;
  border-left-color: #dc3545;
}

.detail-label {
  font-size: 0.7rem;
  color: #6c757d;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  margin-bottom: 0.2rem;
}

.detail-value {
  font-size: 0.875rem;
  color: #212529;
}

/* Rondas */
.rounds-list {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.round-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.45rem 0.6rem;
  background: #f8f9fa;
  border-radius: 0.375rem;
  border-left: 3px solid #6f42c1;
}

.round-number {
  font-size: 0.75rem;
  font-weight: 700;
  color: #6f42c1;
  min-width: 1.75rem;
}

.round-info {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  font-size: 0.8rem;
}

/* Badges */
.badge {
  padding: 0.3rem 0.65rem;
  font-size: 0.72rem;
  font-weight: 600;
  border-radius: 0.25rem;
}

.badge-lg {
  font-size: 0.8rem;
  padding: 0.4rem 0.85rem;
}

.badge-success { background-color: #28a745; color: white; }
.badge-primary { background-color: #007bff; color: white; }
.badge-info    { background-color: #17a2b8; color: white; }
.badge-warning { background-color: #ffc107; color: #212529; }
.badge-danger  { background-color: #dc3545; color: white; }
.badge-secondary { background-color: #6c757d; color: white; }
.badge-dark    { background-color: #343a40; color: white; }
.badge-light   { background-color: #f8f9fa; color: #212529; border: 1px solid #dee2e6; }

.text-primary { color: #007bff !important; }
.text-info    { color: #17a2b8 !important; }
.text-success { color: #28a745 !important; }
.text-warning { color: #ffc107 !important; }
.text-danger  { color: #dc3545 !important; }
.text-muted   { color: #6c757d !important; }

.font-weight-bold { font-weight: 600 !important; }

.gap-2 { gap: 0.5rem; }
.ml-1  { margin-left: 0.25rem; }
.ml-2  { margin-left: 0.5rem; }

.room-details::-webkit-scrollbar { width: 5px; }
.room-details::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 3px; }
.room-details::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 3px; }
.room-details::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }

/* ── Ganadores ── */
.winners-list { display: flex; flex-direction: column; gap: 0.4rem; }

.winner-item {
  background: #f8f9fa;
  border-radius: 0.375rem;
  border-left: 3px solid #ffc107;
  padding: 0.45rem 0.6rem;
}

.winner-main { display: flex; align-items: center; gap: 0.6rem; }

.winner-badge {
  font-size: 0.85rem; color: #ffc107; flex-shrink: 0;
  width: 1.5rem; text-align: center;
}

.winner-info { flex: 1; min-width: 0; }
.winner-name { display: block; font-size: 0.82rem; font-weight: 600; color: #212529; }
.winner-id { font-size: 0.68rem; font-weight: 400; color: #adb5bd; margin-left: 4px; }
.winner-meta { font-size: 0.7rem; color: #6c757d; }

.winner-prize { text-align: right; flex-shrink: 0; }
.winner-amount { display: block; font-size: 0.85rem; font-weight: 700; color: #28a745; white-space: nowrap; }
.winner-amount-usd { display: block; font-size: 0.68rem; color: #6c757d; }
.winner-amount-warning {
  display: block; font-size: 0.65rem; font-weight: 600; color: #b45309;
  white-space: nowrap;
}
.winner-status {
  font-size: 0.65rem; font-weight: 600; text-transform: uppercase;
  padding: 1px 5px; border-radius: 3px;
}
.winner-status--waiting   { background: #fff3cd; color: #856404; }
.winner-status--claimed   { background: #d1e7dd; color: #0f5132; }
.winner-status--cancelled { background: #f8d7da; color: #842029; }
.winner-status--suspended { background: #e2e3e5; color: #41464b; }

.winner-card-btn {
  flex-shrink: 0; width: 28px; height: 28px; border-radius: 6px;
  border: 1px solid #dee2e6; background: #fff;
  color: #6c757d; font-size: 0.8rem; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all 0.14s;
}
.winner-card-btn:hover { border-color: #007bff; color: #007bff; background: #e7f1ff; }
.winner-card-btn--loading { opacity: 0.6; pointer-events: none; }

.winner-serial { font-size: 0.65rem; color: #adb5bd; margin-top: 2px; font-family: monospace; }

/* ── Card viewer modal ── */
.wd-overlay {
  position: fixed; inset: 0; z-index: 10000;
  background: rgba(0,0,0,0.65); backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center; padding: 16px;
}
.wd-modal {
  width: 100%; max-width: 380px;
  background: #fff; border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.35);
  overflow: hidden;
}
.wd-modal__header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 16px;
  background: #212529; color: #fff;
}
.wd-modal__title { font-size: 0.85rem; font-weight: 700; display: flex; align-items: center; gap: 6px; }
.wd-modal__close {
  width: 24px; height: 24px; border-radius: 50%; border: 1px solid rgba(255,255,255,0.2);
  background: transparent; color: #adb5bd; font-size: 0.75rem; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
}
.wd-modal__close:hover { background: rgba(255,255,255,0.1); color: #fff; }

/* Resumen del ganador (visible en loading/error/ok) */
.wd-winner-summary {
  padding: 12px 16px 10px;
  border-bottom: 1px solid #f0f2f5;
}
.wd-winner-summary__name { font-size: 0.88rem; font-weight: 700; color: #212529; margin-bottom: 6px; }
.wd-winner-summary__meta { display: flex; flex-wrap: wrap; gap: 6px; }
.wd-chip {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 0.68rem; font-weight: 600; color: #495057;
  background: #f1f3f5; border-radius: 999px; padding: 3px 9px;
}
.wd-chip i { font-size: 0.62rem; }
.wd-chip--prize { background: #eafbf1; color: #0f5132; }

.wd-modal__body { padding: 14px; }
.wd-modal__body--state {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 10px; min-height: 140px; color: #6c757d; text-align: center;
}
.wd-state-icon { font-size: 1.8rem; color: #adb5bd; }
.wd-state-icon--error { color: #dc3545; }
.wd-state-text { font-size: 0.8rem; margin: 0; }
.wd-retry-btn {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: 0.75rem; font-weight: 600; color: #007bff;
  background: #e7f1ff; border: none; border-radius: 6px;
  padding: 6px 12px; cursor: pointer; transition: background 0.14s;
}
.wd-retry-btn:hover { background: #d5e8ff; }

.wd-card-serial {
  display: flex; align-items: center; gap: 7px;
  background: #212529; border-radius: 6px;
  padding: 7px 10px; margin-bottom: 12px;
}
.wd-card-serial i { color: #ffc107; font-size: 0.8rem; }
.wd-card-serial__label {
  font-size: 0.62rem; font-weight: 600; text-transform: uppercase;
  letter-spacing: 0.5px; color: #adb5bd;
}
.wd-card-serial__value {
  font-family: monospace; font-size: 0.82rem; font-weight: 700;
  color: #fff; letter-spacing: 0.5px; margin-left: auto;
}

.wd-card { border-radius: 8px; overflow: hidden; border: 1px solid #dee2e6; }
.wd-card__head { display: grid; }
.wd-col-hdr {
  padding: 7px 4px; text-align: center;
  font-family: 'Anton','Impact',sans-serif; font-size: 14px; font-weight: 900;
  color: #fff; letter-spacing: 0.5px;
  text-shadow: 0 1px 3px rgba(0,0,0,0.3);
}
.wd-card__body { display: grid; background: #fff; }
.wd-cell {
  display: flex; align-items: center; justify-content: center;
  aspect-ratio: 1;
  border: 0.5px solid #e9ecef;
  font-size: 12px; font-weight: 700; color: #212529;
}
.wd-cell--free { background: linear-gradient(135deg,rgba(42,245,152,0.18),rgba(0,158,253,0.12)); }
.wd-cell--empty { background: #f8f9fa; }
.wd-free {
  font-size: 16px;
  background: linear-gradient(135deg,#2af598,#009efd);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}

/* Modal transition */
.wd-modal-enter-active { transition: opacity 0.18s ease, transform 0.18s ease; }
.wd-modal-enter-from  { opacity: 0; transform: scale(0.95); }
.wd-modal-leave-active { transition: opacity 0.14s ease; }
.wd-modal-leave-to    { opacity: 0; }
</style>
