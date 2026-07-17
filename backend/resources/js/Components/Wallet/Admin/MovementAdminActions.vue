<script setup lang="ts">
import type { Movement } from '@js/types/wallet/admin-movement'

defineProps<{
  detail: Movement
  adminNotes: string
  actionLoading: boolean
  actionError: string
  showRejectForm: boolean
}>()

const emit = defineEmits<{
  'update:adminNotes': [value: string]
  'update:showRejectForm': [value: boolean]
  approve: []
  reject: []
}>()
</script>

<template>
  <div class="mv-detail__divider" />
  <div class="mv-actions">
    <p class="mv-detail__label mb-2">Acciones de administración</p>

    <div v-if="actionError" class="alert alert-danger py-2 px-3 mb-2 small">
      <i class="fas fa-exclamation-circle me-1"></i> {{ actionError }}
    </div>

    <!-- Notas admin -->
    <div class="mb-2">
      <label class="mv-detail__label mb-1">Notas (opcional)</label>
      <textarea
        :value="adminNotes"
        class="form-control form-control-sm"
        rows="2"
        placeholder="Motivo de aprobación o rechazo..."
        :disabled="actionLoading"
        @input="emit('update:adminNotes', ($event.target as HTMLTextAreaElement).value)"
      ></textarea>
    </div>

    <!-- Botones -->
    <div v-if="!showRejectForm" class="mv-actions__btns">
      <button
        class="btn btn-success btn-sm"
        :disabled="actionLoading"
        @click="emit('approve')"
      >
        <span v-if="actionLoading" class="spinner-border spinner-border-sm me-1"></span>
        <i v-else class="fas fa-check me-1"></i>
        Aprobar
      </button>
      <button
        class="btn btn-danger btn-sm"
        :disabled="actionLoading"
        @click="emit('update:showRejectForm', true)"
      >
        <i class="fas fa-times me-1"></i>
        Rechazar
      </button>
    </div>

    <!-- Confirmar rechazo -->
    <div v-else class="mv-reject-confirm">
      <p class="mv-reject-confirm__text">
        <i class="fas fa-exclamation-triangle text-warning me-1"></i>
        <template v-if="detail.type === 'WITHDRAWAL'">
          ¿Rechazar el retiro? El saldo será <strong>devuelto</strong> al usuario.
        </template>
        <template v-else>
          ¿Rechazar el depósito? No se acreditará ningún monto.
        </template>
      </p>
      <div class="mv-actions__btns">
        <button
          class="btn btn-danger btn-sm"
          :disabled="actionLoading"
          @click="emit('reject')"
        >
          <span v-if="actionLoading" class="spinner-border spinner-border-sm me-1"></span>
          <i v-else class="fas fa-times me-1"></i>
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

.mb-2 { margin-bottom: 0.5rem; }
.mb-1 { margin-bottom: 0.25rem; }
.me-1 { margin-right: 0.25rem; }
</style>
