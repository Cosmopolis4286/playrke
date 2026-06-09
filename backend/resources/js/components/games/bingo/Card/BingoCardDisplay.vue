<script setup lang="ts">
/**
 * Visor estático de cartón de bingo.
 * Acepta card_matrix: Record<string, (number|null)[]> y variante.
 * Muestra el cartón con colores por columna; las celdas "ganadoras" se resaltan si se pasa drawnNumbers.
 */
import { computed } from 'vue'

interface ColDef { label: string; min: number; max: number; freeRow: number | null; colorIdx: number }
interface VariantDef { columns: ColDef[]; numRows: number }

const VARIANT_DEFS: Record<string, VariantDef> = {
  BINGO_75: {
    numRows: 5,
    columns: [
      { label: 'B', min:  1, max: 15, freeRow: null, colorIdx: 0 },
      { label: 'I', min: 16, max: 30, freeRow: null, colorIdx: 1 },
      { label: 'N', min: 31, max: 45, freeRow: 2,    colorIdx: 2 },
      { label: 'G', min: 46, max: 60, freeRow: null, colorIdx: 3 },
      { label: 'O', min: 61, max: 75, freeRow: null, colorIdx: 4 },
    ],
  },
  BINGO_90: {
    numRows: 3,
    columns: [
      { label: '1', min:  1, max:  9, freeRow: null, colorIdx: 0 },
      { label: '2', min: 10, max: 19, freeRow: null, colorIdx: 1 },
      { label: '3', min: 20, max: 29, freeRow: null, colorIdx: 2 },
      { label: '4', min: 30, max: 39, freeRow: null, colorIdx: 3 },
      { label: '5', min: 40, max: 49, freeRow: null, colorIdx: 4 },
      { label: '6', min: 50, max: 59, freeRow: null, colorIdx: 0 },
      { label: '7', min: 60, max: 69, freeRow: null, colorIdx: 1 },
      { label: '8', min: 70, max: 79, freeRow: null, colorIdx: 2 },
      { label: '9', min: 80, max: 90, freeRow: null, colorIdx: 3 },
    ],
  },
  BINGO_80: {
    numRows: 4,
    columns: [
      { label: '1', min:  1, max: 20, freeRow: null, colorIdx: 0 },
      { label: '2', min: 21, max: 40, freeRow: null, colorIdx: 1 },
      { label: '3', min: 41, max: 60, freeRow: null, colorIdx: 2 },
      { label: '4', min: 61, max: 80, freeRow: null, colorIdx: 3 },
    ],
  },
  BINGO_30: {
    numRows: 3,
    columns: [
      { label: '1', min:  1, max: 10, freeRow: null, colorIdx: 0 },
      { label: '2', min: 11, max: 20, freeRow: null, colorIdx: 1 },
      { label: '3', min: 21, max: 30, freeRow: null, colorIdx: 2 },
    ],
  },
}

const COL_COLORS = [
  { bg: '#3b82f6', text: '#fff' }, // 0 blue
  { bg: '#8b5cf6', text: '#fff' }, // 1 violet
  { bg: '#ec4899', text: '#fff' }, // 2 pink
  { bg: '#f59e0b', text: '#000' }, // 3 amber
  { bg: '#10b981', text: '#fff' }, // 4 emerald
]

const props = defineProps<{
  cardMatrix: Record<string, (number | null)[]>
  variant?: string
  serial?: string
  isWinner?: boolean
  drawnNumbers?: number[]
}>()

const variantDef = computed<VariantDef>(() => VARIANT_DEFS[props.variant ?? 'BINGO_75'] ?? VARIANT_DEFS.BINGO_75)
const drawnSet   = computed(() => new Set(props.drawnNumbers ?? []))

function isFree(col: ColDef, rowIdx: number): boolean {
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
  text-align: center;
  font-size: 13px;
  font-weight: 600;
  padding: 6px 2px;
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
