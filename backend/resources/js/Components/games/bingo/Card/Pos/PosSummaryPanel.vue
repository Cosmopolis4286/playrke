<script setup lang="ts">
import { computed } from 'vue'
import { usePos } from '@/js/composables/games/bingo/card/pos/posContext'

/**
 * Resumen de la venta en curso: fijo a la derecha en escritorio y como
 * barra inferior en móvil. El botón explica qué falta mientras esté
 * deshabilitado, en lugar de quedar gris sin motivo.
 */
const { rooms, sale, requestConfirm } = usePos()

const hasPrice = computed(() => !!sale.cardPriceData)

/** La sala elegida llegó a su cierre de ventas (5 min antes del inicio por defecto). */
const roomClosed = computed(() => !!rooms.selectedRoom && !rooms.selectedRoomOpen)
const canSubmit = computed(() => sale.isFormValid && !sale.submitting && !roomClosed.value)

function money(n: number): string {
  return n.toLocaleString('es', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<template>
  <aside class="pss" aria-label="Resumen de la venta">
    <div class="pss__card">
      <div class="pss__head">
        <i class="fas fa-receipt"></i> Resumen de la venta
      </div>

      <dl class="pss__rows">
        <div>
          <dt>Sala</dt>
          <dd>
            <template v-if="rooms.selectedRoom">{{ rooms.selectedRoom.time?.slice(0, 5) }} · {{ rooms.selectedRoom.uuid_code }}</template>
            <span v-else class="pss__empty">—</span>
          </dd>
        </div>
        <div>
          <dt>Comprador</dt>
          <dd>
            <template v-if="sale.buyer.firstName">{{ sale.buyer.firstName }} {{ sale.buyer.lastName }}</template>
            <span v-else class="pss__empty">—</span>
          </dd>
        </div>
        <div>
          <dt>Cartones</dt>
          <dd class="pos-money">
            {{ sale.cardCount }}
            <span v-if="hasPrice" class="pos-muted">× {{ sale.currencySymbol }} {{ money(sale.cardPrice) }}</span>
          </dd>
        </div>
        <div v-if="sale.exchangeRateLabel">
          <dt>Tasa</dt>
          <dd class="pss__rate">
            <span class="pos-money">{{ sale.exchangeRateLabel }}</span>
            <span class="pos-muted">{{ sale.exchangeRateSourceLabel }}</span>
          </dd>
        </div>
      </dl>

      <div class="pss__total">
        <span>Cobrar al comprador</span>
        <strong v-if="sale.loadingCardPrice" class="pss__loading"><i class="fas fa-spinner fa-spin"></i></strong>
        <strong v-else class="pos-money">{{ hasPrice ? `${sale.currencySymbol} ${money(sale.totalAmount)}` : '—' }}</strong>
      </div>

      <div v-if="hasPrice" class="pss__earn">
        <span><i class="fas fa-coins"></i> Tu comisión ({{ sale.commissionRate }}%)</span>
        <strong class="pos-money">{{ sale.currencySymbol }} {{ money(sale.sellerCommission) }}</strong>
      </div>
      <div v-if="hasPrice && rooms.selectedRoom" class="pss__pot">
        <span>Bote actual de la sala</span>
        <span class="pos-money">{{ sale.currencySymbol }} {{ money(sale.prizePoolLocal) }}</span>
      </div>

      <button
        type="button"
        class="pos-btn pos-btn--primary pos-btn--block pos-btn--lg pss__cta"
        :disabled="!canSubmit"
        @click="requestConfirm"
      >
        <i class="fas fa-ticket-alt"></i>
        Revisar y generar {{ sale.cardCount === 1 ? 'cartón' : `${sale.cardCount} cartones` }}
      </button>

      <div v-if="roomClosed" class="pss__closed" role="alert">
        <i class="fas fa-store-slash"></i>
        Las ventas de esta sala cerraron. Elige otra sala para continuar.
      </div>

      <ul v-else-if="!sale.isFormValid && sale.missingSteps.length" class="pss__missing">
        <li v-for="m in sale.missingSteps" :key="m"><i class="far fa-circle"></i>{{ m }}</li>
      </ul>
    </div>

    <!-- ── Barra inferior (móvil) ── -->
    <div class="pss-bar">
      <div class="pss-bar__total">
        <span class="pos-muted">{{ sale.cardCount }} {{ sale.cardCount === 1 ? 'cartón' : 'cartones' }}</span>
        <strong class="pos-money">{{ hasPrice ? `${sale.currencySymbol} ${money(sale.totalAmount)}` : '—' }}</strong>
      </div>
      <button
        type="button"
        class="pos-btn pos-btn--primary"
        :disabled="!canSubmit"
        @click="requestConfirm"
      >
        {{ roomClosed ? 'Ventas cerradas' : sale.isFormValid ? 'Generar' : (sale.missingSteps[0] ?? 'Generar') }}
      </button>
    </div>
  </aside>
</template>

<style scoped>
.pss { position: sticky; top: calc(76px + var(--pwt-height, 0px)); }

.pss__card {
  background: var(--ds-surface);
  border: 1px solid var(--ds-border);
  border-radius: var(--pos-radius);
  padding: 18px;
}
.pss__head { font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: var(--ds-text-2); margin-bottom: 12px; }

.pss__rows { margin: 0 0 14px; display: flex; flex-direction: column; gap: 8px; }
.pss__rows > div { display: flex; justify-content: space-between; gap: 12px; font-size: 13px; }
.pss__rows dt { font-weight: 500; color: var(--ds-text-2); }
.pss__rate { display: flex; flex-direction: column; align-items: flex-end; line-height: 1.25; }
.pss__rate .pos-muted { font-size: 11px; }
.pss__rows dd { margin: 0; text-align: right; font-weight: 600; min-width: 0; overflow-wrap: anywhere; }
.pss__empty { color: var(--ds-text-muted); }

.pss__total {
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 14px;
  border-radius: 12px;
  background: var(--pos-accent-soft);
  margin-bottom: 10px;
}
.pss__total span { font-size: 12px; font-weight: 700; color: var(--pos-accent-strong); }
.pss__total strong { font-size: 28px; font-weight: 800; letter-spacing: -.02em; line-height: 1.15; }

.pss__earn, .pss__pot { display: flex; justify-content: space-between; gap: 10px; font-size: 13px; padding: 4px 2px; }
.pss__earn strong { color: var(--pos-success); }
.pss__earn i { color: var(--pos-success); margin-right: 4px; }
.pss__pot { color: var(--ds-text-2); }

.pss__cta { margin-top: 14px; }

.pss__missing { list-style: none; padding: 0; margin: 10px 0 0; display: flex; flex-direction: column; gap: 4px; }
.pss__missing li { font-size: 12px; color: var(--ds-text-2); display: flex; align-items: center; gap: 7px; }
.pss__missing i { font-size: 9px; }

.pss__closed {
  display: flex;
  gap: 8px;
  align-items: flex-start;
  margin-top: 10px;
  padding: 10px 12px;
  border-radius: 10px;
  font-size: 12px;
  background: var(--pos-danger-soft);
  color: var(--pos-danger);
}

.pss-bar { display: none; }

@media (max-width: 991.98px) {
  .pss { position: static; }
  .pss__card { display: none; }
  .pss-bar {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1030;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 16px calc(10px + env(safe-area-inset-bottom));
    background: var(--ds-surface);
    border-top: 1px solid var(--ds-border);
    box-shadow: 0 -6px 20px rgba(0, 0, 0, .12);
  }
  .pss-bar__total { display: flex; flex-direction: column; line-height: 1.2; }
  .pss-bar__total strong { font-size: 20px; }
  .pss-bar .pos-btn { max-width: 60%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
}
</style>
