<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import debounce from 'lodash/debounce'
import { getRoute } from '@js/routes/triviaRoutes'
import { showToast } from '@js/utils/toast'
import { showToastConfirmAction } from '@js/utils/toastNotify'

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

// ── Selección de filas ───────────────────────────────────────────────────────
const selectedRows = ref<Set<string>>(new Set())

const toggleSelectAll = () => {
  if (selectedRows.value.size === props.questions.data.length) {
    selectedRows.value.clear()
  } else {
    selectedRows.value = new Set(props.questions.data.map(q => q.id))
  }
}

const toggleSelectRow = (id: string) => {
  if (selectedRows.value.has(id)) {
    selectedRows.value.delete(id)
  } else {
    selectedRows.value.add(id)
  }
}

const clearSelection = () => {
  selectedRows.value.clear()
}

const selectedCount = computed(() => selectedRows.value.size)

const selectedQuestions = computed(() =>
  props.questions.data.filter(q => selectedRows.value.has(q.id))
)

// ── Acciones en bulk ──────────────────────────────────────────────────────────
const bulkEdit = () => {
  if (selectedRows.value.size === 1) {
    const q = selectedQuestions.value[0]
    router.get(getRoute('questionsEdit', { roomId: props.roomId, questionId: q.id }))
  }
}

const bulkToggleActive = () => {
  const count = selectedQuestions.value.length
  selectedQuestions.value.forEach(q => {
    router.patch(getRoute('questionsToggleActive', { roomId: props.roomId, questionId: q.id }), {}, {
      preserveScroll: true,
      onError: () => showToast('No se pudo actualizar una de las preguntas.', 'error'),
    })
  })
  showToast(`Estado actualizado en ${count} pregunta(s).`, 'success')
  clearSelection()
}

const bulkDelete = async () => {
  const count = selectedQuestions.value.length
  const names = selectedQuestions.value.map(q => `"${q.question_text.slice(0, 40)}..."`).join(', ')

  const confirmed = await showToastConfirmAction({
    title: 'Eliminar preguntas',
    text: `¿Eliminar ${count} pregunta(s)? ${names}. Esta acción no se puede deshacer.`,
    icon: 'warning',
    confirmButtonText: 'Sí, eliminar',
  })
  if (!confirmed) return

  selectedQuestions.value.forEach(q => {
    router.delete(getRoute('questionsDestroy', { roomId: props.roomId, questionId: q.id }), {
      onError: () => showToast('No se pudo eliminar una de las preguntas.', 'error'),
    })
  })
  showToast(`${count} pregunta(s) eliminada(s).`, 'success')
  clearSelection()
}

const applyFilters = debounce(() => {
  router.get(
    getRoute('questionsIndex', { roomId: props.roomId }),
    { search: search.value, category: category.value, difficulty: difficulty.value, active: active.value },
    { preserveState: true, replace: true }
  )
}, 300)

async function confirmDelete(q: Question) {
  const confirmed = await showToastConfirmAction({
    title: 'Eliminar pregunta',
    text: `¿Eliminar la pregunta "${q.question_text.slice(0, 80)}..."? Esta acción no se puede deshacer.`,
    icon: 'warning',
    confirmButtonText: 'Sí, eliminar',
  })
  if (!confirmed) return

  router.delete(getRoute('questionsDestroy', { roomId: props.roomId, questionId: q.id }), {
    onSuccess: () => showToast('Pregunta eliminada.', 'success'),
    onError:   () => showToast('No se pudo eliminar la pregunta.', 'error'),
  })
}

