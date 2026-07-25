<script setup lang="ts">
import type { Player } from '@/js/types/trivia'

interface Props {
  players: Player[]
  currentPlayerId?: string
  showRanks?: boolean
  showStats?: boolean
}

const props = defineProps<Props>()

function getRankIcon(index: number): string {
  switch (index) {
    case 0: return '🥇'
    case 1: return '🥈'
    case 2: return '🥉'
    default: return `${index + 1}`
  }
}

function getRankStyle(index: number): string {
  switch (index) {
    case 0: return 'bg-yellow-100 border-yellow-300'
    case 1: return 'bg-gray-100 border-gray-300'
    case 2: return 'bg-orange-100 border-orange-300'
    default: return 'bg-white border-gray-200'
  }
}

function isCurrentPlayer(playerId: string): boolean {
  return playerId === props.currentPlayerId
}
</script>

<template>
  <div class="bg-white/10 backdrop-blur rounded-xl overflow-hidden">
    <div class="px-4 py-3 border-b border-white/20">
      <h3 class="text-lg font-semibold text-white flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
        Clasificación
      </h3>
    </div>

    <div class="max-h-96 overflow-y-auto">
      <!-- Empty State -->
      <div
        v-if="players.length === 0"
        class="px-4 py-8 text-center text-white/60"
      >
        <p>Aún no hay jugadores clasificados</p>
      </div>

      <!-- Player List -->
      <div
        v-for="(player, index) in players"
        :key="player.id"
        class="px-4 py-3 flex items-center gap-3 border-b border-white/10 last:border-b-0 transition-colors"
        :class="[
          getRankStyle(index),
          isCurrentPlayer(player.id) ? 'ring-2 ring-blue-400' : ''
        ]"
      >
        <!-- Rank -->
        <div
          v-if="showRanks"
          class="w-8 h-8 rounded-full flex items-center justify-center text-lg font-bold"
          :class="{
            'bg-yellow-400 text-yellow-900': index === 0,
            'bg-gray-300 text-gray-900': index === 1,
            'bg-orange-400 text-orange-900': index === 2,
            'bg-gray-200 text-gray-600': index > 2
          }"
        >
          {{ getRankIcon(index) }}
        </div>

        <!-- Avatar -->
        <div
          class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold"
          :class="isCurrentPlayer(player.id) ? 'bg-blue-500 text-white' : 'bg-gray-600 text-white'"
        >
          {{ player.name.charAt(0).toUpperCase() }}
        </div>

        <!-- Player Info -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2">
            <span
              class="font-medium truncate"
              :class="isCurrentPlayer(player.id) ? 'text-blue-700' : 'text-gray-900'"
            >
              {{ player.name }}
            </span>
            <span
              v-if="isCurrentPlayer(player.id)"
              class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full"
            >
              Tú
            </span>
          </div>
          
          <!-- Stats -->
          <div
            v-if="showStats"
            class="text-xs text-gray-500 mt-0.5 flex items-center gap-3"
          >
            <span>{{ player.correctAnswers || 0 }} ✓</span>
            <span>{{ player.wrongAnswers || 0 }} ✗</span>
            <span v-if="player.averageResponseTime">{{ Math.round(player.averageResponseTime / 1000) }}s avg</span>
          </div>
        </div>

        <!-- Score -->
        <div class="text-right">
          <span class="text-lg font-bold" :class="isCurrentPlayer(player.id) ? 'text-blue-700' : 'text-gray-900'">
            {{ player.score || 0 }}
          </span>
          <span class="text-xs text-gray-500 block">pts</span>
        </div>
      </div>
    </div>
  </div>
</template>
