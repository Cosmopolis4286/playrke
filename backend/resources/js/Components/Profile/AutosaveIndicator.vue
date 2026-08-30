<template>
  <Transition name="autosave-fade">
    <i v-if="saving" class="fas fa-spinner fa-spin autosave-spinner" title="Guardando..." />
  </Transition>
</template>

<script setup lang="ts">
defineProps<{ saving: boolean }>()
</script>

<style scoped>
.autosave-spinner { font-size: 0.7rem; color: #667eea; flex-shrink: 0; }
.autosave-fade-enter-active, .autosave-fade-leave-active { transition: opacity 0.15s; }
.autosave-fade-enter-from, .autosave-fade-leave-to { opacity: 0; }
</style>
