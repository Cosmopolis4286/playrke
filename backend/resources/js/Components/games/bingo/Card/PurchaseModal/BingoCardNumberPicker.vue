<script setup lang="ts">
import { computed, ref, nextTick, onMounted, onUnmounted } from 'vue'
import { getBingoVariantDef, BINGO_COL_COLORS, type BingoVariantColumn } from '@/js/modules/games/bingo/utils/bingoVariants'

type Matrix = Record<string, (number | null)[]>

const props = defineProps<{
  variant?: string | null
}>()

const modelValue = defineModel<Matrix>({ required: true })

const variantDef = computed(() => getBingoVariantDef(props.variant))
const isBingo90  = computed(() => (props.variant ?? 'BINGO_75') === 'BINGO_90')

// ── Casilla activa y posición del popover ──
const openCell    = ref<{ col: string; row: number } | null>(null)
const popoverStyle = ref<Record<string, string>>({})
const pickerEl    = ref<HTMLElement | null>(null)

function isFreeCell(col: BingoVariantColumn, row: number): boolean {
  return col.freeRow !== null && col.freeRow === row
}

function valueAt(col: string, row: number): number | null {
  return modelValue.value[col]?.[row] ?? null
}

const usedNumbers = computed(() => {
  const set = new Set<number>()
  for (const col of variantDef.value.columns) {
    for (const v of modelValue.value[col.label] ?? []) {
      if (v !== null) set.add(v)
    }
  }
  return set
})

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

// Cuántas casillas de una columna están llenas (excluye casilla libre)
function colFilledCount(col: BingoVariantColumn): number {
  let n = 0
  for (let row = 0; row < variantDef.value.numRows; row++) {
    if (isFreeCell(col, row)) continue
    if (valueAt(col.label, row) !== null) n++
  }
  return n
}

// Total de casillas requeridas en una columna (excluye libre y filas-llenas en BINGO_90)
function colRequiredCount(col: BingoVariantColumn): number {
  if (isBingo90.value) return col.count // count = 3 slots por columna en 90
  return variantDef.value.numRows - (col.freeRow !== null ? 1 : 0)
}

const colIsComplete = computed<Record<string, boolean>>(() => {
  const result: Record<string, boolean> = {}
  for (const col of variantDef.value.columns) {
    result[col.label] = colFilledCount(col) >= colRequiredCount(col)
  }
  return result
})

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

// Cuántos números libres quedan en la columna activa
const openColAvailableCount = computed<number>(() => {
  if (!openCell.value) return 0
  const col = variantDef.value.columns.find(c => c.label === openCell.value!.col)
  if (!col) return 0
  let count = 0
  for (let n = col.min; n <= col.max; n++) {
    if (!usedNumbers.value.has(n)) count++
  }
  return count
})

async function toggleCell(col: BingoVariantColumn, row: number, event: MouseEvent): Promise<void> {
  if (isFreeCell(col, row)) return
  if (isBingo90.value && valueAt(col.label, row) === null && rowIsFull(row)) return

  const same = openCell.value?.col === col.label && openCell.value?.row === row
  if (same) { openCell.value = null; return }

  openCell.value = { col: col.label, row }

  await nextTick()
  positionPopover(event.currentTarget as HTMLElement)
}

