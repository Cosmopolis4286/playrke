<script setup lang="ts">
import type { BingoVariantDef } from '@/js/modules/games/bingo/utils/bingoVariants'
import type { CartonFilter } from '@/js/types/games/bingo/card/in-game/bingo-card'

defineProps<{
  cartonsCount: number
  totalDrawn: number
  totalChips: number
  remainingChips: number | undefined
  streak: number
  visibleFilters: { value: CartonFilter; label: string; icon: string }[]
  activeFilter: CartonFilter
  getFilterCount: (f: CartonFilter) => number
  variantDef: BingoVariantDef
  cardSize: 'sm' | 'md'
  freshHotActive: boolean
}>()

defineEmits<{
  'update:activeFilter': [value: CartonFilter]
  'update:cardSize': [value: 'sm' | 'md']
}>()
</script>

<template>
  <div class="ctn-bar" v-if="cartonsCount > 0 || totalDrawn > 0">
    <div class="ctn-bar__left">
      <i class="fas fa-ticket-alt ctn-bar__icon" />
      <span class="ctn-bar__title">CARTONES</span>
      <span class="ctn-bar__count">{{ cartonsCount }}</span>
      <span v-if="totalDrawn > 0" class="ctn-bar__drawn">
        <i class="fas fa-shuffle" />{{ totalDrawn }}<em>/{{ totalChips }}</em>
      </span>
      <span v-if="remainingChips !== undefined && remainingChips > 0 && totalDrawn > 0" class="ctn-bar__remaining">
        <i class="fas fa-hourglass-half" />{{ remainingChips }}
      </span>
      <Transition name="ctn-streak">
        <span v-if="streak >= 2" class="ctn-bar__streak" :key="streak">
          <i class="fas fa-bolt" />{{ streak }}
        </span>
      </Transition>
    </div>

    <div class="ctn-bar__filters">
      <button
        v-for="f in visibleFilters"
        :key="f.value"
        class="ctn-fbtn"
        :class="{
          'ctn-fbtn--active':  activeFilter === f.value,
          'ctn-fbtn--new-hot': f.value === 'hot' && freshHotActive && activeFilter !== 'hot',
        }"
        @click="$emit('update:activeFilter', f.value)"
      >
        <i :class="f.icon" />
        {{ f.label }}
        <span class="ctn-fbtn__n">{{ getFilterCount(f.value) }}</span>
      </button>
    </div>

    <div class="ctn-bar__right">
      <!-- Legend dots for BINGO columns -->
      <div v-if="totalDrawn > 0 && variantDef.columns.length <= 5" class="ctn-legend">
        <span
          v-for="col in variantDef.columns"
          :key="col.label"
          class="ctn-legend__dot"
          :class="`ctn-legend__dot--col${col.colorIdx}`"
          :title="`${col.label}: ${col.min}–${col.max}`"
        >{{ col.label }}</span>
      </div>
      <div class="ctn-sizebar">
        <button class="ctn-sz" :class="{ 'ctn-sz--on': cardSize === 'sm' }" @click="$emit('update:cardSize', 'sm')" title="Compacto"><i class="fas fa-th" /></button>
        <button class="ctn-sz" :class="{ 'ctn-sz--on': cardSize === 'md' }" @click="$emit('update:cardSize', 'md')" title="Normal"><i class="fas fa-th-large" /></button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.ctn-bar {
  display: flex; align-items: center; gap: 10px;
  padding: 8px 14px;
  background: rgba(255,255,255,0.024);
  border: 1px solid rgba(80,110,220,0.12);
  border-radius: 12px; flex-wrap: wrap;
}
.ctn-bar__left  { display: flex; align-items: center; gap: 7px; flex-shrink: 0; }
.ctn-bar__right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; margin-left: auto; }

