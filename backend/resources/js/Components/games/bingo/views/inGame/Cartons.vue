<template>
  <div class="ctn-root">

    <CartonsToolbar
      :cartons-count="cartons.length"
      :total-drawn="totalDrawn"
      :total-chips="totalChips"
      :remaining-chips="remainingChips"
      :streak="streak"
      :visible-filters="visibleFilters"
      :active-filter="activeFilter"
      :get-filter-count="getFilterCount"
      :variant-def="variantDef"
      :card-size="cardSize"
      :fresh-hot-active="_freshHotVer > 0 && _freshHotSet.size > 0"
      @update:active-filter="activeFilter = $event"
      @update:card-size="cardSize = $event"
    />

    <!-- ── Card grid ── -->
    <TransitionGroup name="ctn-pop" tag="div" class="ctn-grid" :class="`ctn-grid--${cardSize}`">
      <CartonCard
        v-for="(card, idx) in filteredCards"
        :key="card.id || idx"
        :card="card"
        :idx="idx"
        :variant-def="variantDef"
        :card-size="cardSize"
        :is-winner="isWinner(card)"
        :is-hot="isHot(card)"
        :is-active="selectedId === card.id"
        :is-hit="isHit(card)"
        :is-fresh-hot="isFreshHot(card)"
        :is-focus-pulse="focusPulseId === card.id"
        :num="num"
        :drawn="drawn"
        :cell-cls="cellCls"
        :is-free-cell="isFreeCell"
        :hits="hits(card)"
        @click="selectedId = selectedId === card.id ? null : card.id"
      />
    </TransitionGroup>

    <CartonDetailModal
      :selected-card="selectedCard"
      :variant-def="variantDef"
      :bingo-pattern="bingoPattern"
      :is-winner="selectedCard ? isWinner(selectedCard) : false"
      :is-hot="selectedCard ? isHot(selectedCard) : false"
      :num="num"
      :drawn="drawn"
      :cell-cls="cellCls"
      :is-free-cell="isFreeCell"
      :hits="selectedCard ? hits(selectedCard) : 0"
      :pct="selectedCard ? pct(selectedCard) : 0"
      @close="selectedId = null"
    />

    <!-- ── Spectator / empty states ── -->
    <CartonsSpectatorView
      v-if="cartons.length === 0"
      :total-drawn="totalDrawn"
      :total-chips="totalChips"
      :variant-def="variantDef"
      :col-numbers="colNumbers"
      :drawn-set="drawnSet"
      :latest-drawn="drawnNumbers?.[drawnNumbers.length - 1]"
    />

  </div>
</template>

<script setup lang="ts">
import { computed, watch, ref, onMounted, onUnmounted } from 'vue'
import type { BingoCard, CartonFilter } from '@/js/types/games/bingo/card/in-game/bingo-card'
import { useBingoSound } from '@js/composables/games/bingo/useBingoSound'
import { getBingoVariantDef, type BingoVariantDef, type BingoVariantColumn } from '@/js/modules/games/bingo/utils/bingoVariants'
import { useBingoCardSize } from '@js/composables/games/bingo/cartons/useBingoCardSize'
import { useBingoCardStats, useCartonFilterCounts, isFreeCell } from '@js/composables/games/bingo/cartons/useBingoCardStats'
import { useBingoCardDetection } from '@js/composables/games/bingo/cartons/useBingoCardDetection'
import { useBingoFocusPulse } from '@js/composables/games/bingo/cartons/useBingoFocusPulse'
import CartonsToolbar from './Cartons/CartonsToolbar.vue'
import CartonCard from './Cartons/CartonCard.vue'
import CartonDetailModal from './Cartons/CartonDetailModal.vue'
import CartonsSpectatorView from './Cartons/CartonsSpectatorView.vue'

interface Props {
  cartons?:        BingoCard[]
  customerLogo?:   string
  roomId?:         string
  drawnNumbers?:   number[]
  totalDrawn?:     number
  remainingChips?: number
  gameVariant?:    string
  hotThreshold?:   number
  bingoPattern?:   string
}

const props = withDefaults(defineProps<Props>(), {
  cartons:        () => [],
  customerLogo:   null,
  roomId:         '',
  drawnNumbers:   () => [],
  totalDrawn:     0,
  remainingChips: undefined,
  gameVariant:    'BINGO_75',
  hotThreshold:   10,
  bingoPattern:   '',
})

const emit = defineEmits<{
  (e: 'bingo-detected', cards: BingoCard[]): void
  (e: 'card-hit', cardId: string): void
  (e: 'card-selected', id: string | null): void
}>()

const variantDef = computed<BingoVariantDef>(() => getBingoVariantDef(props.gameVariant))
const totalChips = computed(() => variantDef.value.totalChips)
const cartonsComputed = computed(() => props.cartons)
const drawnNumbersComputed = computed(() => props.drawnNumbers)
const hotThresholdComputed = computed(() => props.hotThreshold)

const activeFilter = ref<CartonFilter>('all')
const { cardSize } = useBingoCardSize(props.cartons?.length ?? 0)
const selectedId = ref<string | null>(null)
watch(selectedId, id => emit('card-selected', id))

const { playSelect, playWinner } = useBingoSound()

// computed sobre el prop array — Vue rastrea la dependencia automáticamente,
// sin necesidad de triggerRef ni rutas de actualización manuales.
const drawnSet = computed(() => new Set<number>(props.drawnNumbers))

const selectedCard = computed(() =>
  selectedId.value ? props.cartons.find(c => c.id === selectedId.value) ?? null : null
)

const { drawn, cellCls, hits, pct, isHot, fullBingo } = useBingoCardStats(
  cartonsComputed, drawnSet, variantDef, hotThresholdComputed,
)

