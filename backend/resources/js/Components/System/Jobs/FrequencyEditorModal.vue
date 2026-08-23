<script setup lang="ts">
/**
 * Editor de frecuencia (cron_expression) de un comando programado — presets
 * rápidos para los casos comunes + campo cron avanzado para casos custom,
 * con preview en vivo (debounced) de las próximas 5 ejecuciones calculado
 * en el backend (dragonmantank/cron-expression), y respeto del piso de
 * seguridad min_interval_seconds del comando (min_interval_seconds !== null
 * deshabilita cualquier preset más agresivo que el piso).
 */
import { computed, ref, watch } from 'vue'
import Modal from '@/js/Components/Common/Modal/Modal.vue'
import type { CronPreset, ScheduledCommand } from '@/js/types/system/jobs'

const props = defineProps<{
  modelValue: boolean
  command: ScheduledCommand | null
  saving: boolean
  /** Inyectado por el padre (usa el composable useScheduledCommands) — evita llamadas HTTP directas dentro de un componente de presentación. */
  fetchPreview: (command: ScheduledCommand, cronExpression: string) => Promise<{ frequency: string, next_runs: string[] }>
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  save: [cronExpression: string]
  reset: []
}>()

const ALL_PRESETS: (CronPreset & { seconds: number })[] = [
  { label: 'Cada minuto',    value: '* * * * *',    seconds: 60 },
  { label: 'Cada 5 minutos', value: '*/5 * * * *',  seconds: 300 },
  { label: 'Cada 10 minutos',value: '*/10 * * * *', seconds: 600 },
  { label: 'Cada 15 minutos',value: '*/15 * * * *', seconds: 900 },
  { label: 'Cada 30 minutos',value: '*/30 * * * *', seconds: 1800 },
  { label: 'Cada hora',      value: '0 * * * *',    seconds: 3600 },
  { label: 'Diario a medianoche (UTC)', value: '0 0 * * *', seconds: 86400 },
  { label: 'Diario a las 08:00 (UTC)',  value: '0 8 * * *', seconds: 86400 },
]

const cronInput = ref('')
const mode = ref<'preset' | 'custom'>('preset')
const previewLoading = ref(false)
const previewError = ref<string | null>(null)
const previewFrequency = ref<string | null>(null)
const nextRuns = ref<string[]>([])

const availablePresets = computed(() => {
  const floor = props.command?.min_interval_seconds
  return floor === null || floor === undefined
    ? ALL_PRESETS
    : ALL_PRESETS.filter(p => p.seconds >= floor)
})

const minIntervalLabel = computed(() => {
  const floor = props.command?.min_interval_seconds
  if (!floor) return null
  const minutes = Math.ceil(floor / 60)
  return `Por seguridad, este comando no puede ejecutarse más seguido que cada ${minutes} minuto(s).`
})

function resetLocalState(): void {
  const current = props.command?.cron_expression ?? ''
  cronInput.value = current
  mode.value = availablePresets.value.some(p => p.value === current) ? 'preset' : 'custom'
  previewError.value = null
  previewFrequency.value = null
  nextRuns.value = []
}

watch(() => props.modelValue, (open) => {
  if (open) resetLocalState()
}, { immediate: true })

let debounceTimer: ReturnType<typeof setTimeout> | null = null
// Incrementado en cada nueva llamada — una respuesta de preview que llega
// tarde (request anterior aún en vuelo cuando el usuario ya siguió
// escribiendo) se descarta comparando contra este valor, evitando que
// pise el resultado de una entrada más reciente ("race" clásico de
// búsquedas con debounce).
let previewRequestId = 0

watch(cronInput, (value) => {
  previewError.value = null
  previewFrequency.value = null
  nextRuns.value = []
  previewLoading.value = false

  if (debounceTimer) clearTimeout(debounceTimer)
  if (!value.trim() || !props.command) return

  debounceTimer = setTimeout(() => runPreview(value.trim()), 400)
})

