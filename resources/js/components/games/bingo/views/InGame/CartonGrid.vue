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
            v-for="(prefix, letter) in letters"
            :key="letter"
            class="section-letter"
          >
            <!-- Letra superior -->
            <div class="number-box-letter">
              <b>{{ letter }}</b>
            </div>

            <!-- Cuadros con números (excepto N3) -->
            <div
              v-for="i in getIndexes(letter)"
              :key="prefix + i"
              class="number-box"
              :data-pos="prefix + i"
            >
              <span class="number-box-number">
                {{ ctn[prefix + i] }}
              </span>
            </div>

            <!-- Casilla N3 con logo -->
            <div v-if="letter === 'N'" class="number-box is-logo">
              <img
                class="img-fluid img-logo-ctn"
                :src="customerLogo || defaultLogo"
                alt="Logo"
              />
            </div>
          </div>
        </div>

        <!-- Número de serie -->
        <div class="section-serial">
          <small>{{ ctn.serial }}</small>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  cartons: {
    type: Array,
    required: true,
  },
  customerLogo: {
    type: String,
    default: "",
  },
});

const defaultLogo = "/img/rke.png";

const letters = {
  B: "b",
  I: "i",
  N: "n",
  G: "g",
  O: "o",
};

function getIndexes(letter) {
  // Para la letra N omitimos la posición 3 (casilla del logo)
  return letter === "N" ? [1, 2, 4, 5] : [1, 2, 3, 4, 5];
}
</script>

<style scoped>
/* Puedes colocar aquí los estilos relacionados si no están en un CSS global */
</style>
