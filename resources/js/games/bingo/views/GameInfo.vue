<template>
  <div class="game-info">
    <h1>Ronda {{ data.round }}</h1>
    <BingoBoard :numbers="data.numbers" />
  </div>
</template>

<script setup>
import BingoBoard from '../components/BingoBoard.vue'

const props = defineProps({
  data: {
    type: Object,
    required: true
  }
})
</script>

<style scoped>
.game-info {
  padding: 1rem;
}
</style>