async function runPreview(expression: string): Promise<void> {
  if (!props.command) return
  const requestId = ++previewRequestId
  previewLoading.value = true

  try {
    const result = await props.fetchPreview(props.command, expression)
    if (requestId !== previewRequestId) return
    previewFrequency.value = result.frequency
    nextRuns.value = result.next_runs
  } catch (error: any) {
    if (requestId !== previewRequestId) return
    const firstError = error?.response?.data?.errors?.cron_expression?.[0]
    previewError.value = firstError ?? 'No se pudo validar la expresión cron.'
  } finally {
    if (requestId === previewRequestId) previewLoading.value = false
  }
}

function selectPreset(preset: CronPreset): void {
  mode.value = 'preset'
  cronInput.value = preset.value
}

function switchToCustom(): void {
  mode.value = 'custom'
}

const canSave = computed(() => cronInput.value.trim().length > 0 && !previewError.value && !previewLoading.value)

function handleSubmit(): void {
  if (!canSave.value) return
  emit('save', cronInput.value.trim())
}

function handleReset(): void {
  emit('reset')
}
</script>

<template>
  <Modal
    :model-value="modelValue"
    :title="command ? `Frecuencia — ${command.label ?? command.command}` : 'Frecuencia'"
    icon="fa-clock"
    size="lg"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <p v-if="command" class="fem-command">{{ command.command }}</p>

    <p v-if="minIntervalLabel" class="fem-floor-notice">
      <i class="fas fa-shield-halved mr-1"></i>{{ minIntervalLabel }}
    </p>

    <div class="fem-presets">
      <button
        v-for="preset in availablePresets"
        :key="preset.value"
        type="button"
        class="fem-preset-btn"
        :class="{ 'fem-preset-btn--active': mode === 'preset' && cronInput === preset.value }"
        @click="selectPreset(preset)"
      >
        {{ preset.label }}
      </button>
      <button
        type="button"
        class="fem-preset-btn fem-preset-btn--custom"
        :class="{ 'fem-preset-btn--active': mode === 'custom' }"
        @click="switchToCustom"
      >
        <i class="fas fa-sliders mr-1"></i>Personalizado
      </button>
    </div>

    <div v-if="mode === 'custom'" class="fem-custom">
      <label class="fem-label" for="fem-cron-input">Expresión cron (minuto hora día mes día-semana)</label>
      <input
        id="fem-cron-input"
        v-model="cronInput"
        type="text"
        class="fem-cron-input"
        placeholder="*/5 * * * *"
        spellcheck="false"
        autocomplete="off"
      />
      <p class="fem-hint">Ejemplo: <code>0 */2 * * *</code> = cada 2 horas en punto.</p>
    </div>

    <div class="fem-preview">
      <div v-if="previewLoading" class="fem-preview-loading">
        <div class="spinner-border spinner-border-sm text-primary me-2"></div>
        Calculando próximas ejecuciones...
      </div>

      <div v-else-if="previewError" class="fem-preview-error">
        <i class="fas fa-circle-exclamation mr-1"></i>{{ previewError }}
      </div>

      <div v-else-if="nextRuns.length > 0" class="fem-preview-ok">
        <p class="fem-preview-frequency">
          <i class="fas fa-circle-check mr-1"></i>{{ previewFrequency }}
        </p>
        <p class="fem-preview-label">Próximas ejecuciones (UTC):</p>
        <ul class="fem-next-runs">
          <li v-for="(run, idx) in nextRuns" :key="idx">{{ run }}</li>
        </ul>
      </div>
    </div>

    <template #footer>
      <button
        v-if="command && !command.is_default_frequency"
        type="button"
        class="fem-btn fem-btn--reset"
        :disabled="saving"
        @click="handleReset"
      >
        <i class="fas fa-rotate-left mr-1"></i>Restablecer por defecto
      </button>
      <div class="fem-footer-spacer"></div>
      <button type="button" class="fem-btn fem-btn--ghost" @click="$emit('update:modelValue', false)">Cancelar</button>
      <button
        type="button"
        class="fem-btn fem-btn--primary"
        :disabled="!canSave || saving"
        @click="handleSubmit"
      >
        <i class="fas fa-spinner fa-spin mr-1" v-if="saving"></i>
        {{ saving ? 'Guardando...' : 'Guardar frecuencia' }}
      </button>
    </template>
  </Modal>
