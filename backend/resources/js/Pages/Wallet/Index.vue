<script setup lang="ts">
import { computed, ref, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { usePage } from '@inertiajs/vue3'
import backendClient   from '@js/plugins/axios'
import { useWallet }   from '@js/composables/wallet/useWallet'
import { useTos }      from '@js/composables/useTos'
import { useKyc }      from '@js/composables/useKyc'
import { useMovementMeta } from '@js/composables/wallet/useMovementMeta'
import Modal           from '@js/Components/Common/Modal/Modal.vue'
import TosModal        from '@js/Components/Common/TosModal.vue'
import KycBanner       from '@js/Components/Common/KycBanner.vue'
import KycUploadModal  from '@js/Components/Common/KycUploadModal.vue'
import WalletHeader        from '@js/Components/Wallet/WalletHeader.vue'
import WalletSummaryCard   from '@js/Components/Wallet/WalletSummaryCard.vue'
import MovementTypesCard   from '@js/Components/Wallet/MovementTypesCard.vue'
import MovementsList       from '@js/Components/Wallet/MovementsList.vue'
import WalletOperationModal from '@js/Components/Wallet/WalletOperationModal.vue'
import MovementDetailModal  from '@js/Components/Wallet/MovementDetailModal.vue'
import WalletResultToast    from '@js/Components/Wallet/WalletResultToast.vue'
import type { Wallet, WalletMovement, WalletPageProps } from '@js/types/wallet'

const props = defineProps<WalletPageProps>()

const page   = usePage()
const userId = computed(() => (page.props.auth as any)?.user?.id as string | undefined)

// ─── Wallet core (debe ir primero para que wallets esté disponible) ──────────
const {
  wallets, activeWallet,
  movements, movementsMeta, movementsLoading,
  modalOpen, operation, step,
  amount, selectedQuickAmount,
  receipt, reference, notes,
  directories, directoriesLoading, selectedDirectory,
  pendingOperation, pendingLoading,
  loading, errorMsg, successMsg, tosRequired,
  resultToast,
  canWithdraw, formattedBalance, formattedBonusBalance, hasBonusBalance, parsedAmount,
  canGoToStep2, canSubmitDeposit, canSubmitWithdraw,
  openModal, closeModal,
  goToStep1, goToStep2,
  selectWallet, loadMovements, loadMoreMovements,
  submit, dismissResultToast,
} = useWallet(props.wallets, userId.value)

// ─── TOS ────────────────────────────────────────────────────────────────────
const { status: tosStatus, accepting: tosAccepting, error: tosError,
        needsAcceptance, accept: acceptTos } = useTos()

const tosModalOpen  = ref(false)
const pendingAction = ref<'deposit' | 'withdraw' | null>(null)

async function handleTosAccept() {
  const ok = await acceptTos()
  if (!ok) return

  tosModalOpen.value = false

  if (pendingAction.value) {
    const action = pendingAction.value
    pendingAction.value = null
    openModal(action)
    return
  }

  // El modal de operación ya estaba abierto y el envío fue rechazado por
  // falta de aceptación de TOS: reintentamos automáticamente para que el
  // usuario no tenga que volver a llenar el formulario.
  if (modalOpen.value && step.value === 2) {
    errorMsg.value = null
    await submit()
  }
}

// ─── KYC ────────────────────────────────────────────────────────────────────
const { state: kycState, limits: kycLimits, uploading: kycUploading,
        error: kycError, success: kycSuccess,
        fetchStatus: fetchKycStatus, fetchLimits, submitDocument } = useKyc()

const kycModalOpen = ref(false)

async function handleKycSubmit(form: Parameters<typeof submitDocument>[0]) {
  await submitDocument(form)
}

watch(
  () => activeWallet.value?.currency_id,
  (cid) => { if (cid) { fetchKycStatus(); fetchLimits(cid) } },
  { immediate: true }
)

// Si el servidor rechaza con tos_required, abrir modal TOS
watch(tosRequired, (v) => { if (v) { tosModalOpen.value = true; tosRequired.value = false } })

// ─── Gate TOS en apertura de modales ────────────────────────────────────────
function openDepositModal() {
  if (needsAcceptance.value) { pendingAction.value = 'deposit'; tosModalOpen.value = true; return }
  openModal('deposit')
}
function openWithdrawModal() {
  if (needsAcceptance.value) { pendingAction.value = 'withdraw'; tosModalOpen.value = true; return }
  openModal('withdraw')
}

// ─── Metadata visual de tipos de movimiento (icono, color, agrupación) ─────
const { movMeta, movementTypeSections } = useMovementMeta(computed(() => props.movementTypes))

const hasMoreMovements = computed(() =>
  movementsMeta.value !== null &&
  movementsMeta.value.current_page < movementsMeta.value.last_page
)

// Depósitos aún no confirmados por un admin, de la billetera activa: se
// muestran como "en revisión" en el header para que el saldo no se perciba
// como incorrecto cuando el usuario acaba de subir un comprobante.
const pendingDepositsAmount = computed(() =>
  movements.value
    .filter(m => m.type === 'DEPOSIT' && m.status === 'PENDING' && m.wallet_id === activeWallet.value?.id)
    .reduce((sum, m) => sum + m.amount, 0)
)

// ─── Sincroniza la altura máxima de "Tipos de movimiento" con "Resumen" ────
const summaryCardRef    = ref<HTMLElement | null>(null)
const movementTypesMaxHeight = ref<number | null>(null)
let resizeObserver: ResizeObserver | null = null

function syncMovementTypesHeight() {
  if (summaryCardRef.value) {
    movementTypesMaxHeight.value = summaryCardRef.value.offsetHeight
  }
}

// ─── Abrir modal de detalle desde hash de URL (#movement/{id}) ─────────────
// Usado cuando se llega desde una notificación con link /wallet#movement/{id}
async function openMovementFromHash() {
  const hash = window.location.hash
  const match = hash.match(/^#movement\/(.+)$/)
  if (!match) return

  const movementId = match[1]

  // Limpiar el hash para que no se reabra en futuras navegaciones internas
  history.replaceState(null, '', window.location.pathname + window.location.search)

  // Buscar primero en los movimientos ya cargados
  const found = movements.value.find(m => m.id === movementId)
  if (found) {
    openMovementDetail(found)
    return
  }

  // No está en la lista (puede ser un movimiento antiguo o aún cargando):
  // fetch individual al endpoint del usuario
  try {
    const { data } = await backendClient.get(`wallet/movements/${movementId}`)
    if (data.success && data.data) {
      openMovementDetail(data.data)
    }
  } catch {
    // ignorar silenciosamente
  }
}

onMounted(async () => {
  await nextTick()
  syncMovementTypesHeight()

  if (typeof ResizeObserver !== 'undefined' && summaryCardRef.value) {
    resizeObserver = new ResizeObserver(syncMovementTypesHeight)
    resizeObserver.observe(summaryCardRef.value)
  }
  window.addEventListener('resize', syncMovementTypesHeight)

  // Abrir modal si la URL tiene #movement/{id} (deep-link desde notificación).
  // Si los movimientos aún están cargando, esperar a que terminen antes de buscar.
  if (movementsLoading.value) {
    const stop = watch(movementsLoading, (loading) => {
      if (!loading) {
        stop()
        openMovementFromHash()
      }
    })
  } else {
    openMovementFromHash()
  }
})

onBeforeUnmount(() => {
  resizeObserver?.disconnect()
  window.removeEventListener('resize', syncMovementTypesHeight)
})

function selectQuickAmount(value: number) {
  selectedQuickAmount.value = value
  amount.value = value.toString()
}

function clearQuickAmount() {
  selectedQuickAmount.value = null
}

// ─── Modal de detalle de movimiento ────────────────────────────────────────
const detailMovement = ref<WalletMovement | null>(null)
const detailOpen     = ref(false)

function openMovementDetail(mov: WalletMovement) {
  detailMovement.value = mov
  detailOpen.value     = true
}

const detailMeta = computed(() => {
  if (!detailMovement.value) return null
  return movMeta(detailMovement.value.type)
})

function handleSelectWallet(wallet: Wallet) {
  selectWallet(wallet)
}
</script>

<template>
  <div class="wallet-page">

    <!-- ── Toast de resultado (aprobado / rechazado por admin) ─────── -->
    <WalletResultToast :toast="resultToast" @dismiss="dismissResultToast" />

    <div class="wallet-body">

      <!-- ── Header ─────────────────────────────────────────────────── -->
      <WalletHeader
        :wallets="wallets"
        :active-wallet="activeWallet"
        :formatted-balance="formattedBalance"
        :formatted-bonus-balance="formattedBonusBalance"
        :has-bonus-balance="hasBonusBalance"
        :can-withdraw="canWithdraw"
        :pending-deposits-amount="pendingDepositsAmount"
        @select-wallet="handleSelectWallet"
        @deposit="openDepositModal"
        @withdraw="openWithdrawModal"
      />

      <!-- ── Sin billeteras ──────────────────────────────────────────── -->
      <div v-if="!wallets.length" class="wallet-empty">
        <div class="wallet-empty__icon"><i class="fas fa-wallet"></i></div>
        <h2 class="wallet-empty__title">Sin billeteras activas</h2>
        <p class="wallet-empty__text">Contacta al soporte para activar tu billetera y comenzar a gestionar tus fondos.</p>
        <a
          class="action-btn action-btn--deposit"
          href="https://t.me/+rV3KWBuuKOtjYzkx"
          target="_blank"
          rel="noopener noreferrer"
        >
          <i class="fas fa-headset"></i> Contactar soporte
        </a>
      </div>

      <template v-else>
        <div class="wallet-grid">

          <!-- ── Resumen de actividad ──────────────────────────────── -->
          <div ref="summaryCardRef">
            <WalletSummaryCard
              :movements="movements"
              :currency-symbol="activeWallet?.currency.symbol"
              :decimal-places="activeWallet?.currency.decimal_places"
            />
          </div>

          <!-- ── Tipos de movimiento ────────────────────────────────── -->
          <MovementTypesCard
            :sections="movementTypeSections"
            :max-height="movementTypesMaxHeight"
          />
        </div>

        <!-- ── KYC Banner + Límites ─────────────────────────────────── -->
        <div v-if="kycState" class="wallet-card kyc-card">
          <KycBanner
            :state="kycState"
            :limits="kycLimits"
            :currency-symbol="activeWallet?.currency.symbol"
            @verify="kycModalOpen = true"
          />
        </div>

        <!-- ── Historial de movimientos ──────────────────────────────── -->
        <MovementsList
          :movements="movements"
          :movements-loading="movementsLoading"
          :has-more="hasMoreMovements"
          :currency-symbol="activeWallet?.currency.symbol"
          :decimal-places="activeWallet?.currency.decimal_places"
          :mov-meta="movMeta"
          @refresh="loadMovements(1)"
          @load-more="loadMoreMovements"
          @open-detail="openMovementDetail"
        />
      </template>
    </div><!-- /wallet-body -->

    <!-- ══ MODAL MULTI-STEP (depositar / retirar) ═══════════════════════ -->
    <WalletOperationModal
      v-model="modalOpen"
      :operation="operation"
      :step="step"
      :active-wallet="activeWallet"
      :formatted-balance="formattedBalance"
      :amount="amount"
      :selected-quick-amount="selectedQuickAmount"
      :receipt="receipt"
      :reference="reference"
      :notes="notes"
      :directories="directories"
      :directories-loading="directoriesLoading"
      :selected-directory="selectedDirectory"
      :pending-operation="pendingOperation"
      :pending-loading="pendingLoading"
      :wallet-limits="kycLimits"
      :loading="loading"
      :error-msg="errorMsg"
      :success-msg="successMsg"
      :parsed-amount="parsedAmount"
      :can-go-to-step2="canGoToStep2"
      :can-submit-deposit="canSubmitDeposit"
      :can-submit-withdraw="canSubmitWithdraw"
      @update:amount="amount = $event"
      @update:receipt="receipt = $event"
      @update:reference="reference = $event"
      @update:notes="notes = $event"
      @update:selected-directory="selectedDirectory = $event"
      @select-quick-amount="selectQuickAmount"
      @clear-quick-amount="clearQuickAmount"
      @go-to-step1="goToStep1"
      @go-to-step2="goToStep2"
      @submit="submit"
      @close="closeModal"
    />

    <!-- ══ MODAL DETALLE DE MOVIMIENTO ══════════════════════════════════ -->
    <MovementDetailModal
      v-model="detailOpen"
      :movement="detailMovement"
      :meta="detailMeta"
      :active-wallet="activeWallet"
    />

    <!-- ══ MODAL TOS ════════════════════════════════════════════════════ -->
    <TosModal
      v-model="tosModalOpen"
      :status="tosStatus"
      :accepting="tosAccepting"
      :error="tosError"
      @accept="handleTosAccept"
    />

    <!-- ══ MODAL KYC ════════════════════════════════════════════════════ -->
    <KycUploadModal
      v-model="kycModalOpen"
      :uploading="kycUploading"
      :error="kycError"
      :success="kycSuccess"
      @submit="handleKycSubmit"
    />

  </div>
</template>

<style scoped>
/* ── Página ── */
.wallet-page {
  min-height: 100vh;
  background: var(--ds-bg);
}

.wallet-body {
  max-width: 1100px;
  margin: 0 auto;
  padding: 1.5rem 1.5rem 3rem;
}

/* ── Empty state ── */
.wallet-empty {
  background: var(--ds-surface);
  border-radius: 0 0 16px 16px;
  box-shadow: 0 4px 24px rgba(102, 126, 234, 0.10);
  padding: 4rem 2rem;
  text-align: center;
}

.wallet-empty__icon {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: var(--ds-surface-2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  color: var(--ds-text-muted);
  margin: 0 auto 1.5rem;
}

.wallet-empty__title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--ds-text-2);
  margin: 0 0 0.5rem;
}

.wallet-empty__text {
  color: var(--ds-text-muted);
  margin: 0 0 1.5rem;
}

/* ── Botón de acción (reutilizado en el empty state) ── */
.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 18px;
  border-radius: 10px;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: all 0.15s ease;
  white-space: nowrap;
  text-decoration: none;
}

.action-btn--deposit {
  background: linear-gradient(135deg, #22c55e, #16a34a);
  color: #fff;
}
.action-btn--deposit:hover { filter: brightness(1.08); transform: translateY(-1px); color: #fff; }

/* ── Grid principal ── */
.wallet-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  align-items: start;
  gap: 0 1px;
  background: var(--ds-surface);
  border-bottom: 1px solid var(--ds-border);
}

.wallet-card {
  background: var(--ds-surface);
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.kyc-card {
  border-radius: 0;
  border-top: 1px solid var(--ds-border);
  padding: 1rem 1.5rem;
}

/* ── Responsive ── */
@media (max-width: 768px) {
  .wallet-grid { grid-template-columns: 1fr; }
}
</style>
