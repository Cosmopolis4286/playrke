<script setup lang="ts">
defineProps<{
  allowLateJoin: boolean
  showLeaderboard: boolean
  publicRoom: boolean
}>()

defineEmits<{
  'update:allowLateJoin': [value: boolean]
  'update:showLeaderboard': [value: boolean]
  'update:publicRoom': [value: boolean]
}>()
</script>

<template>
  <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
      🛠️ Opciones Avanzadas
    </h3>

    <div class="space-y-4">
      <label class="flex items-center">
        <input
          :checked="allowLateJoin"
          type="checkbox"
          class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
          @change="$emit('update:allowLateJoin', ($event.target as HTMLInputElement).checked)"
        />
        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
          Permitir unirse tarde (después de iniciar)
        </span>
      </label>

      <label class="flex items-center">
        <input
          :checked="showLeaderboard"
          type="checkbox"
          class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
          @change="$emit('update:showLeaderboard', ($event.target as HTMLInputElement).checked)"
        />
        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
          Mostrar clasificación en tiempo real
        </span>
      </label>

      <label class="flex items-center">
        <input
          :checked="publicRoom"
          type="checkbox"
          class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
          @change="$emit('update:publicRoom', ($event.target as HTMLInputElement).checked)"
        />
        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
          Sala pública (visible en el lobby)
        </span>
      </label>
    </div>
  </div>
</template>
