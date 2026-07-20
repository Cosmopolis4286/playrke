<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { getRoute } from '@js/routes/triviaRoutes'

interface Question {
  id: string
  question_text: string
  options: string[]
  correct_option_index: number
  time_limit_seconds: number
  points: number
  difficulty: 'easy' | 'medium' | 'hard'
  category: string | null
  explanation: string | null
  image_url: string | null
  is_active: boolean
}

const props = defineProps<{
  roomId: string
  question: Question
  categories: string[]
}>()

// Rellenar con 4 slots (el resto vacíos)
const initialOptions = [...props.question.options]
while (initialOptions.length < 4) initialOptions.push('')

const form = useForm({
  question_text:        props.question.question_text,
  options:              initialOptions as string[],
  correct_option_index: props.question.correct_option_index,
  time_limit_seconds:   props.question.time_limit_seconds,
  points:               props.question.points,
  difficulty:           props.question.difficulty,
  category:             props.question.category ?? '',
  explanation:          props.question.explanation ?? '',
  image_url:            props.question.image_url ?? '',
  is_active:            props.question.is_active,
})

const optionCount = ref(props.question.options.length)

const visibleOptions = computed(() => form.options.slice(0, optionCount.value))

function setOptionCount(n: number) {
  optionCount.value = n
  if (form.correct_option_index >= n) {
    form.correct_option_index = 0
  }
}

function submit() {
  const payload = {
    ...form.data(),
    options: form.options.slice(0, optionCount.value).filter(o => o.trim() !== ''),
  }
  form.transform(() => payload).put(
    getRoute('questionsUpdate', { roomId: props.roomId, questionId: props.question.id })
  )
}

const difficultyOptions = [
  { value: 'easy',   label: 'Fácil'   },
  { value: 'medium', label: 'Media'   },
  { value: 'hard',   label: 'Difícil' },
]
</script>

