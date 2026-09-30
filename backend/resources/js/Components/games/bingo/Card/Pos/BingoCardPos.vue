<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, toRef, watch } from 'vue'
import '@/css/games/bingo/bingo-pos.css'

import Modal from '@/js/Components/Common/Modal/Modal.vue'
import PosRoomPicker from './PosRoomPicker.vue'
import PosBuyerStep from './PosBuyerStep.vue'
import PosPaymentStep from './PosPaymentStep.vue'
import PosSummaryPanel from './PosSummaryPanel.vue'
import PosSaleSuccess from './PosSaleSuccess.vue'
import PosTodaySales from './PosTodaySales.vue'

import { providePos } from '@/js/composables/games/bingo/card/pos/posContext'
import { usePosRooms } from '@/js/composables/games/bingo/card/pos/usePosRooms'
import { usePosSale, PAYMENT_METHODS } from '@/js/composables/games/bingo/card/pos/usePosSale'
import { usePosTodaySales } from '@/js/composables/games/bingo/card/pos/usePosTodaySales'
import { showToast } from '@/js/utils/toastNotify'
import type { SelectOption } from '@/js/types/common/form/form'

/**
 * Punto de venta de cartones del vendedor (/bingo-card/generate).
 *
 * Reemplaza al formulario en cascada (BingoCardGenerateForm): pasos
 * visibles (sala → comprador → cartones y pago), resumen fijo, confirmación
 * explícita del cobro, pantalla de éxito para compartir el link y panel de
 * ventas del día. Los endpoints de venta no cambian.
 */
const props = defineProps<{
  roomTypeOptions: SelectOption[]
  connectionTypeOptions: SelectOption[]
  prizePoolTypeOptions: SelectOption[]
}>()

const rooms = reactive(usePosRooms({
  room_type_id: props.roomTypeOptions,
  connection_type_id: props.connectionTypeOptions,
  prize_pool_type_id: props.prizePoolTypeOptions,
}))
const confirmOpen = ref(false)
const paymentConfirmed = ref(false)

/** El backend dijo que la sala ya no acepta ventas: refrescar para mostrarla cerrada. */
function onSalesClosed(): void {
  confirmOpen.value = false
  rooms.fetchRooms()
}

const sale = reactive(usePosSale(toRef(rooms, 'selectedRoom'), onSalesClosed))
const todaySales = reactive(usePosTodaySales())

function requestConfirm(): void {
  if (!rooms.selectedRoomOpen) {
    showToast('Las ventas de esta sala ya cerraron. Elige otra sala.', 'warning')
    return
  }
  if (!sale.isFormValid || sale.submitting) return
  paymentConfirmed.value = false
  confirmOpen.value = true
}

providePos({ rooms, sale, todaySales, requestConfirm })

async function confirmSale(): Promise<void> {
  if (!paymentConfirmed.value) return
  const ok = await sale.submit()
  if (!ok) return
  confirmOpen.value = false
  todaySales.refresh()
  rooms.fetchRooms() // jugadores/bote de la sala cambiaron
}

/** Ctrl/Cmd + Enter: revisar y generar desde cualquier campo. */
function onKeydown(e: KeyboardEvent): void {
  if (e.key === 'Enter' && (e.ctrlKey || e.metaKey) && !confirmOpen.value && !sale.lastSale) {
    e.preventDefault()
    requestConfirm()
  }
}
onMounted(() => window.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown))

// Cerrar el modal si la venta deja de ser válida (precio recargado, o la
// sala llegó a su cierre de ventas mientras estaba abierto).
watch(
  () => sale.isFormValid && rooms.selectedRoomOpen,
  valid => { if (!valid && !sale.submitting) confirmOpen.value = false },
)

const room = computed(() => rooms.selectedRoom)

const paymentMethodLabel = computed(() =>
  PAYMENT_METHODS.find(m => m.value === sale.paymentMethod)?.label ?? '—'
)

