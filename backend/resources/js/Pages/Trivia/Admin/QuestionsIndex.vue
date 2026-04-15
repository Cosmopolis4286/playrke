<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import debounce from 'lodash/debounce'
import { getRoute } from '@js/routes/triviaRoutes'

interface Question {
  id: string
  question_text: string
  options: string[]
  time_limit_seconds: number
  points: number
  category: string | null
  difficulty: 'easy' | 'medium' | 'hard'
  is_active: boolean
  times_used: number
  created_at: string
}

interface Pagination {
  data: Question[]
  total: number
  from: number
  to: number
  links: { url: string | null; label: string; active: boolean }[]
}

const props = defineProps<{
  questions: Pagination
  stats: { total: number; active: number; easy: number; medium: number; hard: number }
  categories: string[]
  filters: { search?: string; category?: string; difficulty?: string; active?: string }
  roomId: string
}>()

const search     = ref(props.filters.search     ?? '')
const category   = ref(props.filters.category   ?? '')
const difficulty = ref(props.filters.difficulty ?? '')
const active     = ref(props.filters.active     ?? '')

const applyFilters = debounce(() => {
  router.get(
    getRoute('questionsIndex', { roomId: props.roomId }),
    { search: search.value, category: category.value, difficulty: difficulty.value, active: active.value },
    { preserveState: true, replace: true }
  )
}, 300)

function confirmDelete(q: Question) {
  if (confirm(`¿Eliminar la pregunta?\n\n"${q.question_text.slice(0, 80)}..."\n\nEsta acción no se puede deshacer.`)) {
    router.delete(getRoute('questionsDestroy', { roomId: props.roomId, questionId: q.id }))
  }
}

function toggleActive(q: Question) {
  router.patch(getRoute('questionsToggleActive', { roomId: props.roomId, questionId: q.id }), {}, {
    preserveScroll: true,
  })
}

const difficultyLabel = (d: string) => ({ easy: 'Fácil', medium: 'Media', hard: 'Difícil' }[d] ?? d)
const difficultyColor = (d: string) => ({
  easy:   'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
  medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
  hard:   'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
}[d] ?? 'bg-gray-100 text-gray-700')

const statsCards = computed(() => [
  { label: 'Total',   value: props.stats.total,  icon: '📚', color: 'blue'   },
  { label: 'Activas', value: props.stats.active, icon: '✅', color: 'green'  },
  { label: 'Fácil',   value: props.stats.easy,   icon: '🟢', color: 'green'  },
  { label: 'Media',   value: props.stats.medium, icon: '🟡', color: 'yellow' },
  { label: 'Difícil', value: props.stats.hard,   icon: '🔴', color: 'red'    },
])

function formatDate(d: string) {
  return new Date(d).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' })
}
</script>

