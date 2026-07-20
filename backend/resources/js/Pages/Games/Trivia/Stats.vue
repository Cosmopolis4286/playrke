<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { getRoute } from '@js/routes/triviaRoutes'

interface RoomStats {
  total: number
  waiting: number
  playing: number
  finished: number
  cancelled: number
}

interface GameStats {
  total: number
  finished: number
  playing: number
}

interface QuestionStats {
  total: number
  active: number
  by_difficulty: Record<string, number>
  categories: { category: string; total: number }[]
}

interface ParticipantStats {
  total: number
  unique_users: number
  winners: number
  abandoned: number
  total_prizes: number
}

interface TopPlayer {
  player_id: string
  total_score: number
  correct: number
  games_played: number
  wins: number
}

interface MonthlyActivity {
  month: string
  games: number
  players: number
}

const props = defineProps<{
  roomStats: RoomStats
  gameStats: GameStats
  questionStats: QuestionStats
  participantStats: ParticipantStats
  topPlayers: TopPlayer[]
  monthlyActivity: MonthlyActivity[]
  avgResponseMs: number
  accuracyRate: number
}>()

// ── Helpers ──────────────────────────────────────────────────────────────────

function msToSeconds(ms: number): string {
  return (ms / 1000).toFixed(1) + 's'
}

function winRate(player: TopPlayer): string {
  if (!player.games_played) return '0%'
  return Math.round((player.wins / player.games_played) * 100) + '%'
}

function formatMonth(m: string): string {
  if (!m) return ''
  const [year, month] = m.split('-')
  const d = new Date(+year, +month - 1)
  return d.toLocaleDateString('es-ES', { month: 'short', year: '2-digit' })
}

function formatNumber(n: number): string {
  return n.toLocaleString('es-ES')
}

const difficultyColor = (d: string) => ({
  easy:   'bg-green-100 text-green-800',
  medium: 'bg-yellow-100 text-yellow-800',
  hard:   'bg-red-100 text-red-800',
}[d] ?? 'bg-gray-100 text-gray-700')

const difficultyLabel = (d: string) => ({ easy: 'Fácil', medium: 'Media', hard: 'Difícil' }[d] ?? d)

// Altura de barras del gráfico de actividad mensual (normalizada a 100px)
const maxGames = computed(() => Math.max(...props.monthlyActivity.map(m => m.games), 1))
function barHeight(games: number): number {
  return Math.round((games / maxGames.value) * 80)
}

// Tasa de acierto visual
const accuracyColor = computed(() => {
  if (props.accuracyRate >= 70) return 'text-green-600'
  if (props.accuracyRate >= 50) return 'text-yellow-600'
  return 'text-red-600'
})

// Porcentaje para progress bar de preguntas
const questionActivePercent = computed(() =>
  props.questionStats.total > 0
    ? Math.round((props.questionStats.active / props.questionStats.total) * 100)
    : 0
)
</script>

