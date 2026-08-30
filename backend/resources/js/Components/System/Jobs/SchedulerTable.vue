<script setup lang="ts">
import { computed, ref } from 'vue'
import type { ScheduledCommand } from '@/js/types/system/jobs'
import { useLocalDateTime } from '@/js/composables/common/useLocalDateTime'
import { showToastConfirmAction } from '@/js/helpers/form/ui/crudModalUI'

const props = defineProps<{
  commands: ScheduledCommand[]
  loading: boolean
  togglingId: string | null
  runningId: string | null
}>()

const emit = defineEmits<{
  toggle: [command: ScheduledCommand, enabled: boolean]
  'view-history': [command: ScheduledCommand]
  'edit-frequency': [command: ScheduledCommand]
  'run-now': [command: ScheduledCommand]
}>()

const searchQuery = ref('')
const statusFilter = ref<'all' | 'enabled' | 'disabled' | 'error'>('all')

const { describe: describeDateTime } = useLocalDateTime()

function localLabel(iso: string | null, serverTimezone: string): string {
  const info = describeDateTime(iso, serverTimezone)
  return info ? `${info.local} (${info.localAbbrev})` : '—'
}

async function handleToggle(cmd: ScheduledCommand, event: Event): Promise<void> {
  const input = event.target as HTMLInputElement
  const checked = input.checked

  const ok = checked
    ? await showToastConfirmAction(
        `"${cmd.label ?? cmd.command}" volverá a ejecutarse automáticamente según su frecuencia.`,
        'question',
        'Sí, activar'
      )
    : await showToastConfirmAction(
        `"${cmd.label ?? cmd.command}" dejará de ejecutarse automáticamente hasta que se reactive.`,
        'warning',
        'Sí, desactivar'
      )

  if (ok) {
    emit('toggle', cmd, checked)
  } else {
    // El navegador ya cambió el checkbox (evento @change nativo) antes de
    // que este handler corriera — al cancelar, se revierte el DOM sin emitir
    // 'toggle' (que dispararía un PUT real solo para deshacer un cambio que
    // nunca se confirmó).
    input.checked = !checked
  }
}

async function handleRunNow(cmd: ScheduledCommand): Promise<void> {
  const ok = await showToastConfirmAction(
    `Se ejecutará "${cmd.label ?? cmd.command}" inmediatamente, fuera de su horario programado.`,
    'question',
    'Sí, ejecutar ahora'
  )
  if (ok) emit('run-now', cmd)
}

const filteredCommands = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()

  return props.commands.filter(cmd => {
    if (q && !cmd.command.toLowerCase().includes(q) && !(cmd.label ?? '').toLowerCase().includes(q)) {
      return false
    }
    if (statusFilter.value === 'enabled' && !cmd.is_enabled) return false
    if (statusFilter.value === 'disabled' && cmd.is_enabled) return false
    if (statusFilter.value === 'error' && cmd.last_execution?.status !== 'error') return false
    return true
  })
})
</script>

