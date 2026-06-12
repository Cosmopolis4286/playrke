<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { getRoute } from '@js/routes/triviaRoutes'
import { localInputToUtc, getMinLocalDatetime } from '@js/composables/useTimezone'

interface Currency { id: string; symbol: string; name: string; iso_code: string }
interface GameMode { value: string; label: string; description: string }

const props = defineProps({
  defaultSettings: Object,
  gameModes: { type: Array as () => GameMode[], default: () => [] },
  categories: Array as () => string[],
  currencies: { type: Array as () => Currency[], default: () => [] },
  errors: Object,
})

// Widget de monedas múltiples para el fee de entrada
const feeByCurrency = ref<Record<string, number>>({})

function toggleCurrency(id: string) {
  if (feeByCurrency.value[id] !== undefined) {
    const copy = { ...feeByCurrency.value }
    delete copy[id]
    feeByCurrency.value = copy
  } else {
    feeByCurrency.value = { ...feeByCurrency.value, [id]: 0 }
  }
}

function getCurrencyById(id: string): Currency | undefined {
  return props.currencies.find(c => c.id === id)
}

// Distribución por posición para el modelo de premio "pool"
const prizePayouts = ref<{ rank: number; percent: number }[]>([
  { rank: 1, percent: 0.60 },
  { rank: 2, percent: 0.25 },
  { rank: 3, percent: 0.15 },
])

function addPrizePayout() {
  const nextRank = prizePayouts.value.length > 0
    ? Math.max(...prizePayouts.value.map(p => p.rank)) + 1
    : 1
  prizePayouts.value.push({ rank: nextRank, percent: 0 })
}

function removePrizePayout(index: number) {
  prizePayouts.value.splice(index, 1)
}

const form = useForm({
  title: '',
  description: '',
  category: '',
  game_mode: 'free_for_all' as 'free_for_all' | 'teams' | 'duel',
  teams_config: {
    team_count: 2,
    team_size: null as number | null,
    duel_type: '2vs2' as '2vs2' | '1vsX',
    team_names: ['', ''] as string[],
  },
  max_players: props.defaultSettings?.max_players || 100,
  question_count: props.defaultSettings?.question_count || 10,
  time_per_question: props.defaultSettings?.time_per_question || 15,
  entry_fee: 0,
  entry_currency_id: '' as string,
  prize_type: 'accumulated' as 'accumulated' | 'fixed',
  prize_amount: 0,
  prize_currency_id: '' as string,
  prize_model: 'guaranteed' as 'guaranteed' | 'pool',
  sponsor_prize: 0,
  platform_rake_percent: 0,
  scheduled_at: '' as string,
  settings: {
    allow_late_join: false,
    show_leaderboard: true,
    public_room: true,
  },
})

// Calcular tiempo estimado del juego
const estimatedTime = computed(() => {
  const seconds = form.question_count * form.time_per_question
  const minutes = Math.ceil(seconds / 60)
  return minutes
})

// Min datetime for scheduled_at (must be in the future)
const minScheduledAt = computed(() => getMinLocalDatetime())

// Validate scheduled_at is in the future
const scheduledAtError = computed(() => {
  if (!form.scheduled_at) return ''
  if (new Date(form.scheduled_at) <= new Date()) {
    return 'La fecha y hora de inicio debe ser futura'
  }
  return ''
})

// Si hay aporte de sponsor, se debe seleccionar la moneda del premio
const sponsorPrizeError = computed(() => {
  const value = Number(form.sponsor_prize)
  if (value < 0) return 'El aporte del sponsor no puede ser negativo'
  if (value > 0 && !form.prize_currency_id) {
    return 'Selecciona la moneda del premio cuando defines un aporte de sponsor'
  }
  return ''
})

const platformRakeError = computed(() => {
  const value = Number(form.platform_rake_percent)
  if (!Number.isFinite(value) || value < 0) return 'La comisión no puede ser negativa'
  if (value > 30) return 'La comisión de la plataforma no puede superar 30%'
  return ''
})

// La suma de porcentajes de prize_payouts debe ser ≈ 100% cuando el modelo es 'pool'
const prizePayoutsError = computed(() => {
  if (form.prize_model !== 'pool') return ''
  if (prizePayouts.value.length === 0) return ''
  const total = prizePayouts.value.reduce((sum, p) => sum + (Number(p.percent) || 0), 0)
  if (Math.abs(total - 1) > 0.01) {
    return `La suma de los porcentajes debe ser 100% (actual: ${Math.round(total * 100)}%)`
  }
  return ''
})

