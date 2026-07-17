<script setup lang="ts">
import { computed } from 'vue'
import Modal from '@js/Components/Common/Modal/Modal.vue'
import type { Wallet, WalletMovement } from '@js/types/wallet'
import type { MovementMetaEntry } from '@js/composables/wallet/useMovementMeta'
import { useMovementTimeline } from '@js/composables/wallet/useMovementTimeline'
import MovementDetailHeader from './MovementDetail/MovementDetailHeader.vue'
import MovementDetailInfoGrid from './MovementDetail/MovementDetailInfoGrid.vue'
import MovementDetailReceipt from './MovementDetail/MovementDetailReceipt.vue'
import MovementTimeline from './MovementDetail/MovementTimeline.vue'
import MovementStatusFeedback from './MovementDetail/MovementStatusFeedback.vue'

const props = defineProps<{
  modelValue: boolean
  movement: WalletMovement | null
  meta: MovementMetaEntry | null
  activeWallet: Wallet | null
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', v: boolean): void
}>()

const { detailSteps } = useMovementTimeline(
  computed(() => props.movement),
  computed(() => props.activeWallet),
)
</script>

<template>
  <Modal
    :model-value="modelValue"
    title="Detalle del movimiento"
    icon="fa-receipt"
    size="sm"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <template v-if="movement && meta">

      <MovementDetailHeader :movement="movement" :meta="meta" :active-wallet="activeWallet" />

      <MovementDetailInfoGrid :movement="movement" :active-wallet="activeWallet" />

      <MovementDetailReceipt :movement="movement" />

      <MovementTimeline :steps="detailSteps" />

      <MovementStatusFeedback :movement="movement" />

    </template>

    <!-- Footer: solo cerrar -->
    <template #footer>
      <button type="button" class="op-btn op-btn--cancel" @click="emit('update:modelValue', false)">
        <i class="fas fa-times"></i> Cerrar
      </button>
    </template>
  </Modal>
</template>

<style scoped>
.op-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 9px 20px;
  border-radius: 10px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: all 0.15s;
}
.op-btn--cancel {
  background: var(--ds-surface);
  color: var(--ds-text-muted);
  border: 1.5px solid var(--ds-border);
}
.op-btn--cancel:hover:not(:disabled) { background: var(--ds-surface-2); }
</style>
