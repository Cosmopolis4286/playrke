<!-- /components/common/CardHeader.vue -->
<template>
  <div class="card-header">
    <div class="d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center">
        <a v-if="returnBtn" href="/dashboard" title="Regresar" class="mb-n1 mr-2">
          <i class="fas fa-arrow-circle-left" style="font-size: 20px"></i>
        </a>
        <div>
          <div :class="subtitle ? 'mb-n3' : 'mb-n2'" class="d-flex">
            <h5 v-html="cardTitle || title"></h5>
            <a
              v-if="btnCardHeader?.route && btnCardHeader?.icon"
              :href="btnCardHeader.route"
              class="text-muted mb-n1 ml-2"
            >
              <i :class="btnCardHeader.icon"></i>
            </a>
          </div>
          <small v-if="subtitle">{{ subtitle }}</small>
        </div>
      </div>
      <div
        id="loader-sm"
        class="spinner-border spinner-border-sm text-primary"
        role="status"
        style="display: none"
      >
        <span class="sr-only">Loading...</span>
      </div>
    </div>
  </div>
</template>

<script setup>
  const props = defineProps({
    title: { type: String, default: '' },
    cardTitle: { type: String, default: null },
    subtitle: { type: String, default: null },
    returnBtn: { type: Boolean, default: true },
    btnCardHeader: { type: Object, default: null }
  })
</script>
