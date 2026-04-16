<template>
  <div class="ctn-grid">
    <TransitionGroup name="card-appear" tag="div" class="ctn-grid__inner">
      <div
        v-for="(card, index) in displayCards"
        :key="card.id || index"
        class="ctn-card"
        :style="{ '--delay': `${index * 70}ms` }"
        translate="no"
      >
        <!-- Top shimmer line -->
        <div class="ctn-card__shimmer" />

        <!-- BINGO header -->
        <div class="ctn-header">
          <span
            v-for="letter in letters"
            :key="letter"
            class="ctn-header__letter"
          >{{ letter }}</span>
        </div>

        <!-- Number grid -->
        <div class="ctn-body">
          <template v-for="letter in letters" :key="letter">
            <div
              v-for="row in 5"
              :key="`${letter}-${row}`"
              class="ctn-cell"
              :class="getCellClass(card, letter, row)"
            >
              <!-- Free center -->
              <template v-if="letter === 'N' && row === 3">
                <div class="ctn-cell__free">
                  <img
                    :src="customerLogo || defaultLogo"
                    alt="★"
                    class="ctn-cell__logo"
                  />
                </div>
              </template>

              <!-- Drawn number -->
              <template v-else-if="isDrawn(card, letter, row)">
                <div class="ctn-cell__ball">
                  <span class="ctn-cell__ball-num">
                    {{ getCardNumber(card, letter, row) }}
                  </span>
                </div>
              </template>

              <!-- Normal number -->
              <template v-else>
                <span class="ctn-cell__num">
                  {{ getCardNumber(card, letter, row) ?? '–' }}
                </span>
              </template>
            </div>
          </template>
        </div>

        <!-- Serial footer -->
        <div class="ctn-serial">
          <i class="fas fa-barcode ctn-serial__icon" />
          <span>{{ card.serialNumber }}</span>
          <span v-if="getCardHits(card) > 0" class="ctn-serial__hits">
            {{ getCardHits(card) }} ✓
          </span>
        </div>
      </div>
    </TransitionGroup>

    <!-- Empty state -->
    <div v-if="displayCards.length === 0" class="ctn-empty">
      <i class="fas fa-ticket-alt ctn-empty__icon" />
      <p>No hay cartones disponibles</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, watch, ref } from "vue";
import { type BingoCard } from "@js/Services/bingoService";

interface Props {
  cartons?: BingoCard[];
  customerLogo?: string;
  roomId?: string;
  drawnNumbers?: number[];
  autoCheckBingo?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  cartons: () => [],
  customerLogo: null,
  roomId: "",
  drawnNumbers: () => [],
  autoCheckBingo: true,
});

const emit = defineEmits<{
  (e: "bingo-detected", card: BingoCard): void;
}>();

const defaultLogo = "/img/rke.png";
const letters = ["B", "I", "N", "G", "O"] as const;
const bingoTriggered = ref<Set<string>>(new Set());

const displayCards = computed(() => props.cartons);

const drawnSet = computed(() => new Set(props.drawnNumbers));

function getCardNumber(card: BingoCard, letter: string, position: number): number | null {
  const matrix = card.cardMatrix;
  if (!matrix || !matrix[letter as keyof typeof matrix]) return null;
  return (matrix[letter as keyof typeof matrix] as number[])[position - 1] || null;
}

function isDrawn(card: BingoCard, letter: string, row: number): boolean {
  const num = getCardNumber(card, letter, row);
  return num !== null && drawnSet.value.has(num);
}

function getCellClass(card: BingoCard, letter: string, row: number) {
  if (letter === "N" && row === 3) return "ctn-cell--free";
  if (isDrawn(card, letter, row)) return "ctn-cell--hit";
  return "";
}

function getCardHits(card: BingoCard): number {
  let hits = 0;
  for (const letter of letters) {
    for (let row = 1; row <= 5; row++) {
      if (letter === "N" && row === 3) continue;
      if (isDrawn(card, letter, row)) hits++;
    }
  }
  return hits;
}

// Verifica si un cartón tiene bingo completo (24 números + centro libre)
function hasFullBingo(card: BingoCard): boolean {
  for (const letter of letters) {
    for (let row = 1; row <= 5; row++) {
      // El centro es libre (N3)
      if (letter === "N" && row === 3) continue;

      const num = getCardNumber(card, letter, row);
      if (num === null || !drawnSet.value.has(num)) {
        return false;
      }
    }
  }
  return true;
}

// Watcher para detectar bingo automáticamente cuando cambian los números sorteados
watch(
  () => props.drawnNumbers,
  () => {
    if (!props.autoCheckBingo) return;

    for (const card of props.cartons) {
      // Evitar disparar múltiples veces para el mismo cartón
      if (bingoTriggered.value.has(card.id)) continue;

      if (hasFullBingo(card)) {
        bingoTriggered.value.add(card.id);
        emit("bingo-detected", card);
      }
    }
  },
  { deep: true }
);
</script>

<style scoped>
/* ══════════════════════════════════════
   GRID
══════════════════════════════════════ */
.ctn-grid { width: 100% }

.ctn-grid__inner {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(195px, 1fr));
  gap: 16px;
}

