<script setup lang="ts">
import type { ScheduledCommand } from '@/js/types/system/jobs'

defineProps<{
  commands: ScheduledCommand[]
  loading: boolean
  togglingId: string | null
}>()

const emit = defineEmits<{
  toggle: [command: ScheduledCommand, enabled: boolean]
}>()
</script>

<template>
  <div class="sct-card">
    <div class="sct-header">
      <h4 class="sct-title"><i class="fas fa-clock mr-2"></i>Comandos programados</h4>
      <p class="sct-subtitle">Corren automáticamente según su frecuencia — desactivar uno detiene solo su ejecución automática, no impide invocarlo manualmente.</p>
    </div>

    <div v-if="loading" class="sct-loading">
      <div class="spinner-border spinner-border-sm text-primary me-2"></div>
      Cargando comandos programados...
    </div>

    <div v-else class="sct-table-wrap">
      <table class="sct-table">
        <thead>
          <tr>
            <th>Comando</th>
            <th>Frecuencia</th>
            <th>Última ejecución</th>
            <th>Estado</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="cmd in commands" :key="cmd.id">
            <td>
              <span class="sct-label">{{ cmd.label ?? cmd.command }}</span>
              <span class="sct-command">{{ cmd.command }}</span>
            </td>
            <td class="sct-frequency">{{ cmd.frequency }}</td>
            <td>
              <template v-if="cmd.last_execution">
                <span class="sct-exec-status" :class="`sct-exec-status--${cmd.last_execution.status}`">
                  <i class="fas" :class="cmd.last_execution.status === 'success' ? 'fa-circle-check' : 'fa-circle-xmark'"></i>
                  {{ cmd.last_execution.executed_at }}
                </span>
                <p v-if="cmd.last_execution.status === 'error'" class="sct-exec-output" :title="cmd.last_execution.output ?? ''">
                  {{ cmd.last_execution.output }}
                </p>
              </template>
              <span v-else class="text-muted small">Sin ejecuciones registradas</span>
            </td>
            <td>
              <span v-if="!cmd.is_enabled" class="sct-disabled-info">
                Desactivado{{ cmd.disabled_by ? ` por ${cmd.disabled_by}` : '' }}{{ cmd.disabled_at ? ` · ${cmd.disabled_at}` : '' }}
              </span>
            </td>
            <td class="sct-toggle-cell">
              <label class="sct-switch">
                <input
                  type="checkbox"
                  :checked="cmd.is_enabled"
                  :disabled="togglingId === cmd.id"
                  @change="$emit('toggle', cmd, ($event.target as HTMLInputElement).checked)"
                />
                <span class="sct-switch-slider"></span>
              </label>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.sct-card {
  background: var(--ds-surface);
  border: 1px solid var(--ds-border);
  border-radius: 12px;
  padding: 1.1rem 1.3rem;
}
.sct-header { margin-bottom: 0.9rem; }
.sct-title { font-size: 0.95rem; font-weight: 700; color: var(--ds-text); margin: 0; }
.sct-subtitle { font-size: 0.78rem; color: var(--ds-text-muted); margin: 0.2rem 0 0; }

.sct-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
  color: var(--ds-text-muted);
  font-size: 0.88rem;
}

.sct-table-wrap { overflow-x: auto; }
.sct-table { width: 100%; border-collapse: collapse; font-size: 0.83rem; }
.sct-table th {
  text-align: left;
  padding: 0.5rem 0.6rem;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  color: var(--ds-text-muted);
  border-bottom: 1px solid var(--ds-border);
  white-space: nowrap;
}
.sct-table td {
  padding: 0.6rem;
  border-bottom: 1px solid var(--ds-border);
  vertical-align: top;
}
.sct-table tr:last-child td { border-bottom: none; }

.sct-label { display: block; font-weight: 600; color: var(--ds-text); }
.sct-command { display: block; font-family: monospace; font-size: 0.74rem; color: var(--ds-text-muted); margin-top: 0.1rem; }
.sct-frequency { white-space: nowrap; color: var(--ds-text-2); }

.sct-exec-status { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.8rem; white-space: nowrap; }
.sct-exec-status--success { color: #34d399; }
.sct-exec-status--error { color: #f87171; }
.sct-exec-output {
  font-family: monospace;
  font-size: 0.72rem;
  color: #f87171;
  margin: 0.3rem 0 0;
  max-width: 320px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.sct-disabled-info { font-size: 0.74rem; color: #fbbf24; white-space: nowrap; }

.sct-toggle-cell { text-align: right; }
.sct-switch { position: relative; display: inline-block; width: 38px; height: 22px; }
.sct-switch input { opacity: 0; width: 0; height: 0; }
.sct-switch-slider {
  position: absolute;
  cursor: pointer;
  inset: 0;
  background: var(--ds-border-2);
  border-radius: 22px;
  transition: 0.2s;
}
.sct-switch-slider::before {
  content: "";
  position: absolute;
  height: 16px;
  width: 16px;
  left: 3px;
  bottom: 3px;
  background: white;
  border-radius: 50%;
  transition: 0.2s;
}
.sct-switch input:checked + .sct-switch-slider { background: #667eea; }
.sct-switch input:checked + .sct-switch-slider::before { transform: translateX(16px); }
.sct-switch input:disabled + .sct-switch-slider { opacity: 0.5; cursor: not-allowed; }
</style>
