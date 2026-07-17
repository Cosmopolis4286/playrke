<script setup lang="ts">
import { computed } from 'vue'
import type { ViewedCard } from '@js/composables/games/bingo/game/useCardViewer'

const props = defineProps<{
  viewingCard: ViewedCard | null
  viewingCardOwner: string
  drawnSet: Set<number>
  customerLogo?: string | null
}>()

defineEmits<{
  close: []
}>()

// Set de "col-row" con las celdas exactas del patrón que hizo ganar el cartón
// mostrado — permite resaltar la línea/patrón específico, no solo los números marcados.
const winningCellSet = computed(() => {
  const set = new Set<string>()
  for (const p of props.viewingCard?.winningPositions ?? []) set.add(`${p.col}-${p.row}`)
  return set
})
</script>

<template>
  <Transition name="gm-fade">
    <div v-if="viewingCard" class="gm-overlay gm-overlay--stacked" @click.self="$emit('close')">
      <div class="gm-modal cv-modal">

        <div class="cv-head">
          <div class="cv-head__icon"><i class="fas fa-ticket-alt" /></div>
          <div class="cv-head__info">
            <div class="cv-head__title">
              Cartón Ganador
              <span class="cv-head__serial">{{ viewingCard.serialNumber }}</span>
            </div>
            <div class="cv-head__owner">{{ viewingCardOwner }}</div>
          </div>
          <button class="pm-modal__close" @click="$emit('close')"><i class="fas fa-times" /></button>
        </div>

        <div v-if="viewingCard.loading" class="cv-loading">
          <div class="cv-spinner" /> Cargando cartón…
        </div>

        <template v-else-if="viewingCard.cardMatrix && viewingCard.cardMatrix.length">
          <div
            class="cv-card"
            :style="{ '--cv-cols': `repeat(${(viewingCard.colLabels ?? ['B','I','N','G','O']).length}, 1fr)` }"
          >
            <div class="cv-card__header">
              <div v-for="l in (viewingCard.colLabels ?? ['B','I','N','G','O'])" :key="l" class="cv-card__lbl" :class="`cv-card__lbl--${l}`">{{ l }}</div>
            </div>
            <div class="cv-card__grid">
              <div v-for="(row, r) in viewingCard.cardMatrix" :key="`row-${r}`" class="cv-card__row">
                <div
                  v-for="(num, c) in row"
                  :key="`cell-${r}-${c}`"
                  class="cv-cell"
                  :class="{
                    'cv-cell--drawn':   num > 0 && drawnSet.has(num),
                    'cv-cell--free':    num === 0,
                    'cv-cell--winning': winningCellSet.has(`${viewingCard.colLabels?.[c] ?? ''}-${r}`),
                  }"
                >
                  <template v-if="num === 0">
                    <img v-if="customerLogo" :src="customerLogo" class="cv-cell__logo" alt="logo" crossorigin="anonymous" />
                    <span v-else class="cv-cell__free-icon"><i class="fas fa-star" /></span>
                  </template>
                  <template v-else>
                    <span class="cv-cell__num">{{ num }}</span>
                    <span v-if="drawnSet.has(num)" class="cv-cell__mark"><i class="fas fa-check" /></span>
                  </template>
                </div>
              </div>
            </div>
          </div>

          <div class="cv-legend">
            <span v-if="winningCellSet.size > 0" class="cv-legend__item cv-legend__item--winning"><span class="cv-legend__dot" />Patrón ganador</span>
            <span class="cv-legend__item cv-legend__item--drawn"><span class="cv-legend__dot" />Marcado</span>
            <span class="cv-legend__item cv-legend__item--free"><span class="cv-legend__dot" />Libre</span>
            <span class="cv-legend__item cv-legend__item--normal"><span class="cv-legend__dot" />Sin marcar</span>
          </div>
        </template>

        <div v-else class="pm-empty">
          <i class="fas fa-exclamation-triangle" />
          <span>No se pudo cargar el cartón.</span>
        </div>

        <div class="pm-modal__foot">
          <button class="pm-close-btn" @click="$emit('close')">
            <i class="fas fa-arrow-left" /> Volver
          </button>
        </div>

      </div>
    </div>
  </Transition>
