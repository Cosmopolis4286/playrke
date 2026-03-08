<script setup lang="ts">
/**
 * BingoPurchaseModal
 *
 * Modal de compra de cartones de bingo desde game-rooms (2 pasos):
 *  - Step 1 "country" → seleccionar país y ver precio
 *  - Step 2 "confirm" → ajustar cantidad y confirmar compra
 *
 * El jugador ya está autenticado — no se requieren sus datos.
 */
import { computed } from 'vue'
import type { BingoGameRoom } from '@/js/types/games/bingo/room/game-rooms/bingo-game-room'
import type {
  BingoAvailableCountry,
  PurchaseModalStep,
} from '@/js/types/games/bingo/card/purchase-modal/bingo-purchase-modal'

// ─────────────────────────────────────────────────────────────────────────────
// Props
// ─────────────────────────────────────────────────────────────────────────────

const props = defineProps<{
  isOpen:           boolean
  step:             PurchaseModalStep
  selectedRoom:     BingoGameRoom | null
  countries:        BingoAvailableCountry[]
  loadingCountries: boolean
  selectedCountry:  BingoAvailableCountry | null
  cardPrice:        number
  currencySymbol:   string
  loadingPrice:     boolean
  numCtns:          number
  totalAmount:      number
  canAdvance:       boolean
  purchasing:       boolean
  exchangeRate:     number
}>()

// ─────────────────────────────────────────────────────────────────────────────
// Emits
// ─────────────────────────────────────────────────────────────────────────────

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'select-country', country: BingoAvailableCountry): void
  (e: 'next'): void
  (e: 'prev'): void
  (e: 'increment'): void
  (e: 'decrement'): void
  (e: 'set-ctns', value: number): void
  (e: 'purchase'): void
}>()

// ─────────────────────────────────────────────────────────────────────────────
// Computed
// ─────────────────────────────────────────────────────────────────────────────

const stepIndex = computed(() => props.step === 'country' ? 0 : 1)

const prizeFormatted = computed(() => {
  if (!props.selectedRoom) return '0.00'
  return (props.selectedRoom.prize_pool * props.exchangeRate).toFixed(2)
})

// ─────────────────────────────────────────────────────────────────────────────
// Handlers
// ─────────────────────────────────────────────────────────────────────────────

