<script setup lang="ts">
import { ref } from 'vue'
import BingoCustomCardSection from './BingoCustomCardSection.vue'

const props = defineProps<{
  step: string
  currentCardCount: number
  cardCount: number
  canCustomize: boolean
  customizeCard: boolean
  configLoading: boolean
  customCardNumbers: Record<string, (number | null)[]>[]
  manualEdits: boolean[]
  activeCardIndex: number
  variant: string | undefined
  customCardMode?: 'paid' | 'by_range' | 'every_x' | 'free' | null
  customPricePct: number
  maxCustomAllowed?: number
  /** Si true, el jugador puede mover la casilla LIBRE a cualquier celda (solo BINGO_75) */
  allowMoveFreeSpace?: boolean
  manualCardCount: number
  /** True si el jugador personalizó más cartones de los permitidos por el modo 'every_x'. */
  exceedsCustomCardLimit?: boolean
  /** Pregunta al usuario si autocompletar o seguir editando; resuelve true si puede continuar la compra. */
  confirmIncompleteCustomCard: (missingCount: number, autoFill: () => void) => Promise<boolean>
}>()

const emit = defineEmits<{
  increment: []
  decrement: []
  'set-card-count': [value: number]
  'toggle-customize': []
  'update:custom-card-numbers': [value: Record<string, (number | null)[]>[]]
  'update:manual-edits': [value: boolean[]]
  'set-active-card-index': [index: number]
  purchase: []
}>()

const cardSection = ref<InstanceType<typeof BingoCustomCardSection> | null>(null)

function onCardCountInput(e: Event) {
  const val = parseInt((e.target as HTMLInputElement).value, 10)
  if (!isNaN(val)) emit('set-card-count', val)
}

async function handlePurchaseClick(): Promise<void> {
  if (props.exceedsCustomCardLimit) return

  // Solo verificar cartones que el usuario marcó como personalizados (manualEdits[i] = true).
  // Los cartones sin edición manual se envían como al azar y no requieren validación.
  if (props.customizeCard && cardSection.value && props.manualCardCount > 0) {
    const canContinue = await props.confirmIncompleteCustomCard(
      cardSection.value.missingCount,
      cardSection.value.autoFillRemaining,
    )
    if (!canContinue) return
  }
  emit('purchase')
}

defineExpose({ handlePurchaseClick, onCardCountInput })
</script>

<template>
  <Transition name="step-slide" mode="out-in">
    <div v-if="step === 'confirm'" key="confirm" class="pm-step">

      <!-- Badge cartones actuales -->
      <div v-if="currentCardCount > 0" class="pm-card-count-badge">
        <i class="fas fa-ticket-alt" />
        <span>Ya tienes <strong>{{ currentCardCount }}</strong> cartón{{ currentCardCount === 1 ? '' : 'es' }} aquí</span>
      </div>

      <!-- Selector cantidad -->
      <div class="pm-qty-row">
        <button class="pm-qty-btn" :disabled="cardCount <= 1" @click="emit('decrement')">
          <i class="fas fa-minus" />
        </button>
        <input
          type="number"
          class="pm-qty-input"
          :value="cardCount"
          min="1"
          max="40"
          step="1"
          @input="onCardCountInput"
        />
        <button class="pm-qty-btn" :disabled="cardCount >= 40" @click="emit('increment')">
          <i class="fas fa-plus" />
        </button>
      </div>

      <!-- Toggle personalizar cartones -->
      <button
        v-if="canCustomize"
        type="button"
        class="pm-customize-toggle"
        :class="{ 'pm-customize-toggle--on': customizeCard, 'pm-customize-toggle--loading': configLoading }"
        :disabled="configLoading"
        @click="emit('toggle-customize')"
      >
        <i v-if="configLoading" class="fas fa-spinner fa-spin" />
        <i v-else class="fas fa-pen" />
        {{ customizeCard ? 'Personalizando cartones' : 'Elegir mis números' }}
        <i v-if="!configLoading" class="fas fa-chevron-down pm-customize-toggle__chevron" :class="{ 'pm-customize-toggle__chevron--open': customizeCard }" />
      </button>

      <!-- Picker de cartones personalizados -->
      <Transition name="fade">
        <div v-if="customizeCard" class="pm-picker-wrap">
          <BingoCustomCardSection
            ref="cardSection"
            :matrices="customCardNumbers"
            :manual-edits="manualEdits"
            :active-index="activeCardIndex"
            :card-count="cardCount"
            :variant="variant"
            :custom-card-mode="customCardMode"
            :custom-price-pct="customPricePct"
            :max-custom-allowed="maxCustomAllowed"
            :allow-move-free-space="allowMoveFreeSpace"
            @update:matrices="emit('update:custom-card-numbers', $event)"
            @update:manual-edits="emit('update:manual-edits', $event)"
            @update:active-index="emit('set-active-card-index', $event)"
          />
        </div>
      </Transition>

    </div>
  </Transition>
