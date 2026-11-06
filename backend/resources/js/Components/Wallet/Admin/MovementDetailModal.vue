<script setup lang="ts">
import Modal from '@js/Components/Common/Modal/Modal.vue'
import MovementDetailHeader  from './MovementDetailHeader.vue'
import MovementDetailAmounts from './MovementDetailAmounts.vue'
import MovementReceiptViewer from './MovementReceiptViewer.vue'
import MovementDetailMeta    from './MovementDetailMeta.vue'
import MovementFraudSignals  from './MovementFraudSignals.vue'
import MovementAdminActions  from './MovementAdminActions.vue'
import MovementAuditTrail    from './MovementAuditTrail.vue'
import type { Movement } from '@js/types/wallet/admin-movement'

defineProps<{
  modelValue: boolean
  detailLoading: boolean
  detail: Movement | null
  isActionable: boolean
  isReceiptImage: boolean
  receiptZoom: boolean
  adminNotes: string
  playerMessage: string
  actionLoading: boolean
  actionError: string
  showRejectForm: boolean
  typeConfig: Record<string, { label: string; cls: string; style?: string; icon: string }>
  prizeTypeConfig: Record<'DIGITAL' | 'PHYSICAL', { label: string; icon: string }>
  statusConfig: Record<string, { label: string; cls: string }>
  directoryTypeLabel: Record<string, string>
  isCreditByCode: Record<string, boolean>
  userName: (u: Movement['user']) => string
  adminName: (u: Movement['processed_by']) => string
  formatAmount: (mov: Movement) => string
  formatDate: (iso: string) => string
  currentUserId: string
  assignLoading: boolean
  /** false para perfiles de solo lectura (wallet.movements.view sin manage): sin bloque de acciones. */
  canManage?: boolean
  typeLabel?: string
  canSubmitReject?: boolean
}>()

defineEmits<{
  'update:modelValue': [value: boolean]
  'update:receiptZoom': [value: boolean]
  'update:adminNotes': [value: string]
  'update:playerMessage': [value: string]
  'update:showRejectForm': [value: boolean]
  approve: []
  reject: []
  'view-movement': [id: string]
  'view-user-movements': [search: string]
  assign: []
  unassign: []
}>()
</script>

<template>
  <Modal
    :model-value="modelValue"
    title="Detalle del movimiento"
    icon="fa-exchange-alt"
    size="lg"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <!-- Loading -->
    <div v-if="detailLoading" class="mv-modal-loading">
      <div class="spinner-border spinner-border-sm text-primary me-2" />
      Cargando...
    </div>

    <!-- Contenido -->
    <div v-else-if="detail" class="mv-detail">
      <MovementDetailHeader
        :detail="detail"
        :is-actionable="isActionable"
        :type-config="typeConfig"
        :prize-type-config="prizeTypeConfig"
        :status-config="statusConfig"
        :user-name="userName"
      />

      <MovementDetailAmounts :detail="detail" :format-amount="formatAmount" :is-credit-by-code="isCreditByCode" />

      <MovementReceiptViewer
        :detail="detail"
        :is-receipt-image="isReceiptImage"
        :receipt-zoom="receiptZoom"
        @update:receipt-zoom="$emit('update:receiptZoom', $event)"
      />

      <MovementDetailMeta
        :detail="detail"
        :directory-type-label="directoryTypeLabel"
        :admin-name="adminName"
        :format-date="formatDate"
      />

      <MovementFraudSignals
        :detail="detail"
        :format-amount="formatAmount"
        :format-date="formatDate"
        @view-movement="$emit('view-movement', $event)"
        @view-user-movements="$emit('view-user-movements', $event)"
      />

      <MovementAuditTrail :detail="detail" />

      <MovementAdminActions
        v-if="canManage !== false && (isActionable || detail.status === 'PENDING')"
        :detail="detail"
        :is-actionable="isActionable"
        :type-label="typeLabel ?? 'movimiento'"
        :can-submit-reject="canSubmitReject ?? true"
        :is-credit="!!isCreditByCode[detail.type]"
        :admin-notes="adminNotes"
        :player-message="playerMessage"
        :action-loading="actionLoading"
        :action-error="actionError"
        :show-reject-form="showRejectForm"
        :current-user-id="currentUserId"
        :assign-loading="assignLoading"
        @update:admin-notes="$emit('update:adminNotes', $event)"
        @update:player-message="$emit('update:playerMessage', $event)"
        @update:show-reject-form="$emit('update:showRejectForm', $event)"
        @approve="$emit('approve')"
        @reject="$emit('reject')"
        @assign="$emit('assign')"
        @unassign="$emit('unassign')"
      />
    </div>
  </Modal>
</template>

<style scoped>
.mv-modal-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  color: var(--ds-text-2);
  font-size: 0.85rem;
}

.mv-detail { display: flex; flex-direction: column; gap: 0; }

.me-2 { margin-right: 0.5rem; }
</style>
