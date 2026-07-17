<script setup lang="ts">
defineProps<{
  questionCount: number
  timePerQuestion: number
}>()
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 space-y-3">
    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Cómo funciona</p>
    <ul class="space-y-2.5">
      <li class="flex items-start gap-2.5">
        <span class="w-5 h-5 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">1</span>
        <span class="text-xs text-gray-600 dark:text-gray-300">Únete a la sala con tu nombre</span>
      </li>
      <li class="flex items-start gap-2.5">
        <span class="w-5 h-5 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">2</span>
        <span class="text-xs text-gray-600 dark:text-gray-300">Espera a que el anfitrión inicie el juego</span>
      </li>
      <li class="flex items-start gap-2.5">
        <span class="w-5 h-5 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">3</span>
        <span class="text-xs text-gray-600 dark:text-gray-300">Responde {{ questionCount }} preguntas en {{ timePerQuestion }}s cada una</span>
      </li>
    </ul>
  </div>
</template>
