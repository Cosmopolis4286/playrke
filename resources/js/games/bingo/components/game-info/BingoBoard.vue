<!-- BingoBoard.vue -->
<template>
  <div ref="boardRef" class="container d-flex justify-content-center">
    <div class="bingo-content">
      <div
        v-for="(group, index) in bingoGroups"
        :key="index"
        class="bingo-row d-flex align-items-stretch my-1"
      >
        <!-- Letra izquierda -->
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
          {{ letters[index] }}
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
              'called-number': calledNumbers.includes(number),
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

        <!-- Letra derecha (opcional) -->
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
          {{ letters[index] }}
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
});

const letters = ["B", "I", "N", "G", "O"];

const letterBgClass = (index) => {
  return (
    [
      "bg-primary text-white",
      "bg-danger text-white",
      "bg-success text-white",
      "bg-warning text-dark",
      "bg-purple text-white",
    ][index] || ""
  );
};

const bingoGroups = computed(() => {
  const groups = [];
  for (let i = 0; i < 5; i++) {
    const start = i * 15 + 1;
    const group = [];
    for (let n = start; n < start + 15; n++) {
      group.push(n);
    }
    groups.push(group);
  }
  return groups;
});

const boardRef = ref(null);
const gridTemplateColumnsStyle = ref("");
const boxSize = ref(40);
const fontSize = ref(1);

const minBoxSize = 40;
const maxBoxSize = 58;
const cellGap = 4;
const letterBoxMargin = 12; // cada letra tiene un margin: 12px (Bootstrap me-3 / ms-3)

const showRightLetters = ref(false); // activar/desactivar conjunto derecho

const updateLayout = () => {
  if (!boardRef.value) return;
  const containerWidth = boardRef.value.getBoundingClientRect().width;

  // Contar cuántas cajas de letras hay (izquierda + posible derecha)
  const totalLetterBoxes = 1 + (showRightLetters.value ? 1 : 0);
  const columns15 = 15;
  const size15 =
    (containerWidth -
      totalLetterBoxes * (maxBoxSize + letterBoxMargin) -
      (columns15 - 1) * cellGap) /
    columns15;

  if (size15 >= minBoxSize) {
    const finalSize = Math.min(size15, maxBoxSize);
    boxSize.value = finalSize;
    gridTemplateColumnsStyle.value = `repeat(${columns15}, ${finalSize}px)`;
    fontSize.value = Math.min(2, finalSize / 40);
  } else {
    const columns5 = 5;
    const size5 =
      (containerWidth -
        totalLetterBoxes * (maxBoxSize + letterBoxMargin) -
        (columns5 - 1) * cellGap) /
      columns5;
    const finalSize = Math.max(minBoxSize, Math.min(size5, maxBoxSize));
    boxSize.value = finalSize;
    gridTemplateColumnsStyle.value = `repeat(${columns5}, ${finalSize}px)`;
    fontSize.value = Math.min(2, finalSize / 40);
  }
};

const isMultiRow = computed(() =>
  gridTemplateColumnsStyle.value.startsWith("repeat(5")
);

const letterHeight = computed(() => {
  return isMultiRow.value ? boxSize.value * 3 + cellGap * 2 : boxSize.value;
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
</script>

<style scoped>
.bingo-row {
  margin-bottom: 0;
  gap: 4px;
  width: 100%;
}

/* Letra */
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

/* Grid de números */
.bingo-numbers {
  display: grid;
  gap: 4px;
  flex-grow: 0;
}

/* Casilla */
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

/* Número marcado: texto blanco encima */
.bingo-number.called-number {
  color: white !important;
  z-index: 10;
}

/* Círculo rojo dentro de la casilla con gradiente */
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

/* Último número llamado: borde dorado + animación especial */
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

/* Animación de pulso para números llamados */
@keyframes pulseIn {
  0% {
    transform: translate(-50%, -50%) scale(0.5);
    opacity: 0;
  }
  100% {
    transform: translate(-50%, -50%) scale(1);
    opacity: 1;
  }
}

/* Animación de entrada para el último número llamado */
@keyframes glowPulse {
  0% {
    transform: translate(-50%, -50%) scale(1.3);
    opacity: 0.6;
  }
  50% {
    transform: translate(-50%, -50%) scale(1);
    opacity: 1;
  }
  100% {
    transform: translate(-50%, -50%) scale(1);
    opacity: 1;
  }
}

/* Color personalizado */
.bg-purple {
  background-color: rebeccapurple !important;
}
</style>
