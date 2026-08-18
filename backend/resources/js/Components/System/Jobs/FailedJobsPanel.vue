<script setup lang="ts">
import type { FailedJob } from '@/js/composables/roles/superadmin/dashboard/useFailedJobs'

defineProps<{
  jobs: FailedJob[]
  loading: boolean
  retryingUuid: string | null
  retryingAll: boolean
}>()

const emit = defineEmits<{
  retry: [uuid: string]
  'retry-all': []
  forget: [uuid: string]
}>()
</script>

<template>
  <div class="fjp-card">
    <div class="fjp-header">
      <div>
        <h4 class="fjp-title"><i class="fas fa-triangle-exclamation mr-2"></i>Jobs fallidos</h4>
        <p class="fjp-subtitle">Jobs que agotaron sus reintentos automáticos</p>
      </div>
      <button
        v-if="jobs.length > 0"
        class="fjp-btn fjp-btn--primary"
        :disabled="retryingAll"
        @click="$emit('retry-all')"
      >
        <i class="fas fa-rotate-right mr-1" :class="{ 'fa-spin': retryingAll }"></i>
        Reintentar todos ({{ jobs.length }})
      </button>
    </div>

    <div v-if="loading" class="fjp-loading">
      <div class="spinner-border spinner-border-sm text-primary me-2"></div>
      Cargando jobs fallidos...
    </div>

    <div v-else-if="jobs.length === 0" class="fjp-empty">
      <i class="fas fa-circle-check fjp-empty-icon"></i>
      <p class="mb-0">No hay jobs fallidos pendientes.</p>
    </div>

    <div v-else class="fjp-list">
      <div v-for="job in jobs" :key="job.uuid" class="fjp-item">
        <div class="fjp-item-main">
          <span class="fjp-item-name">{{ job.job_name }}</span>
          <span class="fjp-item-meta">{{ job.connection }} · {{ job.queue }} · {{ new Date(job.failed_at).toLocaleString('es-VE') }}</span>
          <p class="fjp-item-exception">{{ job.exception_summary }}</p>
        </div>
        <div class="fjp-item-actions">
          <button
            class="fjp-btn fjp-btn--retry"
            :disabled="retryingUuid === job.uuid"
            @click="$emit('retry', job.uuid)"
          >
            <i class="fas fa-rotate-right" :class="{ 'fa-spin': retryingUuid === job.uuid }"></i>
            Reintentar
          </button>
          <button class="fjp-btn fjp-btn--forget" @click="$emit('forget', job.uuid)">
            <i class="fas fa-trash"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fjp-card {
  background: var(--ds-surface);
  border: 1px solid var(--ds-border);
  border-radius: 12px;
  padding: 1.1rem 1.3rem;
}
.fjp-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.9rem; }
.fjp-title { font-size: 0.95rem; font-weight: 700; color: var(--ds-text); margin: 0; }
.fjp-subtitle { font-size: 0.78rem; color: var(--ds-text-muted); margin: 0.2rem 0 0; }

.fjp-loading, .fjp-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 2rem 1rem;
  color: var(--ds-text-muted);
  font-size: 0.88rem;
}
.fjp-empty-icon { font-size: 1.6rem; color: #34d399; }

.fjp-list { display: flex; flex-direction: column; gap: 0.6rem; max-height: 45vh; overflow-y: auto; }

.fjp-item {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.75rem 0.9rem;
  background: var(--ds-surface-2);
  border-radius: 8px;
}
.fjp-item-main { min-width: 0; flex: 1; }
.fjp-item-name { font-weight: 700; font-size: 0.88rem; color: var(--ds-text); }
.fjp-item-meta { display: block; font-size: 0.74rem; color: var(--ds-text-muted); margin-top: 0.15rem; }
.fjp-item-exception {
  font-family: monospace;
  font-size: 0.76rem;
  color: #f87171;
  margin: 0.4rem 0 0;
  overflow-wrap: break-word;
}

.fjp-item-actions { display: flex; align-items: center; gap: 0.4rem; flex-shrink: 0; }
.fjp-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.35rem 0.7rem;
  border-radius: 6px;
  border: none;
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
}
.fjp-btn--retry { background: rgba(102,126,234,.15); color: #667eea; }
.fjp-btn--retry:disabled { opacity: 0.6; cursor: not-allowed; }
.fjp-btn--forget { background: rgba(220,38,38,.12); color: #f87171; padding: 0.35rem 0.55rem; }
.fjp-btn--primary {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: #fff;
  padding: 0.45rem 0.9rem;
}
.fjp-btn--primary:disabled { opacity: 0.55; cursor: not-allowed; }
</style>
