<template>
  <div class="ctn-grid">
    <TransitionGroup name="card-appear" tag="div" class="ctn-grid__inner">
      <div
        v-for="(card, index) in displayCards"
        :key="card.id || index"
        class="ctn-card"
        :style="{ '--delay': `${index * 60}ms` }"
        translate="no"
      >
        <!-- Glow aura -->
        <div class="ctn-card__glow" />

        <!-- BINGO header -->
        <div class="ctn-header">
          <span v-for="letter in letters" :key="letter" class="ctn-header__letter">
            {{ letter }}
          </span>
        </div>

        <!-- Grid numbers -->
        <div class="ctn-body">
          <template v-for="(letter, li) in letters" :key="letter">
            <div
              v-for="row in 5"
              :key="`${letter}-${row}`"
              class="ctn-cell"
              :class="{
                'ctn-cell--free': letter === 'N' && row === 3,
              }"
            >
              <template v-if="letter === 'N' && row === 3">
                <img
                  :src="customerLogo || defaultLogo"
                  alt="★"
                  class="ctn-cell__logo"
                />
              </template>
              <template v-else>
                <span class="ctn-cell__num">
                  {{ getCardNumber(card, letter, row) ?? '–' }}
                </span>
              </template>
            </div>
          </template>
        </div>

        <!-- Serial -->
        <div class="ctn-serial">
          <i class="fas fa-barcode ctn-serial__icon" />
          <span>{{ card.serialNumber }}</span>
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
import { computed } from "vue";
import { type BingoCard } from '@js/Services/bingoService';

interface Props {
  cartons?: BingoCard[]
  customerLogo?: string
  roomId?: string
}

const props = withDefaults(defineProps<Props>(), {
  cartons: () => [],
  customerLogo: null,
  roomId: '',
});

const defaultLogo = "/img/rke.png";
const letters = ['B', 'I', 'N', 'G', 'O'] as const;

const displayCards = computed(() => props.cartons);

function getCardNumber(card: BingoCard, letter: string, position: number): number | null {
  const matrix = card.cardMatrix;
  if (!matrix || !matrix[letter as keyof typeof matrix]) return null;
  const col = matrix[letter as keyof typeof matrix];
  return col[position - 1] || null;
}
</script>

<style scoped>
/* ════════════════════════════════════════
   GRID WRAPPER
════════════════════════════════════════ */
.ctn-grid {
  width: 100%;
}
.ctn-grid__inner {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
  gap: 16px;
}

/* ════════════════════════════════════════
   CARD
════════════════════════════════════════ */
.ctn-card {
  position: relative;
  border-radius: 16px;
  overflow: hidden;
  background: linear-gradient(160deg, #0d1633 0%, #080f26 100%);
  border: 1px solid rgba(100, 140, 255, 0.18);
  box-shadow:
    0 8px 32px rgba(0, 0, 0, 0.55),
    0 0 0 1px rgba(80, 120, 255, 0.05) inset;
  transition: transform 0.22s ease, box-shadow 0.22s ease;
  animation: card-in 0.4s ease both;
  animation-delay: var(--delay, 0ms);
}
.ctn-card:hover {
  transform: translateY(-3px);
  box-shadow:
    0 16px 48px rgba(0, 0, 0, 0.65),
    0 0 24px rgba(42, 245, 152, 0.08);
  border-color: rgba(42, 245, 152, 0.25);
}

@keyframes card-in {
  from { opacity: 0; transform: translateY(14px) scale(0.97) }
  to   { opacity: 1; transform: translateY(0)   scale(1) }
}

/* Aura glow */
.ctn-card__glow {
  position: absolute;
  top: -40px; left: 50%;
  transform: translateX(-50%);
  width: 140px; height: 80px;
  background: radial-gradient(ellipse, rgba(100, 140, 255, 0.14) 0%, transparent 70%);
  pointer-events: none;
  z-index: 0;
}

/* ════════════════════════════════════════
   BINGO HEADER
════════════════════════════════════════ */
.ctn-header {
  position: relative;
  z-index: 1;
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  background: linear-gradient(90deg, #1a0a4a 0%, #0a1a5c 50%, #1a0a4a 100%);
  border-bottom: 1px solid rgba(100, 140, 255, 0.2);
}
.ctn-header__letter {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 7px 0;
  font-family: 'Anton', sans-serif;
  font-size: 17px;
  letter-spacing: 1px;
  background: linear-gradient(180deg, #c8d8ff, #7c9dff);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* ════════════════════════════════════════
   BODY (number grid)
════════════════════════════════════════ */
.ctn-body {
  position: relative;
  z-index: 1;
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  background: rgba(226, 235, 255, 0.96);
}

.ctn-cell {
  display: flex;
  align-items: center;
  justify-content: center;
  aspect-ratio: 1;
  border: 0.5px solid rgba(180, 200, 255, 0.5);
  position: relative;
  transition: background 0.15s;
}

.ctn-cell__num {
  font-family: 'Poppins', sans-serif;
  font-size: 13px;
  font-weight: 600;
  color: #0d1633;
  line-height: 1;
}

/* Free center cell */
.ctn-cell--free {
  background: linear-gradient(135deg, #1a0a4a, #0a1a5c);
}
.ctn-cell__logo {
  max-width: 68%;
  max-height: 68%;
  object-fit: contain;
}

/* Marked number (class added by game logic) */
.ctn-cell.marked .ctn-cell__num::after {
  content: '';
  position: absolute;
  inset: 10%;
  border-radius: 50%;
  background: linear-gradient(135deg, #d60f26, #b1003c);
  z-index: -1;
}
.ctn-cell.marked .ctn-cell__num {
  color: #fff;
  position: relative;
  z-index: 1;
}

/* ════════════════════════════════════════
   SERIAL
════════════════════════════════════════ */
.ctn-serial {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  padding: 5px 8px;
  background: rgba(0, 5, 20, 0.6);
  border-top: 1px solid rgba(100, 140, 255, 0.1);
  font-size: 9px;
  color: #3a4e80;
  letter-spacing: 0.4px;
  font-family: 'Courier New', monospace;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.ctn-serial__icon {
  font-size: 9px;
  opacity: 0.5;
  flex-shrink: 0;
}

/* ════════════════════════════════════════
   EMPTY STATE
════════════════════════════════════════ */
.ctn-empty {
  grid-column: 1 / -1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 60px 20px;
  color: #3a5080;
}
.ctn-empty__icon {
  font-size: 48px;
  opacity: 0.25;
}
.ctn-empty p {
  font-size: 14px;
  margin: 0;
}

/* ════════════════════════════════════════
   TRANSITION GROUP
════════════════════════════════════════ */
.card-appear-enter-active { transition: opacity 0.35s ease, transform 0.35s ease }
.card-appear-enter-from   { opacity: 0; transform: translateY(12px) }
.card-appear-leave-active { transition: opacity 0.2s ease }
.card-appear-leave-to     { opacity: 0 }

/* ════════════════════════════════════════
   RESPONSIVE
════════════════════════════════════════ */
@media (max-width: 480px) {
  .ctn-grid__inner {
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
  }
  .ctn-header__letter { font-size: 14px; padding: 5px 0 }
  .ctn-cell__num { font-size: 11px }
}
</style>
