<script setup lang="ts">
import { computed, ref } from 'vue'
import { usePos } from '@/js/composables/games/bingo/card/pos/posContext'
import { showToast } from '@/js/utils/toastNotify'
import type { PosSale, PosSaleStatus } from '@/js/types/games/bingo/card/bingo-card-generate'

/**
 * Ventas del día del vendedor: totales y últimas ventas con su estado de
 * aprobación, para reenviar el link a un comprador sin volver a buscarlo.
 */
const { todaySales } = usePos()

const STATUS: Record<PosSaleStatus, { label: string; tone: string; icon: string }> = {
  PENDING:   { label: 'En revisión', tone: 'warning', icon: 'fas fa-hourglass-half' },
  APPROVED:  { label: 'Aprobada',    tone: 'success', icon: 'fas fa-check' },
  REJECTED:  { label: 'Rechazada',   tone: 'danger',  icon: 'fas fa-times' },
  CANCELLED: { label: 'Cancelada',   tone: 'muted',   icon: 'fas fa-ban' },
}

const expanded = ref(false)
const VISIBLE = 5
const visibleSales = computed(() => expanded.value ? todaySales.sales : todaySales.sales.slice(0, VISIBLE))

function time(iso: string | null): string {
  return iso ? new Date(iso).toLocaleTimeString('es', { hour: '2-digit', minute: '2-digit' }) : ''
}

function money(n: number): string {
  return n.toLocaleString('es', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

async function copyLink(sale: PosSale): Promise<void> {
  if (!sale.player_link) return
  try {
    await navigator.clipboard.writeText(sale.player_link)
    showToast(`Link de ${sale.buyer_name || 'comprador'} copiado`, 'success')
  } catch {
    showToast('No se pudo copiar el link', 'error')
  }
}
</script>

<template>
  <section class="pos-card pts" aria-label="Mis ventas de hoy">
    <div class="pts__head">
      <h2 class="pos-step__title"><i class="fas fa-chart-line me-2 text-muted"></i>Mis ventas de hoy</h2>
      <button type="button" class="pos-icon-btn" title="Actualizar" aria-label="Actualizar ventas" @click="todaySales.refresh()">
        <i class="fas fa-sync-alt" :class="{ 'fa-spin': todaySales.loading }"></i>
      </button>
    </div>

    <div v-if="todaySales.summary" class="pts__stats">
      <div class="pts__stat">
        <span>Ventas</span>
        <strong>{{ todaySales.summary.sales_count }}</strong>
      </div>
      <div class="pts__stat">
        <span>Cartones</span>
        <strong>{{ todaySales.summary.cards_count }}</strong>
      </div>
      <div class="pts__stat pts__stat--wide">
        <span>Cobrado</span>
        <strong v-if="!todaySales.summary.totals.length">—</strong>
        <strong v-for="t in todaySales.summary.totals" :key="t.currency_iso" class="pos-money">
          {{ t.currency_symbol }} {{ money(t.amount) }}
        </strong>
      </div>
    </div>

    <div v-if="todaySales.summary && (todaySales.summary.pending || todaySales.summary.rejected)" class="pos-chips mb-2">
      <span v-if="todaySales.summary.pending" class="pos-pill pos-pill--warning">
        <i class="fas fa-hourglass-half"></i>{{ todaySales.summary.pending }} en revisión
      </span>
      <span v-if="todaySales.summary.rejected" class="pos-pill pos-pill--danger">
        <i class="fas fa-times"></i>{{ todaySales.summary.rejected }} rechazada{{ todaySales.summary.rejected === 1 ? '' : 's' }}
      </span>
    </div>

    <p v-if="!todaySales.sales.length && !todaySales.loading" class="pos-muted mb-0 text-center py-3">
      <i class="fas fa-store me-1"></i>Aún no has vendido hoy. ¡La primera venta es la más difícil!
    </p>

    <ul v-else class="pts__list">
      <li v-for="s in visibleSales" :key="s.id" class="pts__item">
        <span class="pts__time">{{ time(s.created_at) }}</span>
        <span class="pts__body">
          <strong class="text-truncate">{{ s.buyer_name || 'Comprador' }}</strong>
          <span class="pos-muted">
            {{ s.card_count }} {{ s.card_count === 1 ? 'cartón' : 'cartones' }} · {{ s.room_code }} ·
            <span class="pos-money">{{ s.currency_symbol }} {{ money(s.amount) }}</span>
            <template v-if="s.exchange_rate"> · <span class="pos-money" title="Tasa con la que se cobró">tasa {{ money(s.exchange_rate) }}</span></template>
          </span>
        </span>
        <span class="pos-pill" :class="`pos-pill--${STATUS[s.status]?.tone ?? 'muted'}`" :title="s.receipt ? `Comprobante ${s.receipt}` : undefined">
          <i :class="STATUS[s.status]?.icon"></i>{{ STATUS[s.status]?.label ?? s.status }}
        </span>
        <button
          v-if="s.player_link"
          type="button"
          class="pos-icon-btn"
          title="Copiar link del comprador"
          :aria-label="`Copiar link de ${s.buyer_name}`"
          @click="copyLink(s)"
        >
          <i class="fas fa-link"></i>
        </button>
      </li>
    </ul>

    <button
      v-if="todaySales.sales.length > VISIBLE"
      type="button"
      class="pts__more"
      @click="expanded = !expanded"
    >
      {{ expanded ? 'Ver menos' : `Ver las ${todaySales.sales.length} ventas` }}
    </button>
  </section>
</template>

<style scoped>
.pts { margin-top: 14px; }
.pts__head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }

.pts__stats { display: grid; grid-template-columns: 1fr 1fr 2fr; gap: 8px; margin-bottom: 10px; }
.pts__stat {
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 10px 12px;
  border-radius: 10px;
  background: var(--ds-surface-2);
  min-width: 0;
}
.pts__stat span { font-size: 11px; font-weight: 700; color: var(--ds-text-2); text-transform: uppercase; letter-spacing: .04em; }
.pts__stat strong { font-size: 18px; font-weight: 800; line-height: 1.2; overflow-wrap: anywhere; }

.pts__list { list-style: none; padding: 0; margin: 0; }
.pts__item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 0;
  border-top: 1px solid var(--ds-border);
}
.pts__time { font-size: 12px; font-weight: 700; color: var(--ds-text-2); font-variant-numeric: tabular-nums; width: 42px; flex-shrink: 0; }
.pts__body { display: flex; flex-direction: column; min-width: 0; flex: 1; font-size: 13px; }

.pts__more {
  display: block;
  width: 100%;
  margin-top: 6px;
  padding: 6px;
  border: none;
  background: none;
  font-size: 12px;
  font-weight: 700;
  color: var(--pos-accent-strong);
  cursor: pointer;
}

@media (max-width: 576px) {
  .pts__stats { grid-template-columns: 1fr 1fr; }
  .pts__stat--wide { grid-column: 1 / -1; }
  .pts__item .pos-pill { font-size: 10px; }
}
</style>
