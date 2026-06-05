<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import { getRoute } from '@js/routes/triviaRoutes'
import { utcToLocalInput, localInputToUtc, getMinLocalDatetime } from '@js/composables/useTimezone'

interface Currency { id: string; symbol: string; name: string; iso_code: string }

const props = defineProps({
  room: Object,
  categories: Array as () => string[],
  currencies: { type: Array as () => Currency[], default: () => [] },
  errors: Object,
})

// Inicializar fee_by_currency desde la sala existente
// Soporta tanto el nuevo formato { id: amount } como el legado entry_fee + entry_currency_id
function initFeeByCurrency(): Record<string, number> {
  if (props.room.fee_by_currency && Object.keys(props.room.fee_by_currency).length > 0) {
    return { ...props.room.fee_by_currency }
  }
  if (props.room.entry_currency_id && Number(props.room.entry_fee) > 0) {
    return { [props.room.entry_currency_id]: Number(props.room.entry_fee) }
  }
  return {}
}

const feeByCurrency = ref<Record<string, number>>(initFeeByCurrency())

// IDs de monedas seleccionadas (las que tienen entrada en feeByCurrency)
const selectedCurrencyIds = computed({
  get: () => Object.keys(feeByCurrency.value),
  set: (ids: string[]) => {
    const updated: Record<string, number> = {}
    for (const id of ids) {
      updated[id] = feeByCurrency.value[id] ?? 0
    }
    feeByCurrency.value = updated
  },
})

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

const form = useForm({
  title: props.room.title,
  description: props.room.description || '',
  category: props.room.category || '',
  max_players: props.room.max_players,
  entry_fee: Number(props.room.entry_fee ?? 0),
  entry_currency_id: props.room.entry_currency_id ?? '',
  prize_type: (props.room.prize_type ?? 'accumulated') as 'accumulated' | 'fixed',
  prize_amount: Number(props.room.prize_amount ?? 0),
  prize_currency_id: props.room.prize_currency_id ?? '',
  scheduled_at: utcToLocalInput(props.room.scheduled_at),
  settings: props.room.settings || {},
})

// Min datetime for scheduled_at (must be in the future)
const minScheduledAt = computed(() => getMinLocalDatetime())

// Validate scheduled_at is in the future (comparando en hora local)
const scheduledAtError = computed(() => {
  if (!form.scheduled_at) return ''
  if (new Date(form.scheduled_at) <= new Date()) {
    return 'La fecha y hora de inicio debe ser futura'
  }
  return ''
})

const submit = () => {
  if (scheduledAtError.value) return

  form.transform((data) => ({
    ...data,
    scheduled_at: localInputToUtc(data.scheduled_at),
    fee_by_currency: feeByCurrency.value,
  }))

  form.put(getRoute('roomsUpdate', { id: props.room.id }))
}
</script>

