<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
import type { Movement } from '@js/types/wallet/admin-movement'
import { adminName } from '@js/composables/wallet/admin/useMovementFormatters'

const props = withDefaults(defineProps<{
  detail: Movement
  isActionable: boolean
  adminNotes: string
  playerMessage: string
  actionLoading: boolean
  actionError: string
  showRejectForm: boolean
  currentUserId: string
  assignLoading: boolean
  typeLabel?: string
  canSubmitReject?: boolean
  isCredit?: boolean
}>(), {
  typeLabel: 'movimiento',
  canSubmitReject: true,
  isCredit: false,
})

const emit = defineEmits<{
  'update:adminNotes': [value: string]
  'update:playerMessage': [value: string]
  'update:showRejectForm': [value: boolean]
  approve: []
  reject: []
  assign: []
  unassign: []
}>()

/**
 * Motivos frecuentes por tipo — un click los inserta en el textarea (editable
 * después). Acelera el caso común y hace que los mensajes que recibe el
 * jugador sean consistentes entre admins.
 */
const quickReasons = computed<string[]>(() => {
  const common = ['Información incompleta o inconsistente']
  if (props.detail.type === 'DEPOSIT') {
    return [
      'Pago no recibido en la cuenta destino',
      'El monto no coincide con el comprobante',
      'Comprobante ilegible',
      'Comprobante duplicado o ya utilizado',
      ...common,
    ]
  }
  if (props.detail.type === 'WITHDRAWAL') {
    return [
      'Datos bancarios incorrectos',
      'La cuenta destino no pertenece al titular',
      'Verificación de identidad (KYC) pendiente',
      ...common,
    ]
  }
  return common
})

function applyQuickReason(reason: string) {
  const current = props.playerMessage.trim()
  emit('update:playerMessage', current && !current.includes(reason) ? `${current}. ${reason}` : reason)
}

/** Consecuencia del rechazo según el tipo — antes todo lo que no era WITHDRAWAL decía "¿Rechazar el depósito?". */
const rejectConsequence = computed(() => {
  if (props.detail.type === 'WITHDRAWAL' || !props.isCredit) {
    return 'El monto reservado será <strong>devuelto</strong> al saldo del usuario.'
  }
  return 'No se acreditará ningún monto al usuario.'
})

const notesRef = ref<HTMLTextAreaElement | null>(null)

// Al abrir el formulario de rechazo (botón o atajo R) el foco va directo al
// motivo, que ahora es obligatorio.
watch(() => props.showRejectForm, async (open) => {
  if (!open) return
  await nextTick()
  notesRef.value?.focus()
})

function onNotesKeydown(e: KeyboardEvent) {
  // Ctrl/⌘+Enter confirma el rechazo sin soltar el teclado.
  if (props.showRejectForm && (e.ctrlKey || e.metaKey) && e.key === 'Enter' && props.canSubmitReject) {
    e.preventDefault()
    emit('reject')
  }
}
</script>

