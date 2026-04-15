<script setup lang="ts">
import { computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { getRoute } from '@js/routes/triviaRoutes'

interface Player {
  rank: number
  player_id: string
  name: string
  email: string | null
  games_played: number
  total_score: number
  total_correct: number
  total_wrong: number
  wins: number
  best_score: number
  best_streak: number
  avg_response_ms: number
  total_prizes: number
  rooms_entered: number
  accuracy: number
  win_rate: number
}

interface GlobalStats {
  total_games: number
  total_players: number
  top_score: number
  top_name: string
}

const props = withDefaults(defineProps<{
  players: Player[]
  period: 'all' | 'week' | 'month'
  globalStats?: GlobalStats
}>(), {
  players:     () => [],
  period:      'all',
  globalStats: () => ({ total_games: 0, total_players: 0, top_score: 0, top_name: '-' }),
})

// ── Helpers ───────────────────────────────────────────────────────────────────

function msToSec(ms: number): string {
  return (ms / 1000).toFixed(1) + 's'
}

function fmt(n: number): string {
  return n.toLocaleString('es-ES')
}

function initials(name: string): string {
  return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()
}

function avatarColor(rank: number): string {
  const colors = [
    'from-yellow-400 to-orange-400',   // 1
    'from-gray-300 to-gray-400',        // 2
    'from-amber-600 to-amber-700',      // 3
    'from-indigo-400 to-purple-500',    // 4+
    'from-blue-400 to-cyan-500',
    'from-green-400 to-teal-500',
    'from-pink-400 to-rose-500',
  ]
  return colors[Math.min(rank - 1, colors.length - 1)]
}

const medalIcon = (rank: number) => rank === 1 ? '🥇' : rank === 2 ? '🥈' : rank === 3 ? '🥉' : null

const periodLabels: Record<string, string> = {
  all:   'Todo el tiempo',
  month: 'Este mes',
  week:  'Esta semana',
}

function changePeriod(p: string) {
  router.get(getRoute('leaderboard'), { period: p }, { preserveState: true, replace: true })
}

// Podio (top 3)
const podium = computed(() => props.players.slice(0, 3))
// Resto de la tabla
const rest   = computed(() => props.players.slice(3))

// Máximo score para barra de progreso relativa
const maxScore = computed(() => props.players[0]?.total_score ?? 1)
</script>

<template>
  <Head title="Clasificación - Trivia" />

  <div class="py-6">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

      <!-- Header + filtro de período -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">🏆 Clasificación Global</h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Top jugadores ordenados por puntuación acumulada
          </p>
        </div>

        <!-- Selector de período -->
        <div class="flex gap-1 bg-gray-100 dark:bg-gray-800 rounded-lg p-1">
          <button
            v-for="p in ['all', 'month', 'week']"
            :key="p"
            @click="changePeriod(p)"
            :class="[
              'px-4 py-1.5 rounded-md text-sm font-medium transition',
              period === p
                ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
            ]"
          >
            {{ periodLabels[p] }}
          </button>
        </div>
      </div>

      <!-- KPI globales -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 text-center">
          <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ fmt(globalStats.total_games) }}</p>
          <p class="text-xs text-gray-500 mt-0.5">Partidas finalizadas</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 text-center">
          <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ fmt(globalStats.total_players) }}</p>
          <p class="text-xs text-gray-500 mt-0.5">Jugadores en ranking</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 text-center col-span-2">
          <p class="text-xs text-gray-500 mb-0.5">Mejor puntuación</p>
          <p class="text-2xl font-bold text-yellow-500">{{ fmt(globalStats.top_score) }} pts</p>
          <p class="text-xs text-gray-700 dark:text-gray-300 font-medium truncate">{{ globalStats.top_name }}</p>
        </div>
      </div>

      <!-- Sin datos -->
      <div v-if="players.length === 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm py-16 text-center">
        <p class="text-4xl mb-3">🎮</p>
        <p class="text-gray-500 dark:text-gray-400">Aún no hay partidas completadas en este período.</p>
        <p class="text-sm text-gray-400 mt-1">¡Sé el primero en la clasificación!</p>
      </div>

      <template v-else>

        <!-- ── Podio (top 3) ──────────────────────────────────────────────── -->
        <div class="flex items-end justify-center gap-4 sm:gap-8">
          <!-- 2° lugar -->
          <div v-if="podium[1]" class="flex flex-col items-center gap-2 pb-0">
            <div :class="['w-14 h-14 rounded-full bg-gradient-to-br flex items-center justify-center text-white font-bold text-lg shadow-md', avatarColor(2)]">
              {{ initials(podium[1].name) }}
            </div>
            <div class="text-center">
              <p class="text-xs font-semibold text-gray-800 dark:text-gray-200 truncate max-w-[80px]">{{ podium[1].name }}</p>
              <p class="text-sm font-bold text-gray-600 dark:text-gray-300">{{ fmt(podium[1].total_score) }}</p>
            </div>
            <div class="w-20 h-16 bg-gray-200 dark:bg-gray-600 rounded-t-lg flex items-center justify-center">
              <span class="text-2xl">🥈</span>
            </div>
          </div>

          <!-- 1° lugar -->
          <div v-if="podium[0]" class="flex flex-col items-center gap-2">
            <div class="relative">
              <div :class="['w-20 h-20 rounded-full bg-gradient-to-br flex items-center justify-center text-white font-bold text-2xl shadow-lg ring-4 ring-yellow-400', avatarColor(1)]">
                {{ initials(podium[0].name) }}
              </div>
              <span class="absolute -top-3 left-1/2 -translate-x-1/2 text-2xl">👑</span>
            </div>
            <div class="text-center">
              <p class="text-sm font-bold text-gray-900 dark:text-white truncate max-w-[100px]">{{ podium[0].name }}</p>
              <p class="text-base font-bold text-yellow-500">{{ fmt(podium[0].total_score) }}</p>
            </div>
            <div class="w-24 h-24 bg-yellow-400 dark:bg-yellow-500 rounded-t-lg flex items-center justify-center">
              <span class="text-3xl">🥇</span>
            </div>
          </div>

          <!-- 3° lugar -->
          <div v-if="podium[2]" class="flex flex-col items-center gap-2 pb-0">
            <div :class="['w-14 h-14 rounded-full bg-gradient-to-br flex items-center justify-center text-white font-bold text-lg shadow-md', avatarColor(3)]">
              {{ initials(podium[2].name) }}
            </div>
            <div class="text-center">
              <p class="text-xs font-semibold text-gray-800 dark:text-gray-200 truncate max-w-[80px]">{{ podium[2].name }}</p>
              <p class="text-sm font-bold text-gray-600 dark:text-gray-300">{{ fmt(podium[2].total_score) }}</p>
            </div>
            <div class="w-20 h-10 bg-amber-600 dark:bg-amber-700 rounded-t-lg flex items-center justify-center">
              <span class="text-2xl">🥉</span>
            </div>
          </div>
        </div>

        <!-- ── Tabla completa ─────────────────────────────────────────────── -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-12">#</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Jugador</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Puntuación</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">Partidas</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Victorias</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Aciertos</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">Mejor racha</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">Resp. prom.</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">% Acierto</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
              <tr
                v-for="p in players"
                :key="p.player_id"
                :class="[
                  'hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors',
                  p.rank <= 3 && 'bg-amber-50/40 dark:bg-amber-900/10'
                ]"
              >
                <!-- Rank -->
                <td class="px-4 py-3 whitespace-nowrap">
                  <span
                    :class="[
                      'inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold',
                      p.rank === 1 ? 'bg-yellow-400 text-white' :
                      p.rank === 2 ? 'bg-gray-300 text-gray-700' :
                      p.rank === 3 ? 'bg-amber-600 text-white' :
                      'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400'
                    ]"
                  >
                    {{ medalIcon(p.rank) ?? p.rank }}
                  </span>
                </td>

                <!-- Jugador -->
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <div :class="['w-9 h-9 rounded-full bg-gradient-to-br flex-shrink-0 flex items-center justify-center text-white text-xs font-bold', avatarColor(p.rank)]">
                      {{ initials(p.name) }}
                    </div>
                    <div class="min-w-0">
                      <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ p.name }}</p>
                      <p v-if="p.email" class="text-xs text-gray-400 truncate hidden sm:block">{{ p.email }}</p>
                    </div>
                  </div>
                </td>

                <!-- Puntuación + barra relativa -->
                <td class="px-4 py-3 whitespace-nowrap text-right">
                  <p class="text-sm font-bold text-gray-900 dark:text-white">{{ fmt(p.total_score) }}</p>
                  <div class="mt-1 w-20 h-1 bg-gray-100 dark:bg-gray-700 rounded-full ml-auto hidden sm:block">
                    <div
                      class="h-full bg-indigo-500 rounded-full"
                      :style="{ width: Math.round((p.total_score / maxScore) * 100) + '%' }"
                    />
                  </div>
                </td>

                <!-- Partidas -->
                <td class="px-4 py-3 whitespace-nowrap text-right text-gray-600 dark:text-gray-400 hidden sm:table-cell">
                  {{ p.games_played }}
                </td>

                <!-- Victorias -->
                <td class="px-4 py-3 whitespace-nowrap text-right hidden md:table-cell">
                  <span class="text-gray-800 dark:text-gray-200">{{ p.wins }}</span>
                  <span class="text-xs text-gray-400 ml-1">({{ p.win_rate }}%)</span>
                </td>

                <!-- Aciertos / fallos -->
                <td class="px-4 py-3 whitespace-nowrap text-right hidden md:table-cell">
                  <span class="text-green-600">{{ fmt(p.total_correct) }}</span>
                  <span class="text-gray-400 mx-0.5">/</span>
                  <span class="text-red-500">{{ fmt(p.total_wrong) }}</span>
                </td>

                <!-- Mejor racha -->
                <td class="px-4 py-3 whitespace-nowrap text-right text-gray-600 dark:text-gray-400 hidden lg:table-cell">
                  🔥 {{ p.best_streak }}
                </td>

                <!-- Tiempo promedio -->
                <td class="px-4 py-3 whitespace-nowrap text-right text-gray-600 dark:text-gray-400 hidden lg:table-cell">
                  {{ msToSec(p.avg_response_ms) }}
                </td>

                <!-- % Acierto -->
                <td class="px-4 py-3 whitespace-nowrap text-right">
                  <span
                    :class="[
                      'px-2 py-0.5 rounded-full text-xs font-medium',
                      p.accuracy >= 70 ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
                      p.accuracy >= 50 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' :
                                         'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                    ]"
                  >
                    {{ p.accuracy }}%
                  </span>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Footer con leyenda -->
          <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between text-xs text-gray-500">
            <span>Mostrando {{ players.length }} jugadores · {{ periodLabels[period] }}</span>
            <span>Ordenado por puntuación total acumulada</span>
          </div>
        </div>

      </template>
    </div>
  </div>
</template>
