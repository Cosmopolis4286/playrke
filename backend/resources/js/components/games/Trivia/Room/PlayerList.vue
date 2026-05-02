<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
import type { Player, Team } from '@js/types/trivia'

interface Props {
  players:         Player[]
  hostId:          string
  currentPlayerId?: string
  teams?:          Team[]
  maxPlayers?:     number
}

const props = defineProps<Props>()

// ── Tiempo relativo ───────────────────────────────────────────────────────────
const now = ref(Date.now())
let timer: ReturnType<typeof setInterval> | null = null

onMounted(() => { timer = setInterval(() => { now.value = Date.now() }, 15_000) })
onUnmounted(() => { if (timer) clearInterval(timer) })

function timeAgo(iso: string | null | undefined): string {
  if (!iso) return ''
  // Normaliza "2026-04-29 10:30:00" → "2026-04-29T10:30:00Z" para parse seguro
  const normalized = iso.includes('T') ? iso : iso.replace(' ', 'T') + 'Z'
  const diff = Math.floor((now.value - new Date(normalized).getTime()) / 1000)
  if (diff < 5)           return 'ahora mismo'
  if (diff < 60)          return `hace ${diff}s`
  const m = Math.floor(diff / 60)
  if (m < 60)             return `hace ${m} min`
  const h = Math.floor(m / 60)
  if (h < 24)             return `hace ${h}h`
  const d = Math.floor(h / 24)
  return `hace ${d}d`
}

// ── Helpers visuales ──────────────────────────────────────────────────────────
const AVATAR_COLORS = [
  'from-violet-500 to-purple-600',
  'from-blue-500 to-indigo-600',
  'from-emerald-500 to-teal-600',
  'from-pink-500 to-rose-600',
  'from-amber-500 to-orange-600',
  'from-cyan-500 to-sky-600',
  'from-fuchsia-500 to-pink-600',
  'from-lime-500 to-green-600',
]

function avatarGradient(index: number): string {
  return AVATAR_COLORS[index % AVATAR_COLORS.length]
}

function isHost(id: string): boolean { return id === props.hostId }
function isMe(id: string): boolean   { return id === props.currentPlayerId }

function teamFor(player: Player): Team | undefined {
  if (!player.teamId || !props.teams) return undefined
  return props.teams.find(t => t.id === player.teamId)
}

// Cupos disponibles
const spotsLeft = computed(() => {
  if (!props.maxPlayers) return null
  return props.maxPlayers - props.players.length
})

// % ocupación para la barra de progreso
const occupancyPercent = computed(() => {
  if (!props.maxPlayers || props.maxPlayers === 0) return 0
  return Math.min(100, Math.round((props.players.length / props.maxPlayers) * 100))
})

