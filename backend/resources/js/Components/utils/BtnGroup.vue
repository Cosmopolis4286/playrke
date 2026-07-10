<template>
  <div class="btn-group" role="group">
    <button
      v-for="btn in buttons"
      :key="btn.action ?? btn.label"
      class="btn btn-sm"
      :class="btn.class ?? 'btn-outline-secondary'"
      @click="$emit('action', { action: btn.action, id: dataId })"
    >
      <i v-if="btn.icon" :class="btn.icon" />
      {{ btn.label }}
    </button>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  buttons: Array<{ label?: string; icon?: string; action?: string; class?: string }>
  name?: string
  dataId?: string | number
}>()

defineEmits<{ (e: 'action', payload: { action?: string; id?: string | number }): void }>()
</script>
