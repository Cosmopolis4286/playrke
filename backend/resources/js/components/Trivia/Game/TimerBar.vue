<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  timeLeft: number
  totalTime: number
}

const props = defineProps<Props>()

const percentage = computed(() => {
  if (props.totalTime === 0) return 0
  return (props.timeLeft / props.totalTime) * 100
})

const isLowTime = computed(() => percentage.value <= 20)

const timeColor = computed(() => {
  if (percentage.value > 50) return 'from-green-400 to-green-500'
  if (percentage.value > 20) return 'from-yellow-400 to-yellow-500'
  return 'from-red-500 to-red-600'
})

const formattedTime = computed(() => {
  const seconds = Math.ceil(props.timeLeft)
  return seconds < 10 ? `0${seconds}` : seconds
})
</script>

<template>
  <div class="bg-gray-800 rounded-xl p-4">
    <div class="flex items-center justify-between mb-2">
      <span class="text-white/80 text-sm font-medium">
        Tiempo restante
      </span>
      <span
        class="text-2xl font-bold tabular-nums"
        :class="isLowTime ? 'text-red-400 animate-pulse' : 'text-white'"
      >
        {{ formattedTime }}s
      </span>
    </div>

    <!-- Timer Bar Container -->
    <div class="h-4 bg-gray-700 rounded-full overflow-hidden">
      <!-- Animated Bar -->
      <div
        class="h-full bg-gradient-to-r transition-all duration-1000 ease-linear rounded-full"
        :class="timeColor"
        :style="{ width: `${percentage}%` }"
      />
    </div>

    <!-- Warning Message -->
    <p
      v-if="isLowTime"
      class="mt-2 text-center text-red-400 text-sm font-medium animate-pulse"
    >
      ¡Rápido! El tiempo se acaba...
    </p>
  </div>
</template>
