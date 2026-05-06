<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import debounce from 'lodash/debounce'
import { getRoute } from '@js/routes/triviaRoutes'

const props = defineProps({
  rooms:   Object,
  stats:   Object,
  filters: Object,
})

const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status || '')
const selected = ref(new Set())

// ── Selección ─────────────────────────────────────────────────────────────────
const allSelected = computed(() =>
  props.rooms.data.length > 0 && selected.value.size === props.rooms.data.length
)
const toggleAll  = () => {
  if (allSelected.value) selected.value = new Set()
  else selected.value = new Set(props.rooms.data.map(r => r.id))
}
const toggle = id => {
  const s = new Set(selected.value)
  s.has(id) ? s.delete(id) : s.add(id)
  selected.value = s
}
const selectedRooms = computed(() => props.rooms.data.filter(r => selected.value.has(r.id)))
const selCount = computed(() => selected.value.size)

// ── Búsqueda ──────────────────────────────────────────────────────────────────
const doSearch = debounce(() => {
  router.get(getRoute('roomsIndex'), { search: search.value, status: status.value }, { preserveState: true, replace: true })
}, 300)

const setStatus = s => { status.value = s; doSearch() }

// ── Acciones ──────────────────────────────────────────────────────────────────
const goShow = () => selCount.value === 1 && router.get(getRoute('roomsShow', { id: selectedRooms.value[0].id }))
const goEdit = () => selCount.value === 1 && selectedRooms.value[0].status === 'waiting' && router.get(getRoute('roomsEdit', { id: selectedRooms.value[0].id }))
const doClose = () => {
  const closable = selectedRooms.value.filter(r => r.status !== 'closed')
  if (!closable.length) return
  if (!confirm(`¿Cerrar ${closable.length} sala(s)? Esta acción no se puede deshacer.`)) return
  closable.forEach(r => router.delete(getRoute('roomsDestroy', { id: r.id })))
  selected.value = new Set()
}

