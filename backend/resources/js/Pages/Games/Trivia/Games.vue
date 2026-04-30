<script setup lang="ts">
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import debounce from 'lodash/debounce'
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
  winner_score: number | null
  started_at: string | null
  ended_at: string | null
  created_at: string
  duration_min: number | null
  room_id: string
  room_code: string
  room_title: string
  max_players: number
}

interface Pagination {
  data: Game[]
  total: number
  from: number | null
  to: number | null
  links: { url: string | null; label: string; active: boolean }[]
}

const props = defineProps<{
  games: Pagination
  stats: { total: number; playing: number; finished: number; cancelled: number }
  filters: { status: string; search: string; dateFrom: string; dateTo: string }
}>()

const search   = ref(props.filters.search   ?? '')
const status   = ref(props.filters.status   ?? '')
const dateFrom = ref(props.filters.dateFrom ?? '')
const dateTo   = ref(props.filters.dateTo   ?? '')

function applyFilters() {
  router.get(
    getRoute('gamesIndex'),
    { status: status.value, search: search.value, date_from: dateFrom.value, date_to: dateTo.value },
    { preserveState: true, replace: true }
  )
}
const debouncedSearch = debounce(applyFilters, 300)

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

function formatDate(d: string | null): string {
  if (!d) return '—'
  return new Date(d).toLocaleString('es-ES', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })
}

function progressPct(g: Game): number {
  if (!g.total_questions) return 0
  return Math.round((g.current_question_index / g.total_questions) * 100)
}
</script>

<template>
  <Head title="Partidas - Trivia" />

  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">🎮 Partidas de Trivia</h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Historial y estado de todas las partidas</p>
        </div>
      </div>

      <!-- Stats cards -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div
          v-for="item in [
            { label: 'Total',        value: stats.total,     icon: '🎲', color: 'indigo' },
            { label: 'En juego',     value: stats.playing,   icon: '▶️',  color: 'green'  },
            { label: 'Finalizadas',  value: stats.finished,  icon: '✅', color: 'blue'   },
            { label: 'Canceladas',   value: stats.cancelled, icon: '❌', color: 'red'    },
          ]"
          :key="item.label"
          class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 flex items-center gap-3"
        >
          <span class="text-2xl">{{ item.icon }}</span>
          <div>
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">{{ item.label }}</p>
            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ item.value.toLocaleString('es-ES') }}</p>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-4">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
          <input
            v-model="search"
            type="text"
            placeholder="Código sala/partida..."
            class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
            @input="debouncedSearch"
          />
          <select
            v-model="status"
            class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
            @change="applyFilters"
          >
            <option value="">Todos los estados</option>
            <option v-for="(label, val) in STATUS_LABEL" :key="val" :value="val">{{ label }}</option>
          </select>
          <input
            v-model="dateFrom"
            type="date"
            class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
            @change="applyFilters"
          />
          <input
            v-model="dateTo"
            type="date"
            class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
            @change="applyFilters"
          />
        </div>
      </div>

      <!-- Tabla -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Partida</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Sala</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Estado</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">Jugadores</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Progreso</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">Ganador</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">Inicio</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden xl:table-cell">Duración</th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Ver</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">

            <tr
              v-for="g in games.data"
              :key="g.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors"
            >
              <!-- Partida -->
              <td class="px-4 py-3 whitespace-nowrap">
                <div class="flex items-center gap-2">
                  <span class="text-lg">🎲</span>
                  <div>
                    <p class="font-mono text-xs font-semibold text-gray-800 dark:text-gray-200">{{ g.game_code }}</p>
                    <p class="text-xs text-gray-400 hidden sm:block">{{ g.time_per_question }}s / pregunta</p>
                  </div>
                </div>
              </td>

              <!-- Sala -->
              <td class="px-4 py-3">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-[140px]">{{ g.room_title }}</p>
                <code class="text-xs text-gray-400">{{ g.room_code }}</code>
              </td>

              <!-- Estado -->
              <td class="px-4 py-3 whitespace-nowrap">
                <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', STATUS_COLOR[g.status] ?? STATUS_COLOR.waiting]">
                  {{ STATUS_LABEL[g.status] ?? g.status }}
                </span>
              </td>

              <!-- Jugadores -->
              <td class="px-4 py-3 whitespace-nowrap hidden sm:table-cell">
                <span class="text-gray-700 dark:text-gray-300">{{ g.player_count }}</span>
                <span class="text-xs text-gray-400"> / {{ g.max_players }}</span>
              </td>

              <!-- Progreso -->
              <td class="px-4 py-3 hidden md:table-cell">
                <div class="flex items-center gap-2">
                  <div class="w-20 h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full">
                    <div
                      class="h-full bg-indigo-500 rounded-full transition-all"
                      :style="{ width: progressPct(g) + '%' }"
                    />
                  </div>
                  <span class="text-xs text-gray-500">{{ g.current_question_index }}/{{ g.total_questions }}</span>
                </div>
              </td>

              <!-- Ganador -->
              <td class="px-4 py-3 hidden lg:table-cell">
                <template v-if="g.winner_name">
                  <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate max-w-[120px]">
                    🏆 {{ g.winner_name }}
                  </p>
                  <p v-if="g.winner_score" class="text-xs text-yellow-600">{{ g.winner_score.toLocaleString() }} pts</p>
                </template>
                <span v-else class="text-xs text-gray-400">—</span>
              </td>

              <!-- Inicio -->
              <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500 hidden lg:table-cell">
                {{ formatDate(g.started_at) }}
              </td>

              <!-- Duración -->
              <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500 hidden xl:table-cell">
                {{ g.duration_min !== null ? g.duration_min + ' min' : '—' }}
              </td>

              <!-- Acción -->
              <td class="px-4 py-3 whitespace-nowrap text-right">
                <Link
                  :href="getRoute('gamesShow', { id: g.id })"
                  class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 text-sm font-medium"
                >
                  Detalle
                </Link>
              </td>
            </tr>

            <tr v-if="games.data.length === 0">
              <td colspan="9" class="px-6 py-14 text-center text-gray-400 dark:text-gray-500">
                <p class="text-4xl mb-2">🎮</p>
                <p class="text-sm">No se encontraron partidas con los filtros aplicados.</p>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Paginación -->
        <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
          <p class="text-sm text-gray-500">
            {{ games.from ?? 0 }} – {{ games.to ?? 0 }} de {{ games.total }} partidas
          </p>
          <div class="flex gap-1">
            <template v-for="link in games.links" :key="link.label">
              <Link
                v-if="link.url"
                :href="link.url"
                :class="[
                  'px-3 py-1 rounded text-sm border transition',
                  link.active
                    ? 'bg-indigo-600 text-white border-indigo-600'
                    : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
                ]"
                v-html="link.label"
              />
              <span
                v-else
                class="px-3 py-1 rounded text-sm text-gray-300 dark:text-gray-600 cursor-not-allowed"
                v-html="link.label"
              />
            </template>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>
