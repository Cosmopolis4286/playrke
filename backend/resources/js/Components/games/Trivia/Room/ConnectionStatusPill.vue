<script setup lang="ts">
defineProps<{
  statusDot: string
  statusLabel: string
}>()
</script>

<template>
  <div class="fixed top-4 right-4 z-50 pointer-events-none">
    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white dark:bg-gray-800 shadow-md border border-gray-100 dark:border-gray-700 text-xs font-medium text-gray-600 dark:text-gray-300">
      <span :class="['w-2 h-2 rounded-full', statusDot]" />
      {{ statusLabel }}
    </div>
  </div>
</template>
