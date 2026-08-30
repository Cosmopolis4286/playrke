<script setup lang="ts">
import Modal from '@/js/Components/Common/Modal/Modal.vue'
import type { ScheduledCommandHistoryEntry } from '@/js/composables/system/jobs/useScheduledCommandHistory'
import type { ScheduledCommand } from '@/js/types/system/jobs'
import { useLocalDateTime } from '@/js/composables/common/useLocalDateTime'

defineProps<{
  modelValue: boolean
  command: ScheduledCommand | null
  entries: ScheduledCommandHistoryEntry[]
  loading: boolean
}>()

defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const { describe: describeDateTime } = useLocalDateTime()

function occurredAtLabel(entry: ScheduledCommandHistoryEntry): string {
  const info = describeDateTime(entry.occurred_at, entry.server_timezone)
  return info ? `${info.local} (${info.localAbbrev})` : entry.occurred_at
}

const TYPE_ICON: Record<ScheduledCommandHistoryEntry['type'], string> = {
  execution:         'fa-play',
  toggle:            'fa-power-off',
  frequency_change:  'fa-clock',
  other_change:      'fa-pen',
}

function typeLabel(entry: ScheduledCommandHistoryEntry): string {
  if (entry.type === 'execution') {
    return entry.status === 'success' ? 'Éxito' : 'Error'
  }
  return entry.detail ?? 'Cambio de configuración'
}

function originLabel(entry: ScheduledCommandHistoryEntry): string {
  if (entry.type === 'execution') {
    return entry.triggered_by === 'manual'
      ? `Ejecución manual${entry.actor ? ` — ${entry.actor}` : ''}`
      : 'Ejecución programada'
  }
  return entry.actor ? `Por ${entry.actor}` : ''
}
</script>

<template>
  <Modal
    :model-value="modelValue"
    :title="command ? `Historial — ${command.label ?? command.command}` : 'Historial'"
    icon="fa-clock-rotate-left"
    size="lg"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <p v-if="command" class="schm-command">{{ command.command }}</p>

    <div v-if="loading" class="schm-loading">
      <div class="spinner-border spinner-border-sm text-primary me-2"></div>
      Cargando historial...
    </div>

    <div v-else-if="entries.length === 0" class="schm-empty">
      <i class="fas fa-inbox schm-empty-icon"></i>
      <p class="mb-0">Sin eventos registrados.</p>
    </div>

    <ul v-else class="schm-list">
      <li v-for="(entry, idx) in entries" :key="idx" class="schm-item" :class="{ 'schm-item--state': entry.type !== 'execution' }">
        <div class="schm-item-header">
          <span
            class="schm-item-status"
            :class="entry.type === 'execution' ? `schm-item-status--${entry.status}` : 'schm-item-status--state'"
          >
            <i class="fas" :class="entry.type === 'execution' ? (entry.status === 'success' ? 'fa-circle-check' : 'fa-circle-xmark') : TYPE_ICON[entry.type]"></i>
            {{ typeLabel(entry) }}
          </span>
          <span class="schm-item-date">{{ occurredAtLabel(entry) }}</span>
        </div>
        <p v-if="originLabel(entry)" class="schm-item-origin">{{ originLabel(entry) }}</p>
        <pre v-if="entry.output" class="schm-item-output" :class="{ 'schm-item-output--error': entry.status === 'error' }">{{ entry.output }}</pre>
      </li>
    </ul>

    <template #footer>
      <button type="button" class="schm-btn schm-btn--ghost" @click="$emit('update:modelValue', false)">Cerrar</button>
    </template>
  </Modal>
</template>

<style scoped>
.schm-command { font-family: monospace; font-size: 0.8rem; color: var(--ds-text-muted); margin: -0.5rem 0 1rem; }

.schm-loading, .schm-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 2rem 1rem;
  color: var(--ds-text-muted);
  font-size: 0.88rem;
}
.schm-empty-icon { font-size: 1.6rem; opacity: 0.5; }

.schm-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.6rem; max-height: 55vh; overflow-y: auto; }
.schm-item { background: var(--ds-surface-2); border-radius: 8px; padding: 0.65rem 0.85rem; }
.schm-item-header { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; }
.schm-item-status { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.8rem; font-weight: 600; }
.schm-item-status--success { color: #34d399; }
.schm-item-status--error { color: #f87171; }
.schm-item-status--state { color: #a78bfa; }
.schm-item-date { font-size: 0.76rem; color: var(--ds-text-muted); }
.schm-item-origin { font-size: 0.72rem; color: var(--ds-text-muted); margin: 0.3rem 0 0; }
.schm-item--state { background: rgba(167, 139, 250, 0.07); }
.schm-item-output {
  font-family: monospace;
  font-size: 0.74rem;
  color: var(--ds-text-2);
  background: var(--ds-surface);
  border-radius: 6px;
  padding: 0.5rem 0.65rem;
  margin: 0.5rem 0 0;
  white-space: pre-wrap;
  word-break: break-word;
  max-height: 180px;
  overflow-y: auto;
}
.schm-item-output--error { color: #fca5a5; }

.schm-btn {
  display: inline-flex;
  align-items: center;
  padding: 9px 22px;
  border-radius: 10px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  border: none;
}
.schm-btn--ghost {
  background: transparent;
  color: var(--ds-text-2);
  border: 1.5px solid var(--ds-border-2);
}
.schm-btn--ghost:hover { background: var(--ds-surface-2); }
</style>
