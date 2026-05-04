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

interface Stats { waiting: number; playing: number; total: number }

const props = defineProps<{
  rooms: Room[]
  categories: string[]
  stats: Stats
  filters: { search: string; category: string }
}>()

const search   = ref(props.filters.search   ?? '')
const category = ref(props.filters.category ?? '')

function applyFilters() {
  router.get(getRoute('lobby'), { search: search.value, category: category.value }, { preserveState: true, replace: true })
}
const debouncedSearch = debounce(applyFilters, 300)

function selectCategory(cat: string) {
  category.value = category.value === cat ? '' : cat
  applyFilters()
}

// ── Categorías ────────────────────────────────────────────────────────────────
const CATEGORY_ICONS: Record<string, string> = {
  'Ciencias': '🔬', 'Historia': '📜', 'Deportes': '⚽', 'Geografía': '🌍',
  'Arte': '🎨', 'Música': '🎵', 'Tecnología': '💻', 'Cine': '🎬',
  'Literatura': '📚', 'Naturaleza': '🌿', 'Matemáticas': '🔢', 'Cultura': '🏛️',
}
function categoryIcon(cat: string | null): string {
  if (!cat) return '🎯'
  return CATEGORY_ICONS[cat] ?? '🏷️'
}

// ── Modos de juego ────────────────────────────────────────────────────────────
const MODE: Record<string, { label: string; icon: string; gradient: string; text: string }> = {
  free_for_all: { label: 'Todos vs Todos', icon: '👤', gradient: 'from-slate-500 to-slate-600',   text: 'text-slate-600 dark:text-slate-400'   },
  teams:        { label: 'Equipos',        icon: '👥', gradient: 'from-indigo-500 to-violet-600', text: 'text-indigo-600 dark:text-indigo-400' },
  duel:         { label: 'Duelo',          icon: '⚔️', gradient: 'from-rose-500 to-red-600',      text: 'text-rose-600 dark:text-rose-400'     },
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function fillPct(room: Room): number {
  if (!room.max_players) return 0
  return Math.min(100, Math.round((room.current_players / room.max_players) * 100))
}

function hasFee(room: Room): boolean   { return Number(room.entry_fee) > 0 }
function hasPrize(room: Room): boolean { return Number(room.prize_amount) > 0 }

function feeLabel(room: Room): string {
  const n = Number(room.entry_fee)
  if (n <= 0) return 'Gratis'
  const sym = room.entry_currency_symbol ?? ''
  return `${sym} ${n % 1 === 0 ? n.toFixed(0) : n.toFixed(2)}`
}

function prizeLabel(room: Room): string {
  const n = Number(room.prize_amount)
  const sym = room.prize_currency_symbol ?? ''
  if (n <= 0) return room.prize_type === 'accumulated' ? 'Bote acumulado' : '—'
  const base = `${sym} ${n % 1 === 0 ? n.toFixed(0) : n.toFixed(2)}`
  return room.prize_type === 'accumulated' ? `${base}` : base
}

// Gradiente de fondo de card según categoría (ciclo)
const CARD_GRADIENTS = [
  ['from-violet-500', 'to-purple-600'],
  ['from-indigo-500', 'to-blue-600'],
  ['from-emerald-500', 'to-teal-600'],
  ['from-rose-500',   'to-pink-600'],
  ['from-amber-500',  'to-orange-600'],
  ['from-cyan-500',   'to-sky-600'],
]
function cardGradient(index: number): string {
  const [from, to] = CARD_GRADIENTS[index % CARD_GRADIENTS.length]
  return `${from} ${to}`
}

const grouped = computed(() => {
  const map: Record<string, Room[]> = {}
  for (const room of props.rooms) {
    const key = room.category ?? 'Sin categoría'
    if (!map[key]) map[key] = []
    map[key].push(room)
  }
  return map
})

const hasFilters  = computed(() => search.value !== '' || category.value !== '')
const flatRooms   = computed(() => props.rooms)

function joinRoom(room: Room) {
  if (room.status === 'playing') return
  router.get(getRoute('roomWait', { code: room.code }))
}
</script>

<template>
  <Head title="Lobby · Trivia" />

  <!-- Fondo oscuro premium -->
  <div class="min-h-screen bg-[#0d0f1a] text-white">

    <!-- ── Partículas decorativas (SVG) ──────────────────────────────────────── -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none select-none" aria-hidden="true">
      <div class="absolute -top-40 -left-40 w-96 h-96 rounded-full bg-violet-600/20 blur-3xl" />
      <div class="absolute top-1/2 -right-32 w-80 h-80 rounded-full bg-indigo-500/15 blur-3xl" />
      <div class="absolute bottom-0 left-1/3 w-72 h-72 rounded-full bg-cyan-500/10 blur-3xl" />
    </div>

    <div class="relative max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8 space-y-10">

      <!-- ── Hero ─────────────────────────────────────────────────────────────── -->
      <div class="text-center space-y-4">
        <!-- Badge live -->
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-violet-500/20 border border-violet-500/30 text-violet-300 text-xs font-semibold uppercase tracking-widest">
          <span class="w-1.5 h-1.5 rounded-full bg-violet-400 animate-pulse" />
          Multijugador en vivo
        </div>

        <h1 class="text-4xl sm:text-5xl font-black tracking-tight">
          <span class="bg-gradient-to-r from-violet-400 via-indigo-300 to-cyan-400 bg-clip-text text-transparent">
            Trivia
          </span>
          <span class="text-white"> Lobby</span>
        </h1>
        <p class="text-gray-400 max-w-md mx-auto text-sm leading-relaxed">
          Compite en tiempo real, demuestra tu conocimiento y gana premios.
        </p>
      </div>

      <!-- ── Stats row ─────────────────────────────────────────────────────────── -->
      <div class="grid grid-cols-3 gap-3 max-w-sm mx-auto">
        <div class="rounded-2xl bg-white/5 border border-white/10 p-4 text-center backdrop-blur-sm">
          <p class="text-2xl font-black text-white">{{ stats.total }}</p>
          <p class="text-[11px] text-gray-400 mt-0.5 uppercase tracking-wide">Salas</p>
        </div>
        <div class="rounded-2xl bg-emerald-500/10 border border-emerald-500/20 p-4 text-center backdrop-blur-sm">
          <p class="text-2xl font-black text-emerald-400">{{ stats.waiting }}</p>
          <p class="text-[11px] text-emerald-500/80 mt-0.5 uppercase tracking-wide">En espera</p>
        </div>
        <div class="rounded-2xl bg-blue-500/10 border border-blue-500/20 p-4 text-center backdrop-blur-sm">
          <p class="text-2xl font-black text-blue-400">{{ stats.playing }}</p>
          <p class="text-[11px] text-blue-500/80 mt-0.5 uppercase tracking-wide">Jugando</p>
        </div>
      </div>

      <!-- ── Filtros ────────────────────────────────────────────────────────────── -->
      <div class="space-y-4">
        <!-- Buscador -->
        <div class="relative max-w-lg mx-auto">
          <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
          <input
            v-model="search"
            type="text"
            placeholder="Buscar sala por nombre o código..."
            class="w-full pl-10 pr-4 py-3 rounded-2xl bg-white/5 border border-white/10 text-white placeholder-gray-500 text-sm focus:outline-none focus:border-violet-500/60 focus:ring-1 focus:ring-violet-500/40 transition-all"
            @input="debouncedSearch"
          />
        </div>

        <!-- Chips de categoría -->
        <div v-if="categories.length" class="flex flex-wrap gap-2 justify-center">
          <button
            v-for="cat in categories"
            :key="cat"
            @click="selectCategory(cat)"
            :class="[
              'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium transition-all border',
              category === cat
                ? 'bg-violet-600 border-violet-500 text-white shadow-lg shadow-violet-500/30'
                : 'bg-white/5 border-white/10 text-gray-400 hover:border-violet-500/40 hover:text-violet-300'
            ]"
          >
            {{ categoryIcon(cat) }} {{ cat }}
          </button>
        </div>

        <div v-if="hasFilters" class="text-center">
          <button
            @click="search = ''; category = ''; applyFilters()"
            class="text-xs text-gray-500 hover:text-violet-400 transition-colors"
          >
            ✕ Limpiar filtros
          </button>
        </div>
      </div>

      <!-- ── Sin resultados ─────────────────────────────────────────────────────── -->
      <div v-if="rooms.length === 0" class="py-20 text-center space-y-4">
        <div class="w-20 h-20 mx-auto rounded-3xl bg-white/5 border border-white/10 flex items-center justify-center text-4xl">🎮</div>
        <p class="text-gray-300 font-semibold">No hay salas disponibles</p>
        <p class="text-sm text-gray-500">Las nuevas salas aparecen aquí automáticamente.</p>
      </div>

      <!-- ── Grid de salas ──────────────────────────────────────────────────────── -->
      <template v-else>

        <!-- Lista plana al filtrar -->
        <template v-if="hasFilters">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <button
              v-for="(room, i) in flatRooms"
              :key="room.id"
              :disabled="room.status === 'playing'"
              class="group text-left w-full rounded-2xl overflow-hidden transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:ring-offset-2 focus:ring-offset-[#0d0f1a] disabled:cursor-not-allowed"
              :class="room.status === 'playing' ? 'opacity-70' : 'hover:-translate-y-1 hover:shadow-2xl'"
              @click="joinRoom(room)"
            >
              <div class="relative h-full rounded-2xl border overflow-hidden"
                :class="room.status === 'playing' ? 'border-blue-500/20 bg-blue-950/20' : 'border-white/10 bg-white/[0.03] group-hover:border-violet-500/30 group-hover:bg-white/[0.06]'">
                <div :class="['h-1 w-full bg-gradient-to-r', cardGradient(i)]" />
                <div class="p-5 space-y-4">
                  <div v-if="hasPrize(room)" class="flex items-center justify-between rounded-xl px-3 py-2 bg-gradient-to-r from-amber-500/15 to-yellow-500/10 border border-amber-500/20">
                    <div class="flex items-center gap-1.5">
                      <span class="text-base">🏆</span>
                      <span class="text-[11px] font-medium text-amber-400/80 uppercase tracking-wide">{{ room.prize_type === 'accumulated' ? 'Bote acumulado' : 'Premio' }}</span>
                    </div>
                    <span class="text-base font-black text-amber-300 tabular-nums">{{ prizeLabel(room) }}</span>
                  </div>
                  <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                      <p class="font-bold text-white text-sm leading-snug line-clamp-1">{{ room.title }}</p>
                      <div class="flex items-center gap-1.5 mt-0.5">
                        <code class="text-[11px] text-gray-500 font-mono">{{ room.code }}</code>
                        <span v-if="room.category" class="text-[11px] text-gray-400">· {{ categoryIcon(room.category) }} {{ room.category }}</span>
                      </div>
                    </div>
                    <span class="flex-shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold"
                      :class="room.status === 'playing' ? 'bg-blue-500/20 text-blue-400 border border-blue-500/30' : 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30'">
                      <span class="w-1.5 h-1.5 rounded-full" :class="room.status === 'playing' ? 'bg-blue-400 animate-pulse' : 'bg-emerald-400 animate-pulse'" />
                      {{ room.status === 'playing' ? 'En juego' : 'En espera' }}
                    </span>
                  </div>
                  <p v-if="room.description" class="text-[12px] text-gray-400 line-clamp-2 leading-relaxed -mt-1">{{ room.description }}</p>
                  <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                      <span class="text-gray-400 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Jugadores inscritos
                      </span>
                      <span class="font-bold text-white tabular-nums">{{ room.current_players }}<span class="font-normal text-gray-500">/{{ room.max_players }}</span></span>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-white/10 overflow-hidden">
                      <div class="h-full rounded-full transition-all duration-500"
                        :class="fillPct(room) >= 90 ? 'bg-red-400' : fillPct(room) >= 60 ? 'bg-amber-400' : 'bg-gradient-to-r from-violet-400 to-cyan-400'"
                        :style="{ width: fillPct(room) + '%' }" />
                    </div>
                  </div>
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-white/10 text-gray-300">{{ MODE[room.game_mode].icon }} {{ MODE[room.game_mode].label }}</span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] bg-white/5 text-gray-400">❓ {{ room.question_count }} pregs</span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] bg-white/5 text-gray-400">⏱ {{ room.time_per_question }}s</span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold ml-auto"
                      :class="hasFee(room) ? 'bg-violet-500/20 text-violet-300 border border-violet-500/30' : 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/25'">
                      {{ hasFee(room) ? '💳 ' + feeLabel(room) : '✅ Gratis' }}
                    </span>
                  </div>
                  <div v-if="room.status === 'waiting'" class="w-full py-2.5 rounded-xl text-xs font-bold text-center transition-all duration-200 bg-gradient-to-r text-white shadow-lg opacity-80 group-hover:opacity-100" :class="cardGradient(i)">
                    Unirse ahora →
                  </div>
                  <div v-else class="w-full py-2.5 rounded-xl text-xs font-medium text-center bg-blue-500/10 text-blue-400 border border-blue-500/20">
                    Partida en curso — observar
                  </div>
                </div>
              </div>
            </button>
          </div>
        </template>

        <!-- Agrupado por categoría -->
        <template v-else>
          <div v-for="(groupRooms, groupName) in grouped" :key="groupName" class="space-y-4">
            <!-- Encabezado de grupo -->
            <div class="flex items-center gap-3">
              <span class="text-2xl">{{ categoryIcon(groupName === 'Sin categoría' ? null : String(groupName)) }}</span>
              <h2 class="text-sm font-bold text-white uppercase tracking-widest">{{ groupName }}</h2>
              <span class="px-2 py-0.5 rounded-full bg-white/10 text-gray-400 text-xs font-medium">{{ groupRooms.length }}</span>
              <div class="flex-1 h-px bg-white/5" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
              <button
                v-for="(room, i) in groupRooms"
                :key="room.id"
                :disabled="room.status === 'playing'"
                class="group text-left w-full rounded-2xl overflow-hidden transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:ring-offset-2 focus:ring-offset-[#0d0f1a] disabled:cursor-not-allowed"
                :class="room.status === 'playing' ? 'opacity-70' : 'hover:-translate-y-1 hover:shadow-2xl'"
                @click="joinRoom(room)"
              >
                <!-- Tarjeta -->
                <div class="relative h-full rounded-2xl border overflow-hidden"
                  :class="room.status === 'playing' ? 'border-blue-500/20 bg-blue-950/20' : 'border-white/10 bg-white/[0.03] group-hover:border-violet-500/30 group-hover:bg-white/[0.06]'">

                  <!-- Franja de color superior (gradiente por categoría) -->
                  <div :class="['h-1 w-full bg-gradient-to-r', cardGradient(i)]" />

                  <div class="p-5 space-y-4">

                    <!-- ── Bote / Premio ─────────────────────────────────────── -->
                    <div v-if="hasPrize(room)"
                      class="flex items-center justify-between rounded-xl px-3 py-2 bg-gradient-to-r from-amber-500/15 to-yellow-500/10 border border-amber-500/20">
                      <div class="flex items-center gap-1.5">
                        <span class="text-base">🏆</span>
                        <span class="text-[11px] font-medium text-amber-400/80 uppercase tracking-wide">
                          {{ room.prize_type === 'accumulated' ? 'Bote acumulado' : 'Premio' }}
                        </span>
                      </div>
                      <span class="text-base font-black text-amber-300 tabular-nums">
                        {{ prizeLabel(room) }}
                      </span>
                    </div>

                    <!-- ── Header: título + status ─────────────────────────── -->
                    <div class="flex items-start justify-between gap-2">
                      <div class="min-w-0">
                        <p class="font-bold text-white text-sm leading-snug line-clamp-1">{{ room.title }}</p>
                        <div class="flex items-center gap-1.5 mt-0.5">
                          <code class="text-[11px] text-gray-500 font-mono">{{ room.code }}</code>
                          <span v-if="room.category" class="text-[11px] text-gray-500">·</span>
                          <span v-if="room.category" class="text-[11px] text-gray-400">{{ categoryIcon(room.category) }} {{ room.category }}</span>
                        </div>
                      </div>
                      <!-- Status badge -->
                      <span
                        class="flex-shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold"
                        :class="room.status === 'playing'
                          ? 'bg-blue-500/20 text-blue-400 border border-blue-500/30'
                          : 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30'"
                      >
                        <span class="w-1.5 h-1.5 rounded-full"
                          :class="room.status === 'playing' ? 'bg-blue-400 animate-pulse' : 'bg-emerald-400 animate-pulse'" />
                        {{ room.status === 'playing' ? 'En juego' : 'En espera' }}
                      </span>
                    </div>

                    <!-- Descripción -->
                    <p v-if="room.description" class="text-[12px] text-gray-400 line-clamp-2 leading-relaxed -mt-1">
                      {{ room.description }}
                    </p>

                    <!-- ── Jugadores inscritos ──────────────────────────────── -->
                    <div class="space-y-1.5">
                      <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-1.5 text-gray-400">
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                          </svg>
                          <span>Jugadores inscritos</span>
                        </div>
                        <span class="font-bold text-white tabular-nums">
                          {{ room.current_players }}<span class="font-normal text-gray-500">/{{ room.max_players }}</span>
                        </span>
                      </div>
                      <!-- Barra de progreso -->
                      <div class="h-1.5 w-full rounded-full bg-white/10 overflow-hidden">
                        <div
                          class="h-full rounded-full transition-all duration-500"
                          :class="fillPct(room) >= 90 ? 'bg-red-400' : fillPct(room) >= 60 ? 'bg-amber-400' : 'bg-gradient-to-r from-violet-400 to-cyan-400'"
                          :style="{ width: fillPct(room) + '%' }"
                        />
                      </div>
                    </div>

                    <!-- ── Stats secundarias ────────────────────────────────── -->
                    <div class="flex items-center gap-2 flex-wrap">
                      <!-- Modo de juego -->
                      <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-white/10 text-gray-300">
                        {{ MODE[room.game_mode].icon }} {{ MODE[room.game_mode].label }}
                      </span>
                      <!-- Preguntas -->
                      <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] bg-white/5 text-gray-400">
                        ❓ {{ room.question_count }} pregs
                      </span>
                      <!-- Tiempo -->
                      <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] bg-white/5 text-gray-400">
                        ⏱ {{ room.time_per_question }}s
                      </span>
                      <!-- Entrada -->
                      <span
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold ml-auto"
                        :class="hasFee(room)
                          ? 'bg-violet-500/20 text-violet-300 border border-violet-500/30'
                          : 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/25'"
                      >
                        {{ hasFee(room) ? '💳 ' + feeLabel(room) : '✅ Gratis' }}
                      </span>
                    </div>

                    <!-- ── CTA ──────────────────────────────────────────────── -->
                    <div
                      v-if="room.status === 'waiting'"
                      class="w-full py-2.5 rounded-xl text-xs font-bold text-center transition-all duration-200"
                      :class="[
                        'bg-gradient-to-r group-hover:opacity-100',
                        cardGradient(i),
                        'text-white shadow-lg opacity-80 group-hover:shadow-xl',
                      ]"
                    >
                      Unirse ahora →
                    </div>
                    <div v-else class="w-full py-2.5 rounded-xl text-xs font-medium text-center bg-blue-500/10 text-blue-400 border border-blue-500/20">
                      Partida en curso — observar
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