function onCtnsInput(e: Event) {
  const val = parseInt((e.target as HTMLInputElement).value, 10)
  if (!isNaN(val)) emit('set-ctns', val)
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="isOpen" class="pm-backdrop" @click.self="emit('close')">
        <div class="pm-dialog" role="dialog" aria-modal="true">

          <!-- ── Glow decorativo ── -->
          <div class="pm-dialog__glow" />

          <!-- ═══════════════════ HEADER ═══════════════════ -->
          <div class="pm-header">
            <div class="pm-header__left">
              <i class="fas fa-ticket-alt pm-header__icon" />
              <span class="pm-header__title">Comprar Cartones</span>
            </div>

            <!-- Stepper 2 pasos -->
            <div class="pm-stepper">
              <div
                v-for="(lbl, i) in ['País', 'Confirmar']"
                :key="i"
                class="pm-stepper__step"
                :class="{
                  'pm-stepper__step--done':   i < stepIndex,
                  'pm-stepper__step--active': i === stepIndex,
                }"
              >
                <div class="pm-stepper__dot">
                  <i v-if="i < stepIndex" class="fas fa-check" style="font-size:8px;" />
                  <span v-else>{{ i + 1 }}</span>
                </div>
                <span class="pm-stepper__lbl">{{ lbl }}</span>
              </div>
            </div>

            <button class="pm-header__close" aria-label="Cerrar" @click="emit('close')">
              <i class="fas fa-times" />
            </button>
          </div>

          <!-- ── Banner sala ── -->
          <div v-if="selectedRoom" class="pm-room-banner">
            <img :src="`/img/games/bingo/estelar_fd.png`" alt="sala" class="pm-room-banner__img" />
            <div class="pm-room-banner__info">
              <span class="pm-room-banner__mode">
                <i class="fas fa-gamepad mr-1" />Cartón Lleno
              </span>
              <div class="pm-room-banner__prize">
                <span class="pm-room-banner__prize-num">{{ prizeFormatted }}</span>
                <span class="pm-room-banner__prize-sym">
                  {{ selectedCountry?.currency_symbol ?? currencySymbol ?? 'USD' }}
                </span>
              </div>
            </div>
          </div>

          <!-- ═══════════════════ BODY ═══════════════════ -->
          <div class="pm-body">

            <!-- ── STEP 1: País ── -->
            <Transition name="step-slide" mode="out-in">
              <div v-if="step === 'country'" key="country" class="pm-step">
                <p class="pm-step__label">Selecciona el país del jugador</p>

                <div v-if="loadingCountries" class="pm-loading">
                  <span class="pm-spinner" />
                  <span>Cargando países…</span>
                </div>

                <div v-else-if="countries.length === 0" class="pm-empty">
                  <i class="fas fa-globe-americas" />
                  <span>Sin países disponibles para esta sala</span>
                </div>

                <div v-else class="pm-country-grid">
                  <button
                    v-for="country in countries"
                    :key="country.id"
                    class="pm-country-btn"
                    :class="{ 'pm-country-btn--selected': selectedCountry?.id === country.id }"
                    @click="emit('select-country', country)"
                  >
                    <span class="pm-country-btn__flag">{{ country.flag ?? '🌐' }}</span>
                    <span class="pm-country-btn__name">{{ country.name }}</span>
                    <span class="pm-country-btn__sym">{{ country.currency_symbol }}</span>
                    <i
                      v-if="selectedCountry?.id === country.id"
                      class="fas fa-check-circle pm-country-btn__check"
                    />
                  </button>
                </div>

                <!-- Precio obtenido -->
                <Transition name="fade">
                  <div v-if="selectedCountry && !loadingPrice && cardPrice > 0" class="pm-price-pill">
                    <i class="fas fa-tag mr-1" />
                    Cartón: <strong>{{ cardPrice.toFixed(2) }} {{ currencySymbol }}</strong>
                  </div>
                  <div v-else-if="loadingPrice" class="pm-price-pill pm-price-pill--loading">
                    <span class="pm-spinner pm-spinner--sm" />Obteniendo precio…
                  </div>
                </Transition>
              </div>
            </Transition>

            <!-- ── STEP 2: Confirmar ── -->
            <Transition name="step-slide" mode="out-in">
              <div v-if="step === 'confirm'" key="confirm" class="pm-step">
                <p class="pm-step__label">Cantidad de cartones</p>

                <!-- Selector cantidad -->
                <div class="pm-qty-row">
                  <button
                    class="pm-qty-btn"
                    :disabled="numCtns <= 1"
                    @click="emit('decrement')"
                  >
                    <i class="fas fa-minus" />
                  </button>
                  <input
                    type="number"
                    class="pm-qty-input"
                    :value="numCtns"
                    min="1"
                    max="40"
                    step="1"
                    @input="onCtnsInput"
                  />
                  <button
                    class="pm-qty-btn"
                    :disabled="numCtns >= 40"
                    @click="emit('increment')"
                  >
                    <i class="fas fa-plus" />
                  </button>
                </div>

                <!-- Resumen -->
                <div class="pm-summary">
                  <div class="pm-summary__row">
                    <span>País</span>
                    <span>{{ selectedCountry?.name ?? '—' }}</span>
                  </div>
                  <div class="pm-summary__row">
                    <span>Precio c/u</span>
                    <span>{{ cardPrice.toFixed(2) }} {{ currencySymbol }}</span>
                  </div>
                  <div class="pm-summary__row">
                    <span>Cartones</span>
                    <span>× {{ numCtns }}</span>
                  </div>
                  <div class="pm-summary__row pm-summary__row--total">
                    <span>Total</span>
                    <span class="pm-summary__total-amount">
                      {{ totalAmount.toFixed(2) }}
                      <small>{{ currencySymbol }}</small>
                    </span>
                  </div>
                </div>
              </div>
            </Transition>

          </div>

          <!-- ═══════════════════ FOOTER ═══════════════════ -->
          <div class="pm-footer">
            <button
              v-if="step === 'confirm'"
              class="pm-btn pm-btn--secondary"
              :disabled="purchasing"
              @click="emit('prev')"
            >
              <i class="fas fa-arrow-left mr-1" />Atrás
            </button>

            <div class="pm-footer__spacer" />

            <button
              v-if="step === 'country'"
              class="pm-btn pm-btn--primary"
              :disabled="!canAdvance || loadingPrice"
              @click="emit('next')"
            >
              Siguiente<i class="fas fa-arrow-right ml-1" />
            </button>

            <button
              v-else
              class="pm-btn pm-btn--buy"
              :disabled="purchasing"
              @click="emit('purchase')"
            >
              <span v-if="purchasing" class="pm-spinner pm-spinner--sm mr-1" />
              <i v-else class="fas fa-lock mr-1" />
              {{ purchasing ? 'Procesando…' : 'Confirmar Compra' }}
            </button>
          </div>

        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
