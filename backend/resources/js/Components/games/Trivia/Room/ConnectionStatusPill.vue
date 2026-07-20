<script setup lang="ts">
defineProps<{
  statusDot: string
  statusLabel: string
}>()
</script>

<template>
  <div class="fixed top-4 right-4 z-50 pointer-events-none">
    <div class="csp-pill inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full shadow-md border text-xs font-medium">
      <span :class="['w-2 h-2 rounded-full', statusDot]" />
      {{ statusLabel }}
    </div>
  </div>
</template>

<style scoped>
.csp-pill { background: var(--ds-surface); border-color: var(--ds-border); color: var(--ds-text-2); }
</style>
