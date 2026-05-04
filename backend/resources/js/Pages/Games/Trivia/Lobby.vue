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
  entry_currency_symbol: string | null
  prize_amount: string | number
  prize_type: string | null
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
function selectCategory(cat: string) { category.value = category.value === cat ? '' : cat; applyFilters() }

// ── Datos ─────────────────────────────────────────────────────────────────────
const CATEGORY_ICONS: Record<string, string> = {
  'Ciencias':'🔬','Historia':'📜','Deportes':'⚽','Geografía':'🌍',
  'Arte':'🎨','Música':'🎵','Tecnología':'💻','Cine':'🎬',
  'Literatura':'📚','Naturaleza':'🌿','Matemáticas':'🔢','Cultura':'🏛️',
}
const MODE_ICON: Record<string, string> = { free_for_all:'👤', teams:'👥', duel:'⚔️' }
const MODE_LABEL: Record<string, string> = { free_for_all:'Todos', teams:'Equipos', duel:'Duelo' }

// Paleta por índice — colores vivos
const THEMES = [
  { top:'from-violet-500 to-purple-500', btn:'from-violet-500 to-purple-500', tag:'bg-violet-100 text-violet-700' },
  { top:'from-blue-500 to-indigo-500',   btn:'from-blue-500 to-indigo-500',   tag:'bg-blue-100 text-blue-700'    },
  { top:'from-emerald-500 to-teal-400',  btn:'from-emerald-500 to-teal-400',  tag:'bg-emerald-100 text-emerald-700'},
  { top:'from-rose-500 to-pink-500',     btn:'from-rose-500 to-pink-500',     tag:'bg-rose-100 text-rose-700'    },
  { top:'from-amber-500 to-orange-400',  btn:'from-amber-500 to-orange-400',  tag:'bg-amber-100 text-amber-700'  },
  { top:'from-cyan-500 to-sky-400',      btn:'from-cyan-500 to-sky-400',      tag:'bg-cyan-100 text-cyan-700'    },
]
const t = (i: number) => THEMES[i % THEMES.length]

// ── Helpers ───────────────────────────────────────────────────────────────────
const catIcon  = (c: string | null) => c ? (CATEGORY_ICONS[c] ?? '🏷️') : '🎯'
const fillPct  = (r: Room) => r.max_players ? Math.min(100, Math.round((r.current_players / r.max_players) * 100)) : 0
const hasFee   = (r: Room) => Number(r.entry_fee) > 0
const hasPrize = (r: Room) => Number(r.prize_amount) > 0

const feeLabel = (r: Room) => {
  const n = Number(r.entry_fee); const s = r.entry_currency_symbol ?? ''
  return n > 0 ? `${s} ${n % 1 === 0 ? n.toFixed(0) : n.toFixed(2)}` : 'Gratis'
}
const prizeLabel = (r: Room) => {
  const n = Number(r.prize_amount); const s = r.prize_currency_symbol ?? ''
  if (n <= 0) return r.prize_type === 'accumulated' ? 'Acumulado' : '—'
  return `${s} ${n % 1 === 0 ? n.toFixed(0) : n.toFixed(2)}`
}
const fillGrad = (pct: number) =>
  pct >= 90 ? 'from-red-400 to-rose-500' : pct >= 60 ? 'from-amber-400 to-orange-400' : 'from-emerald-400 to-teal-400'

const grouped    = computed(() => {
  const m: Record<string, Room[]> = {}
  for (const r of props.rooms) { const k = r.category ?? 'Sin categoría'; (m[k] ??= []).push(r) }
  return m
})
const hasFilters = computed(() => search.value !== '' || category.value !== '')

const joinRoom = (r: Room) => router.get(getRoute('roomWait', { code: r.code }))
</script>

