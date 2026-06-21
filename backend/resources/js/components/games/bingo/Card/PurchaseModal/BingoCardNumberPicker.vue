<script setup lang="ts">
/**
 * Selector interactivo de números para personalizar un cartón de bingo.
 *
 * El usuario hace clic en cada casilla y elige un número del rango permitido
 * para esa columna; los números ya usados en otras columnas quedan
 * deshabilitados para evitar duplicados. Respeta la casilla libre fija
 * (BINGO_75) y, para BINGO_90, exige exactamente 5 números por fila.
 */
import { computed, ref } from 'vue'
import { getBingoVariantDef, BINGO_COL_COLORS, type BingoVariantColumn } from '@/js/modules/games/bingo/utils/bingoVariants'

type Matrix = Record<string, (number | null)[]>

const props = defineProps<{
  variant?: string | null
}>()

const modelValue = defineModel<Matrix>({ required: true })

const variantDef = computed(() => getBingoVariantDef(props.variant))
const isBingo90 = computed(() => (props.variant ?? 'BINGO_75') === 'BINGO_90')

// ── Casilla actualmente abierta para elegir número ──
const openCell = ref<{ col: string; row: number } | null>(null)

function isFreeCell(col: BingoVariantColumn, row: number): boolean {
  return col.freeRow !== null && col.freeRow === row
}

function valueAt(col: string, row: number): number | null {
  return modelValue.value[col]?.[row] ?? null
}

/** Números ya usados en cualquier otra casilla del cartón (para deshabilitar duplicados). */
const usedNumbers = computed(() => {
  const set = new Set<number>()
  for (const col of variantDef.value.columns) {
    for (const v of modelValue.value[col.label] ?? []) {
      if (v !== null) set.add(v)
    }
  }
  return set
})

/** Cuántas casillas no-null tiene cada fila (solo relevante para BINGO_90: máx. 5). */
const rowFillCount = computed(() => {
  const counts: number[] = Array(variantDef.value.numRows).fill(0)
  for (const col of variantDef.value.columns) {
    const arr = modelValue.value[col.label] ?? []
    arr.forEach((v, row) => { if (v !== null) counts[row]++ })
  }
  return counts
})

function rowIsFull(row: number): boolean {
  return isBingo90.value && rowFillCount.value[row] >= 5
}

/** Opciones disponibles para la casilla abierta: dentro de rango, sin duplicar, fila no llena. */
const cellOptions = computed<number[]>(() => {
  if (!openCell.value) return []
  const col = variantDef.value.columns.find(c => c.label === openCell.value!.col)
  if (!col) return []

  const current = valueAt(openCell.value.col, openCell.value.row)
  const options: number[] = []
  for (let n = col.min; n <= col.max; n++) {
    if (n === current || !usedNumbers.value.has(n)) options.push(n)
  }
  return options
})

function toggleCell(col: BingoVariantColumn, row: number): void {
  if (isFreeCell(col, row)) return

  // BINGO_90: si la celda está vacía y la fila ya tiene 5 números, no permite abrir
  if (isBingo90.value && valueAt(col.label, row) === null && rowIsFull(row)) return

  const same = openCell.value?.col === col.label && openCell.value?.row === row
  openCell.value = same ? null : { col: col.label, row }
}

function pickNumber(n: number): void {
  if (!openCell.value) return
  const { col, row } = openCell.value
  const arr = [...(modelValue.value[col] ?? [])]
  arr[row] = n
  modelValue.value = { ...modelValue.value, [col]: arr }
  openCell.value = null
}

function clearCell(): void {
  if (!openCell.value) return
  const { col, row } = openCell.value
  const arr = [...(modelValue.value[col] ?? [])]
  arr[row] = null
  modelValue.value = { ...modelValue.value, [col]: arr }
  openCell.value = null
}

