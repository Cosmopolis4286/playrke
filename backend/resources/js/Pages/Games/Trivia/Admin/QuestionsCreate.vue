<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { getRoute } from '@js/routes/triviaRoutes'

const props = defineProps<{
  roomId: string
  categories: string[]
}>()

const form = useForm({
  question_text:        '',
  options:              ['', '', '', ''] as string[],
  correct_option_index: 0,
  time_limit_seconds:   15,
  points:               100,
  difficulty:           'medium' as 'easy' | 'medium' | 'hard',
  category:             '',
  explanation:          '',
  image_url:            '',
  is_active:            true,
})

// Número de opciones visible (mínimo 2, máximo 4)
const optionCount = ref(4)

const visibleOptions = computed(() => form.options.slice(0, optionCount.value))

function setOptionCount(n: number) {
  optionCount.value = n
  // Si la opción correcta queda fuera del rango, resetearla
  if (form.correct_option_index >= n) {
    form.correct_option_index = 0
  }
}

function submit() {
  const payload = {
    ...form.data(),
    options: form.options.slice(0, optionCount.value).filter(o => o.trim() !== ''),
  }
  form.transform(() => payload).post(getRoute('questionsStore', { roomId: props.roomId }))
}

const difficultyOptions = [
  { value: 'easy',   label: 'Fácil',   color: 'text-green-600' },
  { value: 'medium', label: 'Media',   color: 'text-yellow-600' },
  { value: 'hard',   label: 'Difícil', color: 'text-red-600' },
]
</script>

