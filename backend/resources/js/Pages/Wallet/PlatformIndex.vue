<script setup lang="ts">
/**
 * Billetera de Plataforma en /wallet — mostrada solo a superadmin, que no
 * tiene wallet de jugador propia (ver WalletController::index()). Reutiliza
 * exactamente el mismo panel del dashboard (PlatformWalletPanel/CashModal +
 * sus composables) en vez del flujo de depósito/retiro del jugador, que no
 * aplica aquí: la caja de plataforma no tiene comprobante, aprobación ni KYC.
 */
import { onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import PlatformWalletPanel   from '@js/Components/Roles/SuperAdmin/Dashboard/PlatformWalletPanel.vue'
import PlatformCashModal     from '@js/Components/Roles/SuperAdmin/Dashboard/PlatformCashModal.vue'
import RkeCommissionsSection from '@js/Components/Roles/SuperAdmin/Dashboard/RkeCommissionsSection.vue'
import { usePlatformWallet }    from '@js/composables/roles/superadmin/dashboard/usePlatformWallet'
import { usePlatformCashModal } from '@js/composables/roles/superadmin/dashboard/usePlatformCashModal'
import { useMovementTypeCatalog } from '@js/composables/wallet/useMovementTypeCatalog'
import { useRkeSummary } from '@js/composables/roles/superadmin/dashboard/useRkeSummary'
import { useDashboardFormatters } from '@js/composables/common/dashboard/useDashboardFormatters'

const {
  platformWallets, activePlatformWallet,
  platformMovements, platformMovMeta,
  platformLoading, platformMovLoading, platformError,
  loadPlatformWallets, loadPlatformMovements, selectPlatformWallet,
} = usePlatformWallet()

const { cashModal, openCashModal, closeCashModal, withdrawConfirmed, submitCash } =
  usePlatformCashModal(activePlatformWallet, loadPlatformWallets)

const { metaFor: movMeta, fetchMovementTypes } = useMovementTypeCatalog()
const { rkeSummary, rkeLoading, rkeError, loadRkeSummary } = useRkeSummary()
const { formatUsd, formatDate, formatDateTime } = useDashboardFormatters()

onMounted(() => {
  loadPlatformWallets()
  fetchMovementTypes()
  loadRkeSummary()
})
</script>

<template>
  <Head title="Billetera de Plataforma" />

  <div class="platform-wallet-page">
    <PlatformWalletPanel
      :wallets="platformWallets"
      :active-wallet="activePlatformWallet"
      :movements="platformMovements"
      :movements-meta="platformMovMeta"
      :loading="platformLoading"
      :mov-loading="platformMovLoading"
      :error="platformError"
      :rke-total="rkeSummary?.totals.total_rke ?? null"
      :mov-meta="movMeta"
      :format-usd="formatUsd"
      :format-date-time="formatDateTime"
      @refresh="loadPlatformWallets"
      @refresh-movements="loadPlatformMovements"
      @select-wallet="selectPlatformWallet"
      @open-cash-modal="openCashModal"
    />

    <PlatformCashModal
      v-model="cashModal"
      :active-wallet="activePlatformWallet"
      :withdraw-confirmed="withdrawConfirmed"
      :format-usd="formatUsd"
      @close="closeCashModal"
      @submit="submitCash"
    />

    <RkeCommissionsSection
      :summary="rkeSummary"
      :loading="rkeLoading"
      :error="rkeError"
      :format-usd="formatUsd"
      :format-date="formatDate"
      @refresh="loadRkeSummary"
    />
  </div>
</template>

<style scoped>
.platform-wallet-page {
  max-width: 900px;
  margin: 0 auto;
  padding: 1.5rem 1.5rem 3rem;
}
</style>