/** Casillas que aún faltan por completar (excluye libres y, en BINGO_90, las que deben quedar vacías). */
const missingCount = computed(() => {
  let missing = 0
  for (const col of variantDef.value.columns) {
    for (let row = 0; row < variantDef.value.numRows; row++) {
      if (isFreeCell(col, row)) continue
      if (valueAt(col.label, row) === null) {
        if (isBingo90.value && rowIsFull(row)) continue // fila ya completa, esta celda queda vacía a propósito
        missing++
      }
    }
  }
  return missing
})

const isComplete = computed(() => missingCount.value === 0)

/** Autocompleta únicamente las casillas vacías con números válidos al azar. */
function autoFillRemaining(): void {
  const next: Matrix = {}
  for (const col of variantDef.value.columns) {
    next[col.label] = [...(modelValue.value[col.label] ?? Array(variantDef.value.numRows).fill(null))]
  }

  const used = new Set(usedNumbers.value)
  const rowCounts = [...rowFillCount.value] // copia local mutable (rowFillCount es computed)

  for (const col of variantDef.value.columns) {
    for (let row = 0; row < variantDef.value.numRows; row++) {
      if (isFreeCell(col, row)) continue
      if (next[col.label][row] !== null) continue
      if (isBingo90.value && rowCounts[row] >= 5) continue

      const pool: number[] = []
      for (let n = col.min; n <= col.max; n++) if (!used.has(n)) pool.push(n)
      if (pool.length === 0) continue

      const chosen = pool[Math.floor(Math.random() * pool.length)]
      next[col.label][row] = chosen
      used.add(chosen)
      if (isBingo90.value) rowCounts[row]++
    }
  }

  modelValue.value = next
  openCell.value = null
}

defineExpose({ isComplete, missingCount, autoFillRemaining })
</script>

<template>
  <div class="ncp-wrap">
    <div class="ncp-head" :style="{ gridTemplateColumns: `repeat(${variantDef.columns.length}, 1fr)` }">
      <span
        v-for="col in variantDef.columns"
        :key="col.label"
        class="ncp-head__cell"
        :style="{ background: BINGO_COL_COLORS[col.colorIdx].bg, color: BINGO_COL_COLORS[col.colorIdx].text }"
      >{{ col.label }}</span>
    </div>

    <div class="ncp-body" :style="{ gridTemplateColumns: `repeat(${variantDef.columns.length}, 1fr)` }">
      <template v-for="col in variantDef.columns" :key="col.label">
        <button
          v-for="row in variantDef.numRows"
          :key="`${col.label}${row}`"
          type="button"
          class="ncp-cell"
          :class="{
            'ncp-cell--free': isFreeCell(col, row - 1),
            'ncp-cell--filled': valueAt(col.label, row - 1) !== null,
            'ncp-cell--active': openCell?.col === col.label && openCell?.row === row - 1,
            'ncp-cell--disabled': isBingo90 && valueAt(col.label, row - 1) === null && rowIsFull(row - 1),
          }"
          :disabled="isFreeCell(col, row - 1)"
          @click="toggleCell(col, row - 1)"
        >
          <span v-if="isFreeCell(col, row - 1)" class="ncp-free">★</span>
          <span v-else-if="valueAt(col.label, row - 1) !== null">{{ valueAt(col.label, row - 1) }}</span>
          <span v-else class="ncp-empty">+</span>
        </button>
      </template>
    </div>

    <!-- Panel de selección de número para la casilla abierta -->
    <Transition name="ncp-fade">
      <div v-if="openCell" class="ncp-picker">
        <div class="ncp-picker__head">
          <span>Elige un número para {{ openCell.col }}{{ openCell.row + 1 }}</span>
          <button type="button" class="ncp-picker__close" @click="openCell = null">
            <i class="fas fa-times" />
          </button>
        </div>
        <div class="ncp-picker__grid">
          <button
            v-for="n in cellOptions"
            :key="n"
            type="button"
            class="ncp-picker__btn"
            @click="pickNumber(n)"
          >{{ n }}</button>
        </div>
        <button
          v-if="valueAt(openCell.col, openCell.row) !== null"
          type="button"
          class="ncp-picker__clear"
          @click="clearCell"
        >
          <i class="fas fa-eraser mr-1" />Vaciar casilla
        </button>
      </div>
    </Transition>

    <div class="ncp-status" :class="{ 'ncp-status--ok': isComplete }">
      <i :class="isComplete ? 'fas fa-check-circle' : 'fas fa-info-circle'" />
      <span v-if="isComplete">Cartón completo</span>
      <span v-else>Faltan {{ missingCount }} casilla{{ missingCount === 1 ? '' : 's' }}</span>
    </div>
  </div>
