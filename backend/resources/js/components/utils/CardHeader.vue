<template>
  <div class="card-header d-flex align-items-center justify-content-between">
    <div>
      <h5 v-if="title" class="mb-0">{{ title }}</h5>
      <small v-if="subtitle" class="text-muted">{{ subtitle }}</small>
    </div>
    <a
      v-if="btnCardHeader"
      :href="btnCardHeader.route"
      class="btn btn-sm btn-outline-secondary"
    >
      <i v-if="btnCardHeader.icon" :class="btnCardHeader.icon" />
    </a>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  title?: string
  subtitle?: string
  btnCardHeader?: { route: string; icon?: string }
}>()
</script>
