<script setup lang="ts">
  import { CardHeaderPageProps } from '@/js/types/common/card/card'

  /**
   * Props tipadas correctamente para Inertia
   */
  const props = withDefaults(defineProps<CardHeaderPageProps>(), {
    returnBtn: true
  })
</script>

<template>
  <div class="card-header">
    <div class="d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center">
        <a v-if="returnBtn" href="/dashboard" title="Regresar" class="mb-n1 mr-2">
          <i class="fas fa-arrow-circle-left" style="font-size: 20px"></i>
        </a>
        <div>
          <div class="d-flex">
            <h5 v-html="title"></h5>
            <a
              v-if="btnCardHeader?.route && btnCardHeader?.icon"
              :href="btnCardHeader.route"
              class="text-muted mb-n1 ml-2"
            >
              <i :class="btnCardHeader.icon"></i>
            </a>
          </div>
          <h6 v-if="subtitle" class="mt-n2 mb-n1" style="font-size: 13px;">{{ subtitle }}</h6>
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