function positionPopover(cellEl: HTMLElement): void {
  if (!pickerEl.value) return

  const cellRect = cellEl.getBoundingClientRect()
  const pickerH  = pickerEl.value.offsetHeight || 220
  const pickerW  = Math.max(pickerEl.value.offsetWidth || 200, 190)
  const margin   = 8
  const vw       = window.innerWidth

  let left = cellRect.left + cellRect.width / 2 - pickerW / 2
  left = Math.max(8, Math.min(left, vw - pickerW - 8))

  let top = cellRect.bottom + margin
  const wouldExceedBottom = cellRect.bottom + margin + pickerH > window.innerHeight - 8
  if (wouldExceedBottom) top = cellRect.top - pickerH - margin

  popoverStyle.value = {
    position: 'fixed',
    top: `${top}px`,
    left: `${left}px`,
    width: `${pickerW}px`,
  }
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

function clearAll(): void {
  openCell.value = null
  const next: Matrix = {}
  for (const col of variantDef.value.columns) {
    next[col.label] = Array(variantDef.value.numRows).fill(null)
  }
  modelValue.value = next
}

function onOutsideClick(e: MouseEvent): void {
  if (!openCell.value) return
  if (pickerEl.value?.contains(e.target as Node)) return
  if ((e.target as HTMLElement).closest('.ncp-cell')) return
  openCell.value = null
}

onMounted(() => document.addEventListener('mousedown', onOutsideClick, true))
onUnmounted(() => document.removeEventListener('mousedown', onOutsideClick, true))

const missingCount = computed(() => {
  let missing = 0
  for (const col of variantDef.value.columns) {
    for (let row = 0; row < variantDef.value.numRows; row++) {
      if (isFreeCell(col, row)) continue
      if (valueAt(col.label, row) === null) {
        if (isBingo90.value && rowIsFull(row)) continue
        missing++
      }
    }
  }
  return missing
})

const isComplete = computed(() => missingCount.value === 0)

// Progreso (0–100) de relleno del cartón
const progressPct = computed(() => {
  const total = variantDef.value.totalCells
  if (total === 0) return 0
  const filled = total - missingCount.value
  return Math.round((filled / total) * 100)
})

function autoFillRemaining(): void {
  const next: Matrix = {}
  for (const col of variantDef.value.columns) {
    next[col.label] = [...(modelValue.value[col.label] ?? Array(variantDef.value.numRows).fill(null))]
  }
  const used      = new Set(usedNumbers.value)
  const rowCounts = [...rowFillCount.value]

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

defineExpose({ isComplete, missingCount, autoFillRemaining, clearAll })
</script>

<template>
  <div class="ncp-wrap">

    <!-- ── Barra de progreso ── -->
    <div class="ncp-progress">
      <div
        class="ncp-progress__bar"
        :class="{ 'ncp-progress__bar--complete': isComplete }"
        :style="{ width: `${progressPct}%` }"
      />
    </div>

    <!-- ── Cabecera de columnas con indicadores de completitud ── -->
    <div class="ncp-head" :style="{ gridTemplateColumns: `repeat(${variantDef.columns.length}, 1fr)` }">
      <div
        v-for="col in variantDef.columns"
        :key="col.label"
        class="ncp-head__col"
        :class="{ 'ncp-head__col--done': colIsComplete[col.label] }"
      >
        <span
          class="ncp-head__cell"
          :style="{ background: BINGO_COL_COLORS[col.colorIdx].bg, color: BINGO_COL_COLORS[col.colorIdx].text }"
        >{{ col.label }}</span>
        <i v-if="colIsComplete[col.label]" class="fas fa-check ncp-head__col-check" />
      </div>
    </div>

    <!-- ── Grid de casillas ── -->
    <div class="ncp-body" :style="{ gridTemplateColumns: `repeat(${variantDef.columns.length}, 1fr)` }">
      <template v-for="row in variantDef.numRows" :key="row">
        <button
          v-for="col in variantDef.columns"
          :key="`${col.label}${row}`"
          type="button"
          class="ncp-cell"
          :class="{
            'ncp-cell--free':     isFreeCell(col, row - 1),
            'ncp-cell--filled':   valueAt(col.label, row - 1) !== null,
            'ncp-cell--active':   openCell?.col === col.label && openCell?.row === row - 1,
            'ncp-cell--disabled': isBingo90 && valueAt(col.label, row - 1) === null && rowIsFull(row - 1),
          }"
          :disabled="isFreeCell(col, row - 1)"
          @click="toggleCell(col, row - 1, $event)"
        >
          <span v-if="isFreeCell(col, row - 1)" class="ncp-free">★</span>
          <span v-else-if="valueAt(col.label, row - 1) !== null">{{ valueAt(col.label, row - 1) }}</span>
          <span v-else class="ncp-empty">+</span>
        </button>
      </template>
    </div>

    <!-- ── Acciones rápidas ── -->
    <div class="ncp-actions">
      <button
        type="button"
        class="ncp-action-btn ncp-action-btn--fill"
        :disabled="isComplete"
        @click="autoFillRemaining"
      >
        <i class="fas fa-magic" />
        <span>Autocompletar</span>
      </button>
      <button
        type="button"
        class="ncp-action-btn ncp-action-btn--clear"
        :disabled="usedNumbers.size === 0"
        @click="clearAll"
      >
        <i class="fas fa-trash-alt" />
        <span>Limpiar</span>
      </button>
    </div>

    <!-- ── Status ── -->
    <div class="ncp-status" :class="{ 'ncp-status--ok': isComplete }">
      <i :class="isComplete ? 'fas fa-check-circle' : 'fas fa-info-circle'" />
      <span v-if="isComplete">Cartón completo</span>
      <span v-else>Faltan {{ missingCount }} casilla{{ missingCount === 1 ? '' : 's' }}</span>
    </div>

    <!-- ── Popover flotante ── -->
    <Teleport to="body">
      <Transition name="ncp-pop">
        <div
          v-if="openCell"
          ref="pickerEl"
          class="ncp-picker"
          :style="popoverStyle"
        >
          <!-- Header del popover -->
          <div class="ncp-picker__head">
            <div class="ncp-picker__head-left">
              <span
                class="ncp-picker__col-badge"
                :style="{
                  background: BINGO_COL_COLORS[variantDef.columns.find(c => c.label === openCell!.col)?.colorIdx ?? 0].bg,
                  color: BINGO_COL_COLORS[variantDef.columns.find(c => c.label === openCell!.col)?.colorIdx ?? 0].text,
                }"
              >{{ openCell.col }}</span>
              <span class="ncp-picker__range">
                {{ variantDef.columns.find(c => c.label === openCell!.col)?.min }}–{{ variantDef.columns.find(c => c.label === openCell!.col)?.max }}
              </span>
              <span class="ncp-picker__avail">{{ openColAvailableCount }} disponibles</span>
            </div>
            <div class="ncp-picker__head-actions">
              <button
                v-if="valueAt(openCell.col, openCell.row) !== null"
                type="button"
                class="ncp-picker__erase"
                title="Vaciar casilla"
                @click="clearCell"
              >
                <i class="fas fa-eraser" />
              </button>
              <button type="button" class="ncp-picker__close" @click="openCell = null">
                <i class="fas fa-times" />
              </button>
            </div>
          </div>

          <!-- Grid de números disponibles -->
          <div class="ncp-picker__grid">
            <button
              v-for="n in cellOptions"
              :key="n"
              type="button"
              class="ncp-picker__btn"
              :class="{ 'ncp-picker__btn--current': n === valueAt(openCell.col, openCell.row) }"
              @click="pickNumber(n)"
            >{{ n }}</button>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