<template>
  <Head title="Lobby · Trivia" />

  <div class="min-h-screen bg-gradient-to-br from-violet-50 via-white to-blue-50">

    <!-- Burbujas de fondo -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
      <div class="blob blob-1" /><div class="blob blob-2" /><div class="blob blob-3" />
    </div>

    <div class="relative max-w-6xl mx-auto py-8 px-4 sm:px-6 space-y-7">

      <!-- ── Hero compacto ──────────────────────────────────────────────────── -->
      <div class="text-center space-y-3">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white border border-violet-200 shadow-sm text-violet-700 text-[11px] font-bold uppercase tracking-widest">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
          En vivo
        </span>
        <h1 class="text-4xl font-black tracking-tight">
          <span class="bg-gradient-to-r from-violet-600 via-indigo-500 to-cyan-500 bg-clip-text text-transparent">🎮 Trivia Lobby</span>
        </h1>
        <p class="text-gray-500 text-sm">Únete, compite y gana premios reales.</p>

        <!-- Stats inline -->
        <div class="flex items-center justify-center gap-6 pt-1">
          <div class="text-center">
            <p class="text-xl font-black text-gray-900">{{ stats.total }}</p>
            <p class="text-[10px] text-gray-400 uppercase tracking-wide">Salas</p>
          </div>
          <div class="w-px h-8 bg-gray-200" />
          <div class="text-center">
            <p class="text-xl font-black text-emerald-600">{{ stats.waiting }}</p>
            <p class="text-[10px] text-emerald-500 uppercase tracking-wide">Esperando</p>
          </div>
          <div class="w-px h-8 bg-gray-200" />
          <div class="text-center">
            <p class="text-xl font-black text-blue-600">{{ stats.playing }}</p>
            <p class="text-[10px] text-blue-500 uppercase tracking-wide">Jugando</p>
          </div>
        </div>
      </div>

      <!-- ── Filtros ─────────────────────────────────────────────────────────── -->
      <div class="space-y-3">
        <div class="relative max-w-md mx-auto">
          <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
          <input v-model="search" type="text" placeholder="Buscar sala..."
            class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-white border border-gray-200 text-gray-800 placeholder-gray-400 text-sm shadow-sm focus:outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-100 transition-all"
            @input="debouncedSearch" />
        </div>

        <div v-if="categories.length" class="flex flex-wrap gap-2 justify-center">
          <button v-for="cat in categories" :key="cat" @click="selectCategory(cat)"
            class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold border transition-all"
            :class="category === cat
              ? 'bg-violet-600 border-violet-600 text-white shadow-md shadow-violet-200'
              : 'bg-white border-gray-200 text-gray-600 hover:border-violet-300 hover:text-violet-700'">
            {{ catIcon(cat) }} {{ cat }}
          </button>
        </div>

        <div v-if="hasFilters" class="text-center">
          <button @click="search = ''; category = ''; applyFilters()" class="text-xs text-gray-400 hover:text-violet-600 transition-colors">
            ✕ Limpiar filtros
          </button>
        </div>
      </div>

      <!-- ── Empty ──────────────────────────────────────────────────────────── -->
      <div v-if="rooms.length === 0" class="py-16 text-center space-y-3">
        <div class="text-5xl animate-bounce mx-auto">🎮</div>
        <p class="font-bold text-gray-600">No hay salas disponibles</p>
        <p class="text-sm text-gray-400">Vuelve pronto, las salas aparecen en tiempo real.</p>
      </div>

      <!-- ── Grid ───────────────────────────────────────────────────────────── -->
      <template v-else>

        <!-- Lista plana al filtrar -->
        <div v-if="hasFilters" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <button v-for="(room, i) in rooms" :key="room.id"
            class="room-card group" @click="joinRoom(room)">
            <div :class="['h-1 w-full bg-gradient-to-r', t(i).top]" />
            <div class="p-4 space-y-3">
              <div v-if="hasPrize(room)"
                class="flex items-center justify-between px-3 py-2 rounded-lg bg-gradient-to-r from-amber-50 to-yellow-50 border border-amber-200">
                <div class="flex items-center gap-2">
                  <span class="text-lg">🏆</span>
                  <div class="leading-tight">
                    <p class="text-[10px] font-bold text-amber-600 uppercase tracking-wide">{{ room.prize_type === 'accumulated' ? 'Bote' : 'Premio' }}</p>
                    <p class="text-base font-black text-amber-700 tabular-nums">{{ prizeLabel(room) }}</p>
                  </div>
                </div>
                <span class="text-lg animate-pulse-slow">✨</span>
              </div>
              <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                  <p class="font-bold text-gray-900 text-sm line-clamp-1 leading-tight">{{ room.title }}</p>
                  <code class="text-[10px] text-gray-400 font-mono">{{ room.code }}</code>
                </div>
                <span class="flex-shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold"
                  :class="room.status === 'playing' ? 'bg-blue-100 text-blue-700 border border-blue-200' : 'bg-emerald-100 text-emerald-700 border border-emerald-200'">
                  <span class="w-1.5 h-1.5 rounded-full animate-pulse" :class="room.status === 'playing' ? 'bg-blue-500' : 'bg-emerald-500'" />
                  {{ room.status === 'playing' ? 'En juego' : 'En espera' }}
                </span>
              </div>
              <div class="space-y-1.5 rounded-lg p-2.5 border border-gray-100 bg-gray-50">
                <div class="flex items-center justify-between">
                  <span class="text-[11px] text-gray-500 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Inscritos
                  </span>
                  <span class="text-xs font-black text-gray-900 tabular-nums">{{ room.current_players }}<span class="text-gray-400 font-normal">/{{ room.max_players }}</span></span>
                </div>
                <div class="h-1.5 w-full rounded-full bg-white border border-gray-100 overflow-hidden">
                  <div class="h-full rounded-full bg-gradient-to-r transition-all duration-700"
                    :class="fillGrad(fillPct(room))" :style="{ width: fillPct(room) + '%' }" />
                </div>
              </div>
              <div class="flex flex-wrap gap-1">
                <span class="pill bg-gray-100 text-gray-600">{{ MODE_ICON[room.game_mode] }} {{ MODE_LABEL[room.game_mode] }}</span>
                <span class="pill bg-indigo-50 text-indigo-600">❓{{ room.question_count }}</span>
                <span class="pill bg-cyan-50 text-cyan-700">⏱{{ room.time_per_question }}s</span>
                <span class="pill ml-auto font-bold" :class="hasFee(room) ? 'bg-violet-100 text-violet-700' : 'bg-emerald-100 text-emerald-700'">
                  {{ hasFee(room) ? '💳 ' + feeLabel(room) : '✅ Gratis' }}
                </span>
              </div>
              <div class="w-full py-2.5 rounded-xl text-xs font-bold text-white text-center bg-gradient-to-r shadow transition-all duration-200 group-hover:shadow-md group-hover:brightness-105"
                :class="room.status === 'waiting' ? t(i).btn : 'from-gray-400 to-gray-500'">
                {{ room.status === 'waiting' ? 'Unirse ahora 🚀' : 'Ver sala 👁' }}
              </div>
            </div>
          </button>
        </div>

        <!-- Agrupado por categoría -->
        <template v-else>
          <div v-for="(groupRooms, groupName) in grouped" :key="groupName" class="space-y-3">

            <div class="flex items-center gap-2.5">
              <span class="text-xl">{{ catIcon(groupName === 'Sin categoría' ? null : String(groupName)) }}</span>
              <h2 class="text-xs font-extrabold text-gray-600 uppercase tracking-widest">{{ groupName }}</h2>
              <span class="px-2 py-0.5 rounded-full bg-violet-100 text-violet-700 text-[11px] font-bold">{{ groupRooms.length }}</span>
              <div class="flex-1 h-px bg-gradient-to-r from-violet-200 to-transparent" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <button v-for="(room, i) in groupRooms" :key="room.id"
                class="room-card group" @click="joinRoom(room)">
                <!-- Franja de color -->
                <div :class="['h-1 w-full bg-gradient-to-r', t(i).top]" />

                <div class="p-4 space-y-3">

                  <!-- Banner de premio -->
                  <div v-if="hasPrize(room)"
                    class="flex items-center justify-between px-3 py-2 rounded-lg bg-gradient-to-r from-amber-50 to-yellow-50 border border-amber-200">
                    <div class="flex items-center gap-2">
                      <span class="text-lg">🏆</span>
                      <div class="leading-tight">
                        <p class="text-[10px] font-bold text-amber-600 uppercase tracking-wide">
                          {{ room.prize_type === 'accumulated' ? 'Bote' : 'Premio' }}
                        </p>
                        <p class="text-base font-black text-amber-700 tabular-nums">{{ prizeLabel(room) }}</p>
                      </div>
                    </div>
                    <span class="text-lg animate-pulse-slow">✨</span>
                  </div>

                  <!-- Título + status -->
                  <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                      <p class="font-bold text-gray-900 text-sm line-clamp-1 leading-tight">{{ room.title }}</p>
                      <code class="text-[10px] text-gray-400 font-mono">{{ room.code }}</code>
                    </div>
                    <span class="flex-shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold"
                      :class="room.status === 'playing'
                        ? 'bg-blue-100 text-blue-700 border border-blue-200'
                        : 'bg-emerald-100 text-emerald-700 border border-emerald-200'">
                      <span class="w-1.5 h-1.5 rounded-full animate-pulse"
                        :class="room.status === 'playing' ? 'bg-blue-500' : 'bg-emerald-500'" />
                      {{ room.status === 'playing' ? 'En juego' : 'En espera' }}
                    </span>
                  </div>

                  <!-- Jugadores -->
                  <div class="space-y-1.5 rounded-lg p-2.5 border border-gray-100 bg-gray-50">
                    <div class="flex items-center justify-between">
                      <span class="text-[11px] text-gray-500 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Inscritos
                      </span>
                      <span class="text-xs font-black text-gray-900 tabular-nums">
                        {{ room.current_players }}<span class="text-gray-400 font-normal">/{{ room.max_players }}</span>
                      </span>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-white border border-gray-100 overflow-hidden">
                      <div class="h-full rounded-full bg-gradient-to-r transition-all duration-700"
                        :class="fillGrad(fillPct(room))"
                        :style="{ width: fillPct(room) + '%' }" />
                    </div>
                  </div>

                  <!-- Pills de meta info -->
                  <div class="flex flex-wrap gap-1">
                    <span class="pill bg-gray-100 text-gray-600">{{ MODE_ICON[room.game_mode] }} {{ MODE_LABEL[room.game_mode] }}</span>
                    <span class="pill bg-indigo-50 text-indigo-600">❓{{ room.question_count }}</span>
                    <span class="pill bg-cyan-50 text-cyan-700">⏱{{ room.time_per_question }}s</span>
                    <span class="pill ml-auto font-bold"
                      :class="hasFee(room) ? 'bg-violet-100 text-violet-700' : 'bg-emerald-100 text-emerald-700'">
                      {{ hasFee(room) ? '💳 ' + feeLabel(room) : '✅ Gratis' }}
                    </span>
                  </div>

                  <!-- CTA -->
                  <div class="w-full py-2.5 rounded-xl text-xs font-bold text-white text-center bg-gradient-to-r shadow transition-all duration-200 group-hover:shadow-md group-hover:brightness-105"
                    :class="room.status === 'waiting' ? t(i).btn : 'from-gray-400 to-gray-500'">
                    {{ room.status === 'waiting' ? 'Unirse ahora 🚀' : 'Ver sala 👁' }}
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

