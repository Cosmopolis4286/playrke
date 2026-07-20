<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { getRoute } from '@js/routes/triviaRoutes'

interface Game {
  id: string
  game_code: string
  status: string
  player_count: number
  total_questions: number
  current_question_index: number
  time_per_question: number
  winner_user_id: string | null
  winner_name: string | null
  started_at: string | null
  ended_at: string | null
  created_at: string
  duration_min: number | null
  room_id: string
  room_code: string
  room_title: string
  room_question_count: number
  max_players: number
}

interface ScoreEntry {
  player_id: string
  name: string
  position: number | null
  final_score: number
  correct_answers: number
  wrong_answers: number
  max_streak: number
  avg_response_ms: number
  is_winner: boolean
  total_questions: number
}

interface QuestionEntry {
  order_index: number
  points: number
  time_limit_seconds: number
  is_closed: boolean
  sent_at: string | null
  answered_at: string | null
  question_duration_ms: number | null
  question_id: string
  question_text: string
  category: string | null
  difficulty: string | null
  options: string[]
  correct_option_index: number
}

const props = defineProps<{
  game: Game
  scoreboard: ScoreEntry[]
  questions: QuestionEntry[]
}>()

// ── Helpers ───────────────────────────────────────────────────────────────────

const STATUS_LABEL: Record<string, string> = {
  playing:   'En juego',
  finished:  'Finalizada',
  cancelled: 'Cancelada',
  paused:    'Pausada',
  waiting:   'Esperando',
}
const STATUS_COLOR: Record<string, string> = {
  playing:   'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
  finished:  'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
  cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
  paused:    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
  waiting:   'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
}
const DIFFICULTY_COLOR: Record<string, string> = {
  easy:   'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
  medium: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
  hard:   'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
}

function formatDate(d: string | null): string {
  if (!d) return '—'
  return new Date(d).toLocaleString('es-ES', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  })
}

function msToSec(ms: number | null): string {
  if (!ms) return '—'
  return (ms / 1000).toFixed(1) + 's'
}

function fmt(n: number): string {
  return n.toLocaleString('es-ES')
}

function accuracy(s: ScoreEntry): number {
  const total = s.correct_answers + s.wrong_answers
  if (!total) return 0
  return Math.round((s.correct_answers / total) * 100)
}

const maxScore = computed(() =>
  props.scoreboard.reduce((m, s) => Math.max(m, s.final_score), 1)
)

function rankBadgeClass(pos: number | null, isWinner: boolean): string {
  if (isWinner || pos === 1) return 'bg-yellow-400 text-white'
  if (pos === 2) return 'bg-gray-300 text-gray-700'
  if (pos === 3) return 'bg-amber-600 text-white'
  return 'gs-neutral-badge'
}

function rankIcon(pos: number | null, isWinner: boolean): string {
  if (isWinner || pos === 1) return '🥇'
  if (pos === 2) return '🥈'
  if (pos === 3) return '🥉'
  return String(pos ?? '—')
}
</script>

