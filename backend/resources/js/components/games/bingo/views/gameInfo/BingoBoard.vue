<!-- BingoBoard.vue -->
<template>
  <div ref="boardRef" class="container d-flex justify-content-center">
    <div class="bingo-content">
      <div
        v-for="(group, index) in bingoGroups"
        :key="index"
        class="bingo-row d-flex align-items-stretch my-1"
      >
        <!-- Etiqueta izquierda -->
        <div
          class="bingo-letter-box d-flex align-items-center justify-content-center me-3"
          :class="letterBgClass(index)"
          :style="{
            width: boxSize + 'px',
            height: letterHeight + 'px',
            fontSize: fontSize + 'rem',
            transition: 'height 0.3s ease',
          }"
        >
          {{ colLabels[index] }}
        </div>

        <!-- Números -->
        <div
          class="bingo-numbers"
          :style="{ gridTemplateColumns: gridTemplateColumnsStyle }"
        >
          <div
            v-for="number in group"
            :key="number"
            class="bingo-number d-flex align-items-center justify-content-center"
            :class="{
              'called-number': number !== null && calledNumbers.includes(number),
              'last-called': number === currentNumber,
            }"
            :style="{
              width: boxSize + 'px',
              height: boxSize + 'px',
              fontSize: fontSize + 'rem',
              transition: 'width 0.3s ease, height 0.3s ease',
            }"
          >
            {{ number }}
          </div>
        </div>

        <!-- Etiqueta derecha (opcional) -->
        <div
          v-if="showRightLetters"
          class="bingo-letter-box d-flex align-items-center justify-content-center ms-3"
          :class="letterBgClass(index)"
          :style="{
            width: boxSize + 'px',
            height: letterHeight + 'px',
            fontSize: fontSize + 'rem',
            transition: 'height 0.3s ease',
          }"
        >
          {{ colLabels[index] }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  ref,
  computed,
  onMounted,
  onBeforeUnmount,
  nextTick,
  watch,
} from "vue";

const props = defineProps({
  calledNumbers: {
    type: Array,
    default: () => [],
  },
  currentNumber: {
    type: Number,
    default: null,
  },
  variant: {
    type: String,
    default: "BINGO_75",
  },
});

// ── Definiciones de variante ──────────────────────────────────────────────────
const VARIANT_DEFS = {
  BINGO_75: {
    columns: [
      { label: "B", min: 1,  max: 15,  numbersPerGroup: 15 },
      { label: "I", min: 16, max: 30,  numbersPerGroup: 15 },
      { label: "N", min: 31, max: 45,  numbersPerGroup: 15 },
      { label: "G", min: 46, max: 60,  numbersPerGroup: 15 },
      { label: "O", min: 61, max: 75,  numbersPerGroup: 15 },
    ],
    bgClasses: [
      "bg-primary text-white",
      "bg-danger text-white",
      "bg-success text-white",
      "bg-warning text-dark",
      "bg-purple text-white",
    ],
  },
  BINGO_90: {
    columns: [
      { label: "1",  min: 1,  max: 9,  numbersPerGroup: 9 },
      { label: "2",  min: 10, max: 19, numbersPerGroup: 10 },
      { label: "3",  min: 20, max: 29, numbersPerGroup: 10 },
      { label: "4",  min: 30, max: 39, numbersPerGroup: 10 },
      { label: "5",  min: 40, max: 49, numbersPerGroup: 10 },
      { label: "6",  min: 50, max: 59, numbersPerGroup: 10 },
      { label: "7",  min: 60, max: 69, numbersPerGroup: 10 },
      { label: "8",  min: 70, max: 79, numbersPerGroup: 10 },
      { label: "9",  min: 80, max: 90, numbersPerGroup: 11 },
    ],
    bgClasses: [
      "bg-primary text-white",
      "bg-danger text-white",
      "bg-success text-white",
      "bg-warning text-dark",
      "bg-purple text-white",
      "bg-info text-white",
      "bg-dark text-white",
      "bg-secondary text-white",
      "bg-orange text-white",
    ],
  },
  BINGO_80: {
    columns: [
      { label: "1", min: 1,  max: 20, numbersPerGroup: 20 },
      { label: "2", min: 21, max: 40, numbersPerGroup: 20 },
      { label: "3", min: 41, max: 60, numbersPerGroup: 20 },
      { label: "4", min: 61, max: 80, numbersPerGroup: 20 },
    ],
    bgClasses: [
      "bg-primary text-white",
      "bg-danger text-white",
      "bg-success text-white",
      "bg-warning text-dark",
    ],
  },
  BINGO_30: {
    columns: [
      { label: "1", min: 1,  max: 10, numbersPerGroup: 10 },
      { label: "2", min: 11, max: 20, numbersPerGroup: 10 },
      { label: "3", min: 21, max: 30, numbersPerGroup: 10 },
    ],
    bgClasses: [
      "bg-primary text-white",
      "bg-danger text-white",
      "bg-success text-white",
    ],
  },
};

const variantDef = computed(() => VARIANT_DEFS[props.variant] ?? VARIANT_DEFS.BINGO_75);
const colLabels  = computed(() => variantDef.value.columns.map((c) => c.label));

