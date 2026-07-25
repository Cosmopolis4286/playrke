<script setup lang="ts">
import type { GameState } from '@/js/types/trivia'

interface Props {
  state: GameState
}

defineProps<Props>()

const statusConfig = {
  waiting: {
    icon: '⏳',
    text: 'Esperando siguiente pregunta...',
    color: 'text-yellow-400'
  },
  answering: {
    icon: '⚡',
    text: '¡Responde ahora!',
    color: 'text-green-400'
  },
  closed: {
    icon: '🔒',
    text: 'Tiempo terminado',
    color: 'text-red-400'
  },
  finished: {
    icon: '🏁',
    text: '¡Juego terminado!',
    color: 'text-purple-400'
  }
}
</script>

<template>
  <div
    class="text-center py-4 rounded-xl transition-all duration-300"
    :class="{
      'bg-yellow-500/20': state === 'waiting',
      'bg-green-500/20': state === 'answering',
      'bg-red-500/20': state === 'closed',
      'bg-purple-500/20': state === 'finished'
    }"
  >
    <div class="flex items-center justify-center gap-3">
      <span class="text-3xl">
        {{ statusConfig[state].icon }}
      </span>
      <span
        class="text-lg font-bold"
        :class="statusConfig[state].color"
      >
        {{ statusConfig[state].text }}
      </span>
    </div>

    <!-- Player Status Indicators -->
    <div
      v-if="state === 'answering'"
      class="mt-3 flex items-center justify-center gap-2 text-sm text-white/70"
    >
      <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse" />
      <span>Esperando tu respuesta...</span>
    </div>

    <div
      v-else-if="state === 'waiting'"
      class="mt-3 flex items-center justify-center gap-2 text-sm text-white/70"
    >
      <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
      </svg>
      <span>Preparando pregunta...</span>
    </div>
  </div>
</template>
