<script setup lang="ts">
/**
 * BingoCustomCardSection
 *
 * Sección de personalización de cartones. Maneja tabs por cartón y rastrea
 * si cada cartón tiene edición manual real (≥1 casilla tocada por el usuario)
 * vs. solo autocompletado. Solo los cartones con edición manual se marcan como
 * personalizados — el resto se trata como cartón aleatorio (sin recargo).
 */
import { ref, computed } from 'vue'
import BingoCardNumberPicker from './BingoCardNumberPicker.vue'

type CardMatrix = Record<string, (number | null)[]>

const props = defineProps<{
  matrices: CardMatrix[]
  /** Por cartón: true si el usuario tocó ≥1 casilla manualmente */
  manualEdits: boolean[]
  activeIndex: number
  numCtns: number
  variant?: string | null
  /** Recargo % para cartones personalizados (0 = sin recargo) */
  customPricePct?: number | null
}>()

const emit = defineEmits<{
  (e: 'update:matrices', value: CardMatrix[]): void
  (e: 'update:manual-edits', value: boolean[]): void
  (e: 'update:active-index', index: number): void
}>()

const picker = ref<InstanceType<typeof BingoCardNumberPicker> | null>(null)

/** Cuántos cartones tienen edición manual */
const manualCount = computed(() => props.manualEdits.filter(Boolean).length)

function onPickerUpdate(matrix: CardMatrix): void {
  const next = [...props.matrices]
  next[props.activeIndex] = matrix
  emit('update:matrices', next)

  // Marcar este cartón como editado manualmente
  if (!props.manualEdits[props.activeIndex]) {
    const edits = [...props.manualEdits]
    edits[props.activeIndex] = true
    emit('update:manual-edits', edits)
  }
}

function onAutoFill(): void {
  picker.value?.autoFillRemaining()
  // autoFill no cuenta como edición manual — no emite update:manual-edits
}

function onClearAll(): void {
  picker.value?.clearAll()
  // Limpiar quita la marca de editado para este cartón
  const edits = [...props.manualEdits]
  edits[props.activeIndex] = false
  emit('update:manual-edits', edits)
}

defineExpose({
  get missingCount() { return picker.value?.missingCount ?? 0 },
  get isComplete()   { return picker.value?.isComplete ?? false },
  autoFillRemaining: onAutoFill,
  clearAll:          onClearAll,
  manualCount,
})
</script>

<template>
  <div class="bcs-wrap">

    <!-- Tabs de cartones (solo si hay más de uno) -->
    <div v-if="numCtns > 1" class="bcs-tabs">
      <button
        v-for="i in numCtns"
        :key="i"
        type="button"
        class="bcs-tab"
        :class="{
          'bcs-tab--active':  activeIndex === i - 1,
          'bcs-tab--manual':  manualEdits[i - 1],
        }"
        :title="manualEdits[i - 1] ? 'Personalizado' : 'Al azar'"
        @click="emit('update:active-index', i - 1)"
      >
        #{{ i }}
        <span v-if="manualEdits[i - 1]" class="bcs-tab__dot" />
      </button>
    </div>

    <!-- Aviso de recargo cuando el cartón activo es manual y hay recargo configurado -->
    <div
      v-if="customPricePct && customPricePct > 0 && manualEdits[activeIndex]"
      class="bcs-surcharge-notice"
    >
      <i class="fas fa-info-circle" />
      Este cartón tiene un recargo del <strong>{{ customPricePct }}%</strong> por ser personalizado.
    </div>

    <!-- Aviso de autocompletado puro (sin edición manual) -->
    <div
      v-else-if="customPricePct && customPricePct > 0 && !manualEdits[activeIndex]"
      class="bcs-random-notice"
    >
      <i class="fas fa-dice" />
      Sin cambios manuales → se tratará como <strong>cartón al azar</strong> (precio normal).
    </div>

    <BingoCardNumberPicker
      ref="picker"
      :model-value="matrices[activeIndex] ?? {}"
      :variant="variant"
      @update:model-value="onPickerUpdate"
    />

    <!-- Resumen de distribución cuando hay múltiples cartones -->
    <div v-if="numCtns > 1 && manualCount > 0" class="bcs-dist">
      <span class="bcs-dist__item bcs-dist__item--manual">
        <i class="fas fa-pen" /> {{ manualCount }} personalizado{{ manualCount > 1 ? 's' : '' }}
      </span>
      <span v-if="numCtns - manualCount > 0" class="bcs-dist__item bcs-dist__item--random">
        <i class="fas fa-dice" /> {{ numCtns - manualCount }} al azar
      </span>
    </div>

  </div>
</template>

<style scoped>
.bcs-wrap {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

/* ── Tabs ── */
.bcs-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.bcs-tab {
  position: relative;
  padding: 3px 10px;
  border-radius: 6px;
  border: 1.5px solid rgba(100, 140, 255, 0.22);
  background: rgba(255, 255, 255, 0.04);
  color: rgba(192, 208, 240, 0.7);
  font-size: 10.5px;
  font-weight: 700;
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s, color 0.15s;
}
.bcs-tab:hover {
  border-color: rgba(100, 140, 255, 0.5);
  background: rgba(100, 140, 255, 0.1);
  color: #c0d0f0;
}
.bcs-tab--active {
  border-color: rgba(42, 245, 152, 0.5);
  background: rgba(42, 245, 152, 0.1);
  color: #7affba;
}
.bcs-tab--manual {
  border-color: rgba(100, 180, 255, 0.45);
}
.bcs-tab__dot {
  display: inline-block;
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #4ab4ff;
  margin-left: 4px;
  vertical-align: middle;
}

/* ── Avisos ── */
.bcs-surcharge-notice,
.bcs-random-notice {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 5px 9px;
  border-radius: 7px;
  font-size: 10.5px;
  line-height: 1.4;
}
.bcs-surcharge-notice {
  background: rgba(100, 180, 255, 0.08);
  border: 1px solid rgba(100, 180, 255, 0.22);
  color: #90c8ff;
}
.bcs-random-notice {
  background: rgba(160, 160, 180, 0.07);
  border: 1px solid rgba(160, 160, 180, 0.18);
  color: rgba(192, 208, 240, 0.6);
}

/* ── Distribución ── */
.bcs-dist {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  font-size: 10px;
  padding: 4px 0 0;
}
.bcs-dist__item {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 2px 8px;
  border-radius: 99px;
}
.bcs-dist__item--manual {
  background: rgba(100, 180, 255, 0.1);
  color: #90c8ff;
  border: 1px solid rgba(100, 180, 255, 0.2);
}
.bcs-dist__item--random {
  background: rgba(160, 160, 180, 0.07);
  color: rgba(192, 208, 240, 0.55);
  border: 1px solid rgba(160, 160, 180, 0.15);
}
</style>