const letterBgClass = (index) => variantDef.value.bgClasses[index] ?? "bg-secondary text-white";

const bingoGroups = computed(() => {
  return variantDef.value.columns.map((col) => {
    const group = [];
    for (let n = col.min; n <= col.max; n++) {
      group.push(n);
    }
    return group;
  });
});

// ── Layout ────────────────────────────────────────────────────────────────────
const boardRef = ref(null);
const gridTemplateColumnsStyle = ref("");
const boxSize  = ref(40);
const fontSize = ref(1);

const minBoxSize       = 40;
const maxBoxSize       = 58;
const cellGap          = 4;
const letterBoxMargin  = 12;

const showRightLetters = ref(false);

const maxGroupSize = computed(() =>
  Math.max(...variantDef.value.columns.map((c) => c.max - c.min + 1))
);

const updateLayout = () => {
  if (!boardRef.value) return;
  const containerWidth = boardRef.value.getBoundingClientRect().width;
  const totalLetterBoxes = 1 + (showRightLetters.value ? 1 : 0);
  const colCount = maxGroupSize.value;

  const sizeN =
    (containerWidth -
      totalLetterBoxes * (maxBoxSize + letterBoxMargin) -
      (colCount - 1) * cellGap) /
    colCount;

  if (sizeN >= minBoxSize) {
    const finalSize = Math.min(sizeN, maxBoxSize);
    boxSize.value = finalSize;
    gridTemplateColumnsStyle.value = `repeat(${colCount}, ${finalSize}px)`;
    fontSize.value = Math.min(2, finalSize / 40);
  } else {
    const fallbackCols = Math.ceil(colCount / 3);
    const sizeF =
      (containerWidth -
        totalLetterBoxes * (maxBoxSize + letterBoxMargin) -
        (fallbackCols - 1) * cellGap) /
      fallbackCols;
    const finalSize = Math.max(minBoxSize, Math.min(sizeF, maxBoxSize));
    boxSize.value = finalSize;
    gridTemplateColumnsStyle.value = `repeat(${fallbackCols}, ${finalSize}px)`;
    fontSize.value = Math.min(2, finalSize / 40);
  }
};

const isMultiRow = computed(() => {
  const m = gridTemplateColumnsStyle.value.match(/repeat\((\d+)/);
  if (!m) return false;
  return parseInt(m[1]) < maxGroupSize.value;
});

const letterHeight = computed(() => {
  if (!isMultiRow.value) return boxSize.value;
  const m = gridTemplateColumnsStyle.value.match(/repeat\((\d+)/);
  const cols = m ? parseInt(m[1]) : maxGroupSize.value;
  const rows = Math.ceil(maxGroupSize.value / cols);
  return boxSize.value * rows + cellGap * (rows - 1);
});

onMounted(() => {
  nextTick(() => updateLayout());
  window.addEventListener("resize", updateLayout);
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", updateLayout);
});

watch(showRightLetters, () => {
  nextTick(() => updateLayout());
});

watch(() => props.variant, () => {
  nextTick(() => updateLayout());
});
</script>

<style scoped>
.bingo-row {
  margin-bottom: 0;
  gap: 4px;
  width: 100%;
}

.bingo-letter-box {
  font-weight: bold;
  border-radius: 0.25rem;
  text-transform: uppercase;
  user-select: none;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.bingo-numbers {
  display: grid;
  gap: 4px;
  flex-grow: 0;
}

.bingo-number {
  background: #f1f1f1;
  border-radius: 0.25rem;
  font-weight: bold;
  user-select: none;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  color: black;
  z-index: 0;
}

.bingo-number.called-number {
  color: white !important;
  z-index: 10;
}

.bingo-number.called-number:not(.last-called)::before {
  content: "";
  position: absolute;
  top: 50%;
  left: 50%;
  width: 76%;
  height: 76%;
  background: radial-gradient(circle at center, #ff4d4d, #b30000);
  border-radius: 50%;
  transform: translate(-50%, -50%);
  box-shadow: 0 0 6px rgba(255, 0, 0, 0.5);
  z-index: -1;
  transition: background 0.3s ease;
  animation: pulseIn 0.3s ease-out;
}

.bingo-number.last-called::before {
  content: "";
  position: absolute;
  top: 50%;
  left: 50%;
  width: 85%;
  height: 85%;
  background: radial-gradient(circle at center, #ff4d4d, #b30000);
  border-radius: 50%;
  transform: translate(-50%, -50%);
  box-shadow: 0 0 6px rgba(255, 0, 0, 0.5);
  border: 2px solid gold;
  z-index: -1;
  animation: glowPulse 0.6s ease-out;
}

@keyframes pulseIn {
  0%   { transform: translate(-50%, -50%) scale(0.5); opacity: 0; }
  100% { transform: translate(-50%, -50%) scale(1);   opacity: 1; }
}

@keyframes glowPulse {
  0%   { transform: translate(-50%, -50%) scale(1.3); opacity: 0.6; }
  50%  { transform: translate(-50%, -50%) scale(1);   opacity: 1; }
  100% { transform: translate(-50%, -50%) scale(1);   opacity: 1; }
}

.bg-purple  { background-color: rebeccapurple !important; }
.bg-orange  { background-color: #e8720c !important; }
</style>
