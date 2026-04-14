<script setup lang="ts">
import type { Player } from '@js/types/trivia'

interface Props {
  players: Player[]
  hostId: string
  currentPlayerId?: string
}

const props = defineProps<Props>()

function isHost(playerId: string): boolean {
  return playerId === props.hostId
}

function isCurrentPlayer(playerId: string): boolean {
  return playerId === props.currentPlayerId
}
</script>

<template>
  <div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
      <h2 class="text-lg font-semibold text-gray-900">
        Jugadores
        <span class="ml-2 text-sm text-gray-500">
          ({{ players.length }})
        </span>
      </h2>
    </div>

    <div class="divide-y divide-gray-200">
      <!-- Empty State -->
      <div
        v-if="players.length === 0"
        class="px-6 py-8 text-center text-gray-500"
      >
        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        <p>Aún no hay jugadores</p>
        <p class="text-sm mt-1">¡Sé el primero en unirte!</p>
      </div>

      <!-- Player List -->
      <div
        v-for="(player, index) in players"
        :key="player.id"
        class="px-6 py-4 flex items-center gap-4 hover:bg-gray-50 transition-colors"
        :class="{ 'bg-blue-50': isCurrentPlayer(player.id) }"
      >
        <!-- Avatar -->
        <div class="flex-shrink-0">
          <div
            class="w-10 h-10 rounded-full flex items-center justify-center text-lg font-bold"
            :class="[
              isHost(player.id) ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700',
              isCurrentPlayer(player.id) ? 'ring-2 ring-blue-500' : ''
            ]"
          >
            {{ (player.name ?? '?').charAt(0).toUpperCase() }}
          </div>
        </div>

        <!-- Player Info -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2">
            <span class="text-sm font-medium text-gray-900 truncate">
              {{ player.name ?? 'Jugador' }}
            </span>
            
            <!-- Host Badge -->
            <span
              v-if="isHost(player.id)"
              class="flex-shrink-0 px-2 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full"
            >
              👑 Host
            </span>

            <!-- You Badge -->
            <span
              v-if="isCurrentPlayer(player.id)"
              class="flex-shrink-0 px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-800 rounded-full"
            >
              Tú
            </span>
          </div>
          <p class="text-xs text-gray-500 truncate">
            {{ player.id }}
          </p>
        </div>

        <!-- Join Order -->
        <div class="flex-shrink-0">
          <span class="text-xs text-gray-400">
            #{{ index + 1 }}
          </span>
        </div>
      </div>
    </div>

    <!-- Join Hint -->
    <div
      v-if="players.length > 0 && !currentPlayerId"
      class="px-6 py-3 bg-blue-50 border-t border-blue-100"
    >
      <p class="text-sm text-blue-700 text-center">
        ¡Únete para jugar con {{ players.length }} jugador{{ players.length > 1 ? 'es' : '' }}!
      </p>
    </div>
  </div>
</template>