<template>
  <Head title="Nueva Pregunta - Trivia" />

  <div class="py-6">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

      <!-- Breadcrumb -->
      <div class="flex items-center gap-2 text-sm qc-text-muted mb-6">
        <Link :href="getRoute('questionsIndex', { roomId })" class="hover:text-indigo-600">
          Banco de Preguntas
        </Link>
        <span>/</span>
        <span class="qc-text font-medium">Nueva Pregunta</span>
      </div>

      <form @submit.prevent="submit" class="space-y-6">

        <!-- Pregunta -->
        <div class="qc-card shadow-sm rounded-lg p-6">
          <h2 class="text-lg font-semibold qc-text mb-4">Enunciado</h2>

          <div>
            <label class="block text-sm font-medium qc-text-secondary mb-1">
              Texto de la pregunta <span class="text-red-500">*</span>
            </label>
            <textarea
              v-model="form.question_text"
              rows="3"
              maxlength="1000"
              placeholder="Escribe la pregunta aquí..."
              class="qc-input w-full rounded-md focus:border-indigo-500 focus:ring-indigo-500 text-sm"
            />
            <p v-if="form.errors.question_text" class="mt-1 text-xs text-red-600">{{ form.errors.question_text }}</p>
            <p class="mt-1 text-xs text-gray-400 text-right">{{ form.question_text.length }}/1000</p>
          </div>

          <div class="mt-4">
            <label class="block text-sm font-medium qc-text-secondary mb-1">
              URL de imagen (opcional)
            </label>
            <input
              v-model="form.image_url"
              type="url"
              placeholder="https://..."
              class="qc-input w-full rounded-md focus:border-indigo-500 focus:ring-indigo-500 text-sm"
            />
            <p v-if="form.errors.image_url" class="mt-1 text-xs text-red-600">{{ form.errors.image_url }}</p>
          </div>
        </div>

        <!-- Opciones -->
        <div class="qc-card shadow-sm rounded-lg p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold qc-text">Opciones de Respuesta</h2>
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
                    : 'qc-chip hover:border-indigo-400'
                ]"
              >
                {{ n }}
              </button>
            </div>
          </div>

          <p v-if="form.errors.correct_option_index" class="mb-3 text-xs text-red-600">
            {{ form.errors.correct_option_index }}
          </p>
          <p v-if="form.errors.options" class="mb-3 text-xs text-red-600">{{ form.errors.options }}</p>

          <div class="space-y-3">
            <div
              v-for="(_, i) in visibleOptions"
              :key="i"
              class="flex items-center gap-3"
            >
              <!-- Radio: marcar como correcta -->
              <input
                type="radio"
                :value="i"
                v-model="form.correct_option_index"
                class="h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500"
                :title="`Marcar opción ${String.fromCharCode(65+i)} como correcta`"
              />
              <span
                :class="[
                  'flex-shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold',
                  form.correct_option_index === i
                    ? 'bg-green-500 text-white'
                    : 'qc-letter-badge'
                ]"
              >
                {{ String.fromCharCode(65 + i) }}
              </span>
              <input
                v-model="form.options[i]"
                type="text"
                :placeholder="`Opción ${String.fromCharCode(65 + i)}...`"
                maxlength="500"
                class="qc-input flex-1 rounded-md focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                :class="form.correct_option_index === i ? 'border-green-400 dark:border-green-600' : ''"
              />
              <span v-if="form.correct_option_index === i" class="text-xs text-green-600 font-medium">✓ Correcta</span>
            </div>
          </div>

          <p class="mt-3 text-xs text-gray-500">
            Selecciona el radio de la opción correcta antes de guardar.
          </p>
        </div>

        <!-- Configuración -->
        <div class="qc-card shadow-sm rounded-lg p-6">
          <h2 class="text-lg font-semibold qc-text mb-4">Configuración</h2>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Tiempo -->
            <div>
              <label class="block text-sm font-medium qc-text-secondary mb-1">
                Tiempo límite (segundos) <span class="text-red-500">*</span>
              </label>
              <input
                v-model.number="form.time_limit_seconds"
                type="number"
                min="5"
                max="120"
                class="qc-input w-full rounded-md focus:border-indigo-500 focus:ring-indigo-500 text-sm"
              />
              <p v-if="form.errors.time_limit_seconds" class="mt-1 text-xs text-red-600">{{ form.errors.time_limit_seconds }}</p>
            </div>

            <!-- Puntos -->
            <div>
              <label class="block text-sm font-medium qc-text-secondary mb-1">
                Puntos <span class="text-red-500">*</span>
              </label>
              <input
                v-model.number="form.points"
                type="number"
                min="1"
                max="1000"
                class="qc-input w-full rounded-md focus:border-indigo-500 focus:ring-indigo-500 text-sm"
              />
              <p v-if="form.errors.points" class="mt-1 text-xs text-red-600">{{ form.errors.points }}</p>
            </div>

            <!-- Dificultad -->
            <div>
              <label class="block text-sm font-medium qc-text-secondary mb-1">
                Dificultad <span class="text-red-500">*</span>
              </label>
              <select
                v-model="form.difficulty"
                class="qc-input w-full rounded-md focus:border-indigo-500 focus:ring-indigo-500 text-sm"
              >
                <option v-for="d in difficultyOptions" :key="d.value" :value="d.value">{{ d.label }}</option>
              </select>
              <p v-if="form.errors.difficulty" class="mt-1 text-xs text-red-600">{{ form.errors.difficulty }}</p>
            </div>

            <!-- Categoría -->
            <div>
              <label class="block text-sm font-medium qc-text-secondary mb-1">Categoría</label>
              <input
                v-model="form.category"
                type="text"
                list="category-list"
                placeholder="ej: Historia, Ciencias..."
                maxlength="50"
                class="qc-input w-full rounded-md focus:border-indigo-500 focus:ring-indigo-500 text-sm"
              />
              <datalist id="category-list">
                <option v-for="c in categories" :key="c" :value="c" />
              </datalist>
              <p v-if="form.errors.category" class="mt-1 text-xs text-red-600">{{ form.errors.category }}</p>
            </div>
          </div>

          <!-- Explicación -->
          <div class="mt-4">
            <label class="block text-sm font-medium qc-text-secondary mb-1">
              Explicación de la respuesta (opcional)
            </label>
            <textarea
              v-model="form.explanation"
              rows="2"
              maxlength="2000"
              placeholder="Explica por qué esta es la respuesta correcta..."
              class="qc-input w-full rounded-md focus:border-indigo-500 focus:ring-indigo-500 text-sm"
            />
          </div>

          <!-- Activa -->
          <div class="mt-4 flex items-center gap-3">
            <input
              id="is_active"
              v-model="form.is_active"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500"
            />
            <label for="is_active" class="text-sm qc-text-secondary">
              Pregunta activa (disponible para asignar a salas)
            </label>
          </div>
        </div>

        <!-- Acciones -->
        <div class="flex items-center justify-end gap-3">
          <Link
            :href="getRoute('questionsIndex', { roomId })"
            class="qc-cancel-btn px-4 py-2 text-sm font-medium border rounded-md transition"
          >
            Cancelar
          </Link>
          <button
            type="submit"
            :disabled="form.processing"
            class="px-6 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-60 transition"
          >
            {{ form.processing ? 'Guardando...' : 'Guardar Pregunta' }}
          </button>
        </div>

      </form>
    </div>
  </div>
</template>

<style scoped>
.qc-card { background: var(--ds-surface); }
.qc-text { color: var(--ds-text); }
.qc-text-secondary { color: var(--ds-text-2); }
.qc-text-muted { color: var(--ds-text-muted); }
.qc-input { border: 1px solid var(--ds-border); background: var(--ds-surface); color: var(--ds-text); }
.qc-chip { background: var(--ds-surface); color: var(--ds-text-2); border-color: var(--ds-border); }
.qc-letter-badge { background: var(--ds-surface-2); color: var(--ds-text-2); }
.qc-cancel-btn { color: var(--ds-text-2); background: var(--ds-surface); border-color: var(--ds-border); }
.qc-cancel-btn:hover { background: var(--ds-surface-2); }
</style>