</template>

<style scoped>
.pm-step {
  display: flex;
  flex-direction: column;
  gap: 7px;
}

/* ── Qty row ── */
.pm-qty-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.pm-qty-btn {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  border: 1.5px solid rgba(100, 140, 255, 0.3);
  background: rgba(255, 255, 255, 0.04);
  color: #7c9dff;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  transition: all 0.2s;
}
.pm-qty-btn:not(:disabled):hover {
  background: rgba(100, 140, 255, 0.15);
  border-color: rgba(100, 140, 255, 0.6);
  color: #fff;
}
.pm-qty-btn:disabled {
  opacity: 0.25;
  cursor: not-allowed;
}

.pm-qty-input {
  width: 46px;
  background: rgba(255, 255, 255, 0.05);
  border: 1.5px solid rgba(100, 140, 255, 0.2);
  border-radius: 8px;
  color: #e0e8ff;
  font-size: 18px;
  font-family: 'Anton', sans-serif;
  text-align: center;
  padding: 2px 0;
  outline: none;
  -moz-appearance: textfield;
  appearance: none;
}
.pm-qty-input::-webkit-inner-spin-button,
.pm-qty-input::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
.pm-qty-input:focus {
  border-color: rgba(100, 140, 255, 0.55);
}

/* ── Toggle personalizar cartón ── */
.pm-customize-toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  width: 100%;
  padding: 6px 12px;
  border-radius: 10px;
  border: 1.5px solid rgba(100, 140, 255, 0.25);
  background: rgba(255, 255, 255, 0.03);
  color: #7c9dff;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: border-color 0.2s, background 0.2s, color 0.2s;
}
.pm-customize-toggle:hover {
  border-color: rgba(100, 140, 255, 0.5);
  background: rgba(100, 140, 255, 0.08);
}
.pm-customize-toggle--on {
  border-color: rgba(42, 245, 152, 0.4);
  background: rgba(42, 245, 152, 0.08);
  color: #7affba;
}
.pm-customize-toggle__chevron {
  margin-left: auto;
  font-size: 10px;
  transition: transform 0.2s;
}
.pm-customize-toggle__chevron--open {
  transform: rotate(180deg);
}
.pm-customize-toggle--loading,
.pm-customize-toggle:disabled {
  opacity: 0.55;
  cursor: not-allowed;
  pointer-events: none;
}

/* ═══════════════════════════════════════════════════════
 CARD COUNT BADGE (cartones actuales)
═══════════════════════════════════════════════════════ */
.pm-card-count-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  align-self: center;
  padding: 3px 10px;
  background: rgba(42, 245, 152, 0.08);
  border: 1px solid rgba(42, 245, 152, 0.22);
  border-radius: 30px;
  color: #7affba;
  font-size: 11px;
}

.pm-card-count-badge i {
  font-size: 9px;
  opacity: 0.75;
}

.pm-card-count-badge strong {
  color: #fff;
  font-weight: 700;
}

/* ── Transiciones ── */
.step-slide-enter-active,
.step-slide-leave-active {
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
}
.step-slide-enter-from {
  opacity: 0;
  transform: translateX(18px);
}
.step-slide-leave-to {
  opacity: 0;
  transform: translateX(-18px);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
