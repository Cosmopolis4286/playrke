<template>
  <div class="toast-detail-body">
    <strong class="toast-detail-title">{{ title }}</strong>
    <p v-if="detail" class="toast-detail-text">{{ detail }}</p>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  title: string
  detail?: string | null
}>()
</script>

<style scoped>
.toast-detail-body { display: flex; flex-direction: column; gap: 2px; }
.toast-detail-title { font-size: 0.92rem; line-height: 1.3; }
.toast-detail-text {
  font-size: 0.8rem;
  line-height: 1.35;
  opacity: 0.85;
  margin: 0;
  word-break: break-word;
}
</style>
