<template>
  <div class="row">
    <div
      v-for="(ctn, index) in cartons"
      :key="index"
      class="col-6 col-sm-4 col-xl-3 my-1 notranslate"
      translate="no"
    >
      <div class="card" :data-carton-index="index">
        <div class="container-carton">
          <div
            v-for="(prefix, letter) in letterMap"
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
              <div v-else class="number-box" :data-pos="`${prefix}${i}`">
                <span class="number-box-number">
                  {{ ctn[`${prefix}${i}`] }}
                </span>
              </div>
            </div>
          </div>
        </div>
        <div class="section-serial">
          <small>{{ ctn.serial }}</small>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";

// Props
defineProps({
  cartons: {
    type: Array,
    required: true,
  },
  customerLogo: {
    type: String,
    default: null,
  },
});

// Constantes
const defaultLogo = "/img/rke.png";

const letterMap = {
  B: "b",
  I: "i",
  N: "n",
  G: "g",
  O: "o",
};
</script>

<style scoped>
/* Puedes añadir tus estilos aquí */
</style>
