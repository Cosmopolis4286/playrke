<script setup lang="ts">
defineProps<{
  maxPlayers: number
  questionCount: number
  timePerQuestion: number
  estimatedTime: number
}>()

defineEmits<{
  'update:maxPlayers': [value: number]
  'update:questionCount': [value: number]
  'update:timePerQuestion': [value: number]
}>()
</script>

<template>
  <div class="gss-border-t pt-6">
    <h3 class="text-lg font-medium gss-text mb-4">
      ⚙️ Configuración del Juego
    </h3>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
      <!-- Max Players -->
      <div>
        <label for="max_players" class="block text-sm font-medium gss-text-secondary">
          Máx. Jugadores <span class="text-red-500">*</span>
        </label>
        <input
          id="max_players"
          :value="maxPlayers"
          type="number"
          required
          min="2"
          max="500"
          class="gss-input mt-1 block w-full rounded-md focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          @input="$emit('update:maxPlayers', Number(($event.target as HTMLInputElement).value))"
        />
        <p class="mt-1 text-xs gss-text-muted">Mínimo 2, Máximo 500</p>
      </div>

      <!-- Question Count -->
      <div>
        <label for="question_count" class="block text-sm font-medium gss-text-secondary">
          N° Preguntas <span class="text-red-500">*</span>
        </label>
        <input
          id="question_count"
          :value="questionCount"
          type="number"
          required
          min="1"
          max="100"
          class="gss-input mt-1 block w-full rounded-md focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          @input="$emit('update:questionCount', Number(($event.target as HTMLInputElement).value))"
        />
        <p class="mt-1 text-xs gss-text-muted">Mínimo 1, Máximo 100</p>
      </div>

      <!-- Time per Question -->
      <div>
        <label for="time_per_question" class="block text-sm font-medium gss-text-secondary">
          Tiempo/Pregunta (s) <span class="text-red-500">*</span>
        </label>
        <input
          id="time_per_question"
          :value="timePerQuestion"
          type="number"
          required
          min="5"
          max="300"
          class="gss-input mt-1 block w-full rounded-md focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          @input="$emit('update:timePerQuestion', Number(($event.target as HTMLInputElement).value))"
        />
        <p class="mt-1 text-xs gss-text-muted">Segundos por pregunta</p>
      </div>
    </div>

    <!-- Estimado -->
    <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-md">
      <p class="text-sm text-blue-800 dark:text-blue-300">
        ⏱️ Tiempo estimado del juego: <strong>{{ estimatedTime }} minutos</strong>
      </p>
    </div>
  </div>
</template>

<style scoped>
.gss-border-t { border-top: 1px solid var(--ds-border); }
.gss-text { color: var(--ds-text); }
.gss-text-secondary { color: var(--ds-text-2); }
.gss-text-muted { color: var(--ds-text-muted); }
.gss-input { border: 1px solid var(--ds-border); background: var(--ds-surface); color: var(--ds-text-2); }
</style>