</template>

<style scoped>
.fem-command { font-family: monospace; font-size: 0.8rem; color: var(--ds-text-muted); margin: -0.5rem 0 1rem; }

.fem-floor-notice {
  display: flex;
  align-items: center;
  font-size: 0.78rem;
  color: #fbbf24;
  background: rgba(251, 191, 36, 0.1);
  border: 1px solid rgba(251, 191, 36, 0.25);
  border-radius: 8px;
  padding: 0.5rem 0.75rem;
  margin: 0 0 1rem;
}

.fem-presets { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1rem; }
.fem-preset-btn {
  padding: 0.4rem 0.8rem;
  border-radius: 20px;
  border: 1px solid var(--ds-border-2);
  background: var(--ds-surface);
  color: var(--ds-text-2);
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.15s;
}
.fem-preset-btn:hover { border-color: #667eea; }
.fem-preset-btn--active { background: #667eea; border-color: #667eea; color: #fff; }
.fem-preset-btn--custom { border-style: dashed; }

.fem-custom { margin-bottom: 1rem; }
.fem-label { display: block; font-size: 0.78rem; font-weight: 600; color: var(--ds-text-2); margin-bottom: 0.35rem; }
.fem-cron-input {
  width: 100%;
  padding: 0.55rem 0.75rem;
  border: 1px solid var(--ds-border-2);
  border-radius: 8px;
  font-family: monospace;
  font-size: 0.9rem;
  background: var(--ds-surface);
  color: var(--ds-text);
  box-sizing: border-box;
}
.fem-cron-input:focus { outline: none; border-color: #667eea; }
.fem-hint { font-size: 0.74rem; color: var(--ds-text-muted); margin: 0.4rem 0 0; }
.fem-hint code { background: var(--ds-surface-2); padding: 0.1rem 0.35rem; border-radius: 4px; }

.fem-preview { min-height: 40px; }
.fem-preview-loading, .fem-preview-error, .fem-preview-ok {
  font-size: 0.82rem;
  padding: 0.7rem 0.85rem;
  border-radius: 8px;
}
.fem-preview-loading { color: var(--ds-text-muted); display: flex; align-items: center; }
.fem-preview-error { color: #f87171; background: rgba(248, 113, 113, 0.1); }
.fem-preview-ok { background: rgba(52, 211, 153, 0.08); }
.fem-preview-frequency { font-weight: 700; color: #34d399; margin: 0 0 0.5rem; }
.fem-preview-label { font-size: 0.74rem; color: var(--ds-text-muted); margin: 0 0 0.3rem; }
.fem-next-runs { list-style: none; margin: 0; padding: 0; font-family: monospace; font-size: 0.78rem; color: var(--ds-text-2); display: flex; flex-direction: column; gap: 0.15rem; }

.fem-footer-spacer { flex: 1; }
.fem-btn {
  display: inline-flex;
  align-items: center;
  padding: 9px 18px;
  border-radius: 10px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: all 0.15s;
}
.fem-btn--ghost { background: transparent; color: var(--ds-text-2); border: 1.5px solid var(--ds-border-2); }
.fem-btn--ghost:hover { background: var(--ds-surface-2); }
.fem-btn--reset { background: transparent; color: var(--ds-text-muted); border: 1.5px dashed var(--ds-border-2); }
.fem-btn--reset:hover:not(:disabled) { color: #fbbf24; border-color: #fbbf24; }
.fem-btn--reset:disabled { opacity: 0.5; cursor: not-allowed; }
.fem-btn--primary { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; }
.fem-btn--primary:disabled { opacity: 0.55; cursor: not-allowed; }
</style>
