<script setup lang="ts">
import type { PurchaseModalStep } from '@/js/types/games/bingo/card/purchase-modal/bingo-purchase-modal'

defineProps<{
  step: PurchaseModalStep
  stepIndex: number
  stepperLabels: string[]
  purchasing: boolean
}>()

defineEmits<{
  close: []
}>()
</script>

<template>
  <div class="pm-header">
    <div class="pm-header__left">
      <i class="fas fa-ticket-alt pm-header__icon" />
      <span class="pm-header__title">Comprar Cartones</span>
    </div>

    <!-- Stepper dinámico -->
    <div class="pm-stepper" v-if="step !== 'loading'">
      <div
        v-for="(lbl, i) in stepperLabels"
        :key="i"
        class="pm-stepper__step"
        :class="{
          'pm-stepper__step--done': i < stepIndex,
          'pm-stepper__step--active': i === stepIndex
        }"
      >
        <div class="pm-stepper__dot">
          <i v-if="i < stepIndex" class="fas fa-check" style="font-size: 8px" />
          <span v-else>{{ i + 1 }}</span>
        </div>
        <span class="pm-stepper__lbl">{{ lbl }}</span>
      </div>
    </div>

    <button
      v-if="step !== 'loading'"
      class="pm-header__close"
      aria-label="Cerrar"
      :disabled="purchasing"
      @click="$emit('close')"
    >
      <i class="fas fa-times" />
    </button>

    <div v-else class="spinner-border spinner-border-sm text-primary" role="status"></div>
  </div>
</template>

<style scoped>
.pm-header {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 16px 8px;
  background: linear-gradient(90deg, rgba(0, 74, 255, 0.15), rgba(0, 158, 253, 0.1));
  border-bottom: 1px solid rgba(100, 140, 255, 0.12);
}

.pm-header__left {
  display: flex;
  align-items: center;
  gap: 8px;
}

.pm-header__icon {
  color: #7c9dff;
  font-size: 15px;
}

.pm-header__title {
  font-size: 14px;
  font-weight: 700;
  color: #c8d8ff;
  letter-spacing: 0.5px;
  white-space: nowrap;
}

.pm-header__close {
  margin-left: auto;
  background: rgba(255, 255, 255, 0.06);
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
  transition:
    background 0.2s,
    color 0.2s;
  flex-shrink: 0;
}
.pm-header__close:hover {
  background: rgba(255, 255, 255, 0.14);
  color: #fff;
}

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
.pm-stepper__step--active {
  opacity: 1;
}
.pm-stepper__step--done {
  opacity: 0.65;
}

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
  transition:
    background 0.25s,
    border-color 0.25s;
}
.pm-stepper__step--active .pm-stepper__dot {
  background: rgba(42, 245, 152, 0.2);
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
.pm-stepper__step--active .pm-stepper__lbl {
  color: #c8d8ff;
}

/* Conector entre steps */
.pm-stepper__step:not(:last-child)::after {
  content: '';
  display: block;
  width: 20px;
  height: 1px;
  background: rgba(100, 140, 255, 0.25);
  margin-left: 5px;
}
</style>
