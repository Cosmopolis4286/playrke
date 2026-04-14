<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { getRoute } from '@js/routes/triviaRoutes'

const props = defineProps({
  defaultSettings: Object,
  categories: Array as () => string[],
  errors: Object,
})

const form = useForm({
  title: '',
  description: '',
  category: '',
  max_players: props.defaultSettings?.max_players || 100,
  question_count: props.defaultSettings?.question_count || 10,
  time_per_question: props.defaultSettings?.time_per_question || 15,
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

const submit = () => {
  form.post(getRoute('roomsStore'), {
    onSuccess: () => form.reset(),
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
