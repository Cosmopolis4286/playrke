<script setup lang="ts">
interface Props {
  questionNumber: number
  totalQuestions: number
  progress: number
}

defineProps<Props>()
</script>

<template>
  <div class="flex items-center justify-between mb-6">
    <!-- Question Counter -->
    <div class="flex items-center gap-3">
      <div class="bg-blue-600 text-white px-4 py-2 rounded-lg font-bold">
        {{ questionNumber }} / {{ totalQuestions }}
      </div>
      <span class="text-white/80 text-sm">
        Pregunta {{ questionNumber }} de {{ totalQuestions }}
      </span>
    </div>

    <!-- Progress Bar -->
    <div class="flex-1 mx-6">
      <div class="h-2 bg-white/20 rounded-full overflow-hidden">
        <div
          class="h-full bg-gradient-to-r from-blue-400 to-green-400 transition-all duration-500"
          :style="{ width: `${progress}%` }"
        />
      </div>
    </div>
  </div>
</template>