</template>

<style scoped>
.gm-overlay {
  position: fixed; inset: 0; z-index: 1200;
  background: rgba(4,6,20,0.82); backdrop-filter: blur(10px);
  display: flex; align-items: center; justify-content: center; padding: 16px;
}
.gm-overlay--stacked { z-index: 1300; }

.gm-modal {
  background: linear-gradient(160deg, rgba(11,18,48,0.98) 0%, rgba(6,10,30,0.98) 100%);
  border: 1px solid rgba(80,120,255,0.18); border-radius: 22px;
  box-shadow: 0 24px 80px rgba(0,0,0,0.7), 0 1px 0 rgba(255,255,255,0.04) inset;
  width: 100%; max-width: 460px; overflow: hidden; position: relative;
  max-height: 90vh; display: flex; flex-direction: column;
}

.pm-modal__close {
  width: 30px; height: 30px; border-radius: 50%; background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.08); color: #5a7acc; font-size: 12px;
  display: flex; align-items: center; justify-content: center; cursor: pointer;
  transition: background 0.2s, color 0.2s; flex-shrink: 0;
}
.pm-modal__close:hover { background: rgba(255,255,255,0.1); color: #a0c0ff; }

.pm-modal__foot { padding: 12px 20px; border-top: 1px solid rgba(80,120,255,0.1); }
.pm-close-btn {
  width: 100%; padding: 9px; border-radius: 10px;
  background: rgba(100,140,255,0.1); border: 1px solid rgba(100,140,255,0.2);
  color: #7c9dff; font-size: 12px; font-weight: 700; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 6px;
  transition: background 0.2s, color 0.2s;
}
.pm-close-btn:hover { background: rgba(100,140,255,0.2); color: #b0c8ff; }

.pm-empty { display: flex; align-items: center; gap: 8px; padding: 20px 0; color: #3a5080; font-size: 12px; }
.pm-empty i { font-size: 14px; }

/* ── Card Viewer modal ── */
.cv-modal { max-width: 380px; }

.cv-head {
  display: flex; align-items: center; gap: 12px;
  padding: 16px 20px 12px; border-bottom: 1px solid rgba(80,120,255,0.1);
}
.cv-head__icon {
  width: 36px; height: 36px; border-radius: 10px;
  background: rgba(100,140,255,0.1); border: 1px solid rgba(100,140,255,0.2);
  display: flex; align-items: center; justify-content: center; font-size: 14px; color: #7c9dff;
  flex-shrink: 0;
}
.cv-head__info { flex: 1; min-width: 0; }
.cv-head__title { font-size: 13px; font-weight: 800; color: #e0eaff; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.cv-head__serial { font-size: 10px; color: #4a68a8; font-family: 'Courier New', monospace; background: rgba(80,120,255,0.08); border: 1px solid rgba(80,120,255,0.12); border-radius: 5px; padding: 1px 6px; }
.cv-head__owner { font-size: 11px; color: #5a7acc; margin-top: 2px; }

.cv-loading { display: flex; align-items: center; gap: 10px; padding: 30px 20px; color: #5a7acc; font-size: 12px; }
.cv-spinner { width: 16px; height: 16px; border-radius: 50%; border: 2px solid rgba(80,120,255,0.2); border-top-color: #7c9dff; animation: cv-spin 0.7s linear infinite; }
@keyframes cv-spin { to { transform: rotate(360deg) } }

.cv-card { padding: 16px 20px; }
.cv-card__header { display: grid; grid-template-columns: var(--cv-cols, repeat(5, 1fr)); gap: 4px; margin-bottom: 4px; }
.cv-card__lbl {
  display: flex; align-items: center; justify-content: center;
  height: 28px; border-radius: 6px; font-size: 13px; font-weight: 900; letter-spacing: 1px;
}
.cv-card__lbl--B { background: rgba(40,140,255,0.15); color: #78c8ff; }
.cv-card__lbl--I { background: rgba(180,80,255,0.15); color: #d08cff; }
.cv-card__lbl--N { background: rgba(42,245,152,0.12); color: #50f0a0; }
.cv-card__lbl--G { background: rgba(255,200,0,0.12); color: #ffdc50; }
.cv-card__lbl--O { background: rgba(255,100,50,0.12); color: #ff9650; }

.cv-card__grid { display: flex; flex-direction: column; gap: 4px; }
.cv-card__row { display: grid; grid-template-columns: var(--cv-cols, repeat(5, 1fr)); gap: 4px; }
.cv-cell {
  display: flex; align-items: center; justify-content: center;
  height: 46px; border-radius: 8px; position: relative;
  background: rgba(255,255,255,0.035); border: 1px solid rgba(80,120,255,0.1);
  transition: all 0.25s ease;
}
.cv-cell--drawn { background: rgba(42,245,152,0.12); border-color: rgba(42,245,152,0.35); box-shadow: 0 0 10px rgba(42,245,152,0.1); }
.cv-cell--free  { background: rgba(255,200,0,0.07); border-color: rgba(255,200,0,0.2); }
/* Patrón ganador: prevalece sobre --drawn para que se note cuál línea/celda hizo BINGO */
.cv-cell--winning {
  background: rgba(255,200,0,0.16); border-color: rgba(255,200,0,0.55);
  box-shadow: 0 0 14px rgba(255,200,0,0.25); animation: cv-winning-pulse 1.6s ease-in-out infinite;
}
@keyframes cv-winning-pulse {
  0%,100% { box-shadow: 0 0 10px rgba(255,200,0,0.18); }
  50%     { box-shadow: 0 0 18px rgba(255,200,0,0.4); }
}
.cv-cell__num { font-size: 14px; font-weight: 700; color: #c8d8ff; font-family: 'Courier New', monospace; }
.cv-cell__mark {
  position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
  background: rgba(42,245,152,0.12); border-radius: 8px; font-size: 16px; color: #2af598;
}
.cv-cell--winning .cv-cell__mark { background: rgba(255,200,0,0.14); color: #ffd700; }
.cv-cell__logo { width: 28px; height: 28px; object-fit: contain; opacity: 0.6; }
.cv-cell__free-icon { font-size: 14px; color: #ffc840; }

.cv-legend { display: flex; gap: 14px; padding: 0 20px 12px; flex-wrap: wrap; }
.cv-legend__item { display: flex; align-items: center; gap: 5px; font-size: 10px; color: #4a68a8; }
.cv-legend__dot { width: 10px; height: 10px; border-radius: 3px; display: inline-block; }
.cv-legend__item--winning .cv-legend__dot { background: rgba(255,200,0,0.35); border: 1px solid rgba(255,200,0,0.6); }
.cv-legend__item--drawn .cv-legend__dot { background: rgba(42,245,152,0.3); border: 1px solid rgba(42,245,152,0.5); }
.cv-legend__item--free  .cv-legend__dot { background: rgba(255,200,0,0.2); border: 1px solid rgba(255,200,0,0.4); }
.cv-legend__item--normal .cv-legend__dot { background: rgba(255,255,255,0.06); border: 1px solid rgba(80,120,255,0.15); }

/* ── Fade transition ── */
.gm-fade-enter-active, .gm-fade-leave-active { transition: opacity 0.25s ease; }
.gm-fade-enter-from, .gm-fade-leave-to { opacity: 0; }

/* ── Responsive ── */
@media (max-width: 480px) {
  .gm-modal { border-radius: 16px; }
  .cv-cell { height: 38px; }
  .cv-cell__num { font-size: 12px; }
}
</style>
