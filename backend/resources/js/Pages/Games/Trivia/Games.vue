<script setup lang="ts">
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import debounce from 'lodash/debounce'
import { getRoute } from '@js/routes/triviaRoutes'
import BaseTable from '@/js/Components/Common/Table/BaseTable.vue'
import type { Column } from '@/js/types/common/table/column-row'

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
  waiting:   'gp-badge-neutral',
}

function formatDate(d: string | null): string {
  if (!d) return '—'
  return new Date(d).toLocaleString('es-ES', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })
}

function progressPct(currentIndex: number, totalQuestions: number): number {
  if (!totalQuestions) return 0
  return Math.round((currentIndex / totalQuestions) * 100)
}

// ── Tabla como BaseTable ──────────────────────────────────────────────────────
// Solo se reemplaza el renderer de la tabla; paginación (links de Laravel) y
// filtros (fecha/estado/búsqueda vía query params al servidor) se mantienen
// como están — no encajan en el contrato server-side de TableWithFilterBridge
// (Pagination con total/last_page, filtros por columna vs. rango de fechas).

const tableColumns: Column[] = [
  { key: 'game_code', label: 'Partida' },
  { key: 'room_title', label: 'Sala' },
  { key: 'status', label: 'Estado' },
  { key: 'player_count', label: 'Jugadores', hideBelow: 'sm' },
  { key: 'progress', label: 'Progreso', hideBelow: 'md' },
  { key: 'winner_name', label: 'Ganador', hideBelow: 'lg' },
  { key: 'started_at', label: 'Inicio', hideBelow: 'lg' },
  { key: 'duration_min', label: 'Duración', hideBelow: 'xl' },
  { key: 'actions', label: 'Ver' },
]
</script>

<template>
  <Head title="Partidas - Trivia" />

  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold gp-text">🎮 Partidas de Trivia</h1>
          <p class="text-sm gp-text-muted mt-1">Historial y estado de todas las partidas</p>
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
          class="gp-card rounded-xl shadow-sm p-4 flex items-center gap-3"
        >
          <span class="text-2xl">{{ item.icon }}</span>
          <div>
            <p class="text-xs gp-text-muted uppercase">{{ item.label }}</p>
            <p class="text-xl font-bold gp-text">{{ item.value.toLocaleString('es-ES') }}</p>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="gp-card shadow-sm rounded-xl p-4">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
          <input
            v-model="search"
            type="text"
            placeholder="Código sala/partida..."
            class="gp-input rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500"
            @input="debouncedSearch"
          />
          <select
            v-model="status"
            class="gp-input rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500"
            @change="applyFilters"
          >
            <option value="">Todos los estados</option>
            <option v-for="(label, val) in STATUS_LABEL" :key="val" :value="val">{{ label }}</option>
          </select>
          <input
            v-model="dateFrom"
            type="date"
            class="gp-input rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500"
            @change="applyFilters"
          />
          <input
            v-model="dateTo"
            type="date"
            class="gp-input rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500"
            @change="applyFilters"
          />
        </div>
      </div>

      <!-- Tabla -->
      <div class="gp-card rounded-xl shadow-sm overflow-hidden">
        <BaseTable
          table-id="trivia-games-table"
          class="gp-games-table"
          :columns="tableColumns"
          :rows="games.data"
          empty-message="No se encontraron partidas con los filtros aplicados."
        >
          <template #cell-game_code="{ row: g }">
            <div class="flex items-center gap-2">
              <span class="text-lg">🎲</span>
              <div>
                <p class="font-mono text-xs font-semibold gp-text-secondary mb-0">{{ g.game_code }}</p>
                <p class="text-xs text-gray-400 d-none d-sm-block mb-0">{{ g.time_per_question }}s / pregunta</p>
              </div>
            </div>
          </template>

          <template #cell-room_title="{ row: g }">
            <p class="text-sm font-medium gp-text truncate max-w-[140px] mb-0">{{ g.room_title }}</p>
            <code class="text-xs text-gray-400">{{ g.room_code }}</code>
          </template>

          <template #cell-status="{ row: g }">
            <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', STATUS_COLOR[g.status] ?? STATUS_COLOR.waiting]">
              {{ STATUS_LABEL[g.status] ?? g.status }}
            </span>
          </template>

          <template #cell-player_count="{ row: g }">
            <span class="gp-text-secondary">{{ g.player_count }}</span>
            <span class="text-xs text-gray-400"> / {{ g.max_players }}</span>
          </template>

          <template #cell-progress="{ row: g }">
            <div class="flex items-center gap-2">
              <div class="w-20 h-1.5 gp-track rounded-full">
                <div
                  class="h-full bg-indigo-500 rounded-full transition-all"
                  :style="{ width: progressPct(g.current_question_index, g.total_questions) + '%' }"
                />
              </div>
              <span class="text-xs text-gray-500">{{ g.current_question_index }}/{{ g.total_questions }}</span>
            </div>
          </template>

          <template #cell-winner_name="{ row: g }">
            <template v-if="g.winner_name">
              <p class="text-sm font-medium gp-text-secondary truncate max-w-[120px] mb-0">
                🏆 {{ g.winner_name }}
              </p>
              <p v-if="g.winner_score" class="text-xs text-yellow-600 mb-0">{{ g.winner_score.toLocaleString() }} pts</p>
            </template>
            <span v-else class="text-xs text-gray-400">—</span>
          </template>

          <template #cell-started_at="{ row: g }">
            <span class="text-xs text-gray-500">{{ formatDate(g.started_at) }}</span>
          </template>

          <template #cell-duration_min="{ row: g }">
            <span class="text-xs text-gray-500">{{ g.duration_min !== null ? g.duration_min + ' min' : '—' }}</span>
          </template>

          <template #cell-actions="{ row: g }">
            <Link
              :href="getRoute('gamesShow', { id: g.id })"
              class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 text-sm font-medium"
            >
              Detalle
            </Link>
          </template>
        </BaseTable>

        <!-- Paginación -->
        <div class="gp-pagination px-6 py-3 border-t flex items-center justify-between">
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
                    : 'gp-page-link'
                ]"
                v-html="link.label"
              />
              <span
                v-else
                class="gp-page-link-disabled px-3 py-1 rounded text-sm cursor-not-allowed"
                v-html="link.label"
              />
            </template>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.gp-text { color: var(--ds-text); }
