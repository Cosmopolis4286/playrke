<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { GAME_VARIANT_LABELS } from '@js/modules/games/bingo/room/game-rooms/gameRoomUtils'
import type { GameRoomFilters } from '@js/types/games/bingo/room/game-rooms/bingo-game-room'

const props = defineProps<{
  filters: GameRoomFilters
  loading: boolean
}>()

const emit = defineEmits<{ apply: []; reset: [] }>()

const showDatePicker = ref(false)
// Refleja si ya hay un rango real aplicado (dateTo distinto de dateFrom) al
// reabrir el popover — antes arrancaba siempre en false, así que un rango ya
// aplicado se "perdía" visualmente (el input dateTo se ocultaba) hasta que
// el usuario tocaba "Rango" de nuevo.
const showRange = ref(!!props.filters.dateTo && props.filters.dateTo !== props.filters.dateFrom)

const rootEl = ref<HTMLElement | null>(null)

// Badge junto al ícono: muestra el rango completo (dd/mm–dd/mm) cuando dateTo
// difiere de dateFrom, no solo la primera fecha — antes siempre mostraba
// nada más que filters.dateFrom, ocultando que había un rango de días activo.
const dateBadgeLabel = computed(() => {
  const from = props.filters.dateFrom
  if (!from) return null
  const to = props.filters.dateTo
  if (to && to !== from) return `${from.slice(5)}–${to.slice(5)}`
  return from.slice(5)
})

function setVariant(variant: string | null) {
  props.filters.variant = variant
  emit('apply')
}

function applyDateFilter() {
  if (!showRange.value) {
    props.filters.dateTo = props.filters.dateFrom
  }
  showDatePicker.value = false
  emit('apply')
}

function handleReset() {
  showDatePicker.value = false
  showRange.value = false
  emit('reset')
}

function handleClickOutside(event: MouseEvent) {
  if (showDatePicker.value && rootEl.value && !rootEl.value.contains(event.target as Node)) {
    showDatePicker.value = false
  }
}

onMounted(() => document.addEventListener('mousedown', handleClickOutside))
onUnmounted(() => document.removeEventListener('mousedown', handleClickOutside))
</script>

<template>
  <div class="grfb-bar flex w-full flex-wrap items-center justify-center gap-1.5">
    <!-- Toggle fecha: colapsado por defecto, evita ocupar 2 inputs siempre visibles -->
    <div ref="rootEl" class="relative">
      <button
        type="button"
        class="grfb-icon-btn"
        :class="{ 'grfb-icon-btn--active': !!filters.dateFrom || showDatePicker }"
        title="Filtrar por fecha"
        @click="showDatePicker = !showDatePicker"
      >
        <i class="fas fa-calendar-day" />
        <span v-if="dateBadgeLabel" class="grfb-date-badge">{{ dateBadgeLabel }}</span>
      </button>

      <div v-if="showDatePicker" class="grfb-date-popover">
        <input v-model="filters.dateFrom" type="date" class="grfb-date__input" :disabled="loading" />
        <button
          type="button"
          class="grfb-date__toggle"
          :class="{ 'grfb-date__toggle--active': showRange }"
          @click="showRange = !showRange"
        >
          Rango
        </button>
        <input v-if="showRange" v-model="filters.dateTo" type="date" class="grfb-date__input" :disabled="loading" />
        <button type="button" class="grfb-apply" :disabled="loading || !filters.dateFrom" @click="applyDateFilter">
          <i class="fas fa-check" />
        </button>
      </div>
    </div>

    <div class="grfb-variants flex max-w-full gap-1 overflow-x-auto">
      <button
        type="button"
        class="grfb-pill"
        :class="{ 'grfb-pill--active': filters.variant === null }"
        :disabled="loading"
        @click="setVariant(null)"
      >
        Todas
      </button>
      <button
        v-for="(label, code) in GAME_VARIANT_LABELS"
        :key="code"
        type="button"
        class="grfb-pill"
        :class="{ 'grfb-pill--active': filters.variant === code }"
        :disabled="loading"
        @click="setVariant(code)"
      >
        {{ code.replace('BINGO_', '') }}
      </button>
    </div>

    <button
      v-if="filters.dateFrom || filters.dateTo || filters.variant"
      type="button"
      class="grfb-icon-btn"
      title="Restablecer filtros"
      :disabled="loading"
      @click="handleReset"
    >
      <i class="fas fa-rotate-left" />
    </button>
  </div>
