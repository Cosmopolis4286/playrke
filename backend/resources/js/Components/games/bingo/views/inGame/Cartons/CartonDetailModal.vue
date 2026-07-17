<script setup lang="ts">
import { nextTick, ref, watch } from 'vue'
import type { BingoVariantDef } from '@/js/modules/games/bingo/utils/bingoVariants'
import type { BingoCard } from '@/js/types/games/bingo/card/in-game/bingo-card'

const props = defineProps<{
  selectedCard: BingoCard | null
  variantDef: BingoVariantDef
  bingoPattern: string
  isWinner: boolean
  isHot: boolean
  num: (card: BingoCard, colLabel: string, r: number) => number | null
  drawn: (card: BingoCard, colLabel: string, r: number) => boolean
  cellCls: (card: BingoCard, colLabel: string, r: number) => string
  isFreeCell: (col: { freeRow: number | null }, r: number) => boolean
  hits: number
  pct: number
}>()

const emit = defineEmits<{
  close: []
}>()

const modalRef = ref<HTMLElement | null>(null)

// Enfocar el modal al abrirlo para que Escape funcione sin mouse y los lectores de pantalla anuncien el dialog
watch(() => props.selectedCard, (card) => {
  if (card) nextTick(() => modalRef.value?.focus())
})
</script>

