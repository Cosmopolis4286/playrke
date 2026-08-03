<script setup lang="ts">
import { ref } from 'vue'
import { GAME_VARIANT_LABELS } from '@js/modules/games/bingo/room/game-rooms/gameRoomUtils'
import type { GameRoomFilters } from '@js/types/games/bingo/room/game-rooms/bingo-game-room'

const props = defineProps<{
  filters: GameRoomFilters
  loading: boolean
}>()

const emit = defineEmits<{ apply: []; reset: [] }>()

const showDatePicker = ref(false)
const showRange = ref(false)

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
</script>

<template>
  <div class="grfb-bar">
    <!-- Toggle fecha: colapsado por defecto, evita ocupar 2 inputs siempre visibles -->
    <div class="grfb-date-wrap">
      <button
        type="button"
        class="grfb-icon-btn"
        :class="{ 'grfb-icon-btn--active': !!filters.dateFrom || showDatePicker }"
        title="Filtrar por fecha"
        @click="showDatePicker = !showDatePicker"
      >
        <i class="fas fa-calendar-day" />
        <span v-if="filters.dateFrom" class="grfb-date-badge">{{ filters.dateFrom.slice(5) }}</span>
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

    <div class="grfb-variants">
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
.grfb-bar {
  display: flex; flex-wrap: wrap; align-items: center; justify-content: center;
  gap: 6px; width: 100%;
}

.grfb-date-wrap { position: relative; }

.grfb-icon-btn {
  position: relative;
  width: 30px; height: 30px; border-radius: 9px;
  display: inline-flex; align-items: center; justify-content: center;
  background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.1);
  color: #7a96c8; font-size: 12px; cursor: pointer;
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
  position: absolute; top: calc(100% + 6px); left: 0; z-index: 20;
  display: flex; align-items: center; gap: 6px; flex-wrap: nowrap;
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

.grfb-variants {
  display: flex; gap: 4px; overflow-x: auto; scrollbar-width: none;
  max-width: 100%;
}
.grfb-variants::-webkit-scrollbar { display: none; }
.grfb-pill {
  padding: 5px 10px; border-radius: 20px; font-size: 10.5px; font-weight: 700;
  background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.1);
  color: #7a96c8; cursor: pointer; white-space: nowrap; flex-shrink: 0;
  transition: background .15s, color .15s, border-color .15s;
}
.grfb-pill--active { background: rgba(123,79,255,.16); color: #c8b8ff; border-color: rgba(123,79,255,.4); }
.grfb-pill:disabled { opacity: .5; cursor: not-allowed; }

@media (max-width: 380px) {
  .grfb-date-popover { left: 50%; transform: translateX(-50%); }
  .grfb-date__input { width: 108px; }
}
</style>