.ncp-wrap {
  display: flex;
  flex-direction: column;
  gap: 6px;
  position: relative;
}

/* ── Barra de progreso ── */
.ncp-progress {
  height: 3px;
  background: rgba(100, 140, 255, 0.12);
  border-radius: 99px;
  overflow: hidden;
}
.ncp-progress__bar {
  height: 100%;
  background: linear-gradient(90deg, #4a6fff, #2af598);
  border-radius: 99px;
  transition: width 0.25s ease, background 0.3s ease;
}
.ncp-progress__bar--complete {
  background: #2af598;
}

/* ── Cabecera columnas ── */
.ncp-head, .ncp-body {
  display: grid;
  gap: 2px;
}

.ncp-head__col {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
}

.ncp-head__col-check {
  font-size: 8px;
  color: #2af598;
  height: 9px;
  opacity: 0;
  transition: opacity 0.2s;
}
.ncp-head__col--done .ncp-head__col-check {
  opacity: 1;
}

.ncp-head__cell {
  text-align: center;
  font-weight: 800;
  font-size: 11px;
  padding: 3px 2px;
  border-radius: 4px;
  letter-spacing: .3px;
  width: 100%;
  transition: box-shadow 0.2s;
}
.ncp-head__col--done .ncp-head__cell {
  box-shadow: 0 0 0 1.5px rgba(42, 245, 152, 0.5);
}

/* ── Casillas ── */
.ncp-cell {
  aspect-ratio: 1;
  border-radius: 6px;
  border: 1.5px solid rgba(100, 140, 255, 0.18);
  background: rgba(255, 255, 255, 0.03);
  color: #c0d0f0;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
  transition: border-color 0.12s, background 0.12s, transform 0.1s;
  display: flex;
  align-items: center;
  justify-content: center;
}
.ncp-cell:not(:disabled):hover {
  border-color: rgba(100, 140, 255, 0.5);
  background: rgba(100, 140, 255, 0.09);
}
.ncp-cell--filled {
  border-color: rgba(42, 245, 152, 0.4);
  background: rgba(42, 245, 152, 0.09);
  color: #7affba;
}
.ncp-cell--active {
  border-color: #2af598 !important;
  box-shadow: 0 0 0 2px rgba(42, 245, 152, 0.28);
  transform: scale(1.06);
  z-index: 1;
}
.ncp-cell--free {
  cursor: default;
  background: rgba(255, 215, 0, 0.07);
  border-color: rgba(255, 215, 0, 0.28);
  color: #ffd700;
}
.ncp-cell--disabled {
  opacity: 0.28;
  cursor: not-allowed;
}
.ncp-empty {
  opacity: 0.35;
  font-size: 11px;
}

/* ── Acciones rápidas ── */
.ncp-actions {
  display: flex;
  gap: 6px;
}

.ncp-action-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  padding: 6px 10px;
  border-radius: 8px;
  border: 1px solid transparent;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.15s, border-color 0.15s, opacity 0.15s;
}
.ncp-action-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.ncp-action-btn--fill {
  background: rgba(42, 245, 152, 0.08);
  border-color: rgba(42, 245, 152, 0.25);
  color: #7affba;
}
.ncp-action-btn--fill:not(:disabled):hover {
  background: rgba(42, 245, 152, 0.16);
  border-color: rgba(42, 245, 152, 0.5);
}