function toggleActive(q: Question) {
  router.patch(getRoute('questionsToggleActive', { roomId: props.roomId, questionId: q.id }), {}, {
    preserveScroll: true,
    onSuccess: () => showToast(q.is_active ? 'Pregunta desactivada.' : 'Pregunta activada.', 'success'),
    onError:   () => showToast('No se pudo actualizar la pregunta.', 'error'),
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
          <h1 class="text-2xl font-bold qi-text">📚 Banco de Preguntas</h1>
          <p class="text-sm qi-text-muted mt-1">
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
          class="qi-card rounded-lg shadow-sm p-4 flex items-center gap-3"
        >
          <span class="text-2xl">{{ s.icon }}</span>
          <div>
            <p class="text-xs qi-text-muted uppercase">{{ s.label }}</p>
            <p class="text-xl font-bold qi-text">{{ s.value }}</p>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="qi-card shadow-sm rounded-lg p-4 mb-4">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
          <!-- Búsqueda -->
          <input
            v-model="search"
            type="text"
            placeholder="Buscar pregunta..."
            class="qi-input rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500"
            @input="applyFilters"
          />
          <!-- Categoría -->
          <select
            v-model="category"
            class="qi-input rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500"
            @change="applyFilters"
          >
            <option value="">Todas las categorías</option>
            <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
          </select>
          <!-- Dificultad -->
          <select
            v-model="difficulty"
            class="qi-input rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500"
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
            class="qi-input rounded-md text-sm focus:border-indigo-500 focus:ring-indigo-500"
            @change="applyFilters"
          >
            <option value="">Todas</option>
            <option value="1">Activas</option>
            <option value="0">Inactivas</option>
          </select>
        </div>
      </div>

      <!-- Acciones Bulk (debajo del filtrado) -->
      <Transition name="slide-down">
        <div v-if="selectedCount > 0" class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-700 rounded-lg p-3 mb-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <span class="text-sm font-medium text-indigo-700 dark:text-indigo-300">
                {{ selectedCount }} pregunta(s) seleccionada(s)
              </span>
              <button
                @click="clearSelection"
                class="text-xs qi-text-muted hover:opacity-80 underline"
              >
                Limpiar
              </button>
            </div>
            <div class="flex items-center gap-2">
              <button
                v-if="selectedCount === 1"
                @click="bulkEdit"
                class="px-3 py-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
              >
                Editar
              </button>
              <button
                @click="bulkToggleActive"
                class="px-3 py-1.5 text-sm font-medium text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300"
              >
                Activar/Desactivar
              </button>
              <button
                @click="bulkDelete"
                class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md transition-colors"
              >
                Eliminar {{ selectedCount > 1 ? `(${selectedCount})` : '' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Tabla -->
      <div class="qi-card shadow-sm rounded-lg overflow-hidden">
        <table class="min-w-full qi-divide-y">
          <thead class="qi-thead">
            <tr>
              <th class="px-3 py-3 text-left">
                <input
                  type="checkbox"
                  :checked="selectedCount === questions.data.length && questions.data.length > 0"
                  @change="toggleSelectAll"
                  class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium qi-text-secondary uppercase">Pregunta</th>
              <th class="px-4 py-3 text-left text-xs font-medium qi-text-secondary uppercase">Opciones</th>
              <th class="px-4 py-3 text-left text-xs font-medium qi-text-secondary uppercase">Dificultad</th>
              <th class="px-4 py-3 text-left text-xs font-medium qi-text-secondary uppercase">Pts / Seg</th>
              <th class="px-4 py-3 text-left text-xs font-medium qi-text-secondary uppercase">Usos</th>
              <th class="px-4 py-3 text-left text-xs font-medium qi-text-secondary uppercase">Estado</th>
            </tr>
          </thead>
          <tbody class="qi-divide-y">
            <tr
              v-for="q in questions.data"
              :key="q.id"
              :class="[
                'transition-colors cursor-pointer',
                selectedRows.has(q.id)
                  ? 'bg-indigo-50 dark:bg-indigo-900/30'
                  : 'qi-row-hover',
                !q.is_active && 'opacity-60'
              ]"
              @click="toggleSelectRow(q.id)"
            >
              <!-- Checkbox -->
              <td class="px-3 py-4 whitespace-nowrap" @click.stop>
                <input
                  type="checkbox"
                  :checked="selectedRows.has(q.id)"
                  @change="toggleSelectRow(q.id)"
                  class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
              </td>
              <!-- Pregunta -->
              <td class="px-4 py-4 max-w-xs">
                <p class="text-sm font-medium qi-text line-clamp-2">
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
                    class="text-xs qi-text-secondary truncate max-w-[180px]"
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
              <td class="px-4 py-4 whitespace-nowrap text-sm qi-text-secondary">
                {{ q.points }}pts / {{ q.time_limit_seconds }}s
              </td>
              <!-- Usos -->
              <td class="px-4 py-4 whitespace-nowrap text-sm qi-text-muted">
                {{ q.times_used }}
              </td>
              <!-- Estado -->
              <td class="px-4 py-4 whitespace-nowrap" @click.stop>
                <button
                  @click="toggleActive(q)"
                  :class="[
                    'px-2 py-0.5 rounded-full text-xs font-medium transition',
                    q.is_active
                      ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 hover:bg-green-200'
                      : 'qi-inactive-badge'
                  ]"
                >
                  {{ q.is_active ? 'Activa' : 'Inactiva' }}
                </button>
              </td>
            </tr>

            <tr v-if="questions.data.length === 0">
              <td colspan="7" class="px-6 py-12 text-center qi-text-muted">
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
        <div class="qi-pagination px-6 py-4 flex items-center justify-between">
          <p class="text-sm qi-text-secondary">
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
                    : 'qi-page-link border'
                ]"
                v-html="link.label"
              />
              <span
                v-else
                class="px-3 py-1 rounded text-sm qi-text-muted cursor-not-allowed"
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
.slide-down-enter-active {
  transition: all 0.25s ease-out;
}
.slide-down-leave-active {
  transition: all 0.15s ease-in;
}
.slide-down-enter-from {
  opacity: 0;
  transform: translateY(-8px);
}
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

.qi-text { color: var(--ds-text); }
.qi-text-secondary { color: var(--ds-text-2); }
.qi-text-muted { color: var(--ds-text-muted); }
.qi-card { background: var(--ds-surface); }
.qi-input { border: 1px solid var(--ds-border); background: var(--ds-surface); color: var(--ds-text-2); }
.qi-thead { background: var(--ds-surface-2); }
.qi-divide-y > :not([hidden]) ~ :not([hidden]) { border-top: 1px solid var(--ds-border); }
.qi-row-hover:hover { background: var(--ds-surface-2); }
.qi-inactive-badge { background: var(--ds-surface-2); color: var(--ds-text-2); }
.qi-inactive-badge:hover { filter: brightness(0.95); }
.qi-pagination { background: var(--ds-surface-2); border-top: 1px solid var(--ds-border); }
.qi-page-link { background: var(--ds-surface); color: var(--ds-text-2); border-color: var(--ds-border); }
.qi-page-link:hover { background: var(--ds-surface-2); }
</style>
