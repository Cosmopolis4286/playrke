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

const selectedSet = computed<Set<number>>(() => {
  if (!props.modelValue?.trim()) return new Set()
  return new Set(
    props.modelValue.split(';').map(s => parseInt(s.trim(), 10)).filter(n => !isNaN(n))
  )
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
  <div class="bcs">
    <!-- Cartón -->
    <div class="bcs-grid">
      <template v-for="letter in LETTERS" :key="letter">
        <!-- Cabecera -->
        <div class="bcs-hd">{{ letter }}</div>
        <!-- Celdas -->
        <span
          v-for="row in 5"
          :key="row"
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
          <i v-if="isFree(letter, row)" class="fa-solid fa-star fa-2xs"></i>
          <template v-else>{{ letter }}{{ row }}</template>
        </span>
      </template>
    </div>

    <!-- Footer -->
    <div class="bcs-footer">
      <span class="text-muted" style="font-size:0.72rem">
        {{ selectedSet.size ? `${selectedSet.size} sel.` : 'Sin selección' }}
      </span>
      <button
        type="button"
        class="btn btn-xs btn-outline-secondary"
        :disabled="disabled || selectedSet.size === 0"
        title="Limpiar"
        @click="clear"
      >
        <i class="fa-solid fa-eraser fa-xs"></i>
      </button>
    </div>
  </div>
</template>

<style scoped>
.bcs {
  display: inline-block;
  width: 100%;
}

/* Grid: 5 cols × 6 rows (1 header + 5 celdas) */
.bcs-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 3px;
  padding: 6px;
  background: var(--bs-border-color, #dee2e6);
  border-radius: 6px;
  border: 1px solid var(--bs-border-color, #dee2e6);
}

/* Cabecera letra */
.bcs-hd {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 24px;
  font-size: 0.7rem;
  font-weight: 700;
  background: var(--bs-primary, #0d6efd);
  color: #fff;
  border-radius: 3px;
  user-select: none;
}

/* Celda base */
.bcs-cell {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 28px;
  font-size: 0.62rem;
  font-weight: 600;
  border-radius: 3px;
  cursor: pointer;
  user-select: none;
  background: var(--bs-body-bg, #fff);
  color: var(--bs-body-color, #212529);
  transition: background 0.1s, color 0.1s;
}

.bcs-cell:hover:not(.bcs-off):not(.bcs-free) {
  background: var(--bs-primary-bg-subtle, #cfe2ff);
}

/* Seleccionada */
.bcs-cell.bcs-active {
  background: var(--bs-primary, #0d6efd);
  color: #fff;
}
.bcs-cell.bcs-active:hover:not(.bcs-off) {
  background: var(--bs-primary-text-emphasis, #052c65);
}

/* Libre */
.bcs-cell.bcs-free {
  background: var(--bs-warning-bg-subtle, #fff3cd);
  color: var(--bs-warning-text-emphasis, #664d03);
  cursor: default;
}

/* Deshabilitada */
.bcs-cell.bcs-off {
  opacity: 0.5;
  cursor: not-allowed;
  pointer-events: none;
}

/* Footer */
.bcs-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 4px;
}

.btn-xs {
  padding: 1px 6px;
  font-size: 0.7rem;
  line-height: 1.4;
}
</style>
