<script setup lang="ts">
import { ref } from 'vue'
import type { Room } from '@js/types/trivia'

interface Props {
  room: Room
  playerCount: number
  maxPlayers: number
}

defineProps<Props>()

const copied = ref(false)

function copyRoomCode(code: string) {
  navigator.clipboard.writeText(code)
    .then(() => {
      copied.value = true
      setTimeout(() => { copied.value = false }, 2000)
    })
    .catch(() => {})
}

function fillPct(current: number, max: number): number {
  if (!max) return 0
  return Math.min(100, Math.round((current / max) * 100))
}

function fillColor(pct: number): string {
  if (pct >= 90) return 'bg-red-400'
  if (pct >= 60) return 'bg-amber-400'
  return 'bg-emerald-400'
}
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
    <!-- Color accent top bar -->
    <div class="h-1.5 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500" />

    <div class="p-6">
      <!-- Title row -->
      <div class="flex items-start justify-between gap-4 flex-wrap">
        <div class="min-w-0">
          <div class="flex items-center gap-2 mb-1">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
              En espera
            </span>
          </div>
          <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight truncate">
            {{ room.title }}
          </h1>
          <p v-if="room.description" class="text-sm text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-1">
            {{ room.description }}
          </p>
        </div>

        <!-- Code copy button -->
        <button
          @click="copyRoomCode(room.code)"
          :class="[
            'flex-shrink-0 inline-flex items-center gap-2 px-4 py-2 rounded-xl font-mono font-bold text-lg transition-all border',
            copied
              ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-300 dark:border-emerald-700 text-emerald-700 dark:text-emerald-400'
              : 'bg-indigo-50 dark:bg-indigo-900/20 border-indigo-200 dark:border-indigo-700 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/40',
          ]"
        >
          {{ room.code }}
          <svg v-if="!copied" class="w-4 h-4 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
          </svg>
          <svg v-else class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
          </svg>
        </button>
      </div>

      <!-- Stats row -->
      <div class="mt-5 grid grid-cols-3 gap-3">
        <!-- Players -->
        <div class="rounded-xl bg-gray-50 dark:bg-gray-700/50 p-3">
          <div class="flex items-center gap-2 mb-2">
            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <span class="text-xs text-gray-500 dark:text-gray-400">Jugadores</span>
          </div>
          <p class="text-sm font-bold text-gray-900 dark:text-white">{{ playerCount }} / {{ maxPlayers }}</p>
          <div class="mt-1.5 h-1 w-full bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
            <div
              :class="['h-full rounded-full transition-all', fillColor(fillPct(playerCount, maxPlayers))]"
              :style="{ width: fillPct(playerCount, maxPlayers) + '%' }"
            />
          </div>
        </div>

        <!-- Questions -->
        <div class="rounded-xl bg-gray-50 dark:bg-gray-700/50 p-3">
          <div class="flex items-center gap-2 mb-2">
            <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-xs text-gray-500 dark:text-gray-400">Preguntas</span>
          </div>
          <p class="text-sm font-bold text-gray-900 dark:text-white">{{ room.questionCount }}</p>
        </div>

        <!-- Time -->
        <div class="rounded-xl bg-gray-50 dark:bg-gray-700/50 p-3">
          <div class="flex items-center gap-2 mb-2">
            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-xs text-gray-500 dark:text-gray-400">Por pregunta</span>
          </div>
          <p class="text-sm font-bold text-gray-900 dark:text-white">{{ room.timePerQuestion }}s</p>
        </div>
      </div>

      <!-- Entry fee + Prize row -->
      <div v-if="room.entryFee || room.prizeAmount" class="mt-3 grid grid-cols-2 gap-3">
        <!-- Entry fee -->
        <div v-if="room.entryFee" class="rounded-xl bg-rose-50 dark:bg-rose-900/20 border border-rose-100 dark:border-rose-800 p-3">
          <div class="flex items-center gap-2 mb-1">
            <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
            </svg>
            <span class="text-xs text-rose-600 dark:text-rose-400">Entrada</span>
          </div>
          <p class="text-sm font-bold text-rose-700 dark:text-rose-300">{{ room.entryFee.toFixed(2) }}</p>
        </div>

        <!-- Prize -->
        <div v-if="room.prizeAmount" class="rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 p-3">
          <div class="flex items-center gap-2 mb-1">
            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-xs text-emerald-600 dark:text-emerald-400">
              {{ room.prizeType === 'accumulated' ? 'Bote acumulado' : 'Premio' }}
            </span>
          </div>
          <p class="text-sm font-bold text-emerald-700 dark:text-emerald-300">{{ room.prizeAmount.toFixed(2) }}</p>
        </div>
      </div>

      <!-- Category + Host row -->
      <div class="mt-3 flex items-center gap-2 flex-wrap">
        <span v-if="room.category" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800">
          {{ room.category }}
        </span>
        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
          👑 Anfitrión
        </span>
      </div>
    </div>
  </div>
</template>
