<script setup lang="ts">
import { computed } from 'vue'
import type { BingoRoom } from '@/js/types/games/bingo/room/bingo-room'

interface Props {
  room: BingoRoom
  roomTypeOptions: { value: string; label: string }[]
  gameModeOptions: { value: string; label: string }[]
  prizePoolTypeOptions: { value: string; code: string; label: string }[]
  allowedCurrenciesOptions: { value: string; label: string }[]
}

const props = defineProps<Props>()

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
    return { label, price: fmt.currency(price) }
  })
})

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

const totalPct = computed(() => {
  const commn = parseFloat(String(props.room.config?.room_commission ?? 0)) || 0
  const rke = parseFloat(String(props.room.config?.rke_commission ?? 0)) || 0
  const jk = parseFloat(String(props.room.config?.jackpot_pct ?? 0)) || 0
  const ser = props.room.config?.use_serials ? (parseFloat(String(props.room.config?.serials_pct ?? 0)) || 0) : 0
  const rounds = parseFloat(String(props.room.config?.rounds_pct ?? 0)) || 0
  return Math.round((commn + rke + jk + ser + rounds) * 100) / 100
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
          <span class="detail-label">Total distribuido</span>
          <span class="detail-value font-weight-bold" :class="totalPct > 100 ? 'text-danger' : ''">{{ totalPct }}%</span>
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

.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
  gap: 0.6rem;
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
</style>
