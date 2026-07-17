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
          :value="maxPlayers"
          type="number"
          required
          min="2"
          max="500"
          class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          @input="$emit('update:maxPlayers', Number(($event.target as HTMLInputElement).value))"
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
          :value="questionCount"
          type="number"
          required
          min="1"
          max="100"
          class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          @input="$emit('update:questionCount', Number(($event.target as HTMLInputElement).value))"
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
          :value="timePerQuestion"
          type="number"
          required
          min="5"
          max="300"
          class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          @input="$emit('update:timePerQuestion', Number(($event.target as HTMLInputElement).value))"
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
</template>