.ctn-bar__icon { color: #7b4fff; font-size: 11px; }
.ctn-bar__title {
  font-size: 10px; font-weight: 800; color: #5a70a0;
  text-transform: uppercase; letter-spacing: 1px;
}
.ctn-bar__count {
  background: rgba(123,79,255,0.14); border: 1px solid rgba(123,79,255,0.26);
  color: #a090e0; border-radius: 20px; padding: 1px 8px;
  font-size: 10px; font-weight: 800;
}
.ctn-bar__drawn {
  display: flex; align-items: center; gap: 4px;
  font-size: 10px; font-weight: 700; color: #2af598;
  padding: 2px 8px;
  background: rgba(42,245,152,0.06); border: 1px solid rgba(42,245,152,0.16);
  border-radius: 20px; letter-spacing: 0.2px;
}
.ctn-bar__drawn i   { font-size: 9px; opacity: 0.6; }
.ctn-bar__drawn em  { opacity: 0.35; font-style: normal; font-weight: 500; }
.ctn-bar__remaining {
  display: flex; align-items: center; gap: 4px;
  font-size: 10px; font-weight: 700; color: #7b8ec8;
  padding: 2px 8px;
  background: rgba(120,140,220,0.06); border: 1px solid rgba(120,140,220,0.14);
  border-radius: 20px; letter-spacing: 0.2px;
}
.ctn-bar__remaining i { font-size: 8px; opacity: 0.55; }
.ctn-bar__streak {
  display: flex; align-items: center; gap: 3px;
  font-size: 10px; font-weight: 800; color: #ffd060;
  padding: 2px 8px;
  background: rgba(255,200,0,0.08); border: 1px solid rgba(255,200,0,0.25);
  border-radius: 20px; letter-spacing: 0.2px;
  animation: streak-pop 0.35s cubic-bezier(0.34,1.56,0.64,1);
}
.ctn-bar__streak i { font-size: 9px; opacity: 0.8; }
@keyframes streak-pop { from { transform: scale(0.6); opacity: 0; } to { transform: scale(1); opacity: 1; } }
.ctn-streak-enter-active { animation: streak-pop 0.35s cubic-bezier(0.34,1.56,0.64,1); }
.ctn-streak-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.ctn-streak-leave-to     { opacity: 0; transform: scale(0.8); }

/* Filters */
.ctn-bar__filters { display: flex; align-items: center; gap: 4px; flex: 1; justify-content: center; flex-wrap: wrap; }
.ctn-fbtn {
  display: flex; align-items: center; gap: 4px;
  padding: 3px 10px; border-radius: 8px;
  border: 1px solid rgba(80,110,220,0.15); background: transparent;
  color: #4a5e90; font-size: 10px; font-weight: 600; cursor: pointer;
  transition: all 0.14s ease; letter-spacing: 0.2px;
}
.ctn-fbtn:hover { border-color: rgba(100,140,255,0.3); color: #7a90c0; }
.ctn-fbtn:active, .ctn-sz:active { transform: scale(0.92); }
.ctn-fbtn--active { background: rgba(100,140,255,0.1); border-color: rgba(100,140,255,0.35); color: #b0c4ff; }
.ctn-fbtn__n {
  min-width: 14px; height: 14px; padding: 0 3px; border-radius: 7px;
  background: rgba(100,140,255,0.1); font-size: 9px; font-weight: 800; color: #6a80b0;
  display: inline-flex; align-items: center; justify-content: center;
}
.ctn-fbtn--active .ctn-fbtn__n { background: rgba(42,245,152,0.14); color: #2af598; }
.ctn-fbtn--new-hot {
  border-color: rgba(255,140,30,0.45); color: #ffb060;
  animation: fbtn-hot-pulse 0.7s ease-in-out infinite alternate;
}
@keyframes fbtn-hot-pulse {
  from { box-shadow: 0 0 0 0 rgba(255,140,30,0.0); background: transparent; }
  to   { box-shadow: 0 0 8px 2px rgba(255,140,30,0.25); background: rgba(255,140,30,0.08); }
}

/* Legend */
.ctn-legend { display: flex; align-items: center; gap: 3px; }
.ctn-legend__dot {
  width: 16px; height: 16px; border-radius: 50%;
  font-size: 7.5px; font-weight: 800; color: #fff;
  display: flex; align-items: center; justify-content: center;
  text-shadow: 0 1px 2px rgba(0,0,0,0.5);
  box-shadow: 0 1px 4px rgba(0,0,0,0.35);
  cursor: default;
}
.ctn-legend__dot--col0 { background: radial-gradient(circle at 38% 32%, var(--bc0-light), var(--bc0-solid) 60%, var(--bc0-dark)); box-shadow: 0 1px 6px var(--bc0-glow); }
.ctn-legend__dot--col1 { background: radial-gradient(circle at 38% 32%, var(--bc1-light), var(--bc1-solid) 60%, var(--bc1-dark)); box-shadow: 0 1px 6px var(--bc1-glow); }
.ctn-legend__dot--col2 { background: radial-gradient(circle at 38% 32%, var(--bc2-light), var(--bc2-solid) 60%, var(--bc2-dark)); box-shadow: 0 1px 6px var(--bc2-glow); }
.ctn-legend__dot--col3 { background: radial-gradient(circle at 38% 32%, var(--bc3-light), var(--bc3-solid) 60%, var(--bc3-dark)); box-shadow: 0 1px 6px var(--bc3-glow); }
.ctn-legend__dot--col4 { background: radial-gradient(circle at 38% 32%, var(--bc4-light), var(--bc4-solid) 60%, var(--bc4-dark)); box-shadow: 0 1px 6px var(--bc4-glow); }

/* Size toggle */
.ctn-sizebar { display: flex; gap: 2px; }
.ctn-sz {
  width: 26px; height: 26px; border-radius: 7px;
  border: 1px solid rgba(80,110,220,0.16); background: transparent;
  color: #384870; font-size: 11px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: all 0.13s ease;
}
.ctn-sz:hover  { border-color: rgba(100,140,255,0.3); color: #6a80b0; }
.ctn-sz--on    { background: rgba(100,140,255,0.1); border-color: rgba(100,140,255,0.35); color: #b0c4ff; }
</style>
