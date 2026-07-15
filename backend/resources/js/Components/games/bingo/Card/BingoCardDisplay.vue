<script setup lang="ts">
/**
 * Visor estático de cartón de bingo.
 * Acepta card_matrix: Record<string, (number|null)[]> y variante.
 * Muestra el cartón con colores por columna; las celdas "ganadoras" se resaltan si se pasa drawnNumbers.
 */
import { computed } from 'vue'
import { getBingoVariantDef, BINGO_COL_COLORS, type BingoVariantDef, type BingoVariantColumn } from '@/js/modules/games/bingo/utils/bingoVariants'

const COL_COLORS = BINGO_COL_COLORS

const props = defineProps<{
  cardMatrix: Record<string, (number | null)[]>
  variant?: string
  serial?: string
  isWinner?: boolean
  drawnNumbers?: number[]
}>()

const variantDef = computed<BingoVariantDef>(() => getBingoVariantDef(props.variant))
const drawnSet   = computed(() => new Set(props.drawnNumbers ?? []))

function isFree(col: BingoVariantColumn, rowIdx: number): boolean {
  return col.freeRow !== null && col.freeRow === rowIdx
}

function getNum(col: string, rowIdx: number): number | null {
  const colArr = props.cardMatrix?.[col]
  return colArr ? (colArr[rowIdx] ?? null) : null
}

function isDrawn(col: string, rowIdx: number): boolean {
  const n = getNum(col, rowIdx)
  return n !== null && drawnSet.value.has(n)
}
</script>

<template>
  <div class="bcd-wrap" :class="{ 'bcd-wrap--winner': isWinner }">
    <!-- winner crown -->
    <div v-if="isWinner" class="bcd-crown">
      <i class="fas fa-trophy"></i> Cartón Ganador
    </div>

    <!-- header -->
    <div class="bcd-head" :style="{ gridTemplateColumns: `repeat(${variantDef.columns.length}, 1fr)` }">
      <div
        v-for="col in variantDef.columns"
        :key="col.label"
        class="bcd-head__cell"
        :style="{ background: COL_COLORS[col.colorIdx].bg, color: COL_COLORS[col.colorIdx].text }"
      >{{ col.label }}</div>
    </div>

    <!-- body -->
    <div class="bcd-body" :style="{ gridTemplateColumns: `repeat(${variantDef.columns.length}, 1fr)` }">
      <template v-for="col in variantDef.columns" :key="col.label">
        <div
          v-for="r in variantDef.numRows"
          :key="`${col.label}${r}`"
          class="bcd-cell"
          :class="{
            'bcd-cell--free':   isFree(col, r - 1),
            'bcd-cell--drawn':  !isFree(col, r - 1) && isDrawn(col.label, r - 1),
            'bcd-cell--empty':  !isFree(col, r - 1) && getNum(col.label, r - 1) === null,
          }"
          :style="isFree(col, r-1) ? { background: COL_COLORS[col.colorIdx].bg + '22', color: COL_COLORS[col.colorIdx].bg } : (isDrawn(col.label, r-1) ? { background: COL_COLORS[col.colorIdx].bg, color: COL_COLORS[col.colorIdx].text } : {})"
        >
          <span v-if="isFree(col, r - 1)" class="bcd-free">★</span>
          <span v-else>{{ getNum(col.label, r - 1) ?? '' }}</span>
        </div>
      </template>
    </div>

    <!-- serial -->
    <div v-if="serial" class="bcd-serial">{{ serial }}</div>
  </div>
</template>

<style scoped>
.bcd-wrap {
  display: inline-flex;
  flex-direction: column;
  border-radius: 10px;
  overflow: hidden;
  border: 2px solid #dee2e6;
  background: #fff;
  box-shadow: 0 2px 8px rgba(0,0,0,.08);
  width: 100%;
  max-width: 280px;
}
.bcd-wrap--winner {
  border-color: #ffd700;
  box-shadow: 0 0 16px rgba(255,215,0,.4);
}
.bcd-crown {
  background: linear-gradient(135deg, #ffd700, #ff9900);
  color: #000;
  text-align: center;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 1px;
  padding: 4px 8px;
}
.bcd-head, .bcd-body {
  display: grid;
}
.bcd-head__cell {
  text-align: center;
  font-weight: 800;
  font-size: 13px;
  padding: 5px 2px;
}
.bcd-cell {
  aspect-ratio: 1 / 1;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 600;
  border: 1px solid #f0f0f0;
  color: #333;
  transition: background .15s;
  min-width: 0;
}
.bcd-cell--drawn { font-weight: 800; }
.bcd-cell--free  { font-size: 16px; }
.bcd-free { font-size: 16px; }
.bcd-serial {
  text-align: center;
  font-size: 10px;
  color: #888;
  padding: 4px;
  background: #f8f9fa;
  border-top: 1px solid #eee;
  letter-spacing: 1px;
}
</style>
