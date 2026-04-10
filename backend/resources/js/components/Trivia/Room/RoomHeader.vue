<script setup lang="ts">
import type { Room } from '@/types/trivia'

interface Props {
  room: Room
  playerCount: number
  maxPlayers: number
}

defineProps<Props>()

function copyRoomCode(code: string) {
  navigator.clipboard.writeText(code)
    .then(() => alert('¡Código copiado!'))
    .catch(() => alert('No se pudo copiar'))
}
</script>

<template>
  <div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
      <!-- Title and Code -->
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          {{ room.title }}
        </h1>
        
        <div class="mt-2 flex items-center gap-3">
          <span class="text-sm text-gray-500">Código:</span>
          <button
            @click="copyRoomCode(room.code)"
            class="inline-flex items-center gap-2 px-3 py-1 bg-blue-100 text-blue-700 rounded-lg font-mono text-lg font-bold hover:bg-blue-200 transition-colors"
          >
            {{ room.code }}
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Room Info -->
      <div class="flex items-center gap-6 text-sm">
        <!-- Player Count -->
        <div class="flex items-center gap-2">
          <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
          <span class="text-gray-700">
            {{ playerCount }} / {{ maxPlayers }} jugadores
          </span>
        </div>

        <!-- Questions -->
        <div class="flex items-center gap-2">
          <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span class="text-gray-700">
            {{ room.questionCount }} preguntas
          </span>
        </div>

        <!-- Time per Question -->
        <div class="flex items-center gap-2">
          <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span class="text-gray-700">
            {{ room.timePerQuestion }}s por pregunta
          </span>
        </div>
      </div>
    </div>

    <!-- Description -->
    <p
      v-if="room.description"
      class="mt-4 text-gray-600"
    >
      {{ room.description }}
    </p>

    <!-- Host Badge -->
    <div class="mt-4 flex items-center gap-2">
      <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-medium rounded-full">
        👑 Anfitrión
      </span>
      <span class="text-sm text-gray-600">{{ room.hostId }}</span>
    </div>
  </div>
</template>
