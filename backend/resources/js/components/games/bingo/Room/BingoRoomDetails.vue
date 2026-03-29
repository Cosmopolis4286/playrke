<script setup lang="ts">
/**
 * BingoRoomDetails.vue
 * Componente para mostrar los detalles completos de una sala de bingo
 */
import { computed } from 'vue'
import type { BingoRoom } from '@/js/types/games/bingo/room/bingo-room'

interface Props {
  room: BingoRoom & {
    config?: {
      room_type_id?: string | number | null
      date?: string | null
      time?: string | null
      game_mode_id?: string | number | null
      prize_pool_type_id?: string | number | null
      room_commission?: number | string | null
      rke_commission?: number | string | null
      use_base_usd?: boolean
      card_price_usd?: number | string | null
      jackpot_pct?: number | string | null
      time_chip?: number | string | null
      rounds?: number | string | null
      use_serials?: boolean
      in_live?: string | null
      unix_date?: number | null
    }
    card_price_by_currency?: Record<string, number>
  }
  roomTypeOptions: { value: string; label: string }[]
  gameModeOptions: { value: string; label: string }[]
  prizePoolTypeOptions: { value: string; code: string; label: string }[]
  allowedCurrenciesOptions: { value: string; label: string }[]
}

const props = defineProps<Props>()