const occupancyColor = computed(() => {
  if (occupancyPercent.value >= 90) return 'bg-red-400'
  if (occupancyPercent.value >= 70) return 'bg-amber-400'
  return 'bg-emerald-400'
})
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">

    <!-- ── Header ─────────────────────────────────────────────────────────────── -->
    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center">
            <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>
          <div>
            <h2 class="font-semibold text-gray-900 dark:text-white text-sm leading-tight">Jugadores</h2>
            <p v-if="maxPlayers" class="text-[11px] text-gray-400 dark:text-gray-500 leading-tight">
              {{ players.length }} / {{ maxPlayers }}
              <span v-if="spotsLeft !== null && spotsLeft > 0" class="text-emerald-500"> · {{ spotsLeft }} cupos libres</span>
              <span v-else-if="spotsLeft === 0" class="text-red-500"> · Sala llena</span>
            </p>
          </div>
        </div>

        <!-- Badge contador -->
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 tabular-nums">
          {{ players.length }}
        </span>
      </div>

      <!-- Barra de ocupación -->
      <div v-if="maxPlayers && players.length > 0" class="mt-3">
        <div class="h-1.5 w-full rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
          <div
            class="h-full rounded-full transition-all duration-500"
            :class="occupancyColor"
            :style="{ width: occupancyPercent + '%' }"
          />
        </div>
      </div>
    </div>

    <!-- ── Empty state ─────────────────────────────────────────────────────────── -->
    <div v-if="players.length === 0" class="py-14 px-6 text-center">
      <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gray-50 dark:bg-gray-700 flex items-center justify-center">
        <svg class="w-8 h-8 text-gray-300 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
      </div>
      <p class="font-medium text-gray-500 dark:text-gray-400 text-sm">Aún no hay jugadores</p>
      <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">¡Sé el primero en unirte!</p>
    </div>

    <!-- ── Lista ───────────────────────────────────────────────────────────────── -->
    <ul v-else class="divide-y divide-gray-50 dark:divide-gray-700/50">
      <li
        v-for="(player, index) in players"
        :key="player.id"
        :class="[
          'flex items-center gap-3 px-5 py-3.5 transition-colors',
          isMe(player.id)
            ? 'bg-indigo-50/60 dark:bg-indigo-900/10'
            : 'hover:bg-gray-50/70 dark:hover:bg-gray-700/30',
        ]"
      >
        <!-- Avatar -->
        <div class="relative flex-shrink-0">
          <div
            :class="[
              'w-10 h-10 rounded-xl bg-gradient-to-br flex items-center justify-center text-white font-bold text-base shadow-sm select-none',
              avatarGradient(index),
              isMe(player.id) ? 'ring-2 ring-indigo-400 ring-offset-1 dark:ring-offset-gray-800' : '',
            ]"
          >
            {{ (player.name ?? '?').charAt(0).toUpperCase() }}
          </div>
          <!-- Host crown -->
          <span
            v-if="isHost(player.id)"
            class="absolute -top-1.5 -right-1.5 text-[13px] leading-none drop-shadow"
            title="Anfitrión"
          >👑</span>
        </div>

        <!-- Info principal -->
        <div class="flex-1 min-w-0">
          <!-- Nombre + badges -->
          <div class="flex items-center gap-1.5 flex-wrap">
            <span
              :class="[
                'text-sm font-semibold truncate',
                isMe(player.id) ? 'text-indigo-700 dark:text-indigo-300' : 'text-gray-900 dark:text-white',
              ]"
            >{{ player.name ?? 'Jugador' }}</span>

            <span
              v-if="isMe(player.id)"
              class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 uppercase tracking-wide"
            >Tú</span>

            <span
              v-if="isHost(player.id)"
              class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 uppercase tracking-wide"
            >Host</span>
          </div>

          <!-- Fila secundaria: tiempo + equipo -->
          <div class="flex items-center gap-2 mt-0.5 flex-wrap">
            <!-- Timestamp -->
            <span v-if="player.joinedAt" class="flex items-center gap-1 text-[11px] text-gray-400 dark:text-gray-500">
              <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              Se unió {{ timeAgo(player.joinedAt) }}
            </span>

            <!-- Separador -->
            <span v-if="player.joinedAt && teamFor(player)" class="text-gray-200 dark:text-gray-700 text-[11px]">·</span>

            <!-- Equipo -->
            <span
              v-if="teamFor(player)"
              class="flex items-center gap-1 text-[11px] font-medium px-1.5 py-0.5 rounded-full"
              :style="teamFor(player)?.color
                ? { backgroundColor: teamFor(player)!.color + '22', color: teamFor(player)!.color }
                : {}"
              :class="!teamFor(player)?.color ? 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400' : ''"
            >
              <span v-if="teamFor(player)?.icon" class="text-[12px]">{{ teamFor(player)!.icon }}</span>
              {{ teamFor(player)!.name }}
            </span>
          </div>
        </div>

        <!-- Número de posición -->
        <div class="flex-shrink-0 flex flex-col items-end gap-1">
          <span
            :class="[
              'text-xs font-bold tabular-nums',
              index === 0 ? 'text-amber-500' : index === 1 ? 'text-gray-400' : index === 2 ? 'text-amber-700' : 'text-gray-300 dark:text-gray-600',
            ]"
          >#{{ index + 1 }}</span>
        </div>
      </li>
    </ul>

    <!-- ── Footer ─────────────────────────────────────────────────────────────── -->
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