<template>
  <div class="mv-detail__divider" />
  <div class="mv-actions">
    <p class="mv-detail__label mb-2">Acciones de administración</p>

    <div v-if="actionError" class="alert alert-danger py-2 px-3 mb-2 small">
      <i class="fas fa-exclamation-circle me-1" /> {{ actionError }}
    </div>

    <!-- Asignación: "en revisión por mí" — evita que dos admins trabajen el
         mismo caso a la vez (lock real en el backend, no solo informativo). -->
    <div class="mv-assignment mb-2">
      <template v-if="!detail.assigned_to">
        <button class="btn btn-sm btn-outline-primary" :disabled="assignLoading" @click="emit('assign')">
          <span v-if="assignLoading" class="spinner-border spinner-border-sm me-1" />
          <i v-else class="fas fa-user-check me-1" />
          Tomar caso
        </button>
      </template>
      <template v-else>
        <span class="mv-assignment__badge">
          <i class="fas fa-user-clock me-1" />
          En revisión por {{ adminName(detail.assigned_to) }}
        </span>
        <button
          v-if="detail.assigned_to.id === currentUserId"
          class="btn btn-sm btn-outline-secondary"
          :disabled="assignLoading"
          @click="emit('unassign')"
        >
          Liberar
        </button>
      </template>
    </div>

    <template v-if="isActionable">
      <!-- Las notas internas nunca se envían al jugador. -->
      <div class="mb-2">
        <label class="mv-detail__label mb-1">Notas internas (solo administración)</label>
        <textarea
          :value="adminNotes"
          class="form-control form-control-sm"
          rows="2"
          maxlength="512"
          placeholder="Observaciones internas para el equipo…"
          :disabled="actionLoading"
          @input="emit('update:adminNotes', ($event.target as HTMLTextAreaElement).value)"
        ></textarea>
        <div class="mv-notes-meta">
          <span class="mv-notes-meta__count">{{ adminNotes.length }}/512</span>
        </div>
      </div>

      <!-- El motivo público se captura por separado y solo al rechazar. -->
      <div v-if="showRejectForm" class="mb-2">
        <label class="mv-detail__label mb-1 mv-detail__label--required">
          Motivo del rechazo (obligatorio · lo verá el jugador)
        </label>
        <div class="mv-quick-reasons">
          <button
            v-for="reason in quickReasons"
            :key="reason"
            type="button"
            class="mv-quick-reasons__chip"
            :disabled="actionLoading"
            @click="applyQuickReason(reason)"
          >
            {{ reason }}
          </button>
        </div>
        <textarea
          ref="notesRef"
          :value="playerMessage"
          class="form-control form-control-sm"
          :class="{ 'is-invalid': actionError && !canSubmitReject }"
          rows="2"
          maxlength="512"
          placeholder="Escribe una explicación clara que recibirá el jugador…"
          :disabled="actionLoading"
          @input="emit('update:playerMessage', ($event.target as HTMLTextAreaElement).value)"
          @keydown="onNotesKeydown"
        ></textarea>
        <div class="mv-notes-meta">
          <span class="mv-notes-meta__hint">
            <kbd>Ctrl</kbd>+<kbd>Enter</kbd> para confirmar
          </span>
          <span class="mv-notes-meta__count">{{ playerMessage.length }}/512</span>
        </div>
      </div>

      <!-- Botones -->
      <div v-if="!showRejectForm" class="mv-actions__btns">
        <button
          class="btn btn-success btn-sm"
          :disabled="actionLoading"
          @click="emit('approve')"
        >
          <span v-if="actionLoading" class="spinner-border spinner-border-sm me-1" />
          <i v-else class="fas fa-check me-1" />
          Aprobar
          <kbd class="mv-actions__kbd">A</kbd>
        </button>
        <button
          class="btn btn-danger btn-sm"
          :disabled="actionLoading"
          @click="emit('update:showRejectForm', true)"
        >
          <i class="fas fa-times me-1" />
          Rechazar
          <kbd class="mv-actions__kbd">R</kbd>
        </button>
      </div>

      <!-- Confirmar rechazo -->
      <div v-else class="mv-reject-confirm">
        <p class="mv-reject-confirm__text">
          <i class="fas fa-exclamation-triangle text-warning me-1" />
          ¿Rechazar este {{ typeLabel }}? <span v-html="rejectConsequence" />
        </p>
        <div class="mv-actions__btns">
          <button
            class="btn btn-danger btn-sm"
            :disabled="actionLoading || !canSubmitReject"
            :title="canSubmitReject ? '' : 'Escribe el motivo del rechazo (mínimo 5 caracteres)'"
            @click="emit('reject')"
          >
            <span v-if="actionLoading" class="spinner-border spinner-border-sm me-1" />
            <i v-else class="fas fa-times me-1" />
            Confirmar rechazo
          </button>
          <button
            class="btn btn-outline-secondary btn-sm"
            :disabled="actionLoading"
            @click="emit('update:showRejectForm', false)"
          >
            Cancelar
          </button>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.mv-detail__divider {
  height: 1px;
  background: var(--ds-border);
  margin: 0.75rem 0;
}

.mv-detail__label {
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--ds-text-muted);
  margin: 0;
}

/* Acciones */
.mv-actions {
  background: var(--ds-surface-2);
  border-radius: 10px;
  padding: 1rem;
  border: 1px dashed var(--ds-border-2);
}

.mv-actions__btns {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.mv-actions__kbd {
  margin-left: 6px;
  padding: 1px 5px;
  font-size: 0.65rem;
  font-family: ui-monospace, monospace;
  background: rgba(255,255,255,.18);
  border: 1px solid rgba(255,255,255,.3);
  border-radius: 4px;
  opacity: 0.85;
}

.mv-reject-confirm {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.mv-reject-confirm__text {
  font-size: 0.85rem;
  color: var(--ds-text-2);
  margin: 0;
  background: rgba(217,119,6,.14);
  border-radius: 6px;
  padding: 0.5rem 0.75rem;
  border: 1px solid rgba(217,119,6,.4);
}

.mv-detail__label--required { color: #f87171; }

.mv-quick-reasons {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  margin-bottom: 0.5rem;
}
.mv-quick-reasons__chip {
  font-size: 0.72rem;
  padding: 3px 10px;
  border-radius: 20px;
  border: 1px solid var(--ds-border-2);
  background: var(--ds-surface);
  color: var(--ds-text-2);
  cursor: pointer;
  transition: border-color .15s, color .15s;
}
.mv-quick-reasons__chip:hover:not(:disabled) { border-color: #f87171; color: #f87171; }

.mv-notes-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 0.25rem;
  font-size: 0.68rem;
  color: var(--ds-text-muted);
}
.mv-notes-meta__count { margin-left: auto; }
.mv-notes-meta kbd {
  padding: 0 4px;
  font-size: 0.65rem;
  border: 1px solid var(--ds-border-2);
  border-radius: 3px;
  background: var(--ds-surface);
  color: var(--ds-text-2);
}

.mv-assignment {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.mv-assignment__badge {
  display: inline-flex;
  align-items: center;
  font-size: 0.78rem;
  font-weight: 600;
  color: #60a5fa;
}

.mb-2 { margin-bottom: 0.5rem; }
.mb-1 { margin-bottom: 0.25rem; }
.me-1 { margin-right: 0.25rem; }
</style>