.gp-text-secondary { color: var(--ds-text-2); }
.gp-text-muted { color: var(--ds-text-muted); }
.gp-text-faint { color: var(--ds-text-muted); }
.gp-card { background: var(--ds-surface); }
.gp-input { border: 1px solid var(--ds-border); background: var(--ds-surface); color: var(--ds-text-2); }
.gp-thead { background: var(--ds-surface-2); }
.gp-divide { border-color: var(--ds-border); }
.gp-divide > :not([hidden]) ~ :not([hidden]) { border-top: 1px solid var(--ds-border); }
.gp-row-hover:hover { background: var(--ds-surface-2); }
.gp-track { background: var(--ds-surface-2); }
.gp-pagination { background: var(--ds-surface-2); border-color: var(--ds-border); }
.gp-page-link { background: var(--ds-surface); color: var(--ds-text-2); border-color: var(--ds-border); }
.gp-page-link:hover { background: var(--ds-surface-2); }
.gp-page-link-disabled { color: var(--ds-text-muted); }
.gp-badge-neutral { background: var(--ds-surface-2); color: var(--ds-text-2); }

/* Adapta el look de BaseTable (Bootstrap) al estilo Tailwind del resto de
   esta página — mismo criterio que .gs-scoreboard-table en GameShow.vue.
   A diferencia de esa tabla, aquí el layout original alinea todo a la
   izquierda salvo la última columna ("Ver"). */
.gp-games-table :deep(table) {
  --bs-table-striped-bg: transparent;
  border-collapse: collapse;
}
.gp-games-table :deep(thead th) {
  background: var(--ds-surface-2);
  border: none;
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  color: var(--ds-text-muted);
  padding: 0.75rem 1rem;
  text-align: left;
}
.gp-games-table :deep(thead th:last-child) {
  text-align: right;
}
.gp-games-table :deep(tbody td) {
  border: none;
  border-top: 1px solid var(--ds-border);
  padding: 0.75rem 1rem;
  vertical-align: middle;
}
.gp-games-table :deep(tbody td:last-child) {
  text-align: right;
}
.gp-games-table :deep(tbody tr:hover) {
  background: var(--ds-surface-2);
}
</style>