<template>
  <Transition name="ctn-modal">
    <div v-if="selectedCard" class="ctn-modal-bg" role="dialog" aria-modal="true" :aria-label="`Detalle cartón ${selectedCard.serialNumber}`" @click.self="emit('close')" @keydown.escape.stop="emit('close')">
      <div class="ctn-modal" ref="modalRef" tabindex="-1">
        <button class="ctn-modal__x" @click="emit('close')"><i class="fas fa-times" /></button>

        <div class="ctn-modal__header">
          <div v-if="isWinner" class="ctn-modal__tag ctn-modal__tag--win">🏆 BINGO</div>
          <div v-else-if="isHot" class="ctn-modal__tag ctn-modal__tag--hot">🔥 CALIENTE</div>
          <div class="ctn-modal__serial">{{ selectedCard.serialNumber }}</div>
          <div v-if="bingoPattern" class="ctn-modal__pattern">
            <i class="fas fa-th-large" /> {{ bingoPattern }}
          </div>
        </div>

        <!-- big card -->
        <div class="ctn-dcard">
          <div class="ctn-dcard__head" :style="{ gridTemplateColumns: `repeat(${variantDef.columns.length}, 1fr)` }">
            <span v-for="col in variantDef.columns" :key="col.label" class="ctn-head__l" :class="`ctn-head__l--col${col.colorIdx}`">{{ col.label }}</span>
          </div>
          <div class="ctn-dcard__body" :style="{ gridTemplateColumns: `repeat(${variantDef.columns.length}, 1fr)` }">
            <template v-for="r in variantDef.numRows" :key="r">
              <div v-for="col in variantDef.columns" :key="`d${col.label}${r}`" class="ctn-dcell" :class="cellCls(selectedCard, col.label, r)">
                <template v-if="isFreeCell(col, r)">
                  <span class="ctn-dfree">★</span>
                </template>
                <template v-else-if="drawn(selectedCard, col.label, r)">
                  <div class="ctn-ball ctn-dball" :class="`ctn-ball--col${col.colorIdx}`">
                    <span>{{ num(selectedCard, col.label, r) }}</span>
                  </div>
                </template>
                <template v-else>
                  <span class="ctn-dnum">{{ num(selectedCard, col.label, r) ?? '' }}</span>
                </template>
              </div>
            </template>
          </div>
        </div>

        <!-- stats row -->
        <div class="ctn-stats">
          <div class="ctn-stat">
            <span class="ctn-stat__v ctn-stat__v--hit">{{ hits }}</span>
            <span class="ctn-stat__l">marcados</span>
          </div>
          <div class="ctn-stat">
            <span class="ctn-stat__v">{{ variantDef.totalCells - hits }}</span>
            <span class="ctn-stat__l">faltan</span>
          </div>
          <div class="ctn-stat">
            <span class="ctn-stat__v ctn-stat__v--pct">{{ pct }}%</span>
            <span class="ctn-stat__l">completado</span>
          </div>
        </div>

        <!-- Progress bar in modal -->
        <div class="ctn-modal__prog">
          <div class="ctn-modal__prog-fill"
            :class="{ 'ctn-prog__fill--hot': isHot, 'ctn-prog__fill--win': isWinner }"
            :style="{ width: `${pct}%` }"
          />
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
/* ═══════════════════════════════════════════
   DETAIL MODAL
═══════════════════════════════════════════ */
.ctn-modal-bg {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(2,4,18,0.84); backdrop-filter: blur(12px);
  display: flex; align-items: center; justify-content: center; padding: 20px;
}
.ctn-modal {
  position: relative; width: 100%; max-width: 400px;
  background: linear-gradient(160deg,#0c1440 0%,#060922 100%);
  border: 1.5px solid rgba(70,120,255,0.24); border-radius: 20px;
  padding: 20px 18px 18px;
  box-shadow: 0 28px 80px rgba(0,0,0,0.75), 0 0 36px rgba(70,120,255,0.07);
}
.ctn-modal__x {
  position: absolute; top: 12px; right: 12px;
  width: 26px; height: 26px; border-radius: 50%;
  border: 1px solid rgba(100,130,255,0.2); background: rgba(255,255,255,0.05);
  color: #5a70a0; font-size: 11px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all 0.13s;
}
.ctn-modal__x:hover { background: rgba(255,255,255,0.1); color: #b0c0e0; }

.ctn-modal__header { display: flex; align-items: center; gap: 8px; margin-bottom: 14px; }
.ctn-modal__tag {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: 10px; font-weight: 900; padding: 2px 10px;
  border-radius: 10px; letter-spacing: 0.5px; flex-shrink: 0;
}
.ctn-modal__tag--win { background: linear-gradient(135deg,#ffd700,#ffaa00); color: #1a1000; }
.ctn-modal__tag--hot { background: linear-gradient(135deg,#ff8c1a,#ffcc00); color: #1a0800; }
.ctn-modal__serial   { font-size: 8px; color: #2a3860; font-family: 'Courier New',monospace; }
.ctn-modal__pattern {
  display: flex; align-items: center; gap: 4px;
  font-size: 9px; font-weight: 700; color: #7b4fff;
  background: rgba(123,79,255,0.08); border: 1px solid rgba(123,79,255,0.18);
  padding: 2px 7px; border-radius: 6px; margin-top: 2px;
  text-transform: uppercase; letter-spacing: 0.4px;
}
.ctn-modal__pattern i { font-size: 8px; opacity: 0.8; }

.ctn-dcard { border-radius: 10px; overflow: hidden; border: 1px solid rgba(70,100,200,0.2); margin-bottom: 14px; }
.ctn-dcard__head { display: grid; }
.ctn-dcard__body { display: grid; background: #eef2ff; }
.ctn-dcell {
  display: flex; align-items: center; justify-content: center;
  aspect-ratio: 1; border: 0.5px solid rgba(140,165,230,0.3);
}
.ctn-dcell.ctn-cell--hit  { background: linear-gradient(135deg,#0e0630,#070932); }
.ctn-dcell.ctn-cell--free { background: linear-gradient(135deg,#0c0424,#080c38); }
.ctn-dfree { font-size: 20px; background: linear-gradient(135deg,#2af598,#009efd); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.ctn-dball { width: 70%; height: 70%; }
.ctn-dnum  { font-size: 14px; font-weight: 700; color: #18204c; }

.ctn-stats {
  display: flex; border-radius: 10px; overflow: hidden;
  border: 1px solid rgba(70,100,200,0.16); margin-bottom: 10px;
}
.ctn-stat {
  flex: 1; display: flex; flex-direction: column; align-items: center;
  padding: 10px 4px; background: rgba(255,255,255,0.022);
  border-right: 1px solid rgba(70,100,200,0.12);
}
.ctn-stat:last-child { border-right: none; }
.ctn-stat__v     { font-size: 22px; font-weight: 800; color: #a0b8f8; line-height: 1; }
.ctn-stat__v--hit { color: #2af598; }
.ctn-stat__v--pct { color: #ffd060; }
.ctn-stat__l     { font-size: 8px; color: #384870; margin-top: 3px; letter-spacing: 0.3px; }

.ctn-modal__prog { height: 4px; background: rgba(255,255,255,0.05); border-radius: 4px; overflow: hidden; }
.ctn-modal__prog-fill { height: 100%; background: linear-gradient(90deg,#4a7fff,#2af598); transition: width 0.5s ease; border-radius: 4px; }

/* ── Reutilizados de CartonCard (colores por columna) ── */
.ctn-head__l {
  display: flex; align-items: center; justify-content: center;
  padding: 8px 0 7px;
  font-family: 'Anton','Impact',sans-serif; font-size: 17px;
  letter-spacing: 1px; font-weight: 900; color: #fff;
  text-shadow: 0 2px 5px rgba(0,0,0,0.3); position: relative;
}
.ctn-head__l::after {
  content: ''; position: absolute; bottom: 0; left: 12%; right: 12%; height: 2px;
  background: rgba(255,255,255,0.22); border-radius: 2px;
}
.ctn-head__l--col0 { background: linear-gradient(160deg, var(--bc0-light), var(--bc0-solid) 55%, var(--bc0-dark)); }
.ctn-head__l--col1 { background: linear-gradient(160deg, var(--bc1-light), var(--bc1-solid) 55%, var(--bc1-dark)); }
.ctn-head__l--col2 { background: linear-gradient(160deg, var(--bc2-light), var(--bc2-solid) 55%, var(--bc2-dark)); }
.ctn-head__l--col3 { background: linear-gradient(160deg, var(--bc3-light), var(--bc3-solid) 55%, var(--bc3-dark)); }
.ctn-head__l--col4 { background: linear-gradient(160deg, var(--bc4-light), var(--bc4-solid) 55%, var(--bc4-dark)); }

.ctn-ball {
  width: 78%; height: 78%; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 3px 10px rgba(0,0,0,0.28), inset 0 -2px 4px rgba(0,0,0,0.18), inset 0 2px 4px rgba(255,255,255,0.35);
}
.ctn-ball--col0 { background: radial-gradient(circle at 36% 30%, var(--bc0-light), var(--bc0-solid) 55%, var(--bc0-dark)); }
.ctn-ball--col1 { background: radial-gradient(circle at 36% 30%, var(--bc1-light), var(--bc1-solid) 55%, var(--bc1-dark)); }
.ctn-ball--col2 { background: radial-gradient(circle at 36% 30%, var(--bc2-light), var(--bc2-solid) 55%, var(--bc2-dark)); }
.ctn-ball--col3 { background: radial-gradient(circle at 36% 30%, var(--bc3-light), var(--bc3-solid) 55%, var(--bc3-dark)); }
.ctn-ball--col4 { background: radial-gradient(circle at 36% 30%, var(--bc4-light), var(--bc4-solid) 55%, var(--bc4-dark)); }
.ctn-ball span {
  font-family: 'Poppins',sans-serif; font-size: 11px; font-weight: 900; color: #fff;
  text-shadow: 0 1px 4px rgba(0,0,0,0.5); line-height: 1;
}

/* ═══════════════════════════════════════════
   TRANSITIONS
═══════════════════════════════════════════ */
.ctn-modal-enter-active { transition: opacity 0.2s ease; }
.ctn-modal-enter-from   { opacity: 0; }
.ctn-modal-leave-active { transition: opacity 0.15s ease; }
.ctn-modal-leave-to     { opacity: 0; }
</style>