<style scoped>
/* Burbujas flotantes animadas */
.blob {
  position: absolute;
  border-radius: 9999px;
  filter: blur(70px);
  opacity: 0.35;
  animation: float 14s ease-in-out infinite;
}
.blob-1 { width: 380px; height: 380px; background: #c4b5fd; top: -80px; left: -60px; animation-duration: 16s; }
.blob-2 { width: 280px; height: 280px; background: #a5f3fc; top: 45%; right: -60px; animation-duration: 11s; animation-delay: -5s; }
.blob-3 { width: 240px; height: 240px; background: #bbf7d0; bottom: 40px; left: 30%; animation-duration: 13s; animation-delay: -8s; }

@keyframes float {
  0%, 100% { transform: translateY(0) scale(1); }
  50%       { transform: translateY(-24px) scale(1.05); }
}

/* Card */
.room-card {
  @apply text-left w-full rounded-2xl bg-white border border-gray-100 shadow-sm
         hover:-translate-y-1 hover:shadow-lg hover:border-gray-200
         transition-all duration-300 overflow-hidden
         focus:outline-none focus:ring-2 focus:ring-violet-400 focus:ring-offset-2;
}

/* Pills */
.pill {
  @apply inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-[11px] font-medium;
}

/* Pulse lento */
@keyframes pulse-slow {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.4; }
}
.animate-pulse-slow { animation: pulse-slow 2s ease-in-out infinite; }
</style>
