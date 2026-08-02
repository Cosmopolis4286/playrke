<script setup lang="ts">
import { computed } from 'vue'
import Modal from '@js/Components/Common/Modal/Modal.vue'
import type { Wallet, WalletOperation, UserDirectory, PendingOperation } from '@js/types/wallet'
import type { WithdrawalLimitUsage } from '@js/types/kyc'
import type { WalletStep } from '@js/composables/wallet/useWallet'
import OperationStepsIndicator from './OperationModal/OperationStepsIndicator.vue'
import OperationStepAmount from './OperationModal/OperationStepAmount.vue'
import OperationStepDepositReceipt from './OperationModal/OperationStepDepositReceipt.vue'
import OperationStepWithdrawDirectory from './OperationModal/OperationStepWithdrawDirectory.vue'
import OperationStepDone from './OperationModal/OperationStepDone.vue'
import OperationModalFooter from './OperationModal/OperationModalFooter.vue'

const props = defineProps<{
  modelValue: boolean
  operation: WalletOperation
  step: WalletStep
  activeWallet: Wallet | null
  formattedBalance: string

  amount: string
  selectedQuickAmount: number | null
  receipt: File | null
  reference: string
  notes: string

  directories: UserDirectory[]
  directoriesLoading: boolean
  selectedDirectory: UserDirectory | null

  pendingOperation: PendingOperation | null
  pendingLoading: boolean
  walletLimits: WithdrawalLimitUsage | null

  loading: boolean
  errorMsg: string | null
  successMsg: string | null

  parsedAmount: number
  canGoToStep2: boolean
  canSubmitDeposit: boolean
  canSubmitWithdraw: boolean
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', v: boolean): void
  (e: 'update:amount', v: string): void
  (e: 'update:receipt', v: File | null): void
  (e: 'update:reference', v: string): void
  (e: 'update:notes', v: string): void
  (e: 'update:selectedDirectory', v: UserDirectory | null): void
  (e: 'select-quick-amount', v: number): void
  (e: 'clear-quick-amount'): void
  (e: 'go-to-step1'): void
  (e: 'go-to-step2'): void
  (e: 'submit'): void
  (e: 'close'): void
}>()

const modalTitle = computed(() => {
  if (props.step === 3) return 'Solicitud enviada'
  if (props.step === 2) return props.operation === 'deposit' ? 'Comprobante de pago' : 'Seleccionar destino'
  return props.operation === 'deposit' ? 'Depositar fondos' : 'Retirar fondos'
})

const modalIcon = computed(() => {
  if (props.step === 3) return 'fa-check-circle'
  return props.operation === 'deposit' ? 'fa-arrow-down' : 'fa-arrow-up'
})
</script>

<template>
  <Modal
    :model-value="modelValue"
    :title="modalTitle"
    :icon="modalIcon"
    size="sm"
    :static="true"
    @update:model-value="emit('update:modelValue', $event)"
  >

    <OperationStepsIndicator v-if="step < 3" :step="step" :operation="operation" />

    <OperationStepAmount
      v-if="step === 1"
      :operation="operation"
      :active-wallet="activeWallet"
      :formatted-balance="formattedBalance"
      :amount="amount"
      :selected-quick-amount="selectedQuickAmount"
      :pending-operation="pendingOperation"
      :pending-loading="pendingLoading"
      :wallet-limits="walletLimits"
      :error-msg="errorMsg"
      @update:amount="emit('update:amount', $event)"
      @select-quick-amount="emit('select-quick-amount', $event)"
      @clear-quick-amount="emit('clear-quick-amount')"
    />

    <OperationStepDepositReceipt
      v-else-if="step === 2 && operation === 'deposit'"
      :active-wallet="activeWallet"
      :parsed-amount="parsedAmount"
      :receipt="receipt"
      :reference="reference"
      :notes="notes"
      :error-msg="errorMsg"
      @update:receipt="emit('update:receipt', $event)"
      @update:reference="emit('update:reference', $event)"
      @update:notes="emit('update:notes', $event)"
    />

    <OperationStepWithdrawDirectory
      v-else-if="step === 2 && operation === 'withdraw'"
      :active-wallet="activeWallet"
      :parsed-amount="parsedAmount"
      :directories="directories"
      :directories-loading="directoriesLoading"
      :selected-directory="selectedDirectory"
      :notes="notes"
      :error-msg="errorMsg"
      @update:selectedDirectory="emit('update:selectedDirectory', $event)"
      @update:notes="emit('update:notes', $event)"
    />

    <OperationStepDone
      v-else-if="step === 3"
      :operation="operation"
      :success-msg="successMsg"
    />

    <template #footer>
      <OperationModalFooter
        :step="step"
        :operation="operation"
        :loading="loading"
        :pending-operation="pendingOperation"
        :pending-loading="pendingLoading"
        :can-go-to-step2="canGoToStep2"
        :can-submit-deposit="canSubmitDeposit"
        :can-submit-withdraw="canSubmitWithdraw"
        @go-to-step1="emit('go-to-step1')"
        @go-to-step2="emit('go-to-step2')"
        @submit="emit('submit')"
        @close="emit('close')"
      />
    </template>
  </Modal>
</template>