// Computed para mostrar config de equipos
const showTeamConfig = computed(() => form.game_mode !== 'free_for_all')
const isDuelMode = computed(() => form.game_mode === 'duel')

// Actualizar team_names cuando cambia team_count
watch(() => form.teams_config.team_count, (newCount) => {
  const names = form.teams_config.team_names
  if (newCount > names.length) {
    // Agregar nombres vacíos
    for (let i = names.length; i < newCount; i++) {
      names.push(i < 2 ? ['Equipo Rojo', 'Equipo Azul'][i] : `Equipo ${i + 1}`)
    }
  } else if (newCount < names.length) {
    // Quitar nombres sobrantes
    names.splice(newCount)
  }
  form.teams_config.team_names = names
})

// Ajustar team_count para duelo (máximo 2)
watch(() => form.game_mode, (mode) => {
  if (mode === 'duel') {
    form.teams_config.team_count = 2
  }
})

const submit = () => {
  if (scheduledAtError.value || sponsorPrizeError.value || platformRakeError.value || prizePayoutsError.value) return

  form.transform((data) => ({
    ...data,
    scheduled_at: localInputToUtc(data.scheduled_at),
    fee_by_currency: feeByCurrency.value,
    prize_payouts: data.prize_model === 'pool' ? prizePayouts.value : null,
  }))

  form.post(getRoute('roomsStore'), {
    onSuccess: () => {
      form.reset()
      feeByCurrency.value = {}
    },
  })
}
</script>

