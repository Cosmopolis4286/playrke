<script setup lang="ts">
import type { PurchaseModalStep, BingoAvailableCountry } from '@/js/types/games/bingo/card/purchase-modal/bingo-purchase-modal'

defineProps<{
  isPlayer: boolean | null
  step: PurchaseModalStep
  loadingCountries: boolean
  countries: BingoAvailableCountry[]
  selectedCountry: BingoAvailableCountry | null
  cardPrice: number
  loadingPrice: boolean
  currencySymbol: string
}>()

defineEmits<{
  'select-country': [country: BingoAvailableCountry]
}>()
</script>

<template>
  <Transition name="step-slide" mode="out-in">
    <div
      v-if="!isPlayer && isPlayer !== null && step === 'country'"
      key="country"
      class="pm-step"
    >
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
          :class="{
            'pm-country-btn--selected': selectedCountry?.id === country.id
          }"
          @click="$emit('select-country', country)"
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
        <div
          v-if="selectedCountry && !loadingPrice && cardPrice > 0"
          class="pm-price-pill"
        >
          <i class="fas fa-tag mr-1" />
          Cartón:
          <strong>{{ cardPrice.toFixed(2) }} {{ currencySymbol }}</strong>
        </div>
        <div v-else-if="loadingPrice" class="pm-price-pill pm-price-pill--loading">
          <span class="pm-spinner pm-spinner--sm" />Obteniendo precio…
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

.pm-step__label {
  font-size: 11px;
  color: #5578bb;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  margin: 0;
}

/* ── Loading / Empty ── */
.pm-loading,
.pm-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding: 30px 0;
  color: #3a5080;
  font-size: 13px;
}
.pm-empty i {
  font-size: 36px;
  opacity: 0.4;
}

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
  background: rgba(255, 255, 255, 0.03);
  cursor: pointer;
  transition:
    border-color 0.2s,
    background 0.2s,
    transform 0.15s;
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
.pm-country-btn__flag {
  font-size: 22px;
  line-height: 1;
}
.pm-country-btn__name {
  font-size: 11px;
  font-weight: 600;
  color: #c0d0f0;
}
.pm-country-btn__sym {
  font-size: 10px;
  color: #5578bb;
}
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
  background: rgba(42, 245, 152, 0.1);
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
  border-color: rgba(100, 140, 255, 0.2);
  background: rgba(100, 140, 255, 0.05);
}

/* ── Spinner (usado en loading states) ── */
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

/* ── Transición step-slide (compartida con confirm step) ── */
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
