<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import type { Player } from '@js/types/trivia'

interface Props {
  players: Player[]
  hostId: string
  currentPlayerId?: string
}

const props = defineProps<Props>()

// ── Tiempo relativo ───────────────────────────────────────────────────────────

const now = ref(Date.now())
let timer: ReturnType<typeof setInterval> | null = null

onMounted(() => { timer = setInterval(() => { now.value = Date.now() }, 10_000) })
onUnmounted(() => { if (timer) clearInterval(timer) })

function timeAgo(isoDate: string | null | undefined): string {
  if (!isoDate) return ''
  const diff = Math.floor((now.value - new Date(isoDate).getTime()) / 1000)
  if (diff < 5)   return 'ahora mismo'
  if (diff < 60)  return `hace ${diff}s`
  const m = Math.floor(diff / 60)
  if (m < 60) return `hace ${m} min`
  const h = Math.floor(m / 60)
  return `hace ${h}h`
}

// ── Helpers ───────────────────────────────────────────────────────────────────

const COLORS = [
  'from-violet-500 to-purple-600',
  'from-blue-500 to-indigo-600',
  'from-emerald-500 to-teal-600',
  'from-pink-500 to-rose-600',
  'from-amber-500 to-orange-600',
  'from-cyan-500 to-sky-600',
]

function avatarGradient(index: number): string {
  return COLORS[index % COLORS.length]
}

function isHost(id: string): boolean {
  return id === props.hostId
}

function isMe(id: string): boolean {
  return id === props.currentPlayerId
}
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">

    <!-- Header -->
    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
      <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center">
          <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </div>
        <h2 class="font-semibold text-gray-900 dark:text-white text-sm">Jugadores</h2>
      </div>
      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300">
        {{ players.length }}
      </span>
    </div>

    <!-- Empty state -->
    <div v-if="players.length === 0" class="py-14 px-6 text-center">
      <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gray-50 dark:bg-gray-700 flex items-center justify-center">
        <svg class="w-8 h-8 text-gray-300 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
      </div>
      <p class="font-medium text-gray-500 dark:text-gray-400 text-sm">Aún no hay jugadores</p>
      <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">¡Sé el primero en unirte!</p>
    </div>

    <!-- Player list -->
    <ul v-else class="divide-y divide-gray-50 dark:divide-gray-700/50">
      <li
        v-for="(player, index) in players"
        :key="player.id"
        :class="[
          'flex items-center gap-3 px-5 py-3.5 transition-colors',
          isMe(player.id)
            ? 'bg-indigo-50/60 dark:bg-indigo-900/10'
            : 'hover:bg-gray-50 dark:hover:bg-gray-700/30',
        ]"
      >
        <!-- Avatar con gradiente -->
        <div class="relative flex-shrink-0">
          <div
            :class="[
              'w-9 h-9 rounded-xl bg-gradient-to-br flex items-center justify-center text-white font-bold text-sm shadow-sm',
              avatarGradient(index),
              isMe(player.id) ? 'ring-2 ring-indigo-400 ring-offset-1' : '',
            ]"
          >
            {{ (player.name ?? '?').charAt(0).toUpperCase() }}
          </div>
          <!-- Host crown overlay -->
          <span
            v-if="isHost(player.id)"
            class="absolute -top-1.5 -right-1.5 text-[11px] leading-none"
            title="Anfitrión"
          >👑</span>
        </div>

        <!-- Info -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-1.5 flex-wrap">
            <span
              :class="[
                'text-sm font-medium truncate',
                isMe(player.id) ? 'text-indigo-700 dark:text-indigo-300' : 'text-gray-900 dark:text-white',
              ]"
            >
              {{ player.name ?? 'Jugador' }}
            </span>
            <span
              v-if="isMe(player.id)"
              class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 uppercase tracking-wide"
            >
              Tú
            </span>
            <span
              v-if="isHost(player.id)"
              class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 uppercase tracking-wide"
            >
              Host
            </span>
          </div>
          <p v-if="player.joinedAt" class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">
            Se unió {{ timeAgo(player.joinedAt) }}
          </p>
        </div>

        <!-- Position number -->
        <div class="flex-shrink-0 w-6 text-right">
          <span class="text-xs font-semibold text-gray-300 dark:text-gray-600">#{{ index + 1 }}</span>
        </div>
      </li>
    </ul>

    <!-- Footer hint -->
    <div
      v-if="players.length > 0 && !currentPlayerId"
      class="px-5 py-3 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/10 border-t border-indigo-100 dark:border-indigo-900/20"
    >
      <p class="text-xs text-indigo-600 dark:text-indigo-400 text-center font-medium">
        ¡Únete para jugar con {{ players.length }} jugador{{ players.length !== 1 ? 'es' : '' }}!
      </p>
    </div>
  </div>
</template>
