<script setup lang="ts">
import type { BingoRoom } from '@js/types/games/bingo/room/bingo-room'
import { fmt } from '@js/composables/games/bingo/room/useBingoRoomFormatters'

defineProps<{
  room: BingoRoom
  isAccumulative: boolean
  cardPricesList: { label: string; price: string }[] | null
  metricsByCurrencyList: {
    currencyId: string
    label: string
    totalSales: string
    totalCards: string
    prizePool: string
    totalCommissions: string
  }[] | null
}>()
</script>

<template>
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
</template>

<style scoped>
.details-section {
  margin-bottom: 1.25rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--ds-border);
}

.section-title {
  color: var(--ds-text-2);
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
  color: var(--ds-text-2);
  margin: -0.3rem 0 0.6rem;
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
  background: var(--ds-surface-2);
  border-radius: 0.375rem;
  border-left: 3px solid transparent;
}
.detail-item.highlight { background: #e8f4fd; border-left-color: #007bff; }
.detail-item.highlight .detail-label,
.detail-item.highlight .detail-value { color: #0b3d6b; }

.detail-label {
  font-size: 0.7rem;
  color: var(--ds-text-2);
  text-transform: uppercase;
  letter-spacing: 0.4px;
  margin-bottom: 0.2rem;
}

.detail-value { font-size: 0.875rem; color: var(--ds-text); }

.font-weight-bold { font-weight: 600 !important; }
.text-success { color: #28a745 !important; }
.text-warning { color: #ffc107 !important; }
.text-muted   { color: var(--ds-text-2) !important; }
.ml-1 { margin-left: 0.25rem; }
.gap-2 { gap: 0.5rem; }

.badge {
  padding: 0.3rem 0.65rem;
  font-size: 0.72rem;
  font-weight: 600;
  border-radius: 0.25rem;
}
.badge-light { background-color: var(--ds-surface-2); color: var(--ds-text); border: 1px solid var(--ds-border); }

/* Desglose por Moneda */
.currency-breakdown {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.currency-breakdown-item {
  border: 1px solid var(--ds-border);
  border-radius: 0.375rem;
  padding: 0.5rem 0.6rem;
}

.currency-breakdown-header {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--ds-text-2);
  margin-bottom: 0.4rem;
}

.currency-breakdown-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 0.5rem;
}
</style>
