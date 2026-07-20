<script setup lang="ts">
defineProps<{
  questionCount: number
  timePerQuestion: number
}>()
</script>

<template>
  <div class="rib-panel rounded-2xl shadow-sm border p-4 space-y-3">
    <p class="text-xs font-semibold rib-text-muted uppercase tracking-wide">Cómo funciona</p>
    <ul class="space-y-2.5">
      <li class="flex items-start gap-2.5">
        <span class="w-5 h-5 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">1</span>
        <span class="text-xs rib-text-secondary">Únete a la sala con tu nombre</span>
      </li>
      <li class="flex items-start gap-2.5">
        <span class="w-5 h-5 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">2</span>
        <span class="text-xs rib-text-secondary">Espera a que el anfitrión inicie el juego</span>
      </li>
      <li class="flex items-start gap-2.5">
        <span class="w-5 h-5 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">3</span>
        <span class="text-xs rib-text-secondary">Responde {{ questionCount }} preguntas en {{ timePerQuestion }}s cada una</span>
      </li>
    </ul>
  </div>
</template>

<style scoped>
.rib-panel { background: var(--ds-surface); border-color: var(--ds-border); }
.rib-text-muted { color: var(--ds-text-muted); }
.rib-text-secondary { color: var(--ds-text-2); }
</style>