<template>
  <Head :title="`Editar Pregunta - Trivia`" />

  <div class="py-6">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

      <!-- Breadcrumb -->
      <div class="flex items-center gap-2 text-sm qe-text-muted mb-6">
        <Link :href="getRoute('questionsIndex', { roomId })" class="hover:text-indigo-600">
          Banco de Preguntas
        </Link>
        <span>/</span>
        <span class="qe-text font-medium">Editar Pregunta</span>
      </div>

      <form @submit.prevent="submit" class="space-y-6">

        <!-- Enunciado -->
        <div class="qe-card shadow-sm rounded-lg p-6">
          <h2 class="text-lg font-semibold qe-text mb-4">Enunciado</h2>

          <div>
            <label class="block text-sm font-medium qe-text-secondary mb-1">
              Texto de la pregunta <span class="text-red-500">*</span>
            </label>
            <textarea
              v-model="form.question_text"
              rows="3"
              maxlength="1000"
              class="qe-input w-full rounded-md focus:border-indigo-500 focus:ring-indigo-500 text-sm"
            />
            <p v-if="form.errors.question_text" class="mt-1 text-xs text-red-600">{{ form.errors.question_text }}</p>
            <p class="mt-1 text-xs text-gray-400 text-right">{{ form.question_text.length }}/1000</p>
          </div>

          <div class="mt-4">
            <label class="block text-sm font-medium qe-text-secondary mb-1">
              URL de imagen (opcional)
            </label>
            <input
              v-model="form.image_url"
              type="url"
              placeholder="https://..."
              class="qe-input w-full rounded-md focus:border-indigo-500 focus:ring-indigo-500 text-sm"
            />
            <p v-if="form.errors.image_url" class="mt-1 text-xs text-red-600">{{ form.errors.image_url }}</p>
          </div>
        </div>

        <!-- Opciones -->
        <div class="qe-card shadow-sm rounded-lg p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold qe-text">Opciones de Respuesta</h2>
            <div class="flex items-center gap-2">
              <span class="text-xs text-gray-500">Núm. opciones:</span>
              <button
                v-for="n in [2, 3, 4]"
                :key="n"
                type="button"
                @click="setOptionCount(n)"
                :class="[
                  'px-2 py-0.5 rounded text-xs font-medium border transition',
                  optionCount === n
                    ? 'bg-indigo-600 text-white border-indigo-600'
                    : 'qe-chip hover:border-indigo-400'
                ]"
              >{{ n }}</button>
            </div>
          </div>

          <p v-if="form.errors.correct_option_index" class="mb-3 text-xs text-red-600">{{ form.errors.correct_option_index }}</p>
          <p v-if="form.errors.options" class="mb-3 text-xs text-red-600">{{ form.errors.options }}</p>

          <div class="space-y-3">
            <div v-for="(_, i) in visibleOptions" :key="i" class="flex items-center gap-3">
              <input
                type="radio"
                :value="i"
                v-model="form.correct_option_index"
                class="h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500"
              />
              <span
                :class="[
                  'flex-shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold',
                  form.correct_option_index === i
                    ? 'bg-green-500 text-white'
                    : 'qe-letter-badge'
                ]"
              >{{ String.fromCharCode(65 + i) }}</span>
              <input
                v-model="form.options[i]"
                type="text"
                :placeholder="`Opción ${String.fromCharCode(65 + i)}...`"
                maxlength="500"
                :class="[
                  'qe-input flex-1 rounded-md focus:border-indigo-500 focus:ring-indigo-500 text-sm',
                  form.correct_option_index === i ? 'border-green-400 dark:border-green-600' : ''
                ]"
              />
              <span v-if="form.correct_option_index === i" class="text-xs text-green-600 font-medium whitespace-nowrap">✓ Correcta</span>
            </div>
          </div>
        </div>

        <!-- Configuración -->
        <div class="qe-card shadow-sm rounded-lg p-6">
          <h2 class="text-lg font-semibold qe-text mb-4">Configuración</h2>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium qe-text-secondary mb-1">
                Tiempo límite (seg) <span class="text-red-500">*</span>
              </label>
              <input
                v-model.number="form.time_limit_seconds"
                type="number" min="5" max="120"
                class="qe-input w-full rounded-md focus:border-indigo-500 focus:ring-indigo-500 text-sm"
              />
              <p v-if="form.errors.time_limit_seconds" class="mt-1 text-xs text-red-600">{{ form.errors.time_limit_seconds }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium qe-text-secondary mb-1">
                Puntos <span class="text-red-500">*</span>
              </label>
              <input
                v-model.number="form.points"
                type="number" min="1" max="1000"
                class="qe-input w-full rounded-md focus:border-indigo-500 focus:ring-indigo-500 text-sm"
              />
              <p v-if="form.errors.points" class="mt-1 text-xs text-red-600">{{ form.errors.points }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium qe-text-secondary mb-1">
                Dificultad <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.difficulty"
                class="qe-input w-full rounded-md focus:border-indigo-500 focus:ring-indigo-500 text-sm"
              >
                <option v-for="d in difficultyOptions" :key="d.value" :value="d.value">{{ d.label }}</option>
              </select>
              <p v-if="form.errors.difficulty" class="mt-1 text-xs text-red-600">{{ form.errors.difficulty }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium qe-text-secondary mb-1">Categoría</label>
              <input
                v-model="form.category"
                type="text"
                list="category-list-edit"
                placeholder="ej: Historia, Ciencias..."
                maxlength="50"
                class="qe-input w-full rounded-md focus:border-indigo-500 focus:ring-indigo-500 text-sm"
              />
              <datalist id="category-list-edit">
                <option v-for="c in categories" :key="c" :value="c" />
              </datalist>
            </div>
          </div>

          <div class="mt-4">
            <label class="block text-sm font-medium qe-text-secondary mb-1">
              Explicación (opcional)
            </label>
            <textarea
              v-model="form.explanation"
              rows="2"
              maxlength="2000"
              class="qe-input w-full rounded-md focus:border-indigo-500 focus:ring-indigo-500 text-sm"
            />
          </div>

          <div class="mt-4 flex items-center gap-3">
            <input
              id="is_active_edit"
              v-model="form.is_active"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500"
            />
            <label for="is_active_edit" class="text-sm qe-text-secondary">
              Pregunta activa
            </label>
          </div>
        </div>

        <!-- Acciones -->
        <div class="flex items-center justify-end gap-3">
          <Link
            :href="getRoute('questionsIndex', { roomId })"
            class="qe-cancel-btn px-4 py-2 text-sm font-medium border rounded-md transition"
          >
            Cancelar
          </Link>
          <button
            type="submit"
            :disabled="form.processing"
            class="px-6 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-60 transition"
          >
            {{ form.processing ? 'Guardando...' : 'Actualizar Pregunta' }}
          </button>
        </div>

      </form>
    </div>
  </div>
</template>

<style scoped>
.qe-card { background: var(--ds-surface); }
.qe-text { color: var(--ds-text); }
.qe-text-secondary { color: var(--ds-text-2); }
.qe-text-muted { color: var(--ds-text-muted); }
.qe-input { border: 1px solid var(--ds-border); background: var(--ds-surface); color: var(--ds-text); }
.qe-chip { background: var(--ds-surface); color: var(--ds-text-2); border-color: var(--ds-border); }
.qe-letter-badge { background: var(--ds-surface-2); color: var(--ds-text-2); }
.qe-cancel-btn { color: var(--ds-text-2); background: var(--ds-surface); border-color: var(--ds-border); }
.qe-cancel-btn:hover { background: var(--ds-surface-2); }
</style>