/* ══════════════════════════════════════
   CARD
══════════════════════════════════════ */
.ctn-card {
  position: relative;
  border-radius: 18px;
  overflow: hidden;
  background: linear-gradient(160deg, #0d1633 0%, #080f26 100%);
  border: 1px solid rgba(100,140,255,0.18);
  box-shadow: 0 8px 32px rgba(0,0,0,0.55), 0 0 0 1px rgba(80,120,255,0.05) inset;
  transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
  animation: card-in 0.45s cubic-bezier(0.34,1.56,0.64,1) both;
  animation-delay: var(--delay, 0ms);
}
.ctn-card:hover {
  transform: translateY(-4px) scale(1.01);
  box-shadow: 0 20px 56px rgba(0,0,0,0.65), 0 0 30px rgba(42,245,152,0.1);
  border-color: rgba(42,245,152,0.3);
}

@keyframes card-in {
  from { opacity: 0; transform: translateY(20px) scale(0.95) }
  to   { opacity: 1; transform: translateY(0)   scale(1) }
}

/* Top shimmer */
.ctn-card__shimmer {
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(100,140,255,0.5), rgba(42,245,152,0.35), transparent);
  z-index: 2;
}

/* ══════════════════════════════════════
   HEADER — B I N G O
══════════════════════════════════════ */
.ctn-header {
  position: relative;
  z-index: 1;
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  background: linear-gradient(135deg, #130840 0%, #0a1860 50%, #130840 100%);
  border-bottom: 1px solid rgba(100,140,255,0.18);
}
.ctn-header__letter {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px 0;
  font-family: 'Anton', sans-serif;
  font-size: 18px;
  letter-spacing: 1px;
  background: linear-gradient(180deg, #ffffff 0%, #a0b8ff 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* ══════════════════════════════════════
   BODY — number grid
══════════════════════════════════════ */
.ctn-body {
  position: relative;
  z-index: 1;
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  background: #eef2ff;
}

.ctn-cell {
  display: flex;
  align-items: center;
  justify-content: center;
  aspect-ratio: 1;
  border: 0.5px solid rgba(160,180,240,0.45);
  position: relative;
  transition: background 0.2s ease;
}

/* Normal number */
.ctn-cell__num {
  font-family: 'Poppins', sans-serif;
  font-size: 13px;
  font-weight: 600;
  color: #1a2050;
  line-height: 1;
  z-index: 1;
}

/* ── Hit cell — drawn number ── */
.ctn-cell--hit {
  background: linear-gradient(135deg, #1a0a4a 0%, #0a1a6a 100%);
}
.ctn-cell__ball {
  width: 78%;
  height: 78%;
  border-radius: 50%;
  background: linear-gradient(135deg, #d60f26 0%, #8b0024 100%);
  box-shadow:
    0 0 0 1.5px rgba(214,15,38,0.4),
    0 2px 8px rgba(0,0,0,0.5),
    inset 0 1px 0 rgba(255,255,255,0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  animation: ball-in 0.35s cubic-bezier(0.34,1.56,0.64,1) both;
}
@keyframes ball-in {
  from { transform: scale(0) rotate(-15deg); opacity: 0 }
  to   { transform: scale(1) rotate(0deg);  opacity: 1 }
}
.ctn-cell__ball-num {
  font-family: 'Poppins', sans-serif;
  font-size: 11px;
  font-weight: 800;
  color: #fff;
  line-height: 1;
  text-shadow: 0 1px 2px rgba(0,0,0,0.5);
}

/* ── Free center cell ── */
.ctn-cell--free {
  background: linear-gradient(135deg, #1a0a4a 0%, #0a1a5c 100%);
}
.ctn-cell__free {
  width: 80%; height: 80%;
  border-radius: 50%;
  background: linear-gradient(135deg, rgba(42,245,152,0.2) 0%, rgba(0,158,253,0.15) 100%);
  border: 1px solid rgba(42,245,152,0.3);
  display: flex; align-items: center; justify-content: center;
}
.ctn-cell__logo {
  max-width: 65%; max-height: 65%;
  object-fit: contain;
  filter: brightness(1.1);
}

/* ══════════════════════════════════════
   SERIAL FOOTER
══════════════════════════════════════ */
.ctn-serial {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  padding: 5px 10px;
  background: rgba(0,4,20,0.65);
  border-top: 1px solid rgba(100,140,255,0.1);
  font-size: 9px;
  color: #3a4e80;
  letter-spacing: 0.3px;
  font-family: 'Courier New', monospace;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.ctn-serial__icon { font-size: 9px; opacity: 0.4; flex-shrink: 0 }
.ctn-serial__hits {
  margin-left: auto;
  font-size: 9px;
  font-weight: 700;
  color: #2af598;
  letter-spacing: 0.3px;
  font-family: 'Poppins', sans-serif;
  flex-shrink: 0;
}

/* ══════════════════════════════════════
   EMPTY STATE
══════════════════════════════════════ */
.ctn-empty {
  grid-column: 1 / -1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
  padding: 70px 20px;
  color: #3a5080;
}
.ctn-empty__icon { font-size: 52px; opacity: 0.2 }
.ctn-empty p { font-size: 14px; margin: 0 }

/* ══════════════════════════════════════
   TRANSITIONS
══════════════════════════════════════ */
.card-appear-enter-active { transition: opacity 0.4s ease, transform 0.4s ease }
.card-appear-enter-from   { opacity: 0; transform: translateY(14px) }
.card-appear-leave-active { transition: opacity 0.2s ease }
.card-appear-leave-to     { opacity: 0 }

/* ══════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════ */
@media (max-width: 500px) {
  .ctn-grid__inner {
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
  }
  .ctn-header__letter { font-size: 14px; padding: 6px 0 }
  .ctn-cell__num { font-size: 11px }
  .ctn-cell__ball-num { font-size: 9px }
}
</style>