<template>
  <Head title="Editar Sala - Trivia" />

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
          ✏️ Editar Sala
        </h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
          Código: <code class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded">{{ room.code }}</code>
        </p>
      </div>

      <!-- Alerta si no es editable -->
      <div v-if="room.status !== 'waiting'" class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
        <p class="text-sm text-yellow-800 dark:text-yellow-300">
          ⚠️ Esta sala ya no está en estado "esperando". Algunos campos no se pueden modificar.
        </p>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
        <div class="p-6 space-y-6">
          <!-- Título -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Título <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.title"
              type="text"
              required
              :disabled="room.status !== 'waiting'"
              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:opacity-50"
            />
            <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
          </div>

          <!-- Descripción -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Descripción
            </label>
            <textarea
              v-model="form.description"
              rows="3"
              :disabled="room.status !== 'waiting'"
              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:opacity-50"
            />
          </div>

          <!-- Categoría -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Categoría
            </label>
            <input
              v-model="form.category"
              type="text"
              list="category-list-edit"
              maxlength="50"
              :disabled="room.status !== 'waiting'"
              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:opacity-50"
              placeholder="Ej: Ciencias, Historia, Deportes..."
            />
            <datalist id="category-list-edit">
              <option v-for="cat in categories" :key="cat" :value="cat" />
            </datalist>
          </div>

          <!-- Max Players -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Máximo de Jugadores <span class="text-red-500">*</span>
            </label>
            <input
              v-model.number="form.max_players"
              type="number"
              required
              min="2"
              max="500"
              :disabled="room.status !== 'waiting'"
              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:opacity-50"
            />
          </div>

          <!-- Entrada y Premio -->
          <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">
              💳 Entrada y Premio
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
              Selecciona las monedas aceptadas y el precio de entrada en cada una. Deja vacío para sala gratuita.
            </p>

            <div class="space-y-4">
              <!-- Selector de monedas aceptadas -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Monedas de entrada
                </label>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="c in currencies" :key="c.id"
                    type="button"
                    :disabled="room.status !== 'waiting'"
                    @click="toggleCurrency(c.id)"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold border transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                    :class="feeByCurrency[c.id] !== undefined
                      ? 'bg-indigo-600 border-indigo-600 text-white shadow'
                      : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-indigo-400'"
                  >
                    <span>{{ c.symbol }}</span>
                    <span class="opacity-80">{{ c.iso_code }}</span>
                    <span v-if="feeByCurrency[c.id] !== undefined" class="ml-0.5">✓</span>
                  </button>
                </div>
                <p class="mt-1.5 text-xs text-gray-400">Haz clic para activar/desactivar. Sin selección = sala gratuita.</p>
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
                      :disabled="room.status !== 'waiting'"
                      :value="feeByCurrency[cid]"
                      @change="feeByCurrency[cid] = Number(($event.target as HTMLInputElement).value)"
                      class="w-24 rounded-r-lg border border-l-0 border-indigo-200 dark:border-indigo-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400 disabled:opacity-50"
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
                    :disabled="room.status !== 'waiting'"
                    class="block w-28 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:opacity-50"
                    placeholder="0"
                  />
                  <select
                    v-model="form.prize_currency_id"
                    :disabled="room.status !== 'waiting'"
                    class="block flex-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:opacity-50"
                  >
                    <option value="">— Sin moneda —</option>
                    <option v-for="c in currencies" :key="c.id" :value="c.id">
                      {{ c.symbol }} — {{ c.name }}
                    </option>
                  </select>
                </div>
                <div class="mt-2 flex gap-4 text-sm">
                  <label class="flex items-center gap-1.5 cursor-pointer">
                    <input v-model="form.prize_type" type="radio" value="accumulated" :disabled="room.status !== 'waiting'" class="text-indigo-600" />
                    <span class="text-gray-700 dark:text-gray-300">Acumulado</span>
                  </label>
                  <label class="flex items-center gap-1.5 cursor-pointer">
                    <input v-model="form.prize_type" type="radio" value="fixed" :disabled="room.status !== 'waiting'" class="text-indigo-600" />
                    <span class="text-gray-700 dark:text-gray-300">Fijo</span>
                  </label>
                </div>
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
                :disabled="room.status !== 'waiting'"
                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:opacity-50"
              />
              <p class="mt-1 text-xs text-gray-500">Deja vacío para inicio manual. Si especificas fecha y hora, la sala se iniciará automáticamente.</p>
              <p v-if="scheduledAtError" class="mt-1 text-sm text-red-600">{{ scheduledAtError }}</p>
              <p v-if="form.errors.scheduled_at" class="mt-1 text-sm text-red-600">{{ form.errors.scheduled_at }}</p>
            </div>
          </div>

          <!-- Info de sólo lectura -->
          <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase mb-4">
              Información del Sistema (No editable)
            </h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span class="text-gray-500 dark:text-gray-400">Estado:</span>
                <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ room.status }}</span>
              </div>
              <div>
                <span class="text-gray-500 dark:text-gray-400">Preguntas:</span>
                <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ room.question_count }}</span>
              </div>
              <div>
                <span class="text-gray-500 dark:text-gray-400">Tiempo/Pregunta:</span>
                <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ room.time_per_question }}s</span>
              </div>
              <div>
                <span class="text-gray-500 dark:text-gray-400">Creada:</span>
                <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ new Date(room.created_at).toLocaleDateString() }}</span>
              </div>
              <div v-if="room.scheduled_at">
                <span class="text-gray-500 dark:text-gray-400">Inicio Programado:</span>
                <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ new Date(room.scheduled_at).toLocaleString() }}</span>
              </div>
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
            v-if="room.status === 'waiting'"
            type="submit"
            :disabled="form.processing"
            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
          >
            <span v-if="form.processing">Guardando...</span>
            <span v-else>Guardar Cambios</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
