<script setup lang="ts">
import type { PrizePayout } from '@js/composables/games/trivia/admin/useTriviaPrizePayouts'

defineProps<{
  prizeModel: 'guaranteed' | 'pool'
  sponsorPrize: number
  platformRakePercent: number
  prizePayouts: PrizePayout[]
  sponsorPrizeError?: string
  sponsorPrizeServerError?: string
  platformRakeError?: string
  platformRakeServerError?: string
  prizePayoutsError?: string
  prizePayoutsServerError?: string
  disabled?: boolean
}>()

defineEmits<{
  'update:prizeModel': [value: 'guaranteed' | 'pool']
  'update:sponsorPrize': [value: number]
  'update:platformRakePercent': [value: number]
  'add-payout': []
  'remove-payout': [index: number]
}>()
</script>

<template>
  <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">
      🏆 Configuración de Premios
    </h3>
    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
      Define cómo se reparte el premio final entre los ganadores.
    </p>

    <div class="space-y-4">
      <!-- Modelo de premio -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
          Modelo de premio
        </label>
        <div class="flex gap-4 text-sm">
          <label class="flex items-center gap-1.5 cursor-pointer">
            <input
              :checked="prizeModel === 'guaranteed'"
              type="radio"
              value="guaranteed"
              :disabled="disabled"
              class="text-indigo-600"
              @change="$emit('update:prizeModel', 'guaranteed')"
            />
            <span class="text-gray-700 dark:text-gray-300">Garantizado</span>
          </label>
          <label class="flex items-center gap-1.5 cursor-pointer">
            <input
              :checked="prizeModel === 'pool'"
              type="radio"
              value="pool"
              :disabled="disabled"
              class="text-indigo-600"
              @change="$emit('update:prizeModel', 'pool')"
            />
            <span class="text-gray-700 dark:text-gray-300">Pozo (entradas + sponsor)</span>
          </label>
        </div>
        <p class="mt-1.5 text-xs text-gray-400">
          <strong>Garantizado:</strong> se reparte el monto del premio + sponsor, sin importar cuántos jugadores haya.
          <strong>Pozo:</strong> el premio se forma con las entradas pagadas + sponsor, menos la comisión de la plataforma.
        </p>
      </div>

      <!-- Aporte de sponsor -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
          Aporte de sponsor (opcional)
        </label>
        <input
          :value="sponsorPrize"
          type="number"
          min="0"
          step="0.01"
          :disabled="disabled"
          class="mt-1 block w-40 rounded-md dark:bg-gray-900 dark:text-gray-300 focus:ring-indigo-500 sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
          :class="(sponsorPrizeError || sponsorPrizeServerError) ? 'border-red-300 dark:border-red-700 focus:border-red-500' : 'border-gray-300 dark:border-gray-700 focus:border-indigo-500'"
          placeholder="0"
          @input="$emit('update:sponsorPrize', Number(($event.target as HTMLInputElement).value))"
        />
        <p v-if="sponsorPrizeError" class="mt-1 text-sm text-red-600">{{ sponsorPrizeError }}</p>
        <p v-else-if="sponsorPrizeServerError" class="mt-1 text-sm text-red-600">{{ sponsorPrizeServerError }}</p>
        <p v-else class="mt-1 text-xs text-gray-400">Monto adicional aportado por un sponsor, en la moneda del premio.</p>
      </div>

      <!-- Comisión de la plataforma (solo modelo pool) -->
      <div v-if="prizeModel === 'pool'">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
          Comisión de la plataforma (%)
        </label>
        <input
          :value="platformRakePercent"
          type="number"
          min="0"
          max="30"
          step="0.01"
          :disabled="disabled"
          class="mt-1 block w-32 rounded-md dark:bg-gray-900 dark:text-gray-300 focus:ring-indigo-500 sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
          :class="(platformRakeError || platformRakeServerError) ? 'border-red-300 dark:border-red-700 focus:border-red-500' : 'border-gray-300 dark:border-gray-700 focus:border-indigo-500'"
          placeholder="0"
          @input="$emit('update:platformRakePercent', Number(($event.target as HTMLInputElement).value))"
        />
        <p v-if="platformRakeError" class="mt-1 text-sm text-red-600">{{ platformRakeError }}</p>
        <p v-else-if="platformRakeServerError" class="mt-1 text-sm text-red-600">{{ platformRakeServerError }}</p>
        <p v-else class="mt-1 text-xs text-gray-400">Porcentaje del pozo que retiene la plataforma (máx. 30%).</p>
      </div>

      <!-- Distribución por posición (solo modelo pool) -->
      <div v-if="prizeModel === 'pool'">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Distribución por posición
        </label>
        <div class="space-y-2">
          <div v-for="(payout, index) in prizePayouts" :key="index" class="flex items-center gap-2">
            <span class="text-sm text-gray-500 dark:text-gray-400 w-16">Puesto</span>
            <input
              v-model.number="payout.rank"
              type="number"
              min="1"
              :disabled="disabled"
              class="w-20 rounded-md dark:bg-gray-900 dark:text-gray-300 border-gray-300 dark:border-gray-700 focus:ring-indigo-500 sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
            />
            <span class="text-sm text-gray-500 dark:text-gray-400">→</span>
            <input
              :value="Math.round(payout.percent * 100)"
              @input="payout.percent = Math.max(0, Number(($event.target as HTMLInputElement).value)) / 100"
              type="number"
              min="0"
              max="100"
              step="1"
              :disabled="disabled"
              class="w-20 rounded-md dark:bg-gray-900 dark:text-gray-300 border-gray-300 dark:border-gray-700 focus:ring-indigo-500 sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
            />
            <span class="text-sm text-gray-500 dark:text-gray-400">%</span>
            <button
              type="button"
              :disabled="disabled"
              @click="$emit('remove-payout', index)"
              class="ml-auto text-xs text-red-600 hover:underline disabled:opacity-40 disabled:cursor-not-allowed"
            >
              Quitar
            </button>
          </div>
        </div>
        <button
          type="button"
          :disabled="disabled"
          @click="$emit('add-payout')"
          class="mt-2 text-xs text-indigo-600 dark:text-indigo-400 hover:underline disabled:opacity-40 disabled:cursor-not-allowed"
        >
          + Añadir posición
        </button>
        <p v-if="prizePayoutsError" class="mt-1 text-sm text-red-600">{{ prizePayoutsError }}</p>
        <p v-else-if="prizePayoutsServerError" class="mt-1 text-sm text-red-600">{{ prizePayoutsServerError }}</p>
        <p v-else class="mt-1.5 text-xs text-gray-400">La suma de los porcentajes debe ser 100%. Por defecto: 1° 60%, 2° 25%, 3° 15%.</p>
      </div>
    </div>
  </div>
</template>
