<script setup lang="ts">
import type { PurchaseModalStep } from '@/js/types/games/bingo/card/purchase-modal/bingo-purchase-modal'

defineProps<{
  isPlayer: boolean | null
  step: PurchaseModalStep
  currentCardCount: number
  purchasing: boolean
  canAdvance: boolean
  loadingPrice: boolean
  customizeCard: boolean
  manualCardCount: number
  basePricePerCard: number
  customPricePerCard: number
  customCardMode?: 'paid' | 'every_x' | 'free' | null
  customPricePct: number
  exceedsCustomCardLimit?: boolean
  cardCount: number
  totalAmount: number
  currencySymbol: string
}>()

const emit = defineEmits<{
  prev: []
  next: []
  purchase: []
  lobby: []
}>()
</script>

<template>
  <div class="pm-footer">

    <!-- Botones izquierda: solo iconos -->
    <div class="pm-footer__actions">
      <button
        v-if="!isPlayer && isPlayer !== null && step === 'confirm'"
        class="pm-icon-btn"
        title="Atrás"
        :disabled="purchasing"
        @click="emit('prev')"
      >
        <i class="fas fa-arrow-left" />
      </button>
      <button
        v-if="step === 'confirm' && currentCardCount > 0"
        class="pm-icon-btn pm-icon-btn--lobby"
        title="Ir al lobby"
        @click="emit('lobby')"
      >
        <i class="fas fa-door-open" />
      </button>
    </div>

    <!-- Resumen compacto centrado -->
    <div v-if="step === 'confirm'" class="pm-footer__summary">
      <!-- Sin personalización o todos al azar -->
      <template v-if="!customizeCard || manualCardCount === 0">
        <span class="pm-fs__pill">
          <i class="fas fa-ticket-alt" />{{ cardCount }}× {{ basePricePerCard.toFixed(2) }}
        </span>
      </template>
      <!-- Mixto -->
      <template v-else>
        <span v-if="cardCount - manualCardCount > 0" class="pm-fs__pill">
          <i class="fas fa-dice" />{{ cardCount - manualCardCount }}× {{ basePricePerCard.toFixed(2) }}
        </span>
        <span class="pm-fs__pill pm-fs__pill--custom">
          <i class="fas fa-pen" />{{ manualCardCount }}× {{ customPricePerCard.toFixed(2) }}
          <em v-if="customCardMode === 'paid' && customPricePct > 0">+{{ customPricePct }}%</em>
        </span>
      </template>
      <span class="pm-fs__sep">=</span>
      <span class="pm-fs__total">
        {{ totalAmount.toFixed(2) }}<small>{{ currencySymbol }}</small>
      </span>
    </div>
    <div v-else class="pm-footer__spacer" />

    <!-- Botón principal -->
    <button
      v-if="!isPlayer && isPlayer !== null && step === 'country'"
      class="pm-btn pm-btn--primary"
      :disabled="!canAdvance || loadingPrice"
      @click="emit('next')"
    >
      <i class="fas fa-arrow-right" />
    </button>
    <button
      v-else
      class="pm-btn pm-btn--buy"
      :disabled="purchasing || isPlayer === null || exceedsCustomCardLimit"
      @click="emit('purchase')"
    >
      <span v-if="purchasing" class="pm-spinner pm-spinner--sm" />
      <i v-else class="fas fa-lock" />
      {{ purchasing ? 'Procesando…' : 'Comprar' }}
    </button>
  </div>
</template>

<style scoped>
.pm-footer {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-top: 1px solid rgba(100, 140, 255, 0.1);
  background: rgba(0, 5, 20, 0.4);
}

.pm-footer__actions {
  display: flex;
  gap: 4px;
  flex-shrink: 0;
}

.pm-footer__spacer { flex: 1; }

/* Resumen compacto en el footer */
.pm-footer__summary {
  display: flex;
  align-items: center;
  gap: 5px;
  flex: 1;
  flex-wrap: wrap;
  min-width: 0;
}
.pm-fs__pill {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 10px;
  color: #8aa4d0;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(100,140,255,0.12);
  border-radius: 99px;
  padding: 2px 7px;
  white-space: nowrap;
}
.pm-fs__pill i { font-size: 8px; opacity: .65; }
.pm-fs__pill--custom {
  color: #90c8ff;
  border-color: rgba(100,180,255,0.2);
  background: rgba(100,180,255,0.06);
}
.pm-fs__pill--custom em {
  font-style: normal;
  font-size: 8.5px;
  font-weight: 700;
  color: #4ab4ff;
  background: rgba(74,180,255,0.15);
  border-radius: 99px;
  padding: 0 3px;
  margin-left: 1px;
}
.pm-fs__sep {
  font-size: 10px;
  color: rgba(100,140,255,0.4);
}
.pm-fs__total {
  font-family: 'Anton', sans-serif;
  font-size: 15px;
  color: #7affba;
  white-space: nowrap;
}
.pm-fs__total small {
  font-family: inherit;
  font-size: 9px;
  color: #4ea87a;
  margin-left: 2px;
}

/* Botón icono (atrás / lobby) */
.pm-icon-btn {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  border: 1.5px solid rgba(100, 140, 255, 0.25);
  background: rgba(255,255,255,0.04);
  color: #7c9dff;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  flex-shrink: 0;
  transition: background 0.15s, border-color 0.15s;
}
.pm-icon-btn:not(:disabled):hover {
  background: rgba(100,140,255,0.12);
  border-color: rgba(100,140,255,0.5);
}
.pm-icon-btn:disabled { opacity: 0.3; cursor: not-allowed; }
.pm-icon-btn--lobby { color: #7c9dff; }

.pm-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  padding: 7px 16px;
  border-radius: 30px;
  border: none;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.4px;
  cursor: pointer;
  flex-shrink: 0;
  transition: filter 0.2s, opacity 0.2s, transform 0.15s;
  white-space: nowrap;
}
.pm-btn:not(:disabled):hover {
  filter: brightness(1.12);
  transform: translateY(-1px);
}
.pm-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
  transform: none;
}

.pm-btn--primary {
  background: linear-gradient(90deg, #4a6fff 0%, #009efd 100%);
  color: #fff;
  padding: 7px 14px;
}

.pm-btn--buy {
  background: linear-gradient(90deg, #2af598 0%, #009efd 100%);
  color: #051830;
  min-width: 110px;
}

/* ── Spinner (usado en el botón comprar) ── */
.pm-spinner {
  display: inline-block;
  width: 18px;
  height: 18px;
  border: 2px solid rgba(255, 255, 255, 0.2);
  border-top-color: #2af598;
  border-radius: 50%;
  animation: pm-spin 0.7s linear infinite;
}
.pm-spinner--sm {
  width: 13px;
  height: 13px;
}
@keyframes pm-spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