function money(n: number): string {
  return n.toLocaleString('es', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<template>
  <div class="pos bcp">
    <div class="bcp__main">
      <PosRoomPicker />

      <PosSaleSuccess v-if="sale.lastSale" />
      <template v-else>
        <PosBuyerStep />
        <PosPaymentStep />
      </template>

      <p class="bcp__shortcut pos-muted d-none d-lg-block">
        <kbd>Ctrl</kbd> + <kbd>Enter</kbd> para revisar y generar · <kbd>Ctrl</kbd> + <kbd>V</kbd> para pegar el comprobante
      </p>
    </div>

    <div class="bcp__side">
      <PosSummaryPanel v-if="!sale.lastSale" />
      <PosTodaySales />
    </div>

    <!-- ── Confirmación del cobro ── -->
    <Modal v-model="confirmOpen" title="Confirmar venta" icon="fa-ticket-alt" size="sm" :static="sale.submitting">
      <!-- .pos: el Modal se teletransporta a <body>, fuera de las variables --pos-* -->
      <div class="pos bcp-confirm">
        <span class="bcp-confirm__label">Cobra al comprador</span>
        <strong class="bcp-confirm__total pos-money">{{ sale.currencySymbol }} {{ money(sale.totalAmount) }}</strong>

        <dl class="bcp-confirm__rows">
          <div><dt>Comprador</dt><dd>{{ sale.buyer.firstName }} {{ sale.buyer.lastName }}</dd></div>
          <div><dt>Teléfono</dt><dd>{{ sale.buyer.phone }}</dd></div>
          <div><dt>Sala</dt><dd>{{ room?.time?.slice(0, 5) }} · {{ room?.uuid_code }}</dd></div>
          <div><dt>Cartones</dt><dd>{{ sale.cardCount }} × {{ sale.currencySymbol }} {{ money(sale.cardPrice) }}</dd></div>
          <div><dt>Pago</dt><dd>{{ paymentMethodLabel }}</dd></div>
          <div v-if="sale.requiresReceipt"><dt>Comprobante</dt><dd>{{ sale.receiptNumber }}</dd></div>
          <div><dt>Tu comisión</dt><dd class="bcp-confirm__earn">{{ sale.currencySymbol }} {{ money(sale.sellerCommission) }}</dd></div>
        </dl>

        <label class="bcp-confirm__check">
          <input v-model="paymentConfirmed" type="checkbox" />
          <span>Confirmo que <strong>recibí el pago</strong> de {{ sale.currencySymbol }} {{ money(sale.totalAmount) }}.</span>
        </label>
      </div>

      <template #footer>
        <div class="pos bcp-confirm__actions">
          <button type="button" class="pos-btn pos-btn--ghost" :disabled="sale.submitting" @click="confirmOpen = false">Volver</button>
          <button
            type="button"
            class="pos-btn pos-btn--success"
            :disabled="!paymentConfirmed || sale.submitting"
            @click="confirmSale"
          >
            <i :class="sale.submitting ? 'fas fa-spinner fa-spin' : 'fas fa-check'"></i>
            {{ sale.submitting ? 'Generando…' : 'Generar cartones' }}
          </button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<style scoped>
.bcp {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 340px;
  gap: 16px;
  align-items: start;
}
.bcp__main { min-width: 0; }
.bcp__side { min-width: 0; }
.bcp__shortcut { margin: 12px 2px 0; }
.bcp__shortcut kbd { font-size: 10px; padding: 1px 5px; }

.bcp-confirm { text-align: center; }
.bcp-confirm__label { display: block; font-size: 12px; font-weight: 700; color: var(--ds-text-2); text-transform: uppercase; letter-spacing: .05em; }
.bcp-confirm__total { display: block; font-size: 34px; font-weight: 800; letter-spacing: -.02em; margin-bottom: 14px; color: var(--pos-accent-strong); }
.bcp-confirm__rows { margin: 0 0 14px; text-align: left; display: flex; flex-direction: column; gap: 6px; }
.bcp-confirm__rows > div { display: flex; justify-content: space-between; gap: 12px; font-size: 13px; }
.bcp-confirm__rows dt { font-weight: 500; color: var(--ds-text-2); }
.bcp-confirm__rows dd { margin: 0; font-weight: 600; text-align: right; overflow-wrap: anywhere; }
.bcp-confirm__earn { color: var(--pos-success); }
.bcp-confirm__check {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  text-align: left;
  padding: 10px 12px;
  border-radius: 10px;
  background: var(--pos-warning-soft);
  font-size: 13px;
  cursor: pointer;
}
.bcp-confirm__actions { display: flex; justify-content: flex-end; gap: 10px; width: 100%; }
.bcp-confirm__check input { margin-top: 3px; width: 16px; height: 16px; accent-color: var(--pos-success); flex-shrink: 0; }

@media (max-width: 991.98px) {
  .bcp { grid-template-columns: 1fr; padding-bottom: 76px; }
  .bcp__side { order: 2; }
}
</style>