<template>
  <div class="sct-card">
    <div class="sct-header">
      <div>
        <h4 class="sct-title"><i class="fas fa-clock mr-2"></i>Comandos programados</h4>
        <p class="sct-subtitle">Corren automáticamente según su frecuencia — desactivar uno detiene solo su ejecución automática, no impide invocarlo manualmente.</p>
      </div>
    </div>

    <div class="sct-filters">
      <input
        v-model="searchQuery"
        type="text"
        class="sct-search"
        placeholder="Buscar comando..."
      />
      <div class="sct-status-tabs">
        <button
          v-for="opt in ([['all', 'Todos'], ['enabled', 'Activos'], ['disabled', 'Desactivados'], ['error', 'Con error']] as const)"
          :key="opt[0]"
          class="sct-status-tab"
          :class="{ 'sct-status-tab--active': statusFilter === opt[0] }"
          @click="statusFilter = opt[0]"
        >
          {{ opt[1] }}
        </button>
      </div>
    </div>

    <div v-if="loading" class="sct-loading">
      <div class="spinner-border spinner-border-sm text-primary me-2"></div>
      Cargando comandos programados...
    </div>

    <div v-else-if="filteredCommands.length === 0" class="sct-empty">
      <i class="fas fa-magnifying-glass sct-empty-icon"></i>
      <p class="mb-0">Ningún comando coincide con el filtro actual.</p>
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
          <tr v-for="cmd in filteredCommands" :key="cmd.id">
            <td>
              <span class="sct-label">{{ cmd.label ?? cmd.command }}</span>
              <span class="sct-command">{{ cmd.command }}</span>
            </td>
            <td class="sct-frequency">
              <button class="sct-frequency-btn" title="Editar frecuencia" @click="$emit('edit-frequency', cmd)">
                {{ cmd.frequency }}
                <i class="fas fa-pen sct-frequency-edit-icon"></i>
              </button>
              <span v-if="!cmd.is_default_frequency" class="sct-frequency-custom-badge">Personalizado</span>
            </td>
            <td>
              <template v-if="cmd.last_execution">
                <button class="sct-exec-status" :class="`sct-exec-status--${cmd.last_execution.status}`" @click="$emit('view-history', cmd)">
                  <i class="fas" :class="cmd.last_execution.status === 'success' ? 'fa-circle-check' : 'fa-circle-xmark'"></i>
                  {{ localLabel(cmd.last_execution.executed_at, cmd.server_timezone) }}
                </button>
                <p v-if="cmd.last_execution.status === 'error'" class="sct-exec-output" :title="cmd.last_execution.output ?? ''">
                  {{ cmd.last_execution.output }}
                </p>
              </template>
              <span v-else class="text-muted small">Sin ejecuciones registradas</span>
            </td>
            <td>
              <span class="sct-status-badge" :class="cmd.is_enabled ? 'sct-status-badge--active' : 'sct-status-badge--disabled'">
                <span class="sct-status-dot"></span>
                {{ cmd.is_enabled ? 'Activo' : 'Desactivado' }}
              </span>
              <span v-if="!cmd.is_enabled && (cmd.disabled_by || cmd.disabled_at)" class="sct-disabled-meta">
                {{ cmd.disabled_by ? `por ${cmd.disabled_by}` : '' }}{{ cmd.disabled_by && cmd.disabled_at ? ' · ' : '' }}{{ cmd.disabled_at ? localLabel(cmd.disabled_at, cmd.server_timezone) : '' }}
              </span>
            </td>
            <td class="sct-actions-cell">
              <div class="sct-actions-inner">
                <button
                  class="sct-run-btn"
                  title="Ejecutar ahora"
                  :disabled="runningId === cmd.id"
                  @click="handleRunNow(cmd)"
                >
                  <i class="fas" :class="runningId === cmd.id ? 'fa-spinner fa-spin' : 'fa-play'"></i>
                </button>
                <button class="sct-history-btn" title="Ver historial completo" @click="$emit('view-history', cmd)">
                  <i class="fas fa-clock-rotate-left"></i>
                </button>
                <label class="sct-switch" :title="cmd.is_enabled ? 'Desactivar ejecución automática' : 'Activar ejecución automática'">
                  <input
                    type="checkbox"
                    :checked="cmd.is_enabled"
                    :disabled="togglingId === cmd.id"
                    @change="handleToggle(cmd, $event)"
                  />
                  <span class="sct-switch-slider"></span>
                </label>
              </div>
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
  padding: 1.25rem 1.3rem;
}
.sct-header { margin-bottom: 1rem; }
.sct-title { display: flex; align-items: center; font-size: 0.98rem; font-weight: 700; color: var(--ds-text); margin: 0; }
.sct-subtitle { font-size: 0.78rem; color: var(--ds-text-muted); margin: 0.3rem 0 0; line-height: 1.5; max-width: 640px; }

.sct-filters { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 0.9rem; }
.sct-search {
  flex: 1;
  min-width: 180px;
  padding: 0.4rem 0.7rem;
  border: 1px solid var(--ds-border-2);
  border-radius: 8px;
  font-size: 0.82rem;
  background: var(--ds-surface);
  color: var(--ds-text);
}
.sct-status-tabs { display: flex; gap: 0.3rem; flex-wrap: wrap; }
.sct-status-tab {
  padding: 0.35rem 0.7rem;
  border-radius: 20px;
  border: 1px solid var(--ds-border-2);
  background: var(--ds-surface);
  color: var(--ds-text-2);
  font-size: 0.76rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
}
.sct-status-tab--active { background: #667eea; border-color: #667eea; color: #fff; }

.sct-loading, .sct-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 2rem 1rem;
  color: var(--ds-text-muted);
  font-size: 0.88rem;
}
.sct-empty-icon { font-size: 1.6rem; opacity: 0.5; }

