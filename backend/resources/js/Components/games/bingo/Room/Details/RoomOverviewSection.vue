<script setup lang="ts">
/**
 * Resumen de la sala (pestaña principal del modal de detalles): KPIs de
 * participación con el mínimo de cartones como progreso, ventas por moneda
 * y, si la sala se canceló, el motivo y el estado de los reembolsos.
 */
import { computed } from 'vue'
import type { BingoRoom } from '@js/types/games/bingo/room/bingo-room'
import { fmt } from '@js/composables/games/bingo/room/useBingoRoomFormatters'

const props = defineProps<{
  room: BingoRoom
  metricsByCurrencyList: { currencyId: string; label: string; totalSales: string; totalCards: string; prizePool: string }[] | null
}>()

const minCards = computed(() => Number(props.room.config?.min_cards_to_start ?? 0))
const cards = computed(() => Number(props.room.total_cards ?? 0))
const minProgress = computed(() => (minCards.value ? Math.min(100, Math.round((cards.value / minCards.value) * 100)) : 100))
const reached = computed(() => !minCards.value || cards.value >= minCards.value)

/** Sala ya cerrada (no va a vender más): "no alcanzado" en vez de "pendiente". */
const isClosed = computed(() => ['CANCELLED', 'REFUND', 'FINISHED', 'DELETED', 'INACTIVE'].includes(props.room.status as string))

const minState = computed(() => {
  if (!minCards.value) return null
  if (reached.value) return { label: 'Mínimo alcanzado', cls: 'ok', icon: 'fa-circle-check' }
  return isClosed.value
    ? { label: `No alcanzó el mínimo (${fmt.number(minCards.value)})`, cls: 'bad', icon: 'fa-circle-xmark' }
    : { label: `Faltan ${fmt.number(minCards.value - cards.value)} para iniciar`, cls: 'warn', icon: 'fa-hourglass-half' }
})

const c = computed(() => props.room.cancellation ?? null)
const refundState = computed(() => {
  if (!c.value) return null
  if (c.value.failed_abandoned > 0) return { cls: 'bad', text: `${c.value.failed_abandoned} reembolso(s) abandonado(s): requieren acreditación manual` }
  if (c.value.failed_pending > 0) return { cls: 'warn', text: `${c.value.failed_pending} reembolso(s) en reintento automático` }
  if (c.value.pending_lots > 0) return { cls: 'warn', text: `${c.value.pending_lots} venta(s) aún por reembolsar` }
  if (c.value.refunded_lots > 0) return { cls: 'ok', text: `${c.value.refunded_lots} venta(s) reembolsada(s) · ${c.value.refunded_cards} cartón(es)` }
  return { cls: 'ok', text: 'Sin ventas que reembolsar' }
})

function when(iso: string | null): string {
  return iso ? new Date(iso).toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—'
}
</script>

