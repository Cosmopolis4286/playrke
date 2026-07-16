<script setup lang="ts">
defineProps<{
  devResetting: boolean
}>()

defineEmits<{
  devReset: []
}>()
</script>

<template>
  <div class="gh-dev">
    <span class="gh-dev__label"><i class="fas fa-code" /> DEV</span>
    <button class="gh-dev__btn" :disabled="devResetting" @click="$emit('devReset')">
      <span v-if="devResetting" class="gh-dev__spin" />
      <i v-else class="fas fa-play" />
      {{ devResetting ? 'Iniciando…' : 'Iniciar sorteo de prueba' }}
    </button>
  </div>
</template>

<style scoped>
.gh-dev {
  display: flex; align-items: center; gap: 10px;
  padding: 6px 18px 8px;
  border-top: 1px dashed rgba(255,171,0,0.16);
  background: rgba(255,171,0,0.025);
}
.gh-dev__label {
  font-size: 7.5px; font-weight: 800; letter-spacing: 1.2px;
  color: rgba(255,171,0,0.4); text-transform: uppercase;
  display: flex; align-items: center; gap: 4px; flex-shrink: 0;
}
.gh-dev__btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 5px 14px; border-radius: 8px;
  background: rgba(255,171,0,0.09); border: 1px solid rgba(255,171,0,0.26);
  color: #ffd060; font-size: 11px; font-weight: 700; cursor: pointer;
  transition: background 0.18s, border-color 0.18s, color 0.18s;
}
.gh-dev__btn:hover:not(:disabled) { background: rgba(255,171,0,0.18); border-color: rgba(255,171,0,0.48); color: #ffe090; }
.gh-dev__btn:disabled { opacity: 0.45; cursor: not-allowed; }
.gh-dev__btn i { font-size: 9px; }
.gh-dev__spin {
  width: 10px; height: 10px; border-radius: 50%;
  border: 1.5px solid rgba(255,171,0,0.28); border-top-color: #ffd060;
  animation: dev-spin 0.7s linear infinite;
}
@keyframes dev-spin { to { transform: rotate(360deg) } }
</style>