</template>

<style scoped>
.ncp-wrap {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.ncp-head, .ncp-body {
  display: grid;
  gap: 4px;
}

.ncp-head__cell {
  text-align: center;
  font-weight: 800;
  font-size: 13px;
  padding: 5px 2px;
  border-radius: 6px;
}

.ncp-cell {
  aspect-ratio: 1;
  border-radius: 8px;
  border: 1.5px solid rgba(100, 140, 255, 0.2);
  background: rgba(255, 255, 255, 0.04);
  color: #c0d0f0;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s, transform 0.1s;
  display: flex;
  align-items: center;
  justify-content: center;
}
.ncp-cell:not(:disabled):hover {
  border-color: rgba(100, 140, 255, 0.55);
  background: rgba(100, 140, 255, 0.1);
}
.ncp-cell--filled {
  border-color: rgba(42, 245, 152, 0.45);
  background: rgba(42, 245, 152, 0.1);
  color: #7affba;
}
.ncp-cell--active {
  border-color: #2af598 !important;
  box-shadow: 0 0 0 2px rgba(42, 245, 152, 0.3);
  transform: scale(1.05);
}
.ncp-cell--free {
  cursor: default;
  background: rgba(255, 215, 0, 0.08);
  border-color: rgba(255, 215, 0, 0.3);
  color: #ffd700;
}
.ncp-cell--disabled {
  opacity: 0.3;
  cursor: not-allowed;
}
.ncp-empty {
  opacity: 0.5;
  font-size: 14px;
}

/* ── Panel selector de número ── */
.ncp-picker {
  background: rgba(0, 10, 40, 0.6);
  border: 1px solid rgba(100, 140, 255, 0.2);
  border-radius: 12px;
  padding: 10px;
}
.ncp-picker__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 11px;
  color: #7c9dff;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
}
.ncp-picker__close {
  background: none;
  border: none;
  color: #7c9dff;
  cursor: pointer;
  font-size: 12px;
}
.ncp-picker__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(34px, 1fr));
  gap: 5px;
  max-height: 160px;
  overflow-y: auto;
}
.ncp-picker__btn {
  padding: 7px 0;
  border-radius: 7px;
  border: 1px solid rgba(100, 140, 255, 0.25);
  background: rgba(255, 255, 255, 0.04);
  color: #e0e8ff;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.15s, border-color 0.15s;
}
.ncp-picker__btn:hover {
  background: rgba(42, 245, 152, 0.18);
  border-color: rgba(42, 245, 152, 0.5);
  color: #7affba;
}
.ncp-picker__clear {
  margin-top: 8px;
  width: 100%;
  padding: 6px 0;
  background: rgba(255, 80, 80, 0.1);
  border: 1px solid rgba(255, 80, 80, 0.25);
  border-radius: 8px;
  color: #ff8080;
  font-size: 11px;
  cursor: pointer;
}
.ncp-picker__clear:hover {
  background: rgba(255, 80, 80, 0.18);
}

/* ── Status ── */
.ncp-status {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  color: #ffc336;
  justify-content: center;
}
.ncp-status--ok {
  color: #7affba;
}

.ncp-fade-enter-active, .ncp-fade-leave-active {
  transition: opacity 0.15s ease;
}
.ncp-fade-enter-from, .ncp-fade-leave-to {
  opacity: 0;
}
</style>