/* ═══════════════════════════════════════════════════════
   BACKDROP
═══════════════════════════════════════════════════════ */
.pm-backdrop {
  position: fixed;
  inset: 0;
  z-index: 1050;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(2, 5, 20, 0.75);
  backdrop-filter: blur(6px);
  padding: 16px;
}

/* ═══════════════════════════════════════════════════════
   DIALOG
═══════════════════════════════════════════════════════ */
.pm-dialog {
  position: relative;
  width: 100%;
  max-width: 480px;
  border-radius: 22px;
  overflow: hidden;
  background: linear-gradient(160deg, #0d1633 0%, #080f26 100%);
  border: 1px solid rgba(100, 140, 255, 0.20);
  box-shadow:
    0 24px 80px rgba(0, 0, 0, 0.75),
    0 0 0 1px rgba(80, 120, 255, 0.08) inset;
  display: flex;
  flex-direction: column;
  max-height: 90vh;
}

.pm-dialog__glow {
  position: absolute;
  top: -60px;
  left: 50%;
  transform: translateX(-50%);
  width: 260px;
  height: 120px;
  background: radial-gradient(ellipse, rgba(42, 245, 152, 0.18) 0%, transparent 70%);
  pointer-events: none;
  z-index: 0;
}

/* ═══════════════════════════════════════════════════════
   HEADER
═══════════════════════════════════════════════════════ */
.pm-header {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 20px 12px;
  background: linear-gradient(90deg, rgba(0,74,255,0.15), rgba(0,158,253,0.10));
  border-bottom: 1px solid rgba(100, 140, 255, 0.12);
}

.pm-header__left {
  display: flex;
  align-items: center;
  gap: 8px;
}

.pm-header__icon { color: #7c9dff; font-size: 15px; }

.pm-header__title {
  font-size: 14px;
  font-weight: 700;
  color: #c8d8ff;
  letter-spacing: 0.5px;
  white-space: nowrap;
}

.pm-header__close {
  margin-left: auto;
  background: rgba(255,255,255,0.06);
  border: none;
  color: #7c9dff;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  transition: background 0.2s, color 0.2s;
  flex-shrink: 0;
}
.pm-header__close:hover { background: rgba(255,255,255,0.14); color: #fff; }

/* ── Stepper ── */
.pm-stepper {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
  justify-content: center;
}

.pm-stepper__step {
  display: flex;
  align-items: center;
  gap: 5px;
  opacity: 0.35;
  transition: opacity 0.25s;
}
.pm-stepper__step--active { opacity: 1; }
.pm-stepper__step--done   { opacity: 0.65; }

.pm-stepper__dot {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: rgba(100, 140, 255, 0.15);
  border: 1.5px solid rgba(100, 140, 255, 0.35);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 9px;
  font-weight: 700;
  color: #7c9dff;
  transition: background 0.25s, border-color 0.25s;
}
.pm-stepper__step--active .pm-stepper__dot {
  background: rgba(42, 245, 152, 0.20);
  border-color: #2af598;
  color: #2af598;
}
.pm-stepper__step--done .pm-stepper__dot {
  background: rgba(42, 245, 152, 0.12);
  border-color: rgba(42, 245, 152, 0.4);
  color: #2af598;
}

.pm-stepper__lbl {
  font-size: 10px;
  font-weight: 600;
  color: #7c9dff;
  letter-spacing: 0.4px;
  white-space: nowrap;
}
.pm-stepper__step--active .pm-stepper__lbl { color: #c8d8ff; }

/* Conector entre steps */
.pm-stepper__step:not(:last-child)::after {
  content: '';
  display: block;
  width: 20px;
  height: 1px;
  background: rgba(100, 140, 255, 0.25);
  margin-left: 5px;
}

/* ═══════════════════════════════════════════════════════
   ROOM BANNER
═══════════════════════════════════════════════════════ */
.pm-room-banner {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 20px;
  background: rgba(0, 10, 40, 0.5);
  border-bottom: 1px solid rgba(100, 140, 255, 0.08);
}

.pm-room-banner__img {
  width: 52px;
  height: 36px;
  object-fit: cover;
  border-radius: 8px;
  opacity: 0.85;
}

.pm-room-banner__info { display: flex; flex-direction: column; gap: 2px; }

.pm-room-banner__mode {
  font-size: 10px;
  color: #5578bb;
  text-transform: uppercase;
  letter-spacing: 0.6px;
}

.pm-room-banner__prize { display: flex; align-items: baseline; gap: 4px; }

.pm-room-banner__prize-num {
  font-family: 'Anton', sans-serif;
  font-size: 20px;
  background: linear-gradient(135deg, #ffe566, #ffab00);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.pm-room-banner__prize-sym {
  font-size: 11px;
  font-weight: 700;
  color: #ffd060;
  -webkit-text-fill-color: #ffd060;
}

/* ═══════════════════════════════════════════════════════
   BODY
═══════════════════════════════════════════════════════ */
.pm-body {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
  scrollbar-width: thin;
  scrollbar-color: #7b4fff transparent;
}
.pm-body::-webkit-scrollbar       { width: 4px; }
.pm-body::-webkit-scrollbar-track { background: transparent; }
.pm-body::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, #7b4fff, #009efd);
  border-radius: 10px;
}

.pm-step { display: flex; flex-direction: column; gap: 14px; }

.pm-step__label {
  font-size: 12px;
  color: #5578bb;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  margin: 0;
}

/* ── Loading / Empty ── */
.pm-loading, .pm-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding: 30px 0;
  color: #3a5080;
  font-size: 13px;
}
.pm-empty i { font-size: 36px; opacity: 0.4; }

/* ── Country grid ── */
.pm-country-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 8px;
}

.pm-country-btn {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 3px;
  padding: 10px 8px;
  border-radius: 12px;
  border: 1.5px solid rgba(100, 140, 255, 0.18);
  background: rgba(255,255,255,0.03);
  cursor: pointer;
  transition: border-color 0.2s, background 0.2s, transform 0.15s;
  text-align: center;
}
.pm-country-btn:hover {
  border-color: rgba(100, 140, 255, 0.45);
  background: rgba(100, 140, 255, 0.08);
  transform: translateY(-1px);
}
.pm-country-btn--selected {
  border-color: #2af598 !important;
  background: rgba(42, 245, 152, 0.08) !important;
}
.pm-country-btn__flag { font-size: 22px; line-height: 1; }
.pm-country-btn__name { font-size: 11px; font-weight: 600; color: #c0d0f0; }
.pm-country-btn__sym  { font-size: 10px; color: #5578bb; }
.pm-country-btn__check {
  position: absolute;
  top: 6px;
  right: 6px;
  color: #2af598;
  font-size: 11px;
}

/* ── Price pill ── */
.pm-price-pill {
  display: flex;
  align-items: center;
  gap: 6px;
  background: rgba(42, 245, 152, 0.10);
  border: 1px solid rgba(42, 245, 152, 0.25);
  border-radius: 30px;
  padding: 6px 14px;
  font-size: 13px;
  color: #7affba;
  width: fit-content;
  margin: 0 auto;
}
.pm-price-pill--loading {
  color: #5578bb;
  border-color: rgba(100,140,255,0.2);
  background: rgba(100,140,255,0.05);
}

/* ── Qty row ── */
.pm-qty-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 14px;
}

.pm-qty-btn {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 1.5px solid rgba(100, 140, 255, 0.30);
  background: rgba(255,255,255,0.04);
  color: #7c9dff;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  transition: all 0.2s;
}
.pm-qty-btn:not(:disabled):hover {
  background: rgba(100, 140, 255, 0.15);
  border-color: rgba(100, 140, 255, 0.6);
  color: #fff;
}
.pm-qty-btn:disabled { opacity: 0.25; cursor: not-allowed; }

.pm-qty-input {
  width: 70px;
  background: rgba(255,255,255,0.05);
  border: 1.5px solid rgba(100, 140, 255, 0.20);
  border-radius: 12px;
  color: #e0e8ff;
  font-size: 28px;
  font-family: 'Anton', sans-serif;
  text-align: center;
  padding: 4px 0;
  outline: none;
  -moz-appearance: textfield;
  appearance: none;
}
.pm-qty-input::-webkit-inner-spin-button,
.pm-qty-input::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
.pm-qty-input:focus { border-color: rgba(100, 140, 255, 0.55); }

/* ── Summary ── */
.pm-summary {
  background: rgba(0, 10, 40, 0.5);
  border: 1px solid rgba(100, 140, 255, 0.12);
  border-radius: 14px;
  overflow: hidden;
}

.pm-summary__row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 9px 14px;
  font-size: 12px;
  border-bottom: 1px solid rgba(100, 140, 255, 0.07);
  color: #8aa4d0;
}
.pm-summary__row:last-child { border-bottom: none; }
.pm-summary__row span:last-child { color: #c0d0f0; font-weight: 600; }

.pm-summary__row--total {
  background: rgba(0, 74, 255, 0.08);
  padding: 12px 14px;
}
.pm-summary__row--total span:first-child { color: #7c9dff; font-weight: 700; font-size: 13px; }

.pm-summary__total-amount {
  font-family: 'Anton', sans-serif !important;
  font-size: 22px !important;
  color: #7affba !important;
  display: flex;
  align-items: baseline;
  gap: 4px;
}
.pm-summary__total-amount small {
  font-family: inherit;
  font-size: 12px !important;
  color: #4ea87a;
}

/* ═══════════════════════════════════════════════════════
   FOOTER
═══════════════════════════════════════════════════════ */
.pm-footer {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 20px;
  border-top: 1px solid rgba(100, 140, 255, 0.10);
  background: rgba(0, 5, 20, 0.4);
}

.pm-footer__spacer { flex: 1; }

.pm-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 10px 20px;
  border-radius: 30px;
  border: none;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.6px;
  cursor: pointer;
  transition: filter 0.2s, opacity 0.2s, transform 0.15s;
  white-space: nowrap;
}
.pm-btn:not(:disabled):hover { filter: brightness(1.12); transform: translateY(-1px); }
.pm-btn:disabled { opacity: 0.45; cursor: not-allowed; transform: none; }

.pm-btn--secondary {
  background: rgba(100, 140, 255, 0.12);
  color: #7c9dff;
  border: 1px solid rgba(100, 140, 255, 0.25);
}

.pm-btn--primary {
  background: linear-gradient(90deg, #4a6fff 0%, #009efd 100%);
  color: #fff;
}

.pm-btn--buy {
  background: linear-gradient(90deg, #2af598 0%, #009efd 100%);
  color: #051830;
  min-width: 160px;
}

/* ═══════════════════════════════════════════════════════
   SPINNER
═══════════════════════════════════════════════════════ */
.pm-spinner {
  display: inline-block;
  width: 18px;
  height: 18px;
  border: 2px solid rgba(255,255,255,0.2);
  border-top-color: #2af598;
  border-radius: 50%;
  animation: pm-spin 0.7s linear infinite;
}
.pm-spinner--sm { width: 13px; height: 13px; }

@keyframes pm-spin { to { transform: rotate(360deg); } }

/* ═══════════════════════════════════════════════════════
   TRANSICIONES
═══════════════════════════════════════════════════════ */
.modal-fade-enter-active,
.modal-fade-leave-active { transition: opacity 0.25s ease, transform 0.25s ease; }
.modal-fade-enter-from,
.modal-fade-leave-to     { opacity: 0; transform: scale(0.96); }

.step-slide-enter-active,
.step-slide-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.step-slide-enter-from   { opacity: 0; transform: translateX(18px); }
.step-slide-leave-to     { opacity: 0; transform: translateX(-18px); }

.fade-enter-active,
.fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from,
.fade-leave-to     { opacity: 0; }
</style>