const formatDateTime = (dateStr?: string | null, timeStr?: string | null): string => {
  if (!dateStr) return '-'
  const dateTime = timeStr ? `${dateStr}T${timeStr}` : dateStr
  const date = new Date(dateTime)
  if (isNaN(date.getTime())) return '-'
  return date.toLocaleString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatCurrency = (value: number | string | null | undefined): string => {
  if (value === null || value === undefined || value === '') return '-'
  const num = typeof value === 'string' ? parseFloat(value) : value
  if (isNaN(num)) return '-'
  return new Intl.NumberFormat('es-ES', {
    style: 'currency',
    currency: 'USD'
  }).format(num)
}

const formatNumber = (value: number | string | null | undefined): string => {
  if (value === null || value === undefined || value === '') return '-'
  const num = typeof value === 'string' ? parseFloat(value) : value
  if (isNaN(num)) return '-'
  return new Intl.NumberFormat('es-ES').format(num)
}

const getStatusLabel = (status?: string): string => {
  const statusMap: Record<string, string> = {
    'ACTIVE': 'Disponible',
    'IN_PROGRESS': 'En Juego',
    'FINISHED': 'Finalizada',
    'REFUND': 'Reembolso',
    'OFFLINE': 'Fuera de Línea',
    'INACTIVE': 'Inactiva',
    'DELETED': 'Eliminada',
    'CANCELLED': 'Cancelada'
  }
  return statusMap[status ?? ''] || status || '-'
}

const getStatusBadgeClass = (status?: string): string => {
  const classMap: Record<string, string> = {
    'ACTIVE': 'badge-success',
    'IN_PROGRESS': 'badge-primary',
    'FINISHED': 'badge-secondary',
    'REFUND': 'badge-warning',
    'OFFLINE': 'badge-dark',
    'INACTIVE': 'badge-light',
    'DELETED': 'badge-danger',
    'CANCELLED': 'badge-danger'
  }
  return classMap[status ?? ''] || 'badge-light'
}

const getRoomTypeLabel = (roomTypeId?: string | number | null): string => {
  if (!roomTypeId) return '-'
  const option = props.roomTypeOptions.find(o => String(o.value) === String(roomTypeId))
  return option?.label || String(roomTypeId)
}

const getGameModeLabel = (gameModeId?: string | number | null): string => {
  if (!gameModeId) return '-'
  const option = props.gameModeOptions.find(o => String(o.value) === String(gameModeId))
  return option?.label || String(gameModeId)
}

const getPrizePoolTypeLabel = (prizePoolTypeId?: string | number | null): string => {
  if (!prizePoolTypeId) return '-'
  const option = props.prizePoolTypeOptions.find(o => String(o.value) === String(prizePoolTypeId))
  return option?.label || String(prizePoolTypeId)
}

const getCardPricesList = (): string => {
  const prices = props.room.card_price_by_currency
  if (!prices || Object.keys(prices).length === 0) return '-'
  return Object.entries(prices)
    .map(([currencyId, price]) => {
      const currency = props.allowedCurrenciesOptions.find(c => String(c.value) === String(currencyId))
      return `${currency?.label || currencyId}: ${formatCurrency(price)}`
    })
    .join(', ')
}
</script>

<template>
  <div class="room-details">
    <!-- Información General -->
    <div class="details-section">
      <h6 class="section-title">Información General</h6>
      <div class="details-grid">
        <div class="detail-item">
          <span class="detail-label">ID de Sala:</span>
          <span class="detail-value font-weight-bold">{{ room.id || '-' }}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Estado:</span>
          <span class="detail-value">
            <span class="badge" :class="getStatusBadgeClass(room.status)">
              {{ getStatusLabel(room.status) }}
            </span>
          </span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Tipo de Sala:</span>
          <span class="detail-value">{{ getRoomTypeLabel(room.config?.room_type_id) }}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Modo de Juego:</span>
          <span class="detail-value">{{ getGameModeLabel(room.config?.game_mode_id) }}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Tipo de Bote:</span>
          <span class="detail-value">{{ getPrizePoolTypeLabel(room.config?.prize_pool_type_id) }}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Fecha y Hora:</span>
          <span class="detail-value">{{ formatDateTime(room.config?.date, room.config?.time) }}</span>
        </div>
      </div>
    </div>

    <!-- Estadísticas de Jugadores -->
    <div class="details-section">
      <h6 class="section-title">Estadísticas de Jugadores</h6>
      <div class="details-grid">
        <div class="detail-item highlight">
          <span class="detail-label">Total de Jugadores:</span>
          <span class="detail-value font-weight-bold text-primary">{{ formatNumber(room.total_players) }}</span>
        </div>
        <div class="detail-item highlight">
          <span class="detail-label">Total de Cartones:</span>
          <span class="detail-value font-weight-bold text-info">{{ formatNumber(room.total_cards) }}</span>
        </div>
      </div>
    </div>

    <!-- Información Financiera -->
    <div class="details-section">
      <h6 class="section-title">Información Financiera</h6>
      <div class="details-grid">
        <div class="detail-item">
          <span class="detail-label">Ventas Totales:</span>
          <span class="detail-value font-weight-bold text-success">{{ formatCurrency(room.total_sales) }}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Bote Acumulado:</span>
          <span class="detail-value font-weight-bold text-warning">{{ formatCurrency(room.prize_pool) }}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Total Comisiones:</span>
          <span class="detail-value font-weight-bold">{{ formatCurrency(room.total_commissions) }}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Precio Cartón USD:</span>
          <span class="detail-value">{{ formatCurrency(room.config?.card_price_usd) }}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Precios por Moneda:</span>
          <span class="detail-value">{{ getCardPricesList() }}</span>
        </div>
      </div>
    </div>

    <!-- Comisiones Configuradas -->
    <div class="details-section">
      <h6 class="section-title">Comisiones Configuradas</h6>
      <div class="details-grid">
        <div class="detail-item">
          <span class="detail-label">Comisión RKE:</span>
          <span class="detail-value">{{ formatNumber(room.config?.rke_commission) }}%</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Comisión Sala:</span>
          <span class="detail-value">{{ formatNumber(room.config?.room_commission) }}%</span>
        </div>
      </div>
    </div>

    <!-- Configuración de Juego -->
    <div class="details-section">
      <h6 class="section-title">Configuración de Juego</h6>
      <div class="details-grid">
        <div class="detail-item">
          <span class="detail-label">Número de Rondas:</span>
          <span class="detail-value">{{ formatNumber(room.config?.rounds) }}</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Tiempo por Ficha:</span>
          <span class="detail-value">{{ formatNumber(room.config?.time_chip) }} seg</span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Usa USD Base:</span>
          <span class="detail-value">
            <span class="badge" :class="room.config?.use_base_usd ? 'badge-success' : 'badge-light'">
              {{ room.config?.use_base_usd ? 'Sí' : 'No' }}
            </span>
          </span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Usa Seriales:</span>
          <span class="detail-value">
            <span class="badge" :class="room.config?.use_serials ? 'badge-success' : 'badge-light'">
              {{ room.config?.use_serials ? 'Sí' : 'No' }}
            </span>
          </span>
        </div>
      </div>
    </div>

    <!-- Información Adicional -->
    <div class="details-section">
      <h6 class="section-title">Información Adicional</h6>
      <div class="details-grid">
        <div class="detail-item">
          <span class="detail-label">Premios Pagados:</span>
          <span class="detail-value">
            <span class="badge" :class="room.paid_prizes === 'Y' ? 'badge-success' : 'badge-warning'">
              {{ room.paid_prizes === 'Y' ? 'Sí' : 'No' }}
            </span>
          </span>
        </div>
        <div class="detail-item">
          <span class="detail-label">Sala Cerrada:</span>
          <span class="detail-value">
            <span class="badge" :class="room.is_closed ? 'badge-danger' : 'badge-success'">
              {{ room.is_closed ? 'Sí' : 'No' }}
            </span>
          </span>
        </div>
        <div class="detail-item">
          <span class="detail-label">En Vivo:</span>
          <span class="detail-value">
            <span class="badge" :class="room.config?.in_live === 'Y' ? 'badge-success' : 'badge-light'">
              {{ room.config?.in_live === 'Y' ? 'Sí' : 'No' }}
            </span>
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.room-details {
  max-height: 60vh;
  overflow-y: auto;
  padding-right: 0.5rem;
}

.details-section {
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e9ecef;
}

.details-section:last-child {
  border-bottom: none;
  margin-bottom: 0;
}

.section-title {
  color: #495057;
  font-weight: 600;
  margin-bottom: 0.75rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #007bff;
  display: inline-block;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 0.75rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  padding: 0.5rem;
  background: #f8f9fa;
  border-radius: 0.375rem;
}

.detail-item.highlight {
  background: #e3f2fd;
  border-left: 3px solid #007bff;
}

.detail-label {
  font-size: 0.75rem;
  color: #6c757d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 0.25rem;
}

.detail-value {
  font-size: 0.9rem;
  color: #212529;
}

.badge {
  padding: 0.375rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 600;
  border-radius: 0.25rem;
}

.badge-success {
  background-color: #28a745;
  color: white;
}

.badge-primary {
  background-color: #007bff;
  color: white;
}

.badge-info {
  background-color: #17a2b8;
  color: white;
}

.badge-warning {
  background-color: #ffc107;
  color: #212529;
}

.badge-danger {
  background-color: #dc3545;
  color: white;
}

.badge-secondary {
  background-color: #6c757d;
  color: white;
}

.badge-dark {
  background-color: #343a40;
  color: white;
}

.badge-light {
  background-color: #f8f9fa;
  color: #212529;
  border: 1px solid #dee2e6;
}

.text-primary {
  color: #007bff !important;
}

.text-info {
  color: #17a2b8 !important;
}

.text-success {
  color: #28a745 !important;
}

.text-warning {
  color: #ffc107 !important;
}

.font-weight-bold {
  font-weight: 600 !important;
}

/* Scrollbar styling */
.room-details::-webkit-scrollbar {
  width: 6px;
}

.room-details::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.room-details::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.room-details::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>