.sct-table-wrap { overflow-x: auto; margin: 0 -0.2rem; }
.sct-table { width: 100%; border-collapse: collapse; font-size: 0.83rem; }
.sct-table th {
  text-align: left;
  padding: 0.55rem 0.7rem;
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--ds-text-muted);
  border-bottom: 1px solid var(--ds-border);
  white-space: nowrap;
}
.sct-table td {
  padding: 0.85rem 0.7rem;
  border-bottom: 1px solid var(--ds-border);
  vertical-align: middle;
}
.sct-table tbody tr:last-child td { border-bottom: none; }
.sct-table tbody tr { transition: background 0.12s; }
.sct-table tbody tr:hover { background: var(--ds-surface-2); }

.sct-label { display: block; font-weight: 600; color: var(--ds-text); }
.sct-command { display: block; font-family: monospace; font-size: 0.74rem; color: var(--ds-text-muted); margin-top: 0.1rem; }
.sct-frequency { white-space: nowrap; }
.sct-frequency-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  background: none;
  border: none;
  padding: 0.15rem 0.3rem;
  margin: -0.15rem -0.3rem;
  border-radius: 6px;
  color: var(--ds-text-2);
  font-size: 0.83rem;
  cursor: pointer;
  transition: background 0.15s, color 0.15s;
}
.sct-frequency-btn:hover { background: var(--ds-surface-2); color: #667eea; }
.sct-frequency-edit-icon { font-size: 0.65rem; opacity: 0.5; }
.sct-frequency-btn:hover .sct-frequency-edit-icon { opacity: 1; }
.sct-frequency-custom-badge {
  display: block;
  font-size: 0.66rem;
  font-weight: 600;
  color: #667eea;
  margin-top: 0.15rem;
}

.sct-exec-status {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.8rem;
  white-space: nowrap;
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
}
.sct-exec-status--success { color: #34d399; }
.sct-exec-status--error { color: #f87171; }
.sct-exec-status:hover { text-decoration: underline; }
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

.sct-status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.72rem;
  font-weight: 600;
  padding: 0.25rem 0.6rem;
  border-radius: 20px;
  white-space: nowrap;
}
.sct-status-badge--active { color: #34d399; background: rgba(52, 211, 153, 0.12); }
.sct-status-badge--active .sct-status-dot { background: #34d399; }
.sct-status-badge--disabled { color: #fbbf24; background: rgba(251, 191, 36, 0.12); }
.sct-status-badge--disabled .sct-status-dot { background: #fbbf24; }
.sct-status-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.sct-disabled-meta { display: block; font-size: 0.7rem; color: var(--ds-text-muted); margin-top: 0.3rem; }

.sct-actions-cell { width: 1%; }
.sct-actions-inner { display: flex; align-items: center; justify-content: flex-end; gap: 0.7rem; }
.sct-history-btn {
  background: none;
  border: none;
  color: var(--ds-text-muted);
  cursor: pointer;
  font-size: 0.85rem;
  width: 28px;
  height: 28px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: background 0.15s, color 0.15s;
}
.sct-history-btn:hover { color: #667eea; background: rgba(102, 126, 234, 0.1); }

.sct-run-btn {
  background: none;
  border: none;
  color: var(--ds-text-muted);
  cursor: pointer;
  font-size: 0.8rem;
  width: 28px;
  height: 28px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: background 0.15s, color 0.15s;
}
.sct-run-btn:hover { color: #34d399; background: rgba(52, 211, 153, 0.1); }
.sct-run-btn:disabled { opacity: 0.5; cursor: not-allowed; }

.sct-switch { position: relative; display: inline-block; width: 38px; height: 22px; flex-shrink: 0; }
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