<template>
  <Head :title="`Partida ${game.game_code} - Trivia`" />

  <div class="py-6">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

      <!-- Breadcrumb -->
      <div class="flex items-center gap-2 text-sm gs-text-muted">
        <Link :href="getRoute('gamesIndex')" class="hover:text-indigo-600 dark:hover:text-indigo-400">
          Partidas
        </Link>
        <span>/</span>
        <span class="gs-text-secondary font-mono">{{ game.game_code }}</span>
      </div>

      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold gs-text font-mono">{{ game.game_code }}</h1>
            <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', STATUS_COLOR[game.status] ?? STATUS_COLOR.waiting]">
              {{ STATUS_LABEL[game.status] ?? game.status }}
            </span>
          </div>
          <p class="text-sm gs-text-muted mt-1">
            Sala: <span class="font-medium gs-text-secondary">{{ game.room_title }}</span>
            <code class="ml-2 text-xs gs-text-faint">({{ game.room_code }})</code>
          </p>
        </div>
        <Link
          :href="getRoute('gamesIndex')"
          class="gs-back-btn inline-flex items-center gap-1.5 px-4 py-2 text-sm rounded-lg border transition"
        >
          ← Volver a partidas
        </Link>
      </div>

      <!-- Metadata cards -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="gs-card rounded-xl shadow-sm p-4">
          <p class="text-xs text-gray-500 uppercase mb-1">Jugadores</p>
          <p class="text-xl font-bold gs-text">{{ game.player_count }} <span class="text-sm font-normal text-gray-400">/ {{ game.max_players }}</span></p>
        </div>
        <div class="gs-card rounded-xl shadow-sm p-4">
          <p class="text-xs text-gray-500 uppercase mb-1">Preguntas</p>
          <p class="text-xl font-bold gs-text">{{ game.current_question_index }} <span class="text-sm font-normal text-gray-400">/ {{ game.total_questions }}</span></p>
        </div>
        <div class="gs-card rounded-xl shadow-sm p-4">
          <p class="text-xs text-gray-500 uppercase mb-1">Inicio</p>
          <p class="text-sm font-medium gs-text">{{ formatDate(game.started_at) }}</p>
        </div>
        <div class="gs-card rounded-xl shadow-sm p-4">
          <p class="text-xs text-gray-500 uppercase mb-1">Duración</p>
          <p class="text-xl font-bold gs-text">
            {{ game.duration_min !== null ? game.duration_min + ' min' : '—' }}
          </p>
        </div>
      </div>

      <!-- Ganador -->
      <div v-if="game.winner_name" class="bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 rounded-xl border border-yellow-200 dark:border-yellow-700/50 p-5 flex items-center gap-4">
        <span class="text-4xl">🏆</span>
        <div>
          <p class="text-xs text-yellow-700 dark:text-yellow-400 uppercase font-semibold mb-0.5">Ganador</p>
          <p class="text-lg font-bold gs-text">{{ game.winner_name }}</p>
        </div>
      </div>

      <!-- ── Scoreboard ──────────────────────────────────────────────────────── -->
      <div class="gs-card rounded-xl shadow-sm overflow-hidden">
        <div class="gs-header-border px-5 py-4 border-b">
          <h2 class="text-base font-semibold gs-text">🏅 Clasificación de jugadores</h2>
        </div>

        <div v-if="scoreboard.length === 0" class="px-6 py-10 text-center gs-text-faint text-sm">
          No hay datos de puntuación para esta partida.
        </div>

        <table v-else class="min-w-full text-sm gs-divide">
          <thead class="gs-thead">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-12">#</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Jugador</th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Puntuación</th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">Aciertos</th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Fallos</th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Racha</th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">Resp. prom.</th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">% Acierto</th>
            </tr>
          </thead>
          <tbody class="gs-divide">
            <tr
              v-for="(s, idx) in scoreboard"
              :key="s.player_id"
              :class="[
                'gs-row-hover transition-colors',
                s.is_winner && 'bg-yellow-50/60 dark:bg-yellow-900/10'
              ]"
            >
              <!-- Posición -->
              <td class="px-4 py-3 whitespace-nowrap">
                <span :class="['inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold', rankBadgeClass(s.position ?? idx + 1, s.is_winner)]">
                  {{ rankIcon(s.position ?? idx + 1, s.is_winner) }}
                </span>
              </td>

              <!-- Jugador -->
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <p class="text-sm font-medium gs-text">{{ s.name }}</p>
                  <span v-if="s.is_winner" class="text-xs bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 px-1.5 py-0.5 rounded-full">Ganador</span>
                </div>
              </td>

              <!-- Puntuación -->
              <td class="px-4 py-3 whitespace-nowrap text-right">
                <p class="text-sm font-bold gs-text">{{ fmt(s.final_score) }}</p>
                <div class="mt-1 w-20 h-1 gs-track rounded-full ml-auto hidden sm:block">
                  <div
                    class="h-full bg-indigo-500 rounded-full"
                    :style="{ width: Math.round((s.final_score / maxScore) * 100) + '%' }"
                  />
                </div>
              </td>

              <!-- Aciertos -->
              <td class="px-4 py-3 whitespace-nowrap text-right text-green-600 dark:text-green-400 font-medium hidden sm:table-cell">
                {{ s.correct_answers }}
              </td>

              <!-- Fallos -->
              <td class="px-4 py-3 whitespace-nowrap text-right text-red-500 dark:text-red-400 hidden md:table-cell">
                {{ s.wrong_answers }}
              </td>

              <!-- Racha -->
              <td class="px-4 py-3 whitespace-nowrap text-right gs-text-secondary hidden md:table-cell">
                🔥 {{ s.max_streak }}
              </td>

              <!-- Tiempo promedio -->
              <td class="px-4 py-3 whitespace-nowrap text-right gs-text-secondary hidden lg:table-cell">
                {{ msToSec(s.avg_response_ms) }}
              </td>

              <!-- % Acierto -->
              <td class="px-4 py-3 whitespace-nowrap text-right">
                <span :class="[
                  'px-2 py-0.5 rounded-full text-xs font-medium',
                  accuracy(s) >= 70 ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
                  accuracy(s) >= 50 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' :
                                     'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                ]">
                  {{ accuracy(s) }}%
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- ── Preguntas jugadas ───────────────────────────────────────────────── -->
      <div class="gs-card rounded-xl shadow-sm overflow-hidden">
        <div class="gs-header-border px-5 py-4 border-b">
          <h2 class="text-base font-semibold gs-text">❓ Preguntas de la partida</h2>
          <p class="text-xs text-gray-500 mt-0.5">{{ questions.length }} preguntas jugadas</p>
        </div>

        <div v-if="questions.length === 0" class="px-6 py-10 text-center gs-text-faint text-sm">
          No hay preguntas registradas para esta partida.
        </div>

        <div v-else class="gs-divide">
          <div
            v-for="q in questions"
            :key="q.question_id"
            class="gs-row-hover px-5 py-4 transition-colors"
          >
            <!-- Cabecera de pregunta -->
            <div class="flex items-start justify-between gap-3 mb-3">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 text-xs font-bold flex-shrink-0">
                  {{ q.order_index + 1 }}
                </span>
                <p class="text-sm font-medium gs-text">{{ q.question_text }}</p>
              </div>
              <div class="flex items-center gap-2 flex-shrink-0">
                <span v-if="q.category" class="gs-tag text-xs px-2 py-0.5 rounded-full">
                  {{ q.category }}
                </span>
                <span v-if="q.difficulty" :class="['text-xs px-2 py-0.5 rounded-full capitalize', DIFFICULTY_COLOR[q.difficulty] ?? DIFFICULTY_COLOR.medium]">
                  {{ q.difficulty }}
                </span>
                <span class="text-xs text-gray-400">{{ q.points }} pts · {{ q.time_limit_seconds }}s</span>
              </div>
            </div>

            <!-- Opciones -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <div
                v-for="(option, idx) in q.options"
                :key="idx"
                :class="[
                  'flex items-center gap-2 px-3 py-2 rounded-lg text-sm border transition-colors',
                  idx === q.correct_option_index
                    ? 'border-green-400 bg-green-50 dark:bg-green-900/20 dark:border-green-600 text-green-800 dark:text-green-300 font-medium'
                    : 'gs-option-border gs-text-secondary'
                ]"
              >
                <span :class="[
                  'inline-flex items-center justify-center w-5 h-5 rounded-full text-xs font-bold flex-shrink-0',
                  idx === q.correct_option_index
                    ? 'bg-green-500 text-white'
                    : 'gs-neutral-badge'
                ]">
                  {{ ['A','B','C','D'][idx] }}
                </span>
                <span>{{ option }}</span>
                <span v-if="idx === q.correct_option_index" class="ml-auto text-xs">✓</span>
              </div>
            </div>

            <!-- Timing info -->
            <div v-if="q.question_duration_ms" class="mt-2 text-xs text-gray-400">
              Duración real: {{ msToSec(q.question_duration_ms) }}
              <span v-if="q.is_closed" class="ml-2 gs-text-faint">· Cerrada</span>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.gs-text { color: var(--ds-text); }
.gs-text-secondary { color: var(--ds-text-2); }
.gs-text-muted { color: var(--ds-text-muted); }
.gs-text-faint { color: var(--ds-text-muted); }
.gs-card { background: var(--ds-surface); }
.gs-header-border { border-color: var(--ds-border); }
.gs-divide > :not([hidden]) ~ :not([hidden]) { border-top: 1px solid var(--ds-border); }
.gs-thead { background: var(--ds-surface-2); }
.gs-row-hover:hover { background: var(--ds-surface-2); }
.gs-track { background: var(--ds-surface-2); }
.gs-neutral-badge { background: var(--ds-surface-2); color: var(--ds-text-muted); }
.gs-tag { background: var(--ds-surface-2); color: var(--ds-text-2); }
.gs-back-btn { border-color: var(--ds-border); color: var(--ds-text-2); }
.gs-back-btn:hover { background: var(--ds-surface-2); }
.gs-option-border { border-color: var(--ds-border); }
</style>