.ncp-action-btn--clear {
  background: rgba(255, 90, 90, 0.06);
  border-color: rgba(255, 90, 90, 0.2);
  color: #ff8080;
}
.ncp-action-btn--clear:not(:disabled):hover {
  background: rgba(255, 90, 90, 0.14);
  border-color: rgba(255, 90, 90, 0.45);
}

/* ── Status ── */
.ncp-status {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 10.5px;
  color: #ffc336;
  justify-content: center;
  opacity: .85;
}
.ncp-status--ok {
  color: #7affba;
}
</style>

<!-- Estilos globales del picker (Teleport vive en <body>, scoped no alcanza) -->
<style>
.ncp-picker {
  position: fixed;
  z-index: 9999;
  background: #0b1535;
  border: 1px solid rgba(100, 140, 255, 0.28);
  border-radius: 10px;
  padding: 8px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.65), 0 0 0 1px rgba(42, 245, 152, 0.06) inset;
  min-width: 190px;
}

.ncp-picker__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 6px;
  gap: 4px;
}

.ncp-picker__head-left {
  display: flex;
  align-items: center;
  gap: 5px;
  min-width: 0;
}

.ncp-picker__col-badge {
  font-size: 9px;
  font-weight: 800;
  padding: 2px 5px;
  border-radius: 4px;
  flex-shrink: 0;
  letter-spacing: .3px;
}

.ncp-picker__range {
  font-size: 9px;
  color: #3a4a80;
  flex-shrink: 0;
}

.ncp-picker__avail {
  font-size: 9px;
  color: #4a6aff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.ncp-picker__head-actions {
  display: flex;
  align-items: center;
  gap: 4px;
  flex-shrink: 0;
}

.ncp-picker__erase,
.ncp-picker__close {
  background: none;
  border: none;
  cursor: pointer;
  width: 20px;
  height: 20px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  transition: background 0.15s, color 0.15s;
}
.ncp-picker__erase { color: #ff8080; }
.ncp-picker__erase:hover { background: rgba(255, 80, 80, 0.14); }
.ncp-picker__close { color: #556080; }
.ncp-picker__close:hover { background: rgba(255, 255, 255, 0.08); color: #c0d0f0; }

.ncp-picker__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(28px, 1fr));
  gap: 3px;
  max-height: 160px;
  overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: #3a4a80 transparent;
}

.ncp-picker__btn {
  padding: 5px 0;
  border-radius: 5px;
  border: 1px solid rgba(100, 140, 255, 0.2);
  background: rgba(255, 255, 255, 0.03);
  color: #c8d8ff;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.12s, border-color 0.12s, color 0.12s;
  text-align: center;
}
.ncp-picker__btn:hover {
  background: rgba(42, 245, 152, 0.16);
  border-color: rgba(42, 245, 152, 0.45);
  color: #7affba;
}
.ncp-picker__btn--current {
  background: rgba(42, 245, 152, 0.2);
  border-color: #2af598;
  color: #7affba;
}

/* Transición del popover */
.ncp-pop-enter-active { transition: opacity 0.12s ease, transform 0.12s ease; }
.ncp-pop-leave-active { transition: opacity 0.08s ease, transform 0.08s ease; }
.ncp-pop-enter-from,
.ncp-pop-leave-to { opacity: 0; transform: translateY(-4px) scale(0.97); }
</style>