<template>
  <Head title="Estadísticas - Trivia" />

  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold st-text">📊 Estadísticas de Trivia</h1>
          <p class="text-sm st-text-muted mt-1">Métricas globales del sistema</p>
        </div>
        <Link
          :href="getRoute('roomsIndex')"
          class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline"
        >
          Ver Salas →
        </Link>
      </div>

      <!-- ── Métricas principales ───────────────────────────────────────── -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <!-- Salas totales -->
        <div class="st-card rounded-xl shadow-sm p-5">
          <p class="text-xs font-medium st-text-muted uppercase tracking-wide">Salas</p>
          <p class="text-3xl font-bold st-text mt-1">{{ formatNumber(roomStats.total) }}</p>
          <div class="mt-3 flex gap-2 flex-wrap">
            <span class="px-2 py-0.5 rounded-full text-xs bg-yellow-100 text-yellow-800">{{ roomStats.waiting }} espera</span>
            <span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800">{{ roomStats.playing }} activas</span>
            <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600">{{ roomStats.finished }} cerradas</span>
          </div>
        </div>

        <!-- Juegos -->
        <div class="st-card rounded-xl shadow-sm p-5">
          <p class="text-xs font-medium st-text-muted uppercase tracking-wide">Partidas</p>
          <p class="text-3xl font-bold st-text mt-1">{{ formatNumber(gameStats.total) }}</p>
          <div class="mt-3 flex gap-2 flex-wrap">
            <span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800">{{ gameStats.playing }} en juego</span>
            <span class="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-800">{{ gameStats.finished }} finalizadas</span>
          </div>
        </div>

        <!-- Participantes únicos -->
        <div class="st-card rounded-xl shadow-sm p-5">
          <p class="text-xs font-medium st-text-muted uppercase tracking-wide">Jugadores únicos</p>
          <p class="text-3xl font-bold st-text mt-1">{{ formatNumber(participantStats.unique_users) }}</p>
          <div class="mt-3 flex gap-2 flex-wrap">
            <span class="px-2 py-0.5 rounded-full text-xs bg-indigo-100 text-indigo-800">{{ participantStats.total }} participaciones</span>
            <span class="px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-800">{{ participantStats.winners }} ganadores</span>
          </div>
        </div>

        <!-- Tasa de acierto -->
        <div class="st-card rounded-xl shadow-sm p-5">
          <p class="text-xs font-medium st-text-muted uppercase tracking-wide">Tasa de acierto</p>
          <p :class="['text-3xl font-bold mt-1', accuracyColor]">{{ accuracyRate }}%</p>
          <div class="mt-3">
            <p class="text-xs text-gray-500">Tiempo prom. respuesta</p>
            <p class="text-sm font-semibold st-text-secondary">{{ msToSeconds(avgResponseMs) }}</p>
          </div>
        </div>
      </div>

      <!-- ── Segunda fila: Preguntas + Actividad mensual ───────────────── -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Banco de Preguntas -->
        <div class="st-card rounded-xl shadow-sm p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-semibold st-text">📚 Banco de Preguntas</h2>
            <Link
              :href="getRoute('questionsIndex', { roomId: 'all' })"
              class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
            >
              Gestionar →
            </Link>
          </div>

          <!-- Total + activas -->
          <div class="flex items-end gap-4 mb-4">
            <div>
              <p class="text-4xl font-bold st-text">{{ formatNumber(questionStats.total) }}</p>
              <p class="text-xs text-gray-500 mt-0.5">preguntas totales</p>
            </div>
            <div class="pb-1">
              <p class="text-xl font-semibold text-green-600">{{ questionStats.active }} activas</p>
              <div class="w-32 h-1.5 bg-gray-200 rounded-full mt-1">
                <div
                  class="h-full bg-green-500 rounded-full transition-all"
                  :style="{ width: questionActivePercent + '%' }"
                />
              </div>
            </div>
          </div>

          <!-- Por dificultad -->
          <div class="flex gap-2 mb-5">
            <span
              v-for="(count, diff) in questionStats.by_difficulty"
              :key="diff"
              :class="['px-3 py-1 rounded-full text-xs font-medium', difficultyColor(String(diff))]"
            >
              {{ difficultyLabel(String(diff)) }}: {{ count }}
            </span>
          </div>

          <!-- Top categorías -->
          <div v-if="questionStats.categories.length">
            <p class="text-xs font-medium st-text-muted uppercase mb-2">Top Categorías</p>
            <div class="space-y-2">
              <div
                v-for="cat in questionStats.categories"
                :key="cat.category"
                class="flex items-center gap-2"
              >
                <span class="text-xs st-text-secondary w-32 truncate">{{ cat.category }}</span>
                <div class="flex-1 h-1.5 st-track rounded-full overflow-hidden">
                  <div
                    class="h-full bg-indigo-400 rounded-full"
                    :style="{ width: Math.round((cat.total / questionStats.total) * 100) + '%' }"
                  />
                </div>
                <span class="text-xs text-gray-500 w-6 text-right">{{ cat.total }}</span>
              </div>
            </div>
          </div>
          <p v-else class="text-xs text-gray-400 italic">Sin categorías asignadas aún.</p>
        </div>

        <!-- Actividad mensual -->
        <div class="st-card rounded-xl shadow-sm p-6">
          <h2 class="text-base font-semibold st-text mb-4">📅 Actividad Mensual</h2>

          <div v-if="monthlyActivity.length" class="flex items-end justify-between gap-2 h-24">
            <div
              v-for="m in [...monthlyActivity].reverse()"
              :key="m.month"
              class="flex-1 flex flex-col items-center gap-1"
            >
              <span class="text-xs text-gray-500">{{ m.games }}</span>
              <div
                class="w-full bg-indigo-500 rounded-t transition-all hover:bg-indigo-600"
                :style="{ height: barHeight(m.games) + 'px', minHeight: '4px' }"
                :title="`${formatMonth(m.month)}: ${m.games} partidas, ${m.players} jugadores`"
              />
              <span class="text-xs text-gray-400 truncate w-full text-center">{{ formatMonth(m.month) }}</span>
            </div>
          </div>
          <p v-else class="text-sm text-gray-400 italic py-8 text-center">Sin actividad registrada aún.</p>

          <!-- Resumen numérico -->
          <div class="st-border-t mt-6 grid grid-cols-3 gap-3 border-t pt-4">
            <div class="text-center">
              <p class="text-lg font-bold st-text">
                {{ formatNumber(monthlyActivity.reduce((a, m) => a + m.games, 0)) }}
              </p>
              <p class="text-xs text-gray-500">partidas (6M)</p>
            </div>
            <div class="text-center">
              <p class="text-lg font-bold st-text">
                {{ formatNumber(monthlyActivity.reduce((a, m) => a + m.players, 0)) }}
              </p>
              <p class="text-xs text-gray-500">jugadores (6M)</p>
            </div>
            <div class="text-center">
              <p class="text-lg font-bold st-text">
                {{ participantStats.winners }}
              </p>
              <p class="text-xs text-gray-500">ganadores</p>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Top jugadores ──────────────────────────────────────────────── -->
      <div class="st-card rounded-xl shadow-sm p-6">
        <h2 class="text-base font-semibold st-text mb-4">🏆 Top Jugadores</h2>

        <div v-if="topPlayers.length" class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="st-border-b">
                <th class="pb-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                <th class="pb-2 text-left text-xs font-medium text-gray-500 uppercase">Jugador</th>
                <th class="pb-2 text-right text-xs font-medium text-gray-500 uppercase">Puntuación</th>
                <th class="pb-2 text-right text-xs font-medium text-gray-500 uppercase">Partidas</th>
                <th class="pb-2 text-right text-xs font-medium text-gray-500 uppercase">Victorias</th>
                <th class="pb-2 text-right text-xs font-medium text-gray-500 uppercase">Aciertos</th>
                <th class="pb-2 text-right text-xs font-medium text-gray-500 uppercase">% Victoria</th>
              </tr>
            </thead>
            <tbody class="st-divide">
              <tr
                v-for="(player, i) in topPlayers"
                :key="player.player_id"
                class="st-row-hover transition-colors"
              >
                <td class="py-2.5 pr-3">
                  <span
                    :class="[
                      'inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold',
                      i === 0 ? 'bg-yellow-400 text-white' :
                      i === 1 ? 'bg-gray-300 text-white' :
                      i === 2 ? 'bg-amber-600 text-white' :
                      'st-neutral-badge'
                    ]"
                  >{{ i + 1 }}</span>
                </td>
                <td class="py-2.5">
                  <span class="font-mono text-xs st-text-muted">{{ player.player_id.slice(0, 8) }}…</span>
                </td>
                <td class="py-2.5 text-right font-semibold st-text">
                  {{ formatNumber(player.total_score) }}
                </td>
                <td class="py-2.5 text-right st-text-secondary">{{ player.games_played }}</td>
                <td class="py-2.5 text-right st-text-secondary">{{ player.wins }}</td>
                <td class="py-2.5 text-right st-text-secondary">{{ formatNumber(player.correct) }}</td>
                <td class="py-2.5 text-right">
                  <span
                    :class="[
                      'px-2 py-0.5 rounded-full text-xs font-medium',
                      +winRate(player).replace('%','') >= 50 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'
                    ]"
                  >{{ winRate(player) }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="py-10 text-center text-gray-400">
          <p class="text-3xl mb-2">🎮</p>
          <p class="text-sm">Aún no hay partidas completadas.</p>
        </div>
      </div>

      <!-- ── Estado de Salas (distribución) ────────────────────────────── -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div
          v-for="item in [
            { label: 'En Espera',    value: roomStats.waiting,   color: 'yellow', total: roomStats.total },
            { label: 'En Juego',     value: roomStats.playing,   color: 'green',  total: roomStats.total },
            { label: 'Canceladas',   value: roomStats.cancelled, color: 'red',    total: roomStats.total },
          ]"
          :key="item.label"
          class="st-card rounded-xl shadow-sm p-5"
        >
          <p class="text-xs font-medium text-gray-500 uppercase">{{ item.label }}</p>
          <p class="text-2xl font-bold st-text mt-1">{{ item.value }}</p>
          <div class="mt-2 h-1.5 w-full st-track rounded-full">
            <div
              :class="[
                'h-full rounded-full transition-all',
                item.color === 'yellow' ? 'bg-yellow-400' :
                item.color === 'green'  ? 'bg-green-500'  : 'bg-red-500'
              ]"
              :style="{ width: item.total > 0 ? Math.round((item.value / item.total) * 100) + '%' : '0%' }"
            />
          </div>
          <p class="text-xs text-gray-400 mt-1">
            {{ item.total > 0 ? Math.round((item.value / item.total) * 100) : 0 }}% del total
          </p>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.st-text { color: var(--ds-text); }
.st-text-secondary { color: var(--ds-text-2); }
.st-text-muted { color: var(--ds-text-muted); }
.st-card { background: var(--ds-surface); }
.st-track { background: var(--ds-surface-2); }
.st-border-t { border-color: var(--ds-border); }
.st-border-b { border-color: var(--ds-border); }
.st-divide > :not([hidden]) ~ :not([hidden]) { border-top: 1px solid var(--ds-border); }
.st-row-hover:hover { background: var(--ds-surface-2); }
.st-neutral-badge { background: var(--ds-surface-2); color: var(--ds-text-2); }
</style>
