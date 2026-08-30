<script setup lang="ts">
import Modal from '@/js/Components/Common/Modal/Modal.vue'
import type { FailedJob } from '@/js/composables/roles/superadmin/dashboard/useFailedJobs'
import { useLocalDateTime } from '@/js/composables/common/useLocalDateTime'
import { showToastConfirmAction } from '@/js/helpers/form/ui/crudModalUI'

const props = defineProps<{
  modelValue: boolean
  jobs: FailedJob[]
  loading: boolean
  retryingUuid: string | null
  retryingAll: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  retry: [uuid: string]
  'retry-all': []
  forget: [uuid: string]
}>()

const { describe: describeDateTime } = useLocalDateTime()

function failedAtLabel(job: FailedJob): string {
  const info = describeDateTime(job.failed_at, job.server_timezone)
  return info ? `${info.local} (${info.localAbbrev})` : job.failed_at
}

async function confirmForget(uuid: string): Promise<void> {
  const ok = await showToastConfirmAction(
    'Esta acción descarta el job fallido de forma permanente. No podrá reintentarse después.',
    'warning',
    'Sí, descartar'
  )
  if (ok) emit('forget', uuid)
}

async function confirmRetryAll(): Promise<void> {
  const ok = await showToastConfirmAction(
    `Se reintentarán ${props.jobs.length} job(s) fallido(s).`,
    'question',
    'Sí, reintentar todos'
  )
  if (ok) emit('retry-all')
}
</script>

<template>
  <Modal
    :model-value="modelValue"
    title="Jobs fallidos"
    icon="fa-triangle-exclamation"
    size="lg"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <div v-if="loading" class="fj-loading">
      <div class="spinner-border spinner-border-sm text-primary me-2"></div>
      Cargando jobs fallidos...
    </div>

    <div v-else-if="jobs.length === 0" class="fj-empty">
      <i class="fas fa-circle-check fj-empty-icon"></i>
      <p class="mb-0">No hay jobs fallidos pendientes.</p>
    </div>

    <div v-else class="fj-list">
      <div v-for="job in jobs" :key="job.uuid" class="fj-item">
        <div class="fj-item-main">
          <span class="fj-item-name">{{ job.job_name }}</span>
          <span class="fj-item-meta">{{ job.connection }} · {{ job.queue }} · {{ failedAtLabel(job) }}</span>
          <p class="fj-item-exception">{{ job.exception_summary }}</p>
        </div>
        <div class="fj-item-actions">
          <button
            class="fj-btn fj-btn--retry"
            :disabled="retryingUuid === job.uuid"
            @click="$emit('retry', job.uuid)"
          >
            <i class="fas fa-rotate-right" :class="{ 'fa-spin': retryingUuid === job.uuid }"></i>
            Reintentar
          </button>
          <button class="fj-btn fj-btn--forget" title="Descartar job" @click="confirmForget(job.uuid)">
            <i class="fas fa-trash"></i>
          </button>
        </div>
      </div>
    </div>

    <template #footer>
      <button type="button" class="fj-modal-btn fj-modal-btn--ghost" @click="$emit('update:modelValue', false)">Cerrar</button>
      <button
        v-if="jobs.length > 0"
        type="button"
        class="fj-modal-btn fj-modal-btn--primary"
        :disabled="retryingAll"
        @click="confirmRetryAll"
      >
        <i class="fas fa-rotate-right mr-1" :class="{ 'fa-spin': retryingAll }"></i>
        Reintentar todos ({{ jobs.length }})
      </button>
    </template>
  </Modal>
</template>

<style scoped>
.fj-loading, .fj-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 2.5rem 1rem;
  color: var(--ds-text-muted);
  font-size: 0.88rem;
}
.fj-empty-icon { font-size: 1.6rem; color: #34d399; }

.fj-list { display: flex; flex-direction: column; gap: 0.6rem; max-height: 55vh; overflow-y: auto; }

.fj-item {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.75rem 0.9rem;
  background: var(--ds-surface-2);
  border-radius: 8px;
}
.fj-item-main { min-width: 0; flex: 1; }
.fj-item-name { font-weight: 700; font-size: 0.88rem; color: var(--ds-text); }
.fj-item-meta { display: block; font-size: 0.74rem; color: var(--ds-text-muted); margin-top: 0.15rem; }
.fj-item-exception {
  font-family: monospace;
  font-size: 0.76rem;
  color: #f87171;
  margin: 0.4rem 0 0;
  overflow-wrap: break-word;
}

.fj-item-actions { display: flex; align-items: center; gap: 0.4rem; flex-shrink: 0; }
.fj-btn {
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
.fj-btn--retry { background: rgba(102,126,234,.15); color: #667eea; }
.fj-btn--retry:disabled { opacity: 0.6; cursor: not-allowed; }
.fj-btn--forget { background: rgba(220,38,38,.12); color: #f87171; padding: 0.35rem 0.55rem; }

/**
 * Botones locales (no .modal-btn de Modal.vue): los estilos scoped de
 * Modal.vue no alcanzan el contenido de sus slots (#footer se renderiza con
 * el atributo data-v-xxxx de ESTE componente, no el de Modal.vue) — mismo
 * patrón que UserDetailModal.vue (.ud-close-btn).
 */
.fj-modal-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 9px 22px;
  border-radius: 10px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: all 0.15s;
  line-height: 1.4;
}
.fj-modal-btn--ghost {
  background: transparent;
  color: var(--ds-text-2);
  border: 1.5px solid var(--ds-border-2);
}
.fj-modal-btn--ghost:hover { background: var(--ds-surface-2); }
.fj-modal-btn--primary {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: #fff;
  box-shadow: 0 2px 8px rgba(102,126,234,0.3);
}
.fj-modal-btn--primary:hover:not(:disabled) { filter: brightness(1.08); transform: translateY(-1px); }
.fj-modal-btn--primary:disabled { opacity: 0.55; cursor: not-allowed; transform: none; }
</style>
