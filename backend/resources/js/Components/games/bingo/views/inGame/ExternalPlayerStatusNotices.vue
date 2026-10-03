<script setup lang="ts">
import { computed } from 'vue'

/**
 * Avisos para el jugador externo (sin cuenta) en su link de juego: cartones
 * con pago en revisión, cartones anulados por pago rechazado y premios
 * ganados, con a quién acudir. Sin esto el comprador veía el link vacío o
 * sus cartones desaparecer sin explicación, y no sabía que el premio se lo
 * entrega su vendedor/comercio (no tiene billetera en la plataforma).
 */
export interface ExternalPlayerPrize {
  type: string
  amount: number
  currency_symbol: string
  status: string
  status_label: string
}

export interface ExternalPlayerStatus {
  pending_cards: number
  rejected_cards: number
  prizes: ExternalPlayerPrize[]
  sold_by: string | null
  commerce_name: string | null
}

const props = defineProps<{ status: ExternalPlayerStatus }>()

/** A quién acudir: el comercio si se conoce, si no el vendedor. */
const contact = computed(() => props.status.commerce_name || props.status.sold_by || 'quien te vendió los cartones')
const seller = computed(() => props.status.sold_by || 'quien te vendió los cartones')

function money(n: number): string {
  return n.toLocaleString('es', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const hasAny = computed(() =>
  props.status.pending_cards > 0 || props.status.rejected_cards > 0 || props.status.prizes.length > 0
)
</script>

<template>
  <div v-if="hasAny" class="epn" role="status" aria-live="polite">
    <div v-for="(prize, i) in status.prizes" :key="`prize-${i}`" class="epn__item epn__item--prize">
      <i class="fas fa-trophy" aria-hidden="true"></i>
      <span>
        ¡Ganaste <strong>{{ prize.type }}</strong> por
        <strong>{{ prize.currency_symbol }} {{ money(prize.amount) }}</strong>!
        Tu premio te lo entrega <strong>{{ contact }}</strong>.
        <span class="epn__badge">{{ prize.status_label }}</span>
      </span>
    </div>

    <div v-if="status.pending_cards > 0" class="epn__item epn__item--pending">
      <i class="fas fa-hourglass-half" aria-hidden="true"></i>
      <span>
        Tienes <strong>{{ status.pending_cards }} cartón(es) en revisión</strong>.
        Aparecerán aquí en cuanto <strong>{{ contact }}</strong> confirme tu pago.
      </span>
    </div>

    <div v-if="status.rejected_cards > 0" class="epn__item epn__item--rejected">
      <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
      <span>
        <strong>{{ status.rejected_cards }} cartón(es) se anularon</strong> porque no se pudo confirmar el pago.
        Comunícate con <strong>{{ seller }}</strong> para que te devuelva tu dinero.
      </span>
    </div>
  </div>
</template>

<style scoped>
.epn {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin: 0.6rem 0;
}
.epn__item {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
  padding: 0.6rem 0.9rem;
  border-radius: 10px;
  font-size: 0.85rem;
  line-height: 1.4;
  border: 1px solid transparent;
}
.epn__item i { margin-top: 0.15rem; }
.epn__item--prize   { background: rgba(250, 204, 21, 0.14); border-color: rgba(250, 204, 21, 0.35); color: #fde68a; }
.epn__item--pending { background: rgba(56, 189, 248, 0.12); border-color: rgba(56, 189, 248, 0.3);  color: #bae6fd; }
.epn__item--rejected { background: rgba(248, 113, 113, 0.12); border-color: rgba(248, 113, 113, 0.3); color: #fecaca; }
.epn__badge {
  display: inline-block;
  margin-left: 0.35rem;
  padding: 0.05rem 0.5rem;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 700;
  background: rgba(255, 255, 255, 0.12);
}
</style>
