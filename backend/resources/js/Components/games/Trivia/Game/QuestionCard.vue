<script setup lang="ts">
import type { Question } from '@/js/types/trivia'

interface Props {
  question: Question
}

defineProps<Props>()

function hideBrokenImage(event: Event) {
  if (event.target instanceof HTMLImageElement) event.target.style.display = 'none'
}
</script>

<template>
  <div class="bg-white rounded-2xl shadow-xl p-8">
    <!-- Category Badge -->
    <div class="flex items-center justify-between mb-4">
      <span
        v-if="question.category"
        class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm font-medium"
      >
        {{ question.category }}
      </span>
      <span
        class="px-3 py-1 rounded-full text-sm font-medium"
        :class="{
          'bg-green-100 text-green-700': question.difficulty === 'easy',
          'bg-yellow-100 text-yellow-700': question.difficulty === 'medium',
          'bg-red-100 text-red-700': question.difficulty === 'hard'
        }"
      >
        {{ question.difficulty === 'easy' ? 'Fácil' : question.difficulty === 'medium' ? 'Medio' : 'Difícil' }}
      </span>
    </div>

    <!-- Question Text -->
    <h2 class="text-2xl font-bold text-gray-900 leading-relaxed">
      {{ question.text }}
    </h2>

    <!-- Question Image -->
    <div
      v-if="question.image"
      class="mt-6 rounded-xl overflow-hidden"
    >
      <img
        :src="question.image"
        :alt="'Imagen de la pregunta'"
        class="w-full h-64 object-cover"
        @error="hideBrokenImage"
      />
    </div>

    <!-- Question Info -->
    <div class="mt-4 flex items-center gap-4 text-sm text-gray-500">
      <span class="flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        {{ question.points }} puntos
      </span>
      <span class="flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        {{ question.timeLimit }} segundos
      </span>
    </div>
  </div>
</template>
