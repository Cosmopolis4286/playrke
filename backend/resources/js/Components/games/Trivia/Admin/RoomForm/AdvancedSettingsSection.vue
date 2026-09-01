<script setup lang="ts">
defineProps<{
  allowLateJoin: boolean
  showLeaderboard: boolean
  publicRoom: boolean
  allowPowerups: boolean
  pauseDisconnectThresholdPercent: number
}>()

defineEmits<{
  'update:allowLateJoin': [value: boolean]
  'update:showLeaderboard': [value: boolean]
  'update:publicRoom': [value: boolean]
  'update:allowPowerups': [value: boolean]
  'update:pauseDisconnectThresholdPercent': [value: number]
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

      <div>
        <label class="flex items-center gap-2">
          <span class="text-sm ass-text-secondary">
            Pausar automáticamente si se desconecta el
          </span>
          <input
            :value="pauseDisconnectThresholdPercent"
            type="number"
            min="0"
            max="100"
            step="5"
            class="w-20 rounded border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500"
            @input="$emit('update:pauseDisconnectThresholdPercent', Number(($event.target as HTMLInputElement).value))"
          />
          <span class="text-sm ass-text-secondary">% o más de los jugadores</span>
        </label>
        <p class="text-xs ass-text-secondary mt-1 ml-0.5">
          0 = desactivado. Se evalúa tras cada pregunta sin respuesta de un jugador durante 2 rondas seguidas.
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.ass-border-t { border-top: 1px solid var(--ds-border); }
.ass-text { color: var(--ds-text); }
.ass-text-secondary { color: var(--ds-text-2); }
</style>
