<script setup lang="ts">
defineProps<{
  statusDot: string
  statusLabel: string
  playerCount: number
}>()
</script>

<template>
  <div class="js-panel rounded-2xl shadow-sm border overflow-hidden">
    <!-- Top accent -->
    <div class="h-1 bg-gradient-to-r from-emerald-400 to-teal-500" />

    <div class="p-6 text-center space-y-4">
      <!-- Check icon -->
      <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center">
        <svg class="w-7 h-7 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
        </svg>
      </div>

      <div>
        <h3 class="text-base font-bold js-text">¡Ya estás dentro!</h3>
        <p class="text-xs js-text-muted mt-1">
          Esperando que el anfitrión inicie el juego
        </p>
      </div>

      <!-- Connection indicator -->
      <div class="flex items-center justify-center gap-2 text-xs">
        <span :class="['w-2 h-2 rounded-full', statusDot]" />
        <span class="js-text-muted">{{ statusLabel }}</span>
      </div>

      <!-- Player count pill -->
      <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 text-xs font-semibold">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        {{ playerCount }} jugador{{ playerCount !== 1 ? 'es' : '' }} en sala
      </div>
    </div>
  </div>
</template>

<style scoped>
.js-panel { background: var(--ds-surface); border-color: var(--ds-border); }
.js-text { color: var(--ds-text); }
.js-text-muted { color: var(--ds-text-muted); }
</style>
