<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import debounce from 'lodash/debounce'
import { getRoute } from '@js/routes/triviaRoutes'

interface Room {
  id: string
  code: string
  title: string
  description: string | null
  category: string | null
  status: 'waiting' | 'playing'
  game_mode: 'free_for_all' | 'teams' | 'duel'
  max_players: number
  current_players: number
  question_count: number
  time_per_question: number
  created_at: string
  entry_fee: string | number
  entry_currency_id: string | null
  entry_currency_symbol: string | null
  prize_amount: string | number
  prize_type: string | null
  prize_currency_id: string | null
  prize_currency_symbol: string | null
}

interface Stats {
  waiting: number
  playing: number
  total: number
}

const props = defineProps<{
  rooms: Room[]
  categories: string[]
  stats: Stats
  filters: { search: string; category: string }
}>()

const search   = ref(props.filters.search   ?? '')
const category = ref(props.filters.category ?? '')

function applyFilters() {
  router.get(
    getRoute('lobby'),
    { search: search.value, category: category.value },
    { preserveState: true, replace: true }
  )
}
const debouncedSearch = debounce(applyFilters, 300)

function selectCategory(cat: string) {
  category.value = category.value === cat ? '' : cat
  applyFilters()
}

// ── Helpers ───────────────────────────────────────────────────────────────────

const STATUS_LABEL: Record<string, string> = {
  waiting: 'En espera',
  playing: 'En juego',
}
const STATUS_COLOR: Record<string, string> = {
  waiting: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
  playing: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
}
const STATUS_DOT: Record<string, string> = {
  waiting: 'bg-emerald-400',
  playing: 'bg-blue-400 animate-pulse',
}

const GAME_MODE_LABEL: Record<string, string> = {
  free_for_all: 'Todos vs Todos',
  teams: 'Equipos',
  duel: 'Duelo',
}
const GAME_MODE_ICON: Record<string, string> = {
  free_for_all: '👤',
  teams: '👥',
  duel: '⚔️',
}
const GAME_MODE_COLOR: Record<string, string> = {
  free_for_all: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
  teams: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
  duel: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
}

const CATEGORY_ICONS: Record<string, string> = {
  'Ciencias':    '🔬',
  'Historia':    '📜',
  'Deportes':    '⚽',
  'Geografía':   '🌍',
  'Arte':        '🎨',
  'Música':      '🎵',
  'Tecnología':  '💻',
  'Cine':        '🎬',
  'Literatura':  '📚',
  'Naturaleza':  '🌿',
  'Matemáticas': '🔢',
  'Cultura':     '🏛️',
}

function categoryIcon(cat: string | null): string {
  if (!cat) return '🎯'
  return CATEGORY_ICONS[cat] ?? '🏷️'
}

function fillPct(room: Room): number {
  if (!room.max_players) return 0
  return Math.min(100, Math.round((room.current_players / room.max_players) * 100))
}

function fillColor(pct: number): string {
  if (pct >= 90) return 'bg-red-400'
  if (pct >= 60) return 'bg-yellow-400'
  return 'bg-emerald-400'
}

function hasFee(room: Room): boolean {
  return Number(room.entry_fee) > 0
}

function hasPrize(room: Room): boolean {
  return Number(room.prize_amount) > 0
}

function feeLabel(room: Room): string {
  const amount = Number(room.entry_fee)
  if (amount <= 0) return 'Gratis'
  const sym = room.entry_currency_symbol ?? ''
  return `${sym} ${amount % 1 === 0 ? amount.toFixed(0) : amount.toFixed(2)}`
}

function prizeLabel(room: Room): string {
  const amount = Number(room.prize_amount)
  if (amount <= 0) return room.prize_type === 'accumulated' ? 'Premio acumulado' : ''
  const sym = room.prize_currency_symbol ?? ''
  const base = `${sym} ${amount % 1 === 0 ? amount.toFixed(0) : amount.toFixed(2)}`
  return room.prize_type === 'accumulated' ? `${base} (acumulado)` : base
}

// Agrupar por categoría para el grid
const grouped = computed(() => {
  const map: Record<string, Room[]> = {}
  for (const room of props.rooms) {
    const key = room.category ?? 'Sin categoría'
    if (!map[key]) map[key] = []
    map[key].push(room)
  }
  return map
})

const hasFilters = computed(() => search.value !== '' || category.value !== '')

function joinRoom(room: Room) {
  if (room.status === 'playing') return
  router.get(getRoute('roomWait', { code: room.code }))
}
</script>

