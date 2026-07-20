<script setup lang="ts">
import type { Room } from '@js/types/trivia'

defineProps<{
  show: boolean
  room: Room
  userWallet?: { balance: number; symbol: string; currencyId: string; found: boolean } | null
  hasInsufficientFunds: boolean
  isLoading: boolean
}>()

defineEmits<{
  close: []
  confirm: []
}>()
</script>

<template>
  <Transition name="fade">
    <div
      v-if="show"
      class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4"
      @click.self="$emit('close')"
    >
      <div class="jcm-panel rounded-2xl shadow-2xl max-w-sm w-full overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-4">
          <h3 class="text-lg font-bold text-white text-center">Confirmar entrada</h3>
        </div>

        <div class="p-6 space-y-4">
          <!-- Detalles de la sala -->
          <div class="text-center space-y-1">
            <p class="text-sm jcm-text-muted">Sala</p>
            <p class="font-semibold jcm-text">{{ room.title }}</p>
            <code class="text-xs text-gray-400">{{ room.code }}</code>
          </div>

          <!-- Detalle del costo -->
          <div class="bg-violet-50 dark:bg-violet-900/20 rounded-xl p-4 space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-violet-700 dark:text-violet-300">Costo de entrada:</span>
              <span class="font-bold text-violet-800 dark:text-violet-200">
                {{ room.entryCurrencySymbol || '' }} {{ room.entryFee || 0 }}
              </span>
            </div>

            <div v-if="userWallet && userWallet.found" class="border-t border-violet-200 dark:border-violet-700 pt-3 space-y-1">
              <div class="flex items-center justify-between text-sm">
                <span class="jcm-text-muted">Tu balance:</span>
                <span class="font-semibold jcm-text-secondary">
                  {{ userWallet.symbol }} {{ userWallet.balance.toFixed(2) }}
                </span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="jcm-text-muted">Balance después:</span>
                <span :class="['font-bold', (userWallet.balance - (room.entryFee || 0)) < 0 ? 'text-red-500' : 'text-emerald-600 dark:text-emerald-400']">
                  {{ userWallet.symbol }} {{ (userWallet.balance - (room.entryFee || 0)).toFixed(2) }}
                </span>
              </div>
            </div>

            <div v-else class="border-t border-violet-200 dark:border-violet-700 pt-2">
              <p class="text-xs text-amber-600 dark:text-amber-400">⚠️ No se encontró wallet para esta moneda</p>
            </div>
          </div>

          <!-- Alerta de saldo insuficiente -->
          <div v-if="hasInsufficientFunds" class="bg-red-50 dark:bg-red-900/20 rounded-lg p-3 text-center">
            <p class="text-sm font-semibold text-red-600 dark:text-red-400">Saldo insuficiente</p>
            <p class="text-xs text-red-500 dark:text-red-400 mt-1">
              Necesitas {{ userWallet?.symbol }} {{ ((room.entryFee || 0) - (userWallet?.balance || 0)).toFixed(2) }} más para unirte
            </p>
          </div>

          <!-- Botones -->
          <div class="flex gap-3 pt-2">
            <button
              @click="$emit('close')"
              class="jcm-cancel-btn flex-1 py-2.5 px-4 rounded-xl font-medium transition-colors"
            >
              Cancelar
            </button>
            <button
              @click="$emit('confirm')"
              :disabled="hasInsufficientFunds || isLoading"
              class="flex-1 py-2.5 px-4 rounded-xl font-semibold text-white transition-colors flex items-center justify-center gap-2"
              :class="hasInsufficientFunds ? 'bg-red-400 cursor-not-allowed' : 'bg-indigo-600 hover:bg-indigo-700'"
            >
              <span v-if="isLoading" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                </svg>
                Procesando...
              </span>
              <span v-else>Confirmar y unirme</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.jcm-panel { background: var(--ds-surface); }
.jcm-text { color: var(--ds-text); }
.jcm-text-secondary { color: var(--ds-text-2); }
.jcm-text-muted { color: var(--ds-text-muted); }
.jcm-cancel-btn { color: var(--ds-text-2); background: var(--ds-surface-2); }
.jcm-cancel-btn:hover { filter: brightness(0.95); }
</style>
