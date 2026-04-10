<script setup lang="ts">
import { ref } from 'vue'

const emit = defineEmits<{
  join: [playerName: string]
}>()

const playerName = ref('')
const isSubmitting = ref(false)
const error = ref('')

const MAX_NAME_LENGTH = 20

function handleSubmit() {
  error.value = ''
  
  // Validation
  const trimmed = playerName.value.trim()
  
  if (!trimmed) {
    error.value = 'Por favor ingresa tu nombre'
    return
  }
  
  if (trimmed.length < 2) {
    error.value = 'El nombre debe tener al menos 2 caracteres'
    return
  }
  
  if (trimmed.length > MAX_NAME_LENGTH) {
    error.value = `El nombre no puede tener más de ${MAX_NAME_LENGTH} caracteres`
    return
  }

  isSubmitting.value = true
  emit('join', trimmed)
}
</script>

<template>
  <div class="bg-white rounded-xl shadow-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
      Unirse a la Sala
    </h3>

    <form @submit.prevent="handleSubmit" class="space-y-4">
      <!-- Name Input -->
      <div>
        <label
          for="playerName"
          class="block text-sm font-medium text-gray-700 mb-1"
        >
          Tu Nombre
        </label>
        <input
          id="playerName"
          v-model="playerName"
          type="text"
          :maxlength="MAX_NAME_LENGTH"
          :disabled="isSubmitting"
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
          placeholder="Ingresa tu nombre"
          autocomplete="off"
        />
        <div class="flex justify-between mt-1">
          <span
            v-if="error"
            class="text-sm text-red-600"
          >
            {{ error }}
          </span>
          <span
            class="text-xs text-gray-500 ml-auto"
            :class="{ 'text-red-500': playerName.length > MAX_NAME_LENGTH }"
          >
            {{ playerName.length }} / {{ MAX_NAME_LENGTH }}
          </span>
        </div>
      </div>

      <!-- Submit Button -->
      <button
        type="submit"
        :disabled="isSubmitting || !playerName.trim()"
        class="w-full flex items-center justify-center px-4 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
      >
        <span v-if="isSubmitting" class="flex items-center">
          <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
          </svg>
          Uniendo...
        </span>
        <span v-else class="flex items-center">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
          ¡Unirme al Juego!
        </span>
      </button>
    </form>

    <!-- Info -->
    <div class="mt-4 text-center">
      <p class="text-xs text-gray-500">
        Al unirte aceptas jugar con respeto
      </p>
    </div>
  </div>
</template>
