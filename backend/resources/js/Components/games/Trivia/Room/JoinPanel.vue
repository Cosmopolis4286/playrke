<script setup lang="ts">
import type { Room } from '@js/types/trivia'

defineProps<{
  room: Room
  authName: string
  userWallet?: { balance: number; symbol: string; currencyId: string; found: boolean } | null
  feeWithoutCurrency: boolean
  hasInsufficientFunds: boolean
  walletNotFound: boolean
}>()

defineEmits<{
  join: []
}>()
</script>

<template>
  <div class="jp-panel rounded-2xl shadow-sm border overflow-hidden">
    <div class="h-1 bg-gradient-to-r from-indigo-400 to-purple-500" />
    <div class="p-6 space-y-4">
      <div class="text-center">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center">
          <svg class="w-7 h-7 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
        </div>
        <h3 class="text-base font-bold jp-text mt-3">¿Listo para jugar?</h3>
        <p class="text-xs jp-text-muted mt-1">Entrarás como <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ authName }}</span></p>
      </div>

      <!-- Info de costo si aplica -->
      <div v-if="room.entryFee && room.entryFee > 0" class="rounded-xl p-3 space-y-1.5"
        :class="feeWithoutCurrency ? 'bg-orange-50 dark:bg-orange-900/20' : 'bg-violet-50 dark:bg-violet-900/20'">
        <div class="flex items-center justify-between text-sm">
          <span :class="feeWithoutCurrency ? 'text-orange-700 dark:text-orange-300' : 'text-violet-700 dark:text-violet-300'">
            Costo de entrada:
          </span>
          <span :class="['font-bold', feeWithoutCurrency ? 'text-orange-800 dark:text-orange-200' : 'text-violet-800 dark:text-violet-200']">
            {{ room.entryCurrencySymbol || '' }} {{ room.entryFee }}
          </span>
        </div>
        <!-- Sala mal configurada: tiene fee pero sin moneda -->
        <div v-if="feeWithoutCurrency" class="flex items-center gap-1.5 text-xs text-orange-600 dark:text-orange-400">
          <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          La sala no tiene moneda configurada — contacta al administrador
        </div>
        <!-- Balance del usuario -->
        <div v-else-if="userWallet && userWallet.found" class="flex items-center justify-between text-xs">
          <span class="jp-text-muted">Tu balance:</span>
          <span :class="['font-semibold', hasInsufficientFunds ? 'text-red-500' : 'text-emerald-600 dark:text-emerald-400']">
            {{ userWallet.symbol }} {{ userWallet.balance.toFixed(2) }}
          </span>
        </div>
        <!-- Usuario sin billetera en esa moneda -->
        <div v-else-if="walletNotFound" class="flex items-center gap-1.5 text-xs text-amber-600 dark:text-amber-400">
          <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          No tienes billetera en <span class="font-bold ml-0.5">{{ room.entryCurrencySymbol || 'esta moneda' }}</span>
        </div>
      </div>
      <div v-else class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl p-3 text-center">
        <span class="text-sm font-medium text-emerald-700 dark:text-emerald-300">✅ Entrada gratuita</span>
      </div>

      <!-- Botón de unión -->
      <button
        @click="$emit('join')"
        :disabled="hasInsufficientFunds"
        class="w-full py-3 px-4 rounded-xl font-semibold text-white transition-colors flex items-center justify-center gap-2"
        :class="hasInsufficientFunds ? 'bg-red-400 cursor-not-allowed' : 'bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800'"
      >
        <template v-if="hasInsufficientFunds">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          Saldo insuficiente
        </template>
        <template v-else>
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
          </svg>
          Unirme al juego
        </template>
      </button>
    </div>
  </div>
</template>

<style scoped>
.jp-panel { background: var(--ds-surface); border-color: var(--ds-border); }
.jp-text { color: var(--ds-text); }
.jp-text-muted { color: var(--ds-text-muted); }
</style>