</template>

<style scoped>
/* .grfb-bar y el layout de .grfb-variants ahora en Tailwind (ver template);
   solo queda aquí lo visual (colores/bordes/animaciones). */
.grfb-variants::-webkit-scrollbar { display: none; }
.grfb-variants { scrollbar-width: none; }

.grfb-icon-btn {
  position: relative;
  width: 26px; height: 26px; border-radius: 8px;
  display: inline-flex; align-items: center; justify-content: center;
  background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.1);
  color: #7a96c8; font-size: 10.5px; cursor: pointer;
  transition: background .15s, color .15s, border-color .15s;
  flex-shrink: 0;
}
.grfb-icon-btn:hover:not(:disabled) { background: rgba(255,255,255,.08); }
.grfb-icon-btn:disabled { opacity: .5; cursor: not-allowed; }
.grfb-icon-btn--active { background: rgba(42,245,152,.12); color: #2af598; border-color: rgba(42,245,152,.3); }

.grfb-date-badge {
  position: absolute; bottom: -6px; left: 50%; transform: translateX(-50%);
  font-size: 7px; font-weight: 700; letter-spacing: .2px;
  background: #2af598; color: #06111f; padding: 0 4px; border-radius: 4px;
  white-space: nowrap;
}

.grfb-date-popover {
  /* Centrado respecto al propio botón que lo abre (su ancestro .relative),
     no anclado a left:0 — con left:0 el popover se desplazaba hacia la
     derecha del botón y se desbordaba del viewport en pantallas angostas
     o cuando el botón está cerca del borde del toolbar. */
  position: absolute; top: calc(100% + 6px); left: 50%; transform: translateX(-50%);
  z-index: 40;
  display: flex; align-items: center; gap: 6px; flex-wrap: nowrap;
  width: max-content; max-width: calc(100vw - 24px);
  padding: 8px; border-radius: 10px;
  background: #0d1530; border: 1px solid rgba(255,255,255,.12);
  box-shadow: 0 10px 30px rgba(0,0,0,.5);
}
.grfb-date__input {
  padding: 5px 8px; border-radius: 7px; font-size: 11px;
  background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.12);
  color: #e0eaff; width: 130px;
}
.grfb-date__toggle {
  padding: 5px 8px; border-radius: 7px; font-size: 10px; font-weight: 700;
  background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.1);
  color: #7a96c8; cursor: pointer; white-space: nowrap;
  transition: background .15s, color .15s;
}
.grfb-date__toggle--active { background: rgba(42,245,152,.12); color: #2af598; border-color: rgba(42,245,152,.3); }
.grfb-apply {
  width: 26px; height: 26px; border-radius: 7px; flex-shrink: 0;
  background: linear-gradient(135deg, #2af598 0%, #009efd 100%); border: none;
  color: #06111f; cursor: pointer; font-size: 11px;
  display: inline-flex; align-items: center; justify-content: center;
  transition: filter .15s;
}
.grfb-apply:hover:not(:disabled) { filter: brightness(1.08); }
.grfb-apply:disabled { opacity: .5; cursor: not-allowed; }

.grfb-pill {
  padding: 4px 9px; border-radius: 16px; font-size: 9.5px; font-weight: 700;
  background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.1);
  color: #7a96c8; cursor: pointer; white-space: nowrap; flex-shrink: 0;
  transition: background .15s, color .15s, border-color .15s;
}
.grfb-pill--active { background: rgba(123,79,255,.16); color: #c8b8ff; border-color: rgba(123,79,255,.4); }
.grfb-pill:disabled { opacity: .5; cursor: not-allowed; }

@media (max-width: 380px) {
  .grfb-date__input { width: 108px; }
}
</style>