<template>
  <Head title="Lobby - Trivia" />

  <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50 dark:from-gray-950 dark:via-gray-900 dark:to-indigo-950">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-8">

      <!-- ── Hero header ──────────────────────────────────────────────────── -->
      <div class="text-center space-y-3">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 text-xs font-semibold uppercase tracking-wider">
          <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse" />
          Multijugador en vivo
        </div>
        <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 dark:text-white tracking-tight">
          🎮 Trivia Lobby
        </h1>
        <p class="text-gray-500 dark:text-gray-400 max-w-xl mx-auto text-sm">
          Únete a una sala activa o espera a que comience el juego. Las salas se actualizan en tiempo real.
        </p>
      </div>

      <!-- ── Stats rápidas ────────────────────────────────────────────────── -->
      <div class="grid grid-cols-3 gap-3 max-w-sm mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-3 text-center">
          <p class="text-lg font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">Salas activas</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-3 text-center">
          <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ stats.waiting }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">En espera</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-3 text-center">
          <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ stats.playing }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">En juego</p>
        </div>
      </div>

      <!-- ── Filtros ───────────────────────────────────────────────────────── -->
      <div class="space-y-3">
        <!-- Buscador -->
        <div class="relative max-w-lg mx-auto">
          <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 pointer-events-none">🔍</span>
          <input
            v-model="search"
            type="text"
            placeholder="Buscar sala por nombre o código..."
            class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 text-sm shadow-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
            @input="debouncedSearch"
          />
        </div>

        <!-- Chips de categoría -->
        <div v-if="categories.length > 0" class="flex flex-wrap gap-2 justify-center">
          <button
            v-for="cat in categories"
            :key="cat"
            @click="selectCategory(cat)"
            :class="[
              'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-all border',
              category === cat
                ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm shadow-indigo-200 dark:shadow-indigo-900'
                : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-600 hover:text-indigo-600 dark:hover:text-indigo-400'
            ]"
          >
            <span>{{ categoryIcon(cat) }}</span>
            <span>{{ cat }}</span>
          </button>
        </div>

        <!-- Clear filters -->
        <div v-if="hasFilters" class="text-center">
          <button
            @click="search = ''; category = ''; applyFilters()"
            class="text-xs text-gray-400 dark:text-gray-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
          >
            ✕ Limpiar filtros
          </button>
        </div>
      </div>

      <!-- ── Sin resultados ────────────────────────────────────────────────── -->
      <div v-if="rooms.length === 0" class="py-16 text-center space-y-4">
        <p class="text-5xl">🎮</p>
        <p class="text-gray-500 dark:text-gray-400 font-medium">No hay salas disponibles en este momento.</p>
        <p class="text-sm text-gray-400 dark:text-gray-500">Las nuevas salas aparecen aquí automáticamente. ¡Vuelve pronto!</p>
      </div>

      <!-- ── Grid de salas ─────────────────────────────────────────────────── -->
      <template v-else>

        <!-- Agrupado por categoría cuando no hay filtro activo, lista plana cuando hay búsqueda -->
        <template v-if="hasFilters">
          <!-- Lista plana al filtrar -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <button
              v-for="room in rooms"
              :key="room.id"
              :disabled="room.status === 'playing'"
              class="group text-left w-full rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 overflow-hidden disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:translate-y-0 disabled:hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2"
              @click="joinRoom(room)"
            >
              <div :class="['h-1', room.status === 'playing' ? 'bg-blue-400' : 'bg-emerald-400 group-hover:bg-indigo-500 transition-colors']" />
              <div class="p-5 space-y-4">
                <div class="flex items-start justify-between gap-2">
                  <div class="min-w-0">
                    <p class="font-semibold text-gray-900 dark:text-white text-sm leading-snug truncate">{{ room.title }}</p>
                    <code class="text-xs text-gray-400 dark:text-gray-500 font-mono">{{ room.code }}</code>
                  </div>
                  <span :class="['flex-shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium', STATUS_COLOR[room.status]]">
                    <span :class="['w-1.5 h-1.5 rounded-full', STATUS_DOT[room.status]]" />
                    {{ STATUS_LABEL[room.status] }}
                  </span>
                </div>
                <p v-if="room.description" class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2 leading-relaxed">{{ room.description }}</p>
                <div class="flex flex-wrap gap-3 text-xs text-gray-500 dark:text-gray-400">
                  <span :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-medium', GAME_MODE_COLOR[room.game_mode]]">
                    {{ GAME_MODE_ICON[room.game_mode] }} {{ GAME_MODE_LABEL[room.game_mode] }}
                  </span>
                  <span>❓ {{ room.question_count }} preguntas</span>
                  <span>⏱️ {{ room.time_per_question }}s / pregunta</span>
                  <span v-if="room.category" class="text-indigo-600 dark:text-indigo-400">{{ categoryIcon(room.category) }} {{ room.category }}</span>
                  <span v-if="hasPrize(room)" class="text-yellow-600 dark:text-yellow-400 font-medium">🏆 {{ prizeLabel(room) }}</span>
                  <span
                    v-if="hasFee(room)"
                    class="font-semibold text-violet-700 dark:text-violet-400 bg-violet-50 dark:bg-violet-900/20 px-2 py-0.5 rounded-full"
                  >💳 {{ feeLabel(room) }}</span>
                  <span v-else class="text-emerald-700 dark:text-emerald-400 font-medium">✅ Gratis</span>
                </div>
                <div class="space-y-1">
                  <div class="flex items-center justify-between text-xs">
                    <span class="text-gray-500 dark:text-gray-400">Jugadores</span>
                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ room.current_players }} / {{ room.max_players }}</span>
                  </div>
                  <div class="h-1.5 w-full bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div :class="['h-full rounded-full transition-all', fillColor(fillPct(room))]" :style="{ width: fillPct(room) + '%' }" />
                  </div>
                </div>
                <div class="pt-1">
                  <div v-if="room.status === 'waiting'" class="w-full py-2 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 text-xs font-semibold text-center group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                    Unirse a la sala →
                  </div>
                  <div v-else class="w-full py-2 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-400 dark:text-gray-500 text-xs font-medium text-center">
                    Partida en curso
                  </div>
                </div>
              </div>
            </button>
          </div>
        </template>

        <template v-else>
          <!-- Agrupado por categoría -->
          <div
            v-for="(groupRooms, groupName) in grouped"
            :key="groupName"
            class="space-y-3"
          >
            <div class="flex items-center gap-2">
              <span class="text-xl">{{ categoryIcon(groupName === 'Sin categoría' ? null : groupName) }}</span>
              <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ groupName }}</h2>
              <span class="text-xs bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 px-2 py-0.5 rounded-full">{{ groupRooms.length }}</span>
              <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700" />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <button
                v-for="room in groupRooms"
                :key="room.id"
                :disabled="room.status === 'playing'"
                class="group text-left w-full rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 overflow-hidden disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:translate-y-0 disabled:hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2"
                @click="joinRoom(room)"
              >
                <!-- Card top accent -->
                <div :class="['h-1', room.status === 'playing' ? 'bg-blue-400' : 'bg-emerald-400 group-hover:bg-indigo-500 transition-colors']" />

                <div class="p-5 space-y-4">
                  <!-- Header -->
                  <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                      <p class="font-semibold text-gray-900 dark:text-white text-sm leading-snug truncate">{{ room.title }}</p>
                      <code class="text-xs text-gray-400 dark:text-gray-500 font-mono">{{ room.code }}</code>
                    </div>
                    <span :class="['flex-shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium', STATUS_COLOR[room.status]]">
                      <span :class="['w-1.5 h-1.5 rounded-full', STATUS_DOT[room.status]]" />
                      {{ STATUS_LABEL[room.status] }}
                    </span>
                  </div>

                  <!-- Descripción -->
                  <p v-if="room.description" class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2 leading-relaxed">
                    {{ room.description }}
                  </p>

                  <!-- Meta info -->
                  <div class="flex flex-wrap gap-3 text-xs text-gray-500 dark:text-gray-400">
                    <span :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-medium', GAME_MODE_COLOR[room.game_mode]]">
                      {{ GAME_MODE_ICON[room.game_mode] }} {{ GAME_MODE_LABEL[room.game_mode] }}
                    </span>
                    <span class="flex items-center gap-1">❓ {{ room.question_count }} preguntas</span>
                    <span class="flex items-center gap-1">⏱️ {{ room.time_per_question }}s / pregunta</span>
                    <span v-if="hasPrize(room)" class="flex items-center gap-1 text-yellow-600 dark:text-yellow-400 font-medium">
                      🏆 {{ prizeLabel(room) }}
                    </span>
                    <span
                      v-if="hasFee(room)"
                      class="flex items-center gap-1 font-semibold text-violet-700 dark:text-violet-400 bg-violet-50 dark:bg-violet-900/20 px-2 py-0.5 rounded-full"
                    >
                      💳 {{ feeLabel(room) }}
                    </span>
                    <span
                      v-else
                      class="flex items-center gap-1 text-emerald-700 dark:text-emerald-400 font-medium"
                    >
                      ✅ Gratis
                    </span>
                  </div>

                  <!-- Barra de jugadores -->
                  <div class="space-y-1">
                    <div class="flex items-center justify-between text-xs">
                      <span class="text-gray-500 dark:text-gray-400">Jugadores</span>
                      <span class="font-medium text-gray-700 dark:text-gray-300">
                        {{ room.current_players }} / {{ room.max_players }}
                      </span>
                    </div>
                    <div class="h-1.5 w-full bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                      <div
                        :class="['h-full rounded-full transition-all', fillColor(fillPct(room))]"
                        :style="{ width: fillPct(room) + '%' }"
                      />
                    </div>
                  </div>

                  <!-- CTA -->
                  <div class="pt-1">
                    <div
                      v-if="room.status === 'waiting'"
                      class="w-full py-2 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 text-xs font-semibold text-center group-hover:bg-indigo-600 group-hover:text-white transition-colors"
                    >
                      Unirse a la sala →
                    </div>
                    <div
                      v-else
                      class="w-full py-2 rounded-xl bg-gray-50 dark:bg-gray-700 text-gray-400 dark:text-gray-500 text-xs font-medium text-center"
                    >
                      Partida en curso
                    </div>
                  </div>
                </div>
              </button>
            </div>
          </div>
        </template>

      </template>

    </div>
  </div>
</template>