<template>
  <Head title="Crear Sala - Trivia" />

  <div class="py-6">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-6">
        <Link
          :href="getRoute('roomsIndex')"
          class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline mb-2 inline-flex items-center"
        >
          ← Volver al listado
        </Link>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          🎮 Crear Nueva Sala de Trivia
        </h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
          Configura los parámetros de la sala. El código se generará automáticamente.
        </p>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
        <div class="p-6 space-y-6">
          <!-- Título -->
          <div>
            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Título de la Sala <span class="text-red-500">*</span>
            </label>
            <input
              id="title"
              v-model="form.title"
              type="text"
              required
              maxlength="255"
              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              placeholder="Ej: Trivia de Ciencias"
            />
            <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
          </div>

          <!-- Descripción -->
          <div>
            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Descripción
            </label>
            <textarea
              id="description"
              v-model="form.description"
              rows="3"
              maxlength="1000"
              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              placeholder="Describe el tema o reglas de la sala..."
            />
            <p class="mt-1 text-xs text-gray-500">Máximo 1000 caracteres</p>
          </div>

          <!-- Categoría -->
          <div>
            <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Categoría
            </label>
            <input
              id="category"
              v-model="form.category"
              type="text"
              list="category-list"
              maxlength="50"
              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              placeholder="Ej: Ciencias, Historia, Deportes..."
            />
            <datalist id="category-list">
              <option v-for="cat in categories" :key="cat" :value="cat" />
            </datalist>
            <p class="mt-1 text-xs text-gray-500">Opcional. Escribe o elige una categoría existente.</p>
          </div>

          <!-- Modo de Juego -->
          <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
              🎮 Modo de Juego
            </h3>

            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Selecciona el modo <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                  <label
                    v-for="mode in gameModes"
                    :key="mode.value"
                    class="relative flex cursor-pointer rounded-lg border p-4 hover:bg-gray-50 dark:hover:bg-gray-700"
                    :class="form.game_mode === mode.value ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-300 dark:border-gray-600'"
                  >
                    <input
                      v-model="form.game_mode"
                      type="radio"
                      :value="mode.value"
                      class="sr-only"
                    />
                    <div class="flex flex-col">
                      <span class="font-medium text-gray-900 dark:text-white">{{ mode.label }}</span>
                      <span class="text-xs text-gray-500 dark:text-gray-400">{{ mode.description }}</span>
                    </div>
                    <div
                      v-if="form.game_mode === mode.value"
                      class="absolute top-2 right-2 text-indigo-600"
                    >
                      ✓
                    </div>
                  </label>
                </div>
              </div>

              <!-- Configuración de Equipos (visible si no es free_for_all) -->
              <div v-if="showTeamConfig" class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <h4 class="font-medium text-gray-900 dark:text-white mb-3">
                  {{ isDuelMode ? '⚔️ Configuración del Duelo' : '👥 Configuración de Equipos' }}
                </h4>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <!-- Tipo de Duelo -->
                  <div v-if="isDuelMode">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Tipo de duelo
                    </label>
                    <select
                      v-model="form.teams_config.duel_type"
                      class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    >
                      <option value="2vs2">2 vs 2 (Igual número)</option>
                      <option value="1vsX">1 vs Todos (Uno contra todos)</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                      {{ form.teams_config.duel_type === '1vsX' ? 'Equipo 1: 1 jugador | Equipo 2: Todos los demás' : 'Ambos equipos con igual número de jugadores' }}
                    </p>
                  </div>

                  <!-- Número de equipos -->
                  <div v-else>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Número de equipos
                    </label>
                    <input
                      v-model.number="form.teams_config.team_count"
                      type="number"
                      min="2"
                      max="10"
                      class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    />
                  </div>

                  <!-- Tamaño máximo por equipo -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Jugadores por equipo
                    </label>
                    <input
                      v-model.number="form.teams_config.team_size"
                      type="number"
                      :placeholder="Math.ceil(form.max_players / form.teams_config.team_count).toString()"
                      min="1"
                      class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    />
                    <p class="mt-1 text-xs text-gray-500">Deja vacío para distribución automática</p>
                  </div>
                </div>

                <!-- Nombres de equipos -->
                <div class="mt-4">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Nombres de los equipos
                  </label>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <input
                      v-for="(name, index) in form.teams_config.team_names"
                      :key="index"
                      v-model="form.teams_config.team_names[index]"
                      type="text"
                      :placeholder="'Equipo ' + (index + 1)"
                      class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Configuración del Juego -->
          <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
              ⚙️ Configuración del Juego
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
              <!-- Max Players -->
              <div>
                <label for="max_players" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Máx. Jugadores <span class="text-red-500">*</span>
                </label>
                <input
                  id="max_players"
                  v-model.number="form.max_players"
                  type="number"
                  required
                  min="2"
                  max="500"
                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
                <p class="mt-1 text-xs text-gray-500">Mínimo 2, Máximo 500</p>
              </div>

              <!-- Question Count -->
              <div>
                <label for="question_count" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  N° Preguntas <span class="text-red-500">*</span>
                </label>
                <input
                  id="question_count"
                  v-model.number="form.question_count"
                  type="number"
                  required
                  min="1"
                  max="100"
                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
                <p class="mt-1 text-xs text-gray-500">Mínimo 1, Máximo 100</p>
              </div>

              <!-- Time per Question -->
              <div>
                <label for="time_per_question" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Tiempo/Pregunta (s) <span class="text-red-500">*</span>
                </label>
                <input
                  id="time_per_question"
                  v-model.number="form.time_per_question"
                  type="number"
                  required
                  min="5"
                  max="300"
                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
                <p class="mt-1 text-xs text-gray-500">Segundos por pregunta</p>
              </div>
            </div>

            <!-- Estimado -->
            <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-md">
              <p class="text-sm text-blue-800 dark:text-blue-300">
                ⏱️ Tiempo estimado del juego: <strong>{{ estimatedTime }} minutos</strong>
              </p>
            </div>
          </div>

          <!-- Entrada y Premio -->
          <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">
              💳 Entrada y Premio
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
              Selecciona las monedas aceptadas y el precio en cada una. Sin selección = sala gratuita.
            </p>

            <div class="space-y-4">
              <!-- Selector de monedas -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Monedas de entrada
                </label>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="c in currencies" :key="c.id"
                    type="button"
                    @click="toggleCurrency(c.id)"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all"
                    :class="feeByCurrency[c.id] !== undefined
                      ? 'bg-indigo-600 border-indigo-600 text-white shadow'
                      : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-indigo-400'"
                  >
                    <span>{{ c.symbol }}</span>
                    <span class="opacity-80">{{ c.iso_code }}</span>
                    <span v-if="feeByCurrency[c.id] !== undefined" class="ml-0.5">✓</span>
                  </button>
                </div>
              </div>

              <!-- Precios por moneda -->
              <div v-if="Object.keys(feeByCurrency).length > 0"
                class="rounded-xl border border-indigo-100 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-900/20 p-4">
                <p class="text-xs font-semibold text-indigo-700 dark:text-indigo-300 mb-3 uppercase tracking-wide">
                  💰 Precio de entrada por moneda
                </p>
                <div class="flex flex-wrap gap-3">
                  <div v-for="(_, cid) in feeByCurrency" :key="cid" class="flex items-center gap-0">
                    <span class="inline-flex items-center px-2.5 py-1.5 rounded-l-lg bg-indigo-100 dark:bg-indigo-800 text-indigo-800 dark:text-indigo-200 text-xs font-bold border border-indigo-200 dark:border-indigo-700">
                      {{ getCurrencyById(String(cid))?.symbol ?? cid }}
                    </span>
                    <input
                      type="number"
                      min="0"
                      step="0.01"
                      :value="feeByCurrency[cid]"
                      @change="feeByCurrency[cid] = Number(($event.target as HTMLInputElement).value)"
                      class="w-24 rounded-r-lg border border-l-0 border-indigo-200 dark:border-indigo-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400"
                      placeholder="0.00"
                    />
                  </div>
                </div>
              </div>

              <!-- Premio -->
              <div class="pt-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Premio
                </label>
                <div class="flex gap-2">
                  <input
                    v-model.number="form.prize_amount"
                    type="number"
                    min="0"
                    step="0.01"
                    class="block w-28 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    placeholder="0"
                  />
                  <select
                    v-model="form.prize_currency_id"
                    class="block flex-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  >
                    <option value="">— Sin moneda —</option>
                    <option v-for="c in currencies" :key="c.id" :value="c.id">
                      {{ c.symbol }} — {{ c.name }}
                    </option>
                  </select>
                </div>
                <div class="mt-2 flex gap-4 text-sm">
                  <label class="flex items-center gap-1.5 cursor-pointer">
                    <input v-model="form.prize_type" type="radio" value="accumulated" class="text-indigo-600" />
                    <span class="text-gray-700 dark:text-gray-300">Acumulado</span>
                  </label>
                  <label class="flex items-center gap-1.5 cursor-pointer">
                    <input v-model="form.prize_type" type="radio" value="fixed" class="text-indigo-600" />
                    <span class="text-gray-700 dark:text-gray-300">Fijo</span>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- Configuración de Premios -->
          <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">
              🏆 Configuración de Premios
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
              Define cómo se reparte el premio final entre los ganadores.
            </p>

            <div class="space-y-4">
              <!-- Modelo de premio -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Modelo de premio
                </label>
                <div class="flex gap-4 text-sm">
                  <label class="flex items-center gap-1.5 cursor-pointer">
                    <input v-model="form.prize_model" type="radio" value="guaranteed" class="text-indigo-600" />
                    <span class="text-gray-700 dark:text-gray-300">Garantizado</span>
                  </label>
                  <label class="flex items-center gap-1.5 cursor-pointer">
                    <input v-model="form.prize_model" type="radio" value="pool" class="text-indigo-600" />
                    <span class="text-gray-700 dark:text-gray-300">Pozo (entradas + sponsor)</span>
                  </label>
                </div>
                <p class="mt-1.5 text-xs text-gray-400">
                  <strong>Garantizado:</strong> se reparte el monto del premio + sponsor, sin importar cuántos jugadores haya.
                  <strong>Pozo:</strong> el premio se forma con las entradas pagadas + sponsor, menos la comisión de la plataforma.
                </p>
              </div>

              <!-- Aporte de sponsor -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Aporte de sponsor (opcional)
                </label>
                <input
                  v-model.number="form.sponsor_prize"
                  type="number"
                  min="0"
                  step="0.01"
                  class="mt-1 block w-40 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  placeholder="0"
                />
                <p v-if="sponsorPrizeError" class="mt-1 text-sm text-red-600">{{ sponsorPrizeError }}</p>
                <p v-else-if="form.errors.sponsor_prize" class="mt-1 text-sm text-red-600">{{ form.errors.sponsor_prize }}</p>
                <p v-else class="mt-1 text-xs text-gray-400">Monto adicional aportado por un sponsor, en la moneda del premio.</p>
              </div>

              <!-- Comisión de la plataforma (solo modelo pool) -->
              <div v-if="form.prize_model === 'pool'">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Comisión de la plataforma (%)
                </label>
                <input
                  v-model.number="form.platform_rake_percent"
                  type="number"
                  min="0"
                  max="30"
                  step="0.01"
                  class="mt-1 block w-32 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  placeholder="0"
                />
                <p v-if="platformRakeError" class="mt-1 text-sm text-red-600">{{ platformRakeError }}</p>
                <p v-else-if="form.errors.platform_rake_percent" class="mt-1 text-sm text-red-600">{{ form.errors.platform_rake_percent }}</p>
                <p v-else class="mt-1 text-xs text-gray-400">Porcentaje del pozo que retiene la plataforma (máx. 30%).</p>
              </div>

              <!-- Distribución por posición (solo modelo pool) -->
              <div v-if="form.prize_model === 'pool'">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Distribución por posición
                </label>
                <div class="space-y-2">
                  <div v-for="(payout, index) in prizePayouts" :key="index" class="flex items-center gap-2">
                    <span class="text-sm text-gray-500 dark:text-gray-400 w-16">Puesto</span>
                    <input
                      v-model.number="payout.rank"
                      type="number"
                      min="1"
                      class="w-20 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    />
                    <span class="text-sm text-gray-500 dark:text-gray-400">→</span>
                    <input
                      :value="Math.round(payout.percent * 100)"
                      @input="payout.percent = Math.max(0, Number(($event.target as HTMLInputElement).value)) / 100"
                      type="number"
                      min="0"
                      max="100"
                      step="1"
                      class="w-20 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    />
                    <span class="text-sm text-gray-500 dark:text-gray-400">%</span>
                    <button
                      type="button"
                      @click="removePrizePayout(index)"
                      class="ml-auto text-xs text-red-600 hover:underline"
                    >
                      Quitar
                    </button>
                  </div>
                </div>
                <button
                  type="button"
                  @click="addPrizePayout"
                  class="mt-2 text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
                >
                  + Añadir posición
                </button>
                <p v-if="prizePayoutsError" class="mt-1 text-sm text-red-600">{{ prizePayoutsError }}</p>
                <p v-else-if="form.errors.prize_payouts" class="mt-1 text-sm text-red-600">{{ form.errors.prize_payouts }}</p>
                <p v-else class="mt-1.5 text-xs text-gray-400">La suma de los porcentajes debe ser 100%. Por defecto: 1° 60%, 2° 25%, 3° 15%.</p>
              </div>
            </div>
          </div>

          <!-- Fecha y Hora de Inicio -->
          <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
              📅 Fecha y Hora de Inicio
            </h3>

            <div>
              <label for="scheduled_at" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Inicio Automático
              </label>
              <input
                id="scheduled_at"
                v-model="form.scheduled_at"
                type="datetime-local"
                :min="minScheduledAt"
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              />
              <p class="mt-1 text-xs text-gray-500">Deja vacío para inicio manual. Si especificas fecha y hora, la sala se iniciará automáticamente.</p>
              <p v-if="scheduledAtError" class="mt-1 text-sm text-red-600">{{ scheduledAtError }}</p>
              <p v-if="form.errors.scheduled_at" class="mt-1 text-sm text-red-600">{{ form.errors.scheduled_at }}</p>
            </div>
          </div>

          <!-- Settings -->
          <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
              🛠️ Opciones Avanzadas
            </h3>

            <div class="space-y-4">
              <label class="flex items-center">
                <input
                  v-model="form.settings.allow_late_join"
                  type="checkbox"
                  class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                  Permitir unirse tarde (después de iniciar)
                </span>
              </label>

              <label class="flex items-center">
                <input
                  v-model="form.settings.show_leaderboard"
                  type="checkbox"
                  class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                  Mostrar clasificación en tiempo real
                </span>
              </label>

              <label class="flex items-center">
                <input
                  v-model="form.settings.public_room"
                  type="checkbox"
                  class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                  Sala pública (visible en el lobby)
                </span>
              </label>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 flex items-center justify-end gap-3">
          <Link
            :href="getRoute('roomsIndex')"
            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"
          >
            Cancelar
          </Link>
          <button
            type="submit"
            :disabled="form.processing"
            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
          >
            <span v-if="form.processing">Creando...</span>
            <span v-else>Crear Sala</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