<template>
  <Head title="Banco de Preguntas - Trivia" />

  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">📚 Banco de Preguntas</h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Gestiona el banco global de preguntas de trivia
          </p>
        </div>
        <Link
          :href="getRoute('questionsCreate', { roomId })"
          class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-xs font-semibold uppercase tracking-widest rounded-md hover:bg-blue-700 transition"
        >
          + Nueva Pregunta
        </Link>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
        <div
          v-for="s in statsCards"
          :key="s.label"
          class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 flex items-center gap-3"
        >
          <span class="text-2xl">{{ s.icon }}</span>
          <div>
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">{{ s.label }}</p>
            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ s.value }}</p>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
          <!-- Búsqueda -->
          <input
            v-model="search"
            type="text"
            placeholder="Buscar pregunta..."
            class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
            @input="applyFilters"
          />
          <!-- Categoría -->
          <select
            v-model="category"
            class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
            @change="applyFilters"
          >
            <option value="">Todas las categorías</option>
            <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
          </select>
          <!-- Dificultad -->
          <select
            v-model="difficulty"
            class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
            @change="applyFilters"
          >
            <option value="">Todas las dificultades</option>
            <option value="easy">Fácil</option>
            <option value="medium">Media</option>
            <option value="hard">Difícil</option>
          </select>
          <!-- Activa/Inactiva -->
          <select
            v-model="active"
            class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
            @change="applyFilters"
          >
            <option value="">Todas</option>
            <option value="1">Activas</option>
            <option value="0">Inactivas</option>
          </select>
        </div>
      </div>

      <!-- Tabla -->
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Pregunta</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Opciones</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Dificultad</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Pts / Seg</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Usos</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Estado</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <tr
              v-for="q in questions.data"
              :key="q.id"
              :class="['hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors', !q.is_active && 'opacity-60']"
            >
              <!-- Pregunta -->
              <td class="px-4 py-4 max-w-xs">
                <p class="text-sm font-medium text-gray-900 dark:text-white line-clamp-2">
                  {{ q.question_text }}
                </p>
                <span v-if="q.category" class="mt-1 inline-block text-xs text-indigo-600 dark:text-indigo-400">
                  {{ q.category }}
                </span>
              </td>
              <!-- Opciones (miniaturas) -->
              <td class="px-4 py-4">
                <div class="space-y-0.5">
                  <div
                    v-for="(opt, i) in q.options"
                    :key="i"
                    class="text-xs text-gray-600 dark:text-gray-400 truncate max-w-[180px]"
                  >
                    <span class="font-mono font-semibold mr-1">{{ String.fromCharCode(65 + i) }}.</span>{{ opt }}
                  </div>
                </div>
              </td>
              <!-- Dificultad -->
              <td class="px-4 py-4 whitespace-nowrap">
                <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', difficultyColor(q.difficulty)]">
                  {{ difficultyLabel(q.difficulty) }}
                </span>
              </td>
              <!-- Pts / Seg -->
              <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                {{ q.points }}pts / {{ q.time_limit_seconds }}s
              </td>
              <!-- Usos -->
              <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                {{ q.times_used }}
              </td>
              <!-- Estado -->
              <td class="px-4 py-4 whitespace-nowrap">
                <button
                  @click="toggleActive(q)"
                  :class="[
                    'px-2 py-0.5 rounded-full text-xs font-medium transition',
                    q.is_active
                      ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 hover:bg-green-200'
                      : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 hover:bg-gray-200'
                  ]"
                >
                  {{ q.is_active ? 'Activa' : 'Inactiva' }}
                </button>
              </td>
              <!-- Acciones -->
              <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                <Link
                  :href="getRoute('questionsEdit', { roomId, questionId: q.id })"
                  class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900"
                >
                  Editar
                </Link>
                <button
                  @click="confirmDelete(q)"
                  class="text-red-600 dark:text-red-400 hover:text-red-900"
                >
                  Eliminar
                </button>
              </td>
            </tr>

            <tr v-if="questions.data.length === 0">
              <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                <div class="text-4xl mb-2">📭</div>
                <p>No se encontraron preguntas</p>
                <Link
                  :href="getRoute('questionsCreate', { roomId })"
                  class="text-indigo-600 dark:text-indigo-400 hover:underline mt-2 inline-block"
                >
                  Crear primera pregunta
                </Link>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Paginación -->
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 flex items-center justify-between">
          <p class="text-sm text-gray-700 dark:text-gray-300">
            Mostrando {{ questions.from ?? 0 }} – {{ questions.to ?? 0 }} de {{ questions.total }} preguntas
          </p>
          <div class="flex gap-1">
            <template v-for="link in questions.links" :key="link.label">
              <Link
                v-if="link.url"
                :href="link.url"
                :class="[
                  'px-3 py-1 rounded text-sm',
                  link.active
                    ? 'bg-indigo-600 text-white'
                    : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600'
                ]"
                v-html="link.label"
              />
              <span
                v-else
                class="px-3 py-1 rounded text-sm text-gray-400 dark:text-gray-500 cursor-not-allowed"
                v-html="link.label"
              />
            </template>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>
