<script setup lang="ts">
defineProps<{
  allowLateJoin: boolean
  showLeaderboard: boolean
  publicRoom: boolean
  allowPowerups: boolean
}>()

defineEmits<{
  'update:allowLateJoin': [value: boolean]
  'update:showLeaderboard': [value: boolean]
  'update:publicRoom': [value: boolean]
  'update:allowPowerups': [value: boolean]
}>()
</script>

<template>
  <div class="ass-border-t pt-6">
    <h3 class="text-lg font-medium ass-text mb-4">
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
        <span class="ml-2 text-sm ass-text-secondary">
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
        <span class="ml-2 text-sm ass-text-secondary">
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
        <span class="ml-2 text-sm ass-text-secondary">
          Sala pública (visible en el lobby)
        </span>
      </label>

      <label class="flex items-center">
        <input
          :checked="allowPowerups"
          type="checkbox"
          class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
          @change="$emit('update:allowPowerups', ($event.target as HTMLInputElement).checked)"
        />
        <span class="ml-2 text-sm ass-text-secondary">
          Habilitar power-ups (50/50, doble puntos, saltar, tiempo extra — 1 uso de cada uno por jugador)
        </span>
      </label>
    </div>
  </div>
</template>

<style scoped>
.ass-border-t { border-top: 1px solid var(--ds-border); }
.ass-text { color: var(--ds-text); }
.ass-text-secondary { color: var(--ds-text-2); }
</style>