const {
  streak, isWinner, isHit, isFreshHot, _freshHotVer, _freshHotSet,
} = useBingoCardDetection({
  cartons: cartonsComputed,
  drawnNumbers: drawnNumbersComputed,
  variantDef,
  hotThreshold: hotThresholdComputed,
  fullBingo,
  isHot,
  onBingoDetected: (cards) => { playWinner(); emit('bingo-detected', cards) },
  onCardHit: (cardId) => emit('card-hit', cardId),
  onAnyHit: () => { playSelect(); navigator.vibrate?.(80) },
})

const { filterCounts, getFilterCount } = useCartonFilterCounts(cartonsComputed, isWinner, isHot)

const visibleFilters = computed(() => {
  const base: { value: CartonFilter; label: string; icon: string }[] = [
    { value: 'all', label: 'Todos', icon: 'fas fa-border-all' },
  ]
  if (getFilterCount('hot') > 0) base.push({ value: 'hot', label: 'Caliente', icon: 'fas fa-fire' })
  if (getFilterCount('win') > 0) base.push({ value: 'win', label: 'Bingo',    icon: 'fas fa-trophy' })
  return base
})

const filteredCards = computed(() => {
  if (activeFilter.value === 'win') return props.cartons.filter(c => isWinner(c))
  if (activeFilter.value === 'hot') return props.cartons.filter(c => isHot(c) && !isWinner(c))
  return props.cartons
})

function num(card: BingoCard, colLabel: string, r: number): number | null {
  const m = card.cardMatrix as Record<string, (number | null)[]> | null
  if (!m) return null
  const val = m[colLabel]?.[r - 1]
  return (val === undefined || val === null) ? null : val
}

// Menor cantidad de fichas que le faltan al jugador para completar su mejor
// cartón (asumiendo cartón lleno, igual criterio que fullBingo() más abajo).
// null si no tiene cartones activos o ya ganó — usado por el padre para
// mostrar un indicador personal fijo ("te faltan N fichas").
const myMissing = computed<number | null>(() => {
  const active = props.cartons.filter(c => !isWinner(c))
  if (active.length === 0) return null
  let min = variantDef.value.totalCells
  for (const card of active) {
    const missing = variantDef.value.totalCells - hits(card)
    if (missing < min) min = missing
  }
  return min
})

// Hasta 3 cartones activos más cerca de completar, ordenados por menor missing
// — usado por el header para listar serial + fichas faltantes de cada uno.
const topCards = computed<{ id: string; serial: string; missing: number }[]>(() => {
  return props.cartons
    .filter(c => !isWinner(c))
    .map(c => ({ id: c.id, serial: c.serialNumber, missing: variantDef.value.totalCells - hits(c) }))
    .sort((a, b) => a.missing - b.missing)
    .slice(0, 3)
})

const { focusPulseId, pulseCard } = useBingoFocusPulse(activeFilter, () => filteredCards.value.map(c => c.id))

defineExpose({ myMissing, topCards, pulseCard, streak })

// La variante no cambia durante la partida — mapa estático construido una vez.
function buildColNumbersMap(def: BingoVariantDef): Map<string, number[]> {
  const map = new Map<string, number[]>()
  for (const col of def.columns)
    map.set(col.label, Array.from({ length: col.max - col.min + 1 }, (_, i) => col.min + i))
  return map
}
const colNumbersMap = ref(buildColNumbersMap(variantDef.value))
watch(variantDef, (def) => { colNumbersMap.value = buildColNumbersMap(def) })

function colNumbers(col: BingoVariantColumn): number[] {
  return colNumbersMap.value.get(col.label) ?? []
}

function onEscapeKey(e: KeyboardEvent) {
  if (e.key === 'Escape' && selectedId.value) selectedId.value = null
}
onMounted(() => { window.addEventListener('keydown', onEscapeKey) })
onUnmounted(() => { window.removeEventListener('keydown', onEscapeKey) })
</script>

<style scoped>
/* ═══════════════════════════════════════════
   ROOT
═══════════════════════════════════════════ */
.ctn-root { width: 100%; display: flex; flex-direction: column; gap: 10px; }

/* ═══════════════════════════════════════════
   GRID
═══════════════════════════════════════════ */
.ctn-grid { display: grid; gap: 12px; }
.ctn-grid--md { grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); }
.ctn-grid--sm { grid-template-columns: repeat(auto-fill, minmax(148px, 1fr)); gap: 8px; }

/* ═══════════════════════════════════════════
   TRANSITIONS
═══════════════════════════════════════════ */
.ctn-pop-enter-active  { transition: opacity 0.3s ease, transform 0.3s ease; }
.ctn-pop-enter-from    { opacity: 0; transform: translateY(12px) scale(0.96); }
.ctn-pop-leave-active  { transition: opacity 0.15s ease; }
.ctn-pop-leave-to      { opacity: 0; }

/* ═══════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════ */
@media (max-width: 600px) {
  .ctn-grid--md { grid-template-columns: repeat(2, 1fr); gap: 8px; }
  .ctn-grid--sm { grid-template-columns: repeat(2, 1fr); gap: 6px; }
  :deep(.ctn-bar) { gap: 6px; padding: 7px 10px; }
  :deep(.ctn-bar__filters) { justify-content: flex-start; }
  :deep(.ctn-legend) { display: none; }
}
@media (max-width: 380px) {
  .ctn-grid--md, .ctn-grid--sm { grid-template-columns: repeat(2, 1fr); gap: 6px; }
  :deep(.ctn-head__l) { font-size: 12px; padding: 5px 0 4px; }
  :deep(.ctn-num) { font-size: 11px; }
  :deep(.ctn-ball span) { font-size: 9px; }
}
</style>