// ── Helpers ───────────────────────────────────────────────────────────────────
const fmtDate = d => {
  if (!d) return '—'
  return new Date(d).toLocaleString('es-ES', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' })
}
const fmtShortDate = d => {
  if (!d) return '—'
  return new Date(d).toLocaleString('es-ES', { day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit' })
}

const fillPct = r => r.max_players ? Math.min(100, Math.round((r.current_players / r.max_players) * 100)) : 0

const STATUS = {
  waiting:   { label: 'En espera', dot: 'bg-amber-400',  bg: 'bg-amber-50 text-amber-700 ring-amber-200'  },
  playing:   { label: 'En juego',  dot: 'bg-emerald-400 animate-pulse', bg: 'bg-emerald-50 text-emerald-700 ring-emerald-200' },
  closed:    { label: 'Cerrada',   dot: 'bg-gray-400',   bg: 'bg-gray-50 text-gray-600 ring-gray-200'     },
  cancelled: { label: 'Cancelada', dot: 'bg-red-400',    bg: 'bg-red-50 text-red-700 ring-red-200'        },
}
const stCfg   = s  => STATUS[s] ?? STATUS.closed
const fillCls = pct => pct >= 90 ? 'bg-red-400' : pct >= 60 ? 'bg-amber-400' : 'bg-emerald-400'

const MODE = { free_for_all:'👤 Todos', teams:'👥 Equipos', duel:'⚔️ Duelo' }
const modeLabel = m => MODE[m] ?? m

const STAT_CARDS = computed(() => [
  { label:'Total',    value: props.stats?.total   || 0, cls:'from-indigo-500 to-violet-600', icon:'🏠' },
  { label:'En espera',value: props.stats?.waiting || 0, cls:'from-amber-400 to-orange-500',  icon:'⏳' },
  { label:'En juego', value: props.stats?.playing || 0, cls:'from-emerald-400 to-teal-500',  icon:'🎮' },
  { label:'Cerradas', value: props.stats?.closed  || 0, cls:'from-gray-400 to-slate-500',    icon:'🔒' },
])

const scheduledLabel = r => {
  if (!r.scheduled_at) return null
  const d = new Date(r.scheduled_at)
  const isPast    = d < new Date()
  const isNearby  = !isPast && (d - new Date()) < 30 * 60 * 1000
  return { text: fmtShortDate(r.scheduled_at), isPast, isNearby }
}
</script>

<template>
  <Head title="Salas · Trivia Admin" />

  <div class="min-h-screen bg-gray-50 dark:bg-gray-950">
    <div class="max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

      <!-- ── Header ───────────────────────────────────────────────────────────── -->
      <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
          <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">
            Salas de Trivia
          </h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
            Gestiona y monitorea todas las salas en tiempo real
          </p>
        </div>
        <Link :href="getRoute('roomsCreate')"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-sm shadow-indigo-200 dark:shadow-none transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Nueva sala
        </Link>
      </div>

      <!-- ── Stats ────────────────────────────────────────────────────────────── -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div v-for="s in STAT_CARDS" :key="s.label"
          class="relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">{{ s.label }}</p>
              <p class="text-3xl font-black text-gray-900 dark:text-white mt-0.5">{{ s.value }}</p>
            </div>
            <div :class="['w-12 h-12 rounded-xl bg-gradient-to-br flex items-center justify-center text-xl shadow-sm', s.cls]">
              {{ s.icon }}
            </div>
          </div>
          <!-- Accent bar -->
          <div :class="['absolute bottom-0 left-0 right-0 h-0.5 bg-gradient-to-r', s.cls]" />
        </div>
      </div>

      <!-- ── Filtros ───────────────────────────────────────────────────────────── -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-4">
        <div class="flex flex-col sm:flex-row gap-3">
          <!-- Buscador -->
          <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input v-model="search" type="text" placeholder="Buscar sala por título..."
              class="w-full pl-9 pr-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 transition-all"
              @input="doSearch" />
          </div>
          <!-- Filtros de estado -->
          <div class="flex gap-1.5 flex-wrap">
            <button v-for="s in ['', 'waiting', 'playing', 'closed']" :key="s"
              @click="setStatus(s)"
              class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all"
              :class="status === s
                ? 'bg-indigo-600 text-white shadow-sm'
                : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'">
              {{ s === '' ? 'Todas' : stCfg(s).label }}
            </button>
          </div>
        </div>
      </div>

      <!-- ── Barra de acciones bulk ────────────────────────────────────────────── -->
      <Transition name="slide">
        <div v-if="selCount > 0"
          class="flex items-center justify-between gap-3 px-4 py-3 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-700">
          <span class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">
            {{ selCount }} sala{{ selCount > 1 ? 's' : '' }} seleccionada{{ selCount > 1 ? 's' : '' }}
          </span>
          <div class="flex items-center gap-2">
            <button v-if="selCount === 1" @click="goShow"
              class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-indigo-300 transition-all">
              Ver detalles
            </button>
            <button v-if="selCount === 1 && selectedRooms[0]?.status === 'waiting'" @click="goEdit"
              class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 hover:bg-amber-200 transition-all">
              Editar
            </button>
            <button @click="doClose"
              class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-600 hover:bg-red-700 text-white transition-all">
              Cerrar{{ selCount > 1 ? ` (${selCount})` : '' }}
            </button>
            <button @click="selected = new Set()" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 ml-1">✕</button>
          </div>
        </div>
      </Transition>

      <!-- ── Tabla ─────────────────────────────────────────────────────────────── -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-800">
                <th class="w-10 px-4 py-3 text-left">
                  <input type="checkbox" :checked="allSelected" @change="toggleAll"
                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0" />
                </th>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Código</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nombre</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Jugadores</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Modo</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Configuración</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Entrada / Premio</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Inicio</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Creada</th>
                <th class="w-20 px-4 py-3" />
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">

              <!-- Filas -->
              <tr v-for="room in rooms.data" :key="room.id"
                class="group transition-colors cursor-pointer"
                :class="selected.has(room.id)
                  ? 'bg-indigo-50/60 dark:bg-indigo-900/10'
                  : 'hover:bg-gray-50/80 dark:hover:bg-gray-700/30'"
                @click="toggle(room.id)">

                <!-- Checkbox -->
                <td class="px-4 py-3.5" @click.stop>
                  <input type="checkbox" :checked="selected.has(room.id)" @change="toggle(room.id)"
                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0" />
                </td>

                <!-- Código -->
                <td class="px-4 py-3.5">
                  <code class="text-xs font-mono font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 px-2 py-1 rounded">{{ room.code }}</code>
                </td>

                <!-- Nombre + Categoría -->
                <td class="px-4 py-3.5">
                  <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white font-bold text-xs flex-shrink-0">
                      {{ room.title?.charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                      <p class="font-semibold text-gray-900 dark:text-white truncate max-w-[140px]">{{ room.title }}</p>
                      <span v-if="room.category" class="text-[11px] text-indigo-500 dark:text-indigo-400">{{ room.category }}</span>
                    </div>
                  </div>
                </td>

                <!-- Estado -->
                <td class="px-4 py-3.5">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1"
                    :class="stCfg(room.status).bg">
                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0" :class="stCfg(room.status).dot" />
                    {{ stCfg(room.status).label }}
                  </span>
                </td>

                <!-- Jugadores -->
                <td class="px-4 py-3.5">
                  <div class="space-y-1 min-w-[90px]">
                    <div class="flex items-center justify-between text-xs">
                      <span class="font-bold text-gray-900 dark:text-white tabular-nums">
                        {{ room.current_players }}
                      </span>
                      <span class="text-gray-400 dark:text-gray-500 tabular-nums">/ {{ room.max_players }}</span>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                      <div class="h-full rounded-full transition-all"
                        :class="fillCls(fillPct(room))"
                        :style="{ width: fillPct(room) + '%' }" />
                    </div>
                    <p class="text-[10px] text-gray-400 tabular-nums">{{ fillPct(room) }}% ocupado</p>
                  </div>
                </td>

                <!-- Modo -->
                <td class="px-4 py-3.5">
                  <span class="text-xs font-medium text-gray-600 dark:text-gray-300 whitespace-nowrap">
                    {{ modeLabel(room.game_mode) }}
                  </span>
                </td>

                <!-- Configuración -->
                <td class="px-4 py-3.5">
                  <div class="space-y-0.5 text-xs text-gray-500 dark:text-gray-400">
                    <p><span class="font-medium text-gray-700 dark:text-gray-300">{{ room.question_count }}</span> preguntas</p>
                    <p><span class="font-medium text-gray-700 dark:text-gray-300">{{ room.time_per_question }}s</span> / pregunta</p>
                  </div>
                </td>

                <!-- Entrada / Premio -->
                <td class="px-4 py-3.5">
                  <div class="space-y-1">
                    <div v-if="Number(room.entry_fee) > 0"
                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300">
                      💳 {{ Number(room.entry_fee).toFixed(2) }}
                    </div>
                    <div v-else class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium">✅ Gratis</div>
                    <div v-if="Number(room.prize_amount) > 0"
                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">
                      🏆 {{ Number(room.prize_amount).toFixed(2) }}
                    </div>
                  </div>
                </td>

                <!-- Inicio programado -->
                <td class="px-4 py-3.5">
                  <template v-if="scheduledLabel(room)">
                    <span class="text-xs font-medium"
                      :class="scheduledLabel(room).isPast
                        ? 'text-gray-400 dark:text-gray-500'
                        : scheduledLabel(room).isNearby
                          ? 'text-orange-600 dark:text-orange-400 font-bold'
                          : 'text-blue-600 dark:text-blue-400'">
                      {{ scheduledLabel(room).text }}
                    </span>
                    <span v-if="scheduledLabel(room).isNearby"
                      class="ml-1 inline-block w-1.5 h-1.5 rounded-full bg-orange-500 animate-pulse" />
                  </template>
                  <span v-else class="text-xs text-gray-400">Manual</span>
                </td>

                <!-- Creada -->
                <td class="px-4 py-3.5 text-xs text-gray-400 dark:text-gray-500 whitespace-nowrap">
                  {{ fmtShortDate(room.created_at) }}
                </td>

                <!-- Acciones rápidas -->
                <td class="px-4 py-3.5" @click.stop>
                  <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <Link :href="getRoute('roomsShow', { id: room.id })"
                      class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors"
                      title="Ver">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                      </svg>
                    </Link>
                    <Link v-if="room.status === 'waiting'" :href="getRoute('roomsEdit', { id: room.id })"
                      class="p-1.5 rounded-lg text-gray-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors"
                      title="Editar">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                      </svg>
                    </Link>
                    <button v-if="room.status !== 'closed'" @click="() => { selected = new Set([room.id]); doClose() }"
                      class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                      title="Cerrar">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                      </svg>
                    </button>
                  </div>
                </td>

              </tr>

              <!-- Empty -->
              <tr v-if="rooms.data.length === 0">
                <td colspan="10" class="px-6 py-16 text-center">
                  <div class="text-4xl mb-3">🎮</div>
                  <p class="font-semibold text-gray-500 dark:text-gray-400">No se encontraron salas</p>
                  <Link :href="getRoute('roomsCreate')"
                    class="mt-3 inline-flex items-center gap-1.5 text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                    + Crear primera sala
                  </Link>
                </td>
              </tr>

            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div class="px-5 py-3.5 bg-gray-50 dark:bg-gray-800/60 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between gap-4 flex-wrap">
          <p class="text-xs text-gray-500 dark:text-gray-400">
            Mostrando <span class="font-semibold text-gray-700 dark:text-gray-300">{{ rooms.from }}–{{ rooms.to }}</span>
            de <span class="font-semibold text-gray-700 dark:text-gray-300">{{ rooms.total }}</span> salas
          </p>
          <div class="flex gap-1">
            <template v-for="link in rooms.links" :key="link.label">
              <Link v-if="link.url" :href="link.url"
                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all"
                :class="link.active
                  ? 'bg-indigo-600 text-white shadow-sm'
                  : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-indigo-300'"
                v-html="link.label" />
              <span v-else
                class="px-3 py-1.5 rounded-lg text-xs font-medium bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-300 dark:text-gray-600 cursor-not-allowed"
                v-html="link.label" />
            </template>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
.slide-enter-active { transition: all 0.2s ease-out; }
.slide-leave-active { transition: all 0.15s ease-in; }
.slide-enter-from, .slide-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
