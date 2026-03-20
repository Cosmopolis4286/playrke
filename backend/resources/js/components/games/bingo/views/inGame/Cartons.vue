<template>
  <div class="row">
    <div
      v-for="(card, index) in displayCards"
      :key="card.id || index"
      class="col-6 col-sm-4 col-xl-3 my-1 notranslate"
      translate="no"
    >
      <div class="card" :data-carton-index="index">
        <div class="container-carton">
          <div
            v-for="letter in Object.keys(letterMap)"
            :key="letter"
            class="section-letter"
          >
            <div class="number-box-letter">
              <b>{{ letter }}</b>
            </div>
            <div v-for="i in 5" :key="i">
              <div v-if="letter === 'N' && i === 3" class="number-box is-logo">
                <img
                  class="img-fluid img-logo-ctn"
                  :src="customerLogo || defaultLogo"
                  alt="Logo"
                />
              </div>
              <div v-else class="number-box" :data-pos="`${letter}${i}`">
                <span class="number-box-number">
                  {{ getCardNumber(card, letter, i) ?? '--' }}
                </span>
              </div>
            </div>
          </div>
        </div>
        <div class="section-serial">
          <small>{{ card.serialNumber }}</small>
        </div>
      </div>
    </div>

    <div v-if="displayCards.length === 0" class="col-12 text-center">
      <p class="text-muted">No hay cartones disponibles</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { type BingoCard } from '@js/Services/bingoService';

// Props
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

// Constantes
const defaultLogo = "/img/rke.png";

const letterMap = {
  B: "B",
  I: "I",
  N: "N",
  G: "G",
  O: "O",
};

/**
 * Cartones finales para mostrar
 */
const displayCards = computed(() => {
  // Los cartones vienen directamente desde el backend via props
  return props.cartons;
});

/**
 * Obtener número del cartón por posición
 */
function getCardNumber(card: BingoCard, letter: string, position: number): number | null {
  const matrix = card.cardMatrix;
  if (!matrix || !matrix[letter as keyof typeof matrix]) return null;

  const columnNumbers = matrix[letter as keyof typeof matrix];
  return columnNumbers[position - 1] || null;
}

// No se necesita onMounted ya que los cartones vienen del backend
</script>

<style scoped>
/* Puedes añadir tus estilos aquí */
</style>
