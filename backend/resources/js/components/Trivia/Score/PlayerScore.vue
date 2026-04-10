<script setup lang="ts">
import type { GameStats } from '@/types/trivia'

interface Props {
  stats: GameStats
  detailed?: boolean
}

defineProps<Props>()

function formatTime(ms: number): string {
  if (!ms) return '0s'
  return `${Math.round(ms / 1000)}s`
}
</script>

<template>
  <div class="space-y-4">
    <!-- Main Score -->
    <div class="text-center">
      <span class="text-5xl font-bold text-blue-600">
        {{ stats.score }}
      </span>
      <p class="text-gray-500 mt-1">puntos totales</p>
    </div>

    <!-- Detailed Stats -->
    <div
      v-if="detailed"
      class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6"
    >
      <!-- Correct Answers -->
      <div class="bg-green-50 rounded-lg p-4 text-center">
        <div class="text-2xl font-bold text-green-600">
          {{ stats.correctAnswers }}
        </div>
        <div class="text-xs text-green-700 mt-1">
          Correctas
        </div>
      </div>

      <!-- Wrong Answers -->
      <div class="bg-red-50 rounded-lg p-4 text-center">
        <div class="text-2xl font-bold text-red-600">
          {{ stats.wrongAnswers }}
        </div>
        <div class="text-xs text-red-700 mt-1">
          Incorrectas
        </div>
      </div>

      <!-- Accuracy -->
      <div class="bg-blue-50 rounded-lg p-4 text-center">
        <div class="text-2xl font-bold text-blue-600">
          {{ stats.accuracy }}%
        </div>
        <div class="text-xs text-blue-700 mt-1">
          Precisión
        </div>
      </div>

      <!-- Average Time -->
      <div class="bg-purple-50 rounded-lg p-4 text-center">
        <div class="text-2xl font-bold text-purple-600">
          {{ formatTime(stats.averageResponseTime) }}
        </div>
        <div class="text-xs text-purple-700 mt-1">
          Tiempo promedio
        </div>
      </div>
    </div>

    <!-- Additional Stats -->
    <div
      v-if="detailed && (stats.maxStreak || stats.position)"
      class="grid grid-cols-2 gap-4 mt-4 pt-4 border-t border-gray-200"
    >
      <!-- Position -->
      <div
        v-if="stats.position"
        class="flex items-center justify-center gap-2"
      >
        <span class="text-gray-500">Posición:</span>
        <span class="text-xl font-bold" :class="{
          'text-yellow-600': stats.position === 1,
          'text-gray-600': stats.position === 2,
          'text-orange-600': stats.position === 3,
          'text-blue-600': stats.position > 3
        }">
          #{{ stats.position }}
        </span>
      </div>

      <!-- Max Streak -->
      <div
        v-if="stats.maxStreak"
        class="flex items-center justify-center gap-2"
      >
        <span class="text-gray-500">Racha máxima:</span>
        <span class="text-xl font-bold text-orange-600">
          {{ stats.maxStreak }} 🔥
        </span>
      </div>
    </div>
  </div>
</template>
