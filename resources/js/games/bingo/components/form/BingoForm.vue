<template>
  <div class="bingo-board">
    <div v-for="n in numbers" :key="n" class="bingo-number">{{ n }}</div>
  </div>
</template>

<script setup>
import { defineProps } from 'vue'

const props = defineProps({
  numbers: {
    type: Array,
    required: true
  }
})
</script>

<style scoped>
.bingo-board {
  display: flex;
  gap: 8px;
}

.bingo-number {
  width: 40px;
  height: 40px;
  background: #eee;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
}
</style>
