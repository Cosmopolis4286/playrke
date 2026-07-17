<script setup lang="ts">
import Modal from '@js/Components/Common/Modal/Modal.vue'
import MovementDetailHeader  from './MovementDetailHeader.vue'
import MovementDetailAmounts from './MovementDetailAmounts.vue'
import MovementReceiptViewer from './MovementReceiptViewer.vue'
import MovementDetailMeta    from './MovementDetailMeta.vue'
import MovementAdminActions  from './MovementAdminActions.vue'
import type { Movement } from '@js/types/wallet/admin-movement'

defineProps<{
  modelValue: boolean
  detailLoading: boolean
  detail: Movement | null
  isActionable: boolean
  isReceiptImage: boolean
  receiptZoom: boolean
  adminNotes: string
  actionLoading: boolean
  actionError: string
  showRejectForm: boolean
  typeConfig: Record<string, { label: string; cls: string; style?: string; icon: string }>
  prizeTypeConfig: Record<'DIGITAL' | 'PHYSICAL', { label: string; icon: string }>
  statusConfig: Record<string, { label: string; cls: string }>
  directoryTypeLabel: Record<string, string>
  userName: (u: Movement['user']) => string
  adminName: (u: Movement['processed_by']) => string
  formatAmount: (mov: Movement) => string
  formatDate: (iso: string) => string
}>()

defineEmits<{
  'update:modelValue': [value: boolean]
  'update:receiptZoom': [value: boolean]
  'update:adminNotes': [value: string]
  'update:showRejectForm': [value: boolean]
  approve: []
  reject: []
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
      <div class="spinner-border spinner-border-sm text-primary me-2"></div>
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

      <MovementDetailAmounts :detail="detail" :format-amount="formatAmount" />

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

      <MovementAdminActions
        v-if="isActionable"
        :detail="detail"
        :admin-notes="adminNotes"
        :action-loading="actionLoading"
        :action-error="actionError"
        :show-reject-form="showRejectForm"
        @update:admin-notes="$emit('update:adminNotes', $event)"
        @update:show-reject-form="$emit('update:showRejectForm', $event)"
        @approve="$emit('approve')"
        @reject="$emit('reject')"
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
