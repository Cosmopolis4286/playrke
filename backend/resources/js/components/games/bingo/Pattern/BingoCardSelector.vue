<script setup lang="ts" name="BingoCardSelector">
import { computed } from 'vue'

const LETTERS = ['B', 'I', 'N', 'G', 'O'] as const
type BingoLetter = typeof LETTERS[number]

const COL_BASE: Record<BingoLetter, number> = { B: 1, I: 16, N: 31, G: 46, O: 61 }

function cellNumber(letter: BingoLetter, row: number): number {
  return COL_BASE[letter] + (row - 1)
}

const FREE_CELL = cellNumber('N', 3)

const props = defineProps<{
  modelValue: string
  freeCenter?: boolean
  disabled?: boolean
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
}>()

/** Convierte una casilla en cualquier formato ('B1', 'b1', '1') a su número 1-75. */
function parseCell(raw: string): number | null {
  const s = raw.trim().toUpperCase()
  // Formato letra+fila: B1, I3, N3, G5, O2…
  const match = s.match(/^([BINGO])(\d)$/)
  if (match) {
    const letter = match[1] as BingoLetter
    const row    = parseInt(match[2], 10)
    if (row >= 1 && row <= 5) return cellNumber(letter, row)
    return null
  }
  // Formato numérico directo: 1-75
  const n = parseInt(s, 10)
  return (!isNaN(n) && n >= 1 && n <= 75) ? n : null
}

const selectedSet = computed<Set<number>>(() => {
  if (!props.modelValue?.trim()) return new Set()
  const nums = props.modelValue.split(';').map(s => parseCell(s)).filter((n): n is number => n !== null)
  return new Set(nums)
})

const isFree = (letter: BingoLetter, row: number) =>
  props.freeCenter !== false && cellNumber(letter, row) === FREE_CELL

const isSelected = (letter: BingoLetter, row: number) =>
  selectedSet.value.has(cellNumber(letter, row))

function toggle(letter: BingoLetter, row: number) {
  if (props.disabled || isFree(letter, row)) return
  const num  = cellNumber(letter, row)
  const next = new Set(selectedSet.value)
  next.has(num) ? next.delete(num) : next.add(num)
  emit('update:modelValue', [...next].sort((a, b) => a - b).join(';'))
}

function clear() {
  if (!props.disabled) emit('update:modelValue', '')
}

defineExpose({ clear })
</script>

<template>
  <div class="bcs-wrap">
    <div class="bcs">

      <!-- Grid: fila de headers + 5 filas de celdas -->
      <div class="bcs-grid">

        <!-- Headers B I N G O -->
        <div v-for="letter in LETTERS" :key="`hd-${letter}`" class="bcs-hd">
          {{ letter }}
        </div>

        <!-- Filas 1-5 × 5 letras -->
        <template v-for="row in 5" :key="`r${row}`">
          <span
            v-for="letter in LETTERS"
            :key="`${letter}${row}`"
            class="bcs-cell"
            :class="{
              'bcs-active': isSelected(letter, row),
              'bcs-free':   isFree(letter, row),
              'bcs-off':    disabled && !isFree(letter, row),
            }"
            :title="`${letter}${row}`"
            :tabindex="disabled || isFree(letter, row) ? -1 : 0"
            :role="disabled || isFree(letter, row) ? undefined : 'checkbox'"
            :aria-checked="isSelected(letter, row)"
            @click="toggle(letter, row)"
            @keydown.enter.space.prevent="toggle(letter, row)"
          >
            <i v-if="isFree(letter, row)" class="fa-solid fa-star" style="font-size:9px"></i>
            <template v-else>{{ letter }}{{ row }}</template>
          </span>
        </template>

      </div>

      <!-- Footer -->
      <div class="bcs-footer">
        <span class="bcs-count">
          <template v-if="selectedSet.size">
            <i class="fa-solid fa-check-square fa-xs"></i> {{ selectedSet.size }} casilla{{ selectedSet.size !== 1 ? 's' : '' }}
          </template>
          <template v-else>Sin selección</template>
        </span>
        <button
          type="button"
          class="bcs-clear-btn"
          :disabled="disabled || selectedSet.size === 0"
          title="Limpiar selección"
          @click="clear"
        >
          <i class="fa-solid fa-eraser fa-xs"></i> Limpiar
        </button>
      </div>

    </div>
  </div>
</template>

<style scoped>
/* Wrapper: centra el cartón y le da un ancho máximo */
.bcs-wrap {
  display: flex;
  justify-content: center;
}

.bcs {
  width: 252px;
  flex-shrink: 0;
}

/* Grid 5 columnas de tamaño fijo */
.bcs-grid {
  display: grid;
  grid-template-columns: repeat(5, 45px);
  grid-auto-rows: auto;
  gap: 2px;
  padding: 5px;
  background: #e9ecef;
  border: 1px solid #dee2e6;
  border-radius: 7px;
  width: fit-content;
  margin: 0 auto;
}

/* Header rectangular */
.bcs-hd {
  width: 45px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.94rem;
  font-weight: 700;
  letter-spacing: 0.03em;
  background: var(--bs-primary, #0044aa);
  color: #fff;
  border-radius: 4px;
  user-select: none;
}

/* Celda cuadrada fija */
.bcs-cell {
  width: 45px;
  height: 45px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.82rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  border-radius: 4px;
  cursor: pointer;
  user-select: none;
  background: #fff;
  color: #343a40;
  border: 1px solid #dee2e6;
  transition: background 0.12s, color 0.12s, border-color 0.12s, transform 0.08s;
}

.bcs-cell:hover:not(.bcs-off):not(.bcs-free) {
  background: #dbeafe;
  border-color: var(--bs-primary, #0d6efd);
  color: var(--bs-primary, #0d6efd);
  transform: scale(1.07);
  z-index: 1;
}

/* Seleccionada */
.bcs-cell.bcs-active {
  background: var(--bs-primary, #0d6efd);
  border-color: var(--bs-primary, #0d6efd);
  color: #fff;
  box-shadow: 0 1px 4px rgba(13,110,253,.35);
}
.bcs-cell.bcs-active:hover:not(.bcs-off) {
  background: #0a58ca;
  border-color: #0a58ca;
}

/* Libre (N3) */
.bcs-cell.bcs-free {
  background: #fff8e1;
  border-color: #ffe082;
  color: #b8860b;
  cursor: default;
}

/* Deshabilitada */
.bcs-cell.bcs-off {
  opacity: 0.45;
  cursor: not-allowed;
  pointer-events: none;
}

/* Footer */
.bcs-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 6px;
  padding: 0 1px;
}

.bcs-count {
  font-size: 0.72rem;
  color: #6c757d;
  display: flex;
  align-items: center;
  gap: 3px;
}

.bcs-clear-btn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 9px;
  font-size: 0.7rem;
  font-weight: 500;
  line-height: 1.5;
  border: 1px solid #adb5bd;
  border-radius: 4px;
  background: #fff;
  color: #495057;
  cursor: pointer;
  transition: background 0.12s, color 0.12s, border-color 0.12s;
}
.bcs-clear-btn:hover:not(:disabled) {
  background: #f8f9fa;
  border-color: #6c757d;
  color: #212529;
}
.bcs-clear-btn:disabled {
  opacity: 0.38;
  cursor: not-allowed;
}
</style>
