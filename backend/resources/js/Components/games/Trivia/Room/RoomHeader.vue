<script setup lang="ts">
import { ref, computed } from 'vue'
import type { Room } from '@js/types/trivia'

interface Props {
  room:        Room
  playerCount: number
  maxPlayers:  number
}

const props = defineProps<Props>()

const copied = ref(false)

function copyCode() {
  navigator.clipboard.writeText(props.room.code)
    .then(() => { copied.value = true; setTimeout(() => { copied.value = false }, 2000) })
    .catch(() => {})
}

const fillPct = computed(() => {
  if (!props.maxPlayers) return 0
  return Math.min(100, Math.round((props.playerCount / props.maxPlayers) * 100))
})

const fillColor = computed(() => {
  if (fillPct.value >= 90) return 'bg-red-400'
  if (fillPct.value >= 60) return 'bg-amber-400'
  return 'bg-emerald-400'
})

const statusMap: Record<string, { label: string; dot: string; text: string }> = {
  waiting: { label: 'En espera',  dot: 'bg-emerald-400 animate-pulse', text: 'text-emerald-600 dark:text-emerald-400' },
  playing: { label: 'En juego',   dot: 'bg-blue-400 animate-pulse',    text: 'text-blue-600 dark:text-blue-400'       },
  closed:  { label: 'Cerrada',    dot: 'bg-gray-400',                  text: 'rh-text-muted'       },
}

const status = computed(() => statusMap[props.room.status] ?? statusMap.waiting)
</script>

<template>
  <div class="rh-panel rounded-2xl border shadow-sm overflow-hidden">

    <!-- Accent bar -->
    <div class="h-0.5 bg-gradient-to-r from-indigo-500 via-violet-500 to-pink-500" />

    <div class="px-5 py-4">

      <!-- ── Fila 1: título + código ───────────────────────────────────────── -->
      <div class="flex items-start justify-between gap-3">

        <!-- Left: status + title + description -->
        <div class="min-w-0 flex-1">
          <!-- Status pill -->
          <div class="flex items-center gap-1.5 mb-1.5">
            <span :class="['w-1.5 h-1.5 rounded-full flex-shrink-0', status.dot]" />
            <span :class="['text-[11px] font-semibold uppercase tracking-wider', status.text]">
              {{ status.label }}
            </span>
          </div>

          <h1 class="text-xl font-bold rh-text leading-tight truncate">
            {{ room.title }}
          </h1>
          <p v-if="room.description" class="text-xs rh-text-muted mt-0.5 line-clamp-1">
            {{ room.description }}
          </p>
        </div>

        <!-- Right: code copy -->
        <button
          @click="copyCode"
          :title="copied ? '¡Copiado!' : 'Copiar código'"
          :class="[
            'flex-shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-mono text-sm font-bold transition-all duration-200',
            copied
              ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400'
              : 'rh-code-btn hover:bg-indigo-50 dark:hover:bg-indigo-900/20 hover:text-indigo-600 dark:hover:text-indigo-400',
          ]"
        >
          {{ room.code }}
          <svg v-if="!copied" class="w-3.5 h-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
          </svg>
          <svg v-else class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
          </svg>
        </button>
      </div>

      <!-- ── Fila 2: stats compactas ───────────────────────────────────────── -->
      <div class="mt-4 flex items-center gap-0 rh-stats-divide rounded-xl border overflow-hidden">

        <!-- Jugadores -->
        <div class="flex-1 flex items-center gap-2.5 px-3.5 py-2.5">
          <div class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center flex-shrink-0">
            <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
          </div>
          <div class="min-w-0">
            <p class="text-[10px] rh-text-muted leading-none mb-0.5">Jugadores</p>
            <p class="text-sm font-bold rh-text leading-none">
              {{ playerCount }}<span class="font-normal rh-text-muted text-xs">/{{ maxPlayers }}</span>
            </p>
            <!-- mini progress -->
            <div class="mt-1 h-0.5 w-full rounded-full rh-track overflow-hidden">
              <div :class="['h-full rounded-full transition-all duration-500', fillColor]" :style="{ width: fillPct + '%' }" />
            </div>
          </div>
        </div>

        <!-- Preguntas -->
        <div class="flex-1 flex items-center gap-2.5 px-3.5 py-2.5">
          <div class="w-7 h-7 rounded-lg bg-violet-50 dark:bg-violet-900/30 flex items-center justify-center flex-shrink-0">
            <svg class="w-3.5 h-3.5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div>
            <p class="text-[10px] rh-text-muted leading-none mb-0.5">Preguntas</p>
            <p class="text-sm font-bold rh-text leading-none">{{ room.questionCount }}</p>
          </div>
        </div>

        <!-- Tiempo -->
        <div class="flex-1 flex items-center gap-2.5 px-3.5 py-2.5">
          <div class="w-7 h-7 rounded-lg bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0">
            <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div>
            <p class="text-[10px] rh-text-muted leading-none mb-0.5">Por pregunta</p>
            <p class="text-sm font-bold rh-text leading-none">{{ room.timePerQuestion }}s</p>
          </div>
        </div>
      </div>

      <!-- ── Fila 3: entrada + premio (solo si existen, inline) ────────────── -->
      <div v-if="room.entryFee || room.prizeAmount" class="mt-3 flex items-center gap-2 flex-wrap">

        <!-- Entry fee pill -->
        <div v-if="room.entryFee" class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-rose-50 dark:bg-rose-900/20 border border-rose-100 dark:border-rose-800/50">
          <svg class="w-3 h-3 text-rose-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
          </svg>
          <span class="text-xs text-rose-500 dark:text-rose-400">Entrada</span>
          <span class="text-xs font-bold text-rose-700 dark:text-rose-300">
            {{ room.entryFee.toFixed(2) }}{{ room.entryCurrencySymbol ? ' ' + room.entryCurrencySymbol : '' }}
          </span>
        </div>

        <!-- Prize pill -->
        <div v-if="room.prizeAmount" class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800/50">
          <svg class="w-3 h-3 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span class="text-xs text-emerald-600 dark:text-emerald-400">
            {{ room.prizeType === 'accumulated' ? 'Bote' : 'Premio' }}
          </span>
          <span class="text-xs font-bold text-emerald-700 dark:text-emerald-300">
            {{ room.prizeAmount.toFixed(2) }}{{ room.entryCurrencySymbol ? ' ' + room.entryCurrencySymbol : '' }}
          </span>
        </div>

        <!-- Free pill -->
        <div v-if="!room.entryFee" class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-gray-50 dark:bg-gray-700/50 border border-gray-100 dark:border-gray-700">
          <span class="text-xs text-gray-400 dark:text-gray-500">Entrada gratuita</span>
        </div>
      </div>

      <!-- ── Fila 4: tags ──────────────────────────────────────────────────── -->
      <div class="mt-3 flex items-center gap-1.5 flex-wrap">
        <span v-if="room.category"
          class="px-2 py-0.5 rounded-md text-[11px] font-medium bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 border border-indigo-100/80 dark:border-indigo-800/50">
          {{ room.category }}
        </span>
        <span class="px-2 py-0.5 rounded-md text-[11px] font-medium bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 border border-amber-100/80 dark:border-amber-800/50">
          👑 Anfitrión
        </span>
      </div>

    </div>
  </div>
</template>