<template>
  <div class="ov">
    <!-- Cancelación -->
    <div v-if="c" class="ov-cancel">
      <i class="fas fa-ban ov-cancel__icon"></i>
      <div class="ov-cancel__body">
        <div class="ov-cancel__title">
          Cancelada el {{ when(c.at) }}<template v-if="c.by"> · por {{ c.by }}</template>
        </div>
        <div v-if="c.reason" class="ov-cancel__reason">{{ c.reason }}</div>
        <div v-if="refundState" class="ov-refund" :class="`ov-refund--${refundState.cls}`">
          <i class="fas fa-rotate-left"></i> {{ refundState.text }}
        </div>
      </div>
    </div>

    <!-- KPIs -->
    <div class="ov-kpis">
      <div class="ov-kpi">
        <span class="ov-kpi__label"><i class="fas fa-users"></i> Jugadores</span>
        <span class="ov-kpi__value">{{ fmt.number(room.total_players) }}<small v-if="room.config?.max_players"> / {{ fmt.number(room.config.max_players) }}</small></span>
      </div>
      <div class="ov-kpi">
        <span class="ov-kpi__label"><i class="fas fa-ticket"></i> Cartones</span>
        <span class="ov-kpi__value">{{ fmt.number(cards) }}<small v-if="minCards"> / mín. {{ fmt.number(minCards) }}</small></span>
        <div v-if="minState" class="ov-bar" :title="minState.label"><span :class="`ov-bar__fill--${minState.cls}`" :style="{ width: `${minProgress}%` }"></span></div>
        <span v-if="minState" class="ov-kpi__hint" :class="`ov-text--${minState.cls}`"><i class="fas" :class="minState.icon"></i> {{ minState.label }}</span>
      </div>
      <div class="ov-kpi">
        <span class="ov-kpi__label"><i class="fas fa-sack-dollar"></i> Bote (USD)</span>
        <span class="ov-kpi__value">{{ fmt.currency(room.prize_pool) }}</span>
      </div>
      <div class="ov-kpi">
        <span class="ov-kpi__label"><i class="fas fa-trophy"></i> Ganadores</span>
        <span class="ov-kpi__value">{{ fmt.number(room.winners?.length ?? 0) }}</span>
        <span v-if="room.paid_prizes === 'Y'" class="ov-kpi__hint ov-text--ok"><i class="fas fa-circle-check"></i> Premios pagados</span>
      </div>
    </div>

    <!-- Ventas por moneda -->
    <div v-if="metricsByCurrencyList?.length" class="ov-sales">
      <span class="ov-sales__title">Ventas</span>
      <span v-for="m in metricsByCurrencyList" :key="m.currencyId" class="ov-chip">
        <strong>{{ m.label }}</strong> {{ m.totalSales }}
        <small>· {{ m.totalCards }} cart. · bote {{ m.prizePool }}</small>
      </span>
    </div>
  </div>
</template>

<style scoped>
.ov { display: flex; flex-direction: column; gap: 0.6rem; margin-bottom: 0.9rem; }

.ov-cancel { display: flex; gap: 0.6rem; padding: 0.6rem 0.75rem; border-radius: 8px; background: rgba(220,38,38,.08); border: 1px solid rgba(220,38,38,.25); }
.ov-cancel__icon { color: #dc2626; margin-top: 0.15rem; }
.ov-cancel__body { display: flex; flex-direction: column; gap: 0.2rem; min-width: 0; }
.ov-cancel__title { font-size: 0.82rem; font-weight: 600; color: var(--ds-text); }
.ov-cancel__reason { font-size: 0.78rem; color: var(--ds-text-2); }

.ov-refund { font-size: 0.75rem; font-weight: 600; }
.ov-refund--ok, .ov-text--ok { color: #16a34a; }
.ov-refund--warn, .ov-text--warn { color: #d97706; }
.ov-refund--bad, .ov-text--bad { color: #dc2626; }

.ov-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.5rem; }
.ov-kpi { display: flex; flex-direction: column; gap: 0.15rem; padding: 0.5rem 0.65rem; border-radius: 8px; background: var(--ds-surface-2); min-width: 0; }
.ov-kpi__label { font-size: 0.66rem; text-transform: uppercase; letter-spacing: 0.04em; color: var(--ds-text-muted); }
.ov-kpi__label i { margin-right: 0.2rem; }
.ov-kpi__value { font-size: 1.05rem; font-weight: 700; color: var(--ds-text); font-variant-numeric: tabular-nums; }
.ov-kpi__value small { font-size: 0.68rem; font-weight: 500; color: var(--ds-text-muted); }
.ov-kpi__hint { font-size: 0.68rem; font-weight: 600; }

.ov-bar { height: 4px; border-radius: 4px; background: var(--ds-border); overflow: hidden; }
.ov-bar span { display: block; height: 100%; }
.ov-bar__fill--ok { background: #16a34a; }
.ov-bar__fill--warn { background: #d97706; }
.ov-bar__fill--bad { background: #dc2626; }

.ov-sales { display: flex; flex-wrap: wrap; align-items: center; gap: 0.35rem; }
.ov-sales__title { font-size: 0.66rem; text-transform: uppercase; letter-spacing: 0.04em; color: var(--ds-text-muted); margin-right: 0.15rem; }
.ov-chip { font-size: 0.75rem; padding: 0.15rem 0.5rem; border-radius: 999px; border: 1px solid var(--ds-border); color: var(--ds-text); font-variant-numeric: tabular-nums; }
.ov-chip small { color: var(--ds-text-muted); }
</style>
