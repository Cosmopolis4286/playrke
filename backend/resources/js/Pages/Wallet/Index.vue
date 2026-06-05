<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useWallet }   from '@js/composables/wallet/useWallet'
import { useTos }      from '@js/composables/useTos'
import { useKyc }      from '@js/composables/useKyc'
import Modal           from '@js/Components/Common/Modal/Modal.vue'
import TosModal        from '@js/Components/Common/TosModal.vue'
import KycBanner       from '@js/Components/Common/KycBanner.vue'
import KycUploadModal  from '@js/Components/Common/KycUploadModal.vue'
import type { WalletPageProps, WalletMovementType } from '@js/types/wallet'

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
  canWithdraw, formattedBalance, parsedAmount,
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
  if (ok && pendingAction.value) {
    const action = pendingAction.value
    pendingAction.value = null
    tosModalOpen.value  = false
    openModal(action)
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

const quickAmounts = [10, 25, 50, 100, 200, 500]

// ─── helpers visuales para movimientos ─────────────────────────────────────
const movementMeta: Record<WalletMovementType, {
  icon: string; label: string; colorClass: string; sign: '+' | '-'; description: string
}> = {
  DEPOSIT:      { icon: 'fas fa-arrow-down',      label: 'Depósito',        colorClass: 'meta--deposit',      sign: '+', description: 'Fondos añadidos a tu cuenta' },
  PRIZE:        { icon: 'fas fa-trophy',          label: 'Premio ganado',    colorClass: 'meta--prize',        sign: '+', description: 'Premio por juego ganado' },
  PURCHASE:     { icon: 'fas fa-ticket-alt',      label: 'Compra de cartón', colorClass: 'meta--purchase',     sign: '-', description: 'Cartón de bingo adquirido' },
  WITHDRAWAL:   { icon: 'fas fa-arrow-up',        label: 'Retiro',           colorClass: 'meta--withdrawal',   sign: '-', description: 'Fondos retirados de tu cuenta' },
  TRIVIA_ENTRY: { icon: 'fas fa-brain',           label: 'Entrada Trivia',   colorClass: 'meta--trivia-entry', sign: '-', description: 'Entrada a sala de Trivia' },
  TRIVIA_PRIZE: { icon: 'fas fa-brain',           label: 'Premio Trivia',    colorClass: 'meta--trivia-prize', sign: '+', description: 'Premio ganado en Trivia' },
}

function formatDate(iso: string): string {
  const date = new Date(iso)
  const now  = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMin = Math.floor(diffMs / 60000)
  const diffH   = diffMs / 3600000
  const timeStr = date.toLocaleTimeString('es-VE', { hour: '2-digit', minute: '2-digit' })
  const dateStr = new Intl.DateTimeFormat('es-VE', { day: '2-digit', month: 'short', year: 'numeric' }).format(date)
  if (diffMin < 1)  return 'Ahora mismo'
  if (diffMin < 60) return `Hace ${diffMin} min · ${timeStr}`
  if (diffH < 24)   return `Hoy, ${timeStr}`
  if (diffH < 48)   return `Ayer, ${timeStr}`
  return `${dateStr} · ${timeStr}`
}

function formatAmount(value: number) {
  return new Intl.NumberFormat('es-VE', {
    minimumFractionDigits: activeWallet.value?.currency.decimal_places ?? 2,
    maximumFractionDigits: activeWallet.value?.currency.decimal_places ?? 2,
  }).format(value)
}

const receiptPreviewUrl = computed(() => {
  if (!receipt.value) return null
  if (!receipt.value.type.startsWith('image/')) return null
  return URL.createObjectURL(receipt.value)
})

const hasMoreMovements = () =>
  movementsMeta.value !== null &&
  movementsMeta.value.current_page < movementsMeta.value.last_page

const totalDeposits = computed(() =>
  movements.value.filter(m => m.type === 'DEPOSIT').reduce((s, m) => s + m.amount, 0)
)
const totalPrizes = computed(() =>
  movements.value.filter(m => m.type === 'PRIZE').reduce((s, m) => s + m.amount, 0)
)
const totalWithdrawals = computed(() =>
  movements.value.filter(m => m.type === 'WITHDRAWAL').reduce((s, m) => s + m.amount, 0)
)

function selectQuickAmount(value: number) {
  selectedQuickAmount.value = value
  amount.value = value.toString()
}

function clearQuickAmount() {
  selectedQuickAmount.value = null
}

function onReceiptChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0] ?? null
  receipt.value = file
}

// ─── Modal de detalle de movimiento ────────────────────────────────────────
const detailMovement = ref<import('@js/types/wallet').WalletMovement | null>(null)
const detailOpen     = ref(false)

function openMovementDetail(mov: import('@js/types/wallet').WalletMovement) {
  detailMovement.value = mov
  detailOpen.value     = true
}

const detailMeta = computed(() => {
  if (!detailMovement.value) return null
  return movementMeta[detailMovement.value.type]
})

interface TimelineStep {
  label:    string
  sub:      string | null
  done:     boolean
  active:   boolean
  rejected: boolean
  icon:     string
  date:     string | null
}

const detailSteps = computed((): TimelineStep[] => {
  const mov = detailMovement.value
  if (!mov) return []

  const isDep  = mov.type === 'DEPOSIT'
  const isWith = mov.type === 'WITHDRAWAL'

  if (isDep || isWith) {
    const done     = mov.status === 'COMPLETED'
    const rejected = mov.status === 'REJECTED'
    const pending  = mov.status === 'PENDING'

    return [
      {
        label:    'Solicitud recibida',
        sub:      isDep
          ? `Depósito de ${activeWallet.value?.currency.symbol ?? ''} ${formatAmount(mov.amount)} registrado`
          : `Retiro de ${activeWallet.value?.currency.symbol ?? ''} ${formatAmount(mov.amount)} solicitado`,
        done:     true,
        active:   false,
        rejected: false,
        icon:     'fa-paper-plane',
        date:     mov.created_at,
      },
      {
        label:    pending ? 'En revisión' : (done ? 'Revisión completada' : 'Revisión fallida'),
        sub:      pending
          ? 'Nuestro equipo está verificando tu solicitud'
          : done
            ? (isDep ? 'Comprobante verificado correctamente' : 'Solicitud de retiro aprobada')
            : (isDep ? 'El comprobante no pudo ser verificado' : 'La solicitud fue rechazada'),
        done:     done || rejected,
        active:   pending,
        rejected: false,
        icon:     pending ? 'fa-hourglass-half' : (done ? 'fa-check' : 'fa-times'),
        date:     !pending && mov.processed_at ? mov.processed_at : null,
      },
      {
        label:    done
          ? (isDep ? 'Fondos acreditados' : 'Retiro enviado')
          : rejected
            ? (isDep ? 'Depósito rechazado' : 'Retiro cancelado')
            : (isDep ? 'Pendiente de acreditación' : 'Pendiente de envío'),
        sub:      done
          ? (isDep
              ? `+${activeWallet.value?.currency.symbol ?? ''} ${formatAmount(mov.amount)} en tu billetera`
              : `${activeWallet.value?.currency.symbol ?? ''} ${formatAmount(mov.amount)} transferidos`)
          : rejected
            ? (isDep ? 'El monto no fue acreditado' : 'El saldo fue reintegrado a tu cuenta')
            : null,
        done,
        active:   false,
        rejected,
        icon:     done ? 'fa-check-circle' : (rejected ? 'fa-times-circle' : 'fa-circle'),
        date:     (done || rejected) && mov.processed_at ? mov.processed_at : null,
      },
    ]
  }

  // PURCHASE
  if (mov.type === 'PURCHASE') {
    return [
      {
        label:    'Pago procesado',
        sub:      `${activeWallet.value?.currency.symbol ?? ''} ${formatAmount(mov.amount)} descontados de tu saldo`,
        done:     true, active: false, rejected: false,
        icon:     'fa-check-circle',
        date:     mov.created_at,
      },
      {
        label:    'Cartón(es) asignados',
        sub:      mov.description ?? 'Tu participación en la sala fue registrada',
        done:     true, active: false, rejected: false,
        icon:     'fa-ticket-alt',
        date:     mov.created_at,
      },
    ]
  }

  // PRIZE / TRIVIA_PRIZE
  if (mov.type === 'PRIZE' || mov.type === 'TRIVIA_PRIZE') {
    return [
      {
        label:    '¡Premio ganado!',
        sub:      mov.description ?? 'Felicitaciones por tu victoria',
        done:     true, active: false, rejected: false,
        icon:     'fa-trophy',
        date:     mov.created_at,
      },
      {
        label:    'Fondos acreditados',
        sub:      `+${activeWallet.value?.currency.symbol ?? ''} ${formatAmount(mov.amount)} añadidos a tu billetera`,
        done:     true, active: false, rejected: false,
        icon:     'fa-check-circle',
        date:     mov.created_at,
      },
    ]
  }

  // TRIVIA_ENTRY
  return [
    {
      label:    'Entrada registrada',
      sub:      mov.description ?? 'Participación en sala de Trivia',
      done:     true, active: false, rejected: false,
      icon:     'fa-brain',
      date:     mov.created_at,
    },
  ]
})

// Título dinámico del modal por paso
const modalTitle = computed(() => {
  if (step.value === 3) return operation.value === 'deposit' ? 'Solicitud enviada' : 'Solicitud enviada'
  if (step.value === 2) return operation.value === 'deposit' ? 'Comprobante de pago' : 'Seleccionar destino'
  return operation.value === 'deposit' ? 'Depositar fondos' : 'Retirar fondos'
})

const modalIcon = computed(() => {
  if (step.value === 3) return 'fa-check-circle'
  return operation.value === 'deposit' ? 'fa-arrow-down' : 'fa-arrow-up'
})
</script>

<template>
  <div class="wallet-page">

    <!-- ── Toast de resultado (aprobado / rechazado por admin) ─────── -->
    <Transition name="wt-toast">
      <div
        v-if="resultToast"
        class="wt-toast"
        :class="resultToast.type === 'approved' ? 'wt-toast--approved' : 'wt-toast--rejected'"
      >
        <div class="wt-toast__icon">
          <i :class="resultToast.type === 'approved' ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>
        </div>
        <div class="wt-toast__body">
          <p class="wt-toast__title">
            <template v-if="resultToast.type === 'approved'">
              {{ resultToast.movementType === 'DEPOSIT' ? '✅ Depósito aprobado' : '✅ Retiro aprobado' }}
            </template>
            <template v-else>
              {{ resultToast.movementType === 'DEPOSIT' ? '❌ Depósito rechazado' : '↩️ Retiro rechazado — saldo reintegrado' }}
            </template>
          </p>
          <p class="wt-toast__sub">
            {{ resultToast.currencySymbol }} {{ resultToast.amount.toLocaleString('es-VE', { minimumFractionDigits: 2 }) }}
          </p>
        </div>
        <button class="wt-toast__close" @click="dismissResultToast">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </Transition>

    <div class="wallet-body">

      <!-- ── Header ─────────────────────────────────────────────────── -->
      <header class="wallet-header">
        <div class="wallet-header__inner">
          <!-- Icono + título -->
          <div class="wallet-header__identity">
            <div class="wallet-header__icon">
              <i class="fas fa-wallet"></i>
            </div>
            <div>
              <h1 class="wallet-header__title">Mi Billetera</h1>
              <p class="wallet-header__subtitle">Gestiona tus fondos y revisa tu actividad</p>
            </div>
          </div>

          <!-- Saldo destacado -->
          <div v-if="activeWallet" class="wallet-header__balance">
            <span class="wallet-header__balance-label">Saldo disponible</span>
            <span class="wallet-header__balance-amount">
              {{ activeWallet.currency.symbol }}&nbsp;{{ formattedBalance }}
            </span>
            <span
              class="wallet-header__status-badge"
              :class="{
                'badge--active':   activeWallet.status === 'ACTIVE',
                'badge--inactive': activeWallet.status === 'INACTIVE',
                'badge--blocked':  activeWallet.status === 'BLOCKED',
              }"
            >
              <span class="badge-dot"></span>
              {{ activeWallet.status === 'ACTIVE' ? 'Activa' :
                 activeWallet.status === 'INACTIVE' ? 'Inactiva' : 'Bloqueada' }}
            </span>
          </div>

          <!-- Acciones -->
          <div v-if="activeWallet" class="wallet-header__actions">
            <button class="action-btn action-btn--deposit" @click="openDepositModal">
              <i class="fas fa-arrow-down"></i> Depositar
            </button>
            <button
              class="action-btn action-btn--withdraw"
              :class="{ 'action-btn--disabled': !canWithdraw }"
              :disabled="!canWithdraw"
              @click="openWithdrawModal"
            >
              <i class="fas fa-arrow-up"></i>
              {{ canWithdraw ? 'Retirar' : 'Sin saldo' }}
            </button>
          </div>
        </div>

        <!-- Selector de billetera (si hay varias) -->
        <div v-if="wallets.length > 1" class="wallet-selector">
          <button
            v-for="wallet in wallets"
            :key="wallet.id"
            type="button"
            class="wallet-selector__btn"
            :class="{ 'wallet-selector__btn--active': activeWallet?.id === wallet.id }"
            @click="selectWallet(wallet)"
          >
            <i class="fas fa-coins"></i>
            {{ wallet.currency.symbol }} {{ wallet.currency.iso_code }}
          </button>
        </div>
      </header>

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
          <div class="wallet-card">
            <h3 class="wallet-card__title">
              <i class="fas fa-chart-line"></i> Resumen de actividad
              <span class="live-dot" title="Actualización en tiempo real"></span>
            </h3>
            <div class="summary-list">
              <div class="summary-item summary-item--deposit">
                <div class="summary-item__icon"><i class="fas fa-arrow-down"></i></div>
                <div class="summary-item__info">
                  <p class="summary-item__label">Depósitos</p>
                  <p class="summary-item__sublabel">Total recibido</p>
                </div>
                <p class="summary-item__amount summary-item__amount--deposit">
                  {{ activeWallet?.currency.symbol }} {{ formatAmount(totalDeposits) }}
                </p>
              </div>

              <div class="summary-item summary-item--prize">
                <div class="summary-item__icon"><i class="fas fa-trophy"></i></div>
                <div class="summary-item__info">
                  <p class="summary-item__label">Premios</p>
                  <p class="summary-item__sublabel">Ganancias</p>
                </div>
                <p class="summary-item__amount summary-item__amount--prize">
                  {{ activeWallet?.currency.symbol }} {{ formatAmount(totalPrizes) }}
                </p>
              </div>

              <div class="summary-item summary-item--withdrawal">
                <div class="summary-item__icon"><i class="fas fa-arrow-up"></i></div>
                <div class="summary-item__info">
                  <p class="summary-item__label">Retiros</p>
                  <p class="summary-item__sublabel">Total retirado</p>
                </div>
                <p class="summary-item__amount summary-item__amount--withdrawal">
                  {{ activeWallet?.currency.symbol }} {{ formatAmount(totalWithdrawals) }}
                </p>
              </div>
            </div>
          </div>

          <!-- ── Tipos de movimiento ────────────────────────────────── -->
          <div class="wallet-card">
            <h3 class="wallet-card__title">
              <i class="fas fa-info-circle"></i> Tipos de movimiento
            </h3>
            <div class="movement-types">
              <div
                v-for="(meta, key) in movementMeta"
                :key="key"
                class="movement-type-item"
                :class="meta.colorClass"
              >
                <div class="movement-type-item__icon">
                  <i :class="meta.icon"></i>
                </div>
                <div class="movement-type-item__info">
                  <p class="movement-type-item__label">{{ meta.label }}</p>
                  <p class="movement-type-item__desc">{{ meta.description }}</p>
                </div>
                <span class="movement-type-item__sign" :class="meta.sign === '+' ? 'sign--positive' : 'sign--negative'">
                  {{ meta.sign }}
                </span>
              </div>
            </div>
          </div>
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
        <div class="wallet-card movements-card">
          <div class="movements-header">
            <h3 class="wallet-card__title">
              <i class="fas fa-history"></i> Movimientos recientes
              <span class="live-dot" title="Actualización en tiempo real"></span>
            </h3>
            <div class="movements-header__right">
              <button
                type="button"
                class="refresh-btn"
                :class="{ 'refresh-btn--loading': movementsLoading }"
                :disabled="movementsLoading"
                @click="loadMovements(1)"
                title="Actualizar"
              >
                <i class="fas fa-sync-alt"></i>
              </button>
              <span class="movements-count">{{ movements.length }} movimientos</span>
            </div>
          </div>

          <!-- Cargando -->
          <div v-if="movementsLoading && !movements.length" class="movements-loading">
            <div class="spinner"></div>
            <p>Cargando movimientos...</p>
          </div>

          <!-- Sin movimientos -->
          <div v-else-if="!movements.length" class="movements-empty">
            <div class="movements-empty__icon"><i class="fas fa-receipt"></i></div>
            <h3>Sin movimientos</h3>
            <p>Aún no hay movimientos registrados en esta billetera.</p>
            <p class="movements-empty__hint">Los depósitos, retiros, premios y compras aparecerán aquí.</p>
          </div>

          <!-- Lista -->
          <template v-else>
            <ul class="movements-list">
              <li
                v-for="(mov, index) in movements"
                :key="mov.id"
                class="movement-row movement-row--clickable"
                :class="{ 'movement-row--first': index === 0 }"
                @click="openMovementDetail(mov)"
              >
                <div
                  class="movement-row__icon"
                  :class="movementMeta[mov.type].colorClass"
                >
                  <i :class="movementMeta[mov.type].icon"></i>
                </div>

                <div class="movement-row__info">
                  <div class="movement-row__title-row">
                    <p class="movement-row__label">{{ movementMeta[mov.type].label }}</p>
                    <span v-if="index === 0" class="badge-recent">Más reciente</span>
                    <span
                      v-if="mov.status"
                      class="badge-status"
                      :class="{
                        'badge-status--pending':   mov.status === 'PENDING',
                        'badge-status--completed': mov.status === 'COMPLETED',
                        'badge-status--rejected':  mov.status === 'REJECTED',
                        'badge-status--cancelled': mov.status === 'CANCELLED',
                      }"
                    >
                      <i :class="{
                        'fas fa-clock':      mov.status === 'PENDING',
                        'fas fa-check':      mov.status === 'COMPLETED',
                        'fas fa-times':      mov.status === 'REJECTED',
                        'fas fa-ban':        mov.status === 'CANCELLED',
                      }"></i>
                      {{ mov.status === 'PENDING' ? 'Pendiente' : mov.status === 'COMPLETED' ? 'Completado' : mov.status === 'REJECTED' ? 'Rechazado' : 'Cancelado' }}
                    </span>
                  </div>
                  <p class="movement-row__desc">{{ mov.description ?? movementMeta[mov.type].description }}</p>
                  <p class="movement-row__date">
                    <i class="fas fa-clock"></i>
                    {{ formatDate(mov.created_at) }}
                  </p>
                </div>

                <div class="movement-row__amounts">
                  <p
                    class="movement-row__amount"
                    :class="movementMeta[mov.type].sign === '+' ? 'amount--positive' : 'amount--negative'"
                  >
                    {{ movementMeta[mov.type].sign }}{{ activeWallet?.currency.symbol }}&nbsp;{{ formatAmount(mov.amount) }}
                  </p>
                  <p class="movement-row__balance">
                    Saldo: {{ activeWallet?.currency.symbol }}&nbsp;{{ formatAmount(mov.balance_after) }}
                  </p>
                </div>
              </li>
            </ul>

            <div v-if="hasMoreMovements() || movementsLoading" class="movements-load-more">
              <button
                type="button"
                class="load-more-btn"
                :disabled="movementsLoading"
                @click="loadMoreMovements"
              >
                <span v-if="movementsLoading" class="flex items-center gap-2">
                  <span class="spinner spinner--sm"></span>
                  Cargando más movimientos...
                </span>
                <span v-else>
                  <i class="fas fa-arrow-down"></i> Ver más movimientos
                </span>
              </button>
            </div>
          </template>
        </div>
      </template>
    </div><!-- /wallet-body -->

    <!-- ══ MODAL MULTI-STEP ══════════════════════════════════════════════ -->
    <Modal
      v-model="modalOpen"
      :title="modalTitle"
      :icon="modalIcon"
      size="sm"
      :static="true"
    >

      <!-- ── Indicador de pasos (solo pasos 1 y 2) ────────────────────── -->
      <div v-if="step < 3" class="op-steps">
        <div class="op-steps__track">
          <div
            class="op-steps__item"
            :class="{ 'op-steps__item--active': step >= 1, 'op-steps__item--done': step > 1 }"
          >
            <div class="op-steps__circle">
              <i v-if="step > 1" class="fas fa-check"></i>
              <span v-else>1</span>
            </div>
            <span class="op-steps__label">Monto</span>
          </div>
          <div class="op-steps__line" :class="{ 'op-steps__line--done': step > 1 }"></div>
          <div
            class="op-steps__item"
            :class="{ 'op-steps__item--active': step >= 2 }"
          >
            <div class="op-steps__circle">
              <span>2</span>
            </div>
            <span class="op-steps__label">
              {{ operation === 'deposit' ? 'Comprobante' : 'Destino' }}
            </span>
          </div>
        </div>
      </div>

      <!-- ════════════════════════════════════════════════════════════════
           PASO 1 — Monto
           ════════════════════════════════════════════════════════════════ -->
      <template v-if="step === 1">

        <!-- Verificando pendiente -->
        <div v-if="pendingLoading" class="op-pending-checking">
          <span class="op-spinner op-spinner--indigo"></span>
          Verificando solicitudes activas...
        </div>

        <!-- BLOQUEADO: ya tiene pendiente -->
        <template v-else-if="pendingOperation">
          <div class="op-pending-block">
            <div class="op-pending-block__icon">
              <i class="fas fa-clock"></i>
            </div>
            <div class="op-pending-block__body">
              <p class="op-pending-block__title">Solicitud en cola</p>
              <p class="op-pending-block__text">
                Tienes {{ pendingOperation.type === 'DEPOSIT' ? 'un depósito' : 'un retiro' }} de
                <strong>{{ activeWallet?.currency.symbol }} {{ formatAmount(pendingOperation.amount) }}</strong>
                pendiente de confirmación.
              </p>
              <p class="op-pending-block__hint">
                No puedes hacer otra solicitud hasta que un administrador la procese.
              </p>
            </div>
          </div>

          <!-- Detalle del movimiento pendiente -->
          <div class="op-pending-detail">
            <div class="op-pending-detail__row">
              <span>Tipo</span>
              <span :class="pendingOperation.type === 'DEPOSIT' ? 'text--deposit' : 'text--withdraw'">
                <i :class="pendingOperation.type === 'DEPOSIT' ? 'fas fa-arrow-down' : 'fas fa-arrow-up'"></i>
                {{ pendingOperation.type === 'DEPOSIT' ? 'Depósito' : 'Retiro' }}
              </span>
            </div>
            <div class="op-pending-detail__row">
              <span>Monto</span>
              <strong>{{ activeWallet?.currency.symbol }} {{ formatAmount(pendingOperation.amount) }}</strong>
            </div>
            <div class="op-pending-detail__row">
              <span>Estado</span>
              <span class="op-pending-detail__badge">
                <span class="op-pending-detail__dot"></span> En revisión
              </span>
            </div>
            <div class="op-pending-detail__row">
              <span>Solicitado</span>
              <span>{{ formatDate(pendingOperation.created_at) }}</span>
            </div>
          </div>
        </template>

        <!-- LIBRE: puede solicitar -->
        <template v-else>
          <!-- Saldo actual -->
          <div class="op-balance">
            <div>
              <p class="op-balance__label">Saldo disponible</p>
              <p class="op-balance__amount">{{ activeWallet?.currency.symbol }} {{ formattedBalance }}</p>
            </div>
            <div class="op-balance__icon"><i class="fas fa-wallet"></i></div>
          </div>

          <!-- Input de monto -->
          <div class="op-field">
            <label class="op-field__label">
              Monto a {{ operation === 'deposit' ? 'depositar' : 'retirar' }}
            </label>
            <div class="op-field__input-wrap">
              <span class="op-field__symbol">{{ activeWallet?.currency.symbol }}</span>
              <input
                v-model="amount"
                type="number"
                min="0.01"
                step="0.01"
                placeholder="0.00"
                class="op-field__input"
                :class="operation === 'deposit' ? 'op-input--deposit' : 'op-input--withdraw'"
                @input="clearQuickAmount"
              />
            </div>

            <!-- Montos rápidos -->
            <div class="op-quick">
              <p class="op-quick__label">Montos rápidos:</p>
              <div class="op-quick__grid">
                <button
                  v-for="qa in quickAmounts"
                  :key="qa"
                  type="button"
                  class="op-quick__btn"
                  :class="{ 'op-quick__btn--active': selectedQuickAmount === qa }"
                  @click="selectQuickAmount(qa)"
                >
                  {{ activeWallet?.currency.symbol }} {{ qa }}
                </button>
              </div>
            </div>
          </div>

          <!-- Error paso 1 -->
          <Transition name="msg-fade">
            <div v-if="errorMsg" class="op-msg op-msg--error">
              <div class="op-msg__icon"><i class="fas fa-exclamation-triangle"></i></div>
              <div><p class="op-msg__title">Atención</p><p>{{ errorMsg }}</p></div>
            </div>
          </Transition>
        </template>
      </template>

      <!-- ════════════════════════════════════════════════════════════════
           PASO 2A — Depósito: comprobante
           ════════════════════════════════════════════════════════════════ -->
      <template v-else-if="step === 2 && operation === 'deposit'">
        <!-- Resumen del monto -->
        <div class="op-summary">
          <i class="fas fa-arrow-down op-summary__icon op-summary__icon--deposit"></i>
          <div>
            <p class="op-summary__label">Depositando</p>
            <p class="op-summary__amount">{{ activeWallet?.currency.symbol }} {{ formatAmount(parsedAmount) }}</p>
          </div>
        </div>

        <div class="op-field">
          <label class="op-field__label">
            Comprobante de pago <span class="op-required">*</span>
          </label>
          <label class="op-file-drop" :class="{ 'op-file-drop--selected': receipt }">
            <input type="file" accept="image/jpeg,image/png,application/pdf" class="op-file-input" @change="onReceiptChange" />
            <template v-if="receipt">
              <i class="fas fa-file-check op-file-drop__icon op-file-drop__icon--ok"></i>
              <p class="op-file-drop__name">{{ receipt.name }}</p>
              <p class="op-file-drop__hint">Haz clic para cambiar</p>
            </template>
            <template v-else>
              <i class="fas fa-cloud-upload-alt op-file-drop__icon"></i>
              <p class="op-file-drop__name">Selecciona o arrastra el comprobante</p>
              <p class="op-file-drop__hint">JPG, PNG o PDF · máx. 5 MB</p>
            </template>
          </label>

          <!-- Previsualización de imagen -->
          <div v-if="receiptPreviewUrl" class="op-receipt-preview">
            <img :src="receiptPreviewUrl" alt="Vista previa del comprobante" class="op-receipt-preview__img" />
          </div>
        </div>

        <div class="op-field">
          <label class="op-field__label">Número de referencia <span class="op-optional">(opcional)</span></label>
          <input v-model="reference" type="text" class="op-field__input op-input--deposit" placeholder="Ej: 00123456" maxlength="100" />
        </div>

        <div class="op-field">
          <label class="op-field__label">Notas <span class="op-optional">(opcional)</span></label>
          <textarea v-model="notes" class="op-field__textarea" rows="2" placeholder="Información adicional para el administrador..." maxlength="500"></textarea>
        </div>

        <Transition name="msg-fade">
          <div v-if="errorMsg" class="op-msg op-msg--error">
            <div class="op-msg__icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div><p class="op-msg__title">Error</p><p>{{ errorMsg }}</p></div>
          </div>
        </Transition>
      </template>

      <!-- ════════════════════════════════════════════════════════════════
           PASO 2B — Retiro: seleccionar directorio
           ════════════════════════════════════════════════════════════════ -->
      <template v-else-if="step === 2 && operation === 'withdraw'">
        <!-- Resumen del monto -->
        <div class="op-summary">
          <i class="fas fa-arrow-up op-summary__icon op-summary__icon--withdraw"></i>
          <div>
            <p class="op-summary__label">Retirando</p>
            <p class="op-summary__amount">{{ activeWallet?.currency.symbol }} {{ formatAmount(parsedAmount) }}</p>
          </div>
        </div>

        <!-- Lista de directorios -->
        <div class="op-field">
          <label class="op-field__label">¿A dónde enviamos el dinero?</label>

          <div v-if="directoriesLoading" class="op-dirs-loading">
            <span class="op-spinner op-spinner--indigo"></span>
            Cargando tus cuentas...
          </div>

          <div v-else-if="directories.length === 0" class="op-dirs-empty">
            <i class="fas fa-exclamation-circle op-dirs-empty__icon"></i>
            <div>
              <p class="op-dirs-empty__text">No tienes cuentas registradas para esta moneda.</p>
              <a href="/directory" class="op-dirs-empty__link">
                <i class="fas fa-plus-circle"></i> Ir a Directorio y agregar una
              </a>
            </div>
          </div>

          <div v-else class="op-dirs-list">
            <label
              v-for="dir in directories"
              :key="dir.id"
              class="op-dir-card"
              :class="{ 'op-dir-card--selected': selectedDirectory?.id === dir.id }"
            >
              <input
                type="radio"
                :value="dir"
                v-model="selectedDirectory"
                class="op-dir-card__radio"
              />
              <div class="op-dir-card__icon">
                <i :class="dir.type === 'pago_movil' ? 'fas fa-mobile-alt' : dir.type === 'pix' ? 'fas fa-barcode' : 'fab fa-bitcoin'"></i>
              </div>
              <div class="op-dir-card__info">
                <p class="op-dir-card__label">{{ dir.label }}</p>
                <p class="op-dir-card__detail">{{ dir.detail }}</p>
                <p v-if="dir.holder" class="op-dir-card__holder">{{ dir.holder }}</p>
              </div>
              <i v-if="selectedDirectory?.id === dir.id" class="fas fa-check-circle op-dir-card__check"></i>
            </label>
          </div>
        </div>

        <div class="op-field">
          <label class="op-field__label">Notas <span class="op-optional">(opcional)</span></label>
          <textarea v-model="notes" class="op-field__textarea" rows="2" placeholder="Información adicional..." maxlength="500"></textarea>
        </div>

        <Transition name="msg-fade">
          <div v-if="errorMsg" class="op-msg op-msg--error">
            <div class="op-msg__icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div><p class="op-msg__title">Error</p><p>{{ errorMsg }}</p></div>
          </div>
        </Transition>
      </template>

      <!-- ════════════════════════════════════════════════════════════════
           PASO 3 — Confirmación
           ════════════════════════════════════════════════════════════════ -->
      <template v-else-if="step === 3">
        <div class="op-done">
          <div class="op-done__icon">
            <i class="fas fa-check"></i>
          </div>
          <h3 class="op-done__title">¡Solicitud enviada!</h3>
          <p class="op-done__text">{{ successMsg }}</p>
          <div class="op-done__detail">
            <i class="fas fa-info-circle"></i>
            {{ operation === 'deposit'
              ? 'Un administrador revisará tu comprobante y acreditará el monto a tu billetera.'
              : 'Un administrador procesará la transferencia a tu cuenta registrada.' }}
          </div>
        </div>
      </template>

      <!-- ── Footer dinámico ──────────────────────────────────────────── -->
      <template #footer>
        <!-- Paso 3: solo cerrar -->
        <template v-if="step === 3">
          <button type="button" class="op-btn op-btn--deposit" style="flex:1" @click="closeModal">
            <i class="fas fa-check"></i> Entendido
          </button>
        </template>

        <!-- Paso 2: volver + confirmar -->
        <template v-else-if="step === 2">
          <button type="button" class="op-btn op-btn--cancel" :disabled="loading" @click="goToStep1">
            <i class="fas fa-arrow-left"></i> Volver
          </button>
          <button
            type="button"
            class="op-btn"
            :class="operation === 'deposit' ? 'op-btn--deposit' : 'op-btn--withdraw'"
            :disabled="loading || (operation === 'deposit' ? !canSubmitDeposit : !canSubmitWithdraw)"
            @click="submit"
          >
            <span v-if="loading" class="op-btn__loading">
              <span class="op-spinner op-spinner--white"></span> Enviando...
            </span>
            <span v-else>
              <i class="fas fa-paper-plane"></i>
              {{ operation === 'deposit' ? 'Enviar solicitud' : 'Confirmar retiro' }}
            </span>
          </button>
        </template>

        <!-- Paso 1: cancelar + siguiente -->
        <template v-else>
          <button type="button" class="op-btn op-btn--cancel" @click="closeModal">
            <i class="fas fa-times"></i> Cerrar
          </button>
          <button
            v-if="!pendingOperation && !pendingLoading"
            type="button"
            class="op-btn"
            :class="operation === 'deposit' ? 'op-btn--deposit' : 'op-btn--withdraw'"
            :disabled="!canGoToStep2"
            @click="goToStep2"
          >
            Siguiente <i class="fas fa-arrow-right"></i>
          </button>
        </template>
      </template>
    </Modal>

    <!-- ══ MODAL DETALLE DE MOVIMIENTO ══════════════════════════════════ -->
    <Modal
      v-model="detailOpen"
      title="Detalle del movimiento"
      icon="fa-receipt"
      size="sm"
    >
      <template v-if="detailMovement && detailMeta">

        <!-- Encabezado: ícono + tipo + monto -->
        <div class="mv-detail__header">
          <div class="mv-detail__icon" :class="detailMeta.colorClass">
            <i :class="detailMeta.icon + ' fa-lg'"></i>
          </div>
          <div class="mv-detail__header-info">
            <p class="mv-detail__type">{{ detailMeta.label }}</p>
            <p
              class="mv-detail__amount"
              :class="detailMeta.sign === '+' ? 'amount--positive' : 'amount--negative'"
            >
              {{ detailMeta.sign }}{{ activeWallet?.currency.symbol }}&nbsp;{{ formatAmount(detailMovement.amount) }}
            </p>
          </div>
          <span
            class="badge-status mv-detail__badge"
            :class="{
              'badge-status--pending':   detailMovement.status === 'PENDING',
              'badge-status--completed': detailMovement.status === 'COMPLETED',
              'badge-status--rejected':  detailMovement.status === 'REJECTED',
              'badge-status--cancelled': detailMovement.status === 'CANCELLED',
            }"
          >
            <i :class="{
              'fas fa-clock':  detailMovement.status === 'PENDING',
              'fas fa-check':  detailMovement.status === 'COMPLETED',
              'fas fa-times':  detailMovement.status === 'REJECTED',
              'fas fa-ban':    detailMovement.status === 'CANCELLED',
            }"></i>
            {{
              detailMovement.status === 'PENDING'   ? 'Pendiente'  :
              detailMovement.status === 'COMPLETED' ? 'Completado' :
              detailMovement.status === 'REJECTED'  ? 'Rechazado'  : 'Cancelado'
            }}
          </span>
        </div>

        <!-- Info financiera -->
        <div class="mv-detail__grid">
          <div class="mv-detail__cell mv-detail__cell--full">
            <span class="mv-detail__cell-label">ID de transacción</span>
            <span class="mv-detail__cell-value mv-detail__cell-value--mono">{{ detailMovement.id }}</span>
          </div>
          <div class="mv-detail__cell">
            <span class="mv-detail__cell-label">Saldo anterior</span>
            <span class="mv-detail__cell-value">
              {{ activeWallet?.currency.symbol }}&nbsp;{{ formatAmount(detailMovement.balance_before) }}
            </span>
          </div>
          <div class="mv-detail__cell">
            <span class="mv-detail__cell-label">Saldo posterior</span>
            <span class="mv-detail__cell-value mv-detail__cell-value--highlight">
              {{ activeWallet?.currency.symbol }}&nbsp;{{ formatAmount(detailMovement.balance_after) }}
            </span>
          </div>
          <div class="mv-detail__cell">
            <span class="mv-detail__cell-label">Fecha</span>
            <span class="mv-detail__cell-value">{{ formatDate(detailMovement.created_at) }}</span>
            <span class="mv-detail__cell-sub">{{ new Date(detailMovement.created_at).toLocaleString('es-VE', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}</span>
          </div>
          <div v-if="detailMovement.description" class="mv-detail__cell mv-detail__cell--full">
            <span class="mv-detail__cell-label">Descripción</span>
            <span class="mv-detail__cell-value">{{ detailMovement.description }}</span>
          </div>
          <div v-if="detailMovement.admin_notes" class="mv-detail__cell mv-detail__cell--full">
            <span class="mv-detail__cell-label">Nota del equipo</span>
            <span class="mv-detail__cell-value">{{ detailMovement.admin_notes }}</span>
          </div>
        </div>

        <!-- Comprobante (solo depósitos con receipt_url) -->
        <div v-if="detailMovement.type === 'DEPOSIT' && detailMovement.receipt_url" class="mv-detail__receipt">
          <p class="mv-detail__cell-label">Comprobante adjunto</p>
          <template v-if="/\.(jpg|jpeg|png|webp|gif)$/i.test(detailMovement.receipt_path ?? '')">
            <img :src="detailMovement.receipt_url" alt="Comprobante" class="mv-detail__receipt-img" />
          </template>
          <template v-else>
            <a :href="detailMovement.receipt_url" target="_blank" rel="noopener" class="mv-detail__receipt-link">
              <i class="fas fa-file-pdf me-1"></i>
              {{ detailMovement.receipt_path?.split('/').pop() ?? 'Ver comprobante' }}
            </a>
          </template>
          <p v-if="detailMovement.reference_id" class="mv-detail__ref">
            Ref: <span class="mv-detail__ref-value">{{ detailMovement.reference_id }}</span>
          </p>
        </div>

        <!-- Timeline de progreso -->
        <div class="mv-detail__timeline">
          <p class="mv-detail__timeline-title">Progreso</p>
          <ul class="mv-timeline">
            <li
              v-for="(step, i) in detailSteps"
              :key="i"
              class="mv-timeline__item"
              :class="{
                'mv-timeline__item--done':     step.done && !step.rejected,
                'mv-timeline__item--active':   step.active,
                'mv-timeline__item--rejected': step.rejected,
              }"
            >
              <div class="mv-timeline__dot">
                <i :class="'fas ' + step.icon"></i>
              </div>
              <div class="mv-timeline__body">
                <p class="mv-timeline__label">{{ step.label }}</p>
                <p v-if="step.sub" class="mv-timeline__sub">{{ step.sub }}</p>
                <p v-if="step.date" class="mv-timeline__date">
                  <i class="fas fa-clock mv-timeline__date-icon"></i>{{ formatDate(step.date) }}
                </p>
              </div>
            </li>
          </ul>
        </div>

        <!-- Feedback contextual -->
        <div
          class="mv-detail__feedback"
          :class="{
            'mv-detail__feedback--pending':   detailMovement.status === 'PENDING',
            'mv-detail__feedback--completed': detailMovement.status === 'COMPLETED',
            'mv-detail__feedback--rejected':  detailMovement.status === 'REJECTED',
          }"
        >
          <i :class="{
            'fas fa-hourglass-half': detailMovement.status === 'PENDING',
            'fas fa-check-circle':  detailMovement.status === 'COMPLETED',
            'fas fa-times-circle':  detailMovement.status === 'REJECTED',
            'fas fa-ban':           detailMovement.status === 'CANCELLED',
          }"></i>
          <span>
            <template v-if="detailMovement.status === 'PENDING'">
              Tu solicitud está siendo revisada por nuestro equipo. Recibirás una notificación cuando sea procesada.
            </template>
            <template v-else-if="detailMovement.status === 'COMPLETED'">
              {{
                detailMovement.type === 'DEPOSIT'
                  ? 'Los fondos han sido acreditados exitosamente a tu billetera.'
                  : detailMovement.type === 'WITHDRAWAL'
                  ? 'Tu retiro fue procesado exitosamente.'
                  : 'Transacción completada exitosamente.'
              }}
            </template>
            <template v-else-if="detailMovement.status === 'REJECTED'">
              {{
                detailMovement.type === 'DEPOSIT'
                  ? 'Tu depósito fue rechazado. El monto no fue acreditado.'
                  : 'Tu retiro fue rechazado. El monto ha sido reintegrado a tu saldo.'
              }}
            </template>
            <template v-else>
              Esta transacción fue cancelada.
            </template>
          </span>
        </div>

      </template>

      <!-- Footer: solo cerrar -->
      <template #footer>
        <button type="button" class="op-btn op-btn--cancel" @click="detailOpen = false">
          <i class="fas fa-times"></i> Cerrar
        </button>
      </template>
    </Modal>

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
  background: #f0f2f7;
}

.wallet-body {
  max-width: 1100px;
  margin: 0 auto;
  padding: 1.5rem 1.5rem 3rem;
}

/* ── Header ── */
.wallet-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 16px 16px 0 0;
  padding: 2rem 1.5rem 1.25rem;
  margin-bottom: 0;
}

.wallet-header__inner {
  display: flex;
  align-items: center;
  gap: 2rem;
  flex-wrap: wrap;
}

.wallet-header__identity {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex: 0 0 auto;
}

.wallet-header__icon {
  width: 56px;
  height: 56px;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: #fff;
  flex-shrink: 0;
}

.wallet-header__title {
  color: #fff;
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 2px;
  letter-spacing: -0.02em;
}

.wallet-header__subtitle {
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.875rem;
  margin: 0;
}

.wallet-header__balance {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.wallet-header__balance-label {
  color: rgba(255, 255, 255, 0.65);
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.wallet-header__balance-amount {
  color: #fff;
  font-size: 2rem;
  font-weight: 800;
  line-height: 1;
  text-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}

.wallet-header__status-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 0.72rem;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 20px;
}

.badge--active   { background: rgba(40, 167, 69, 0.85); color: #fff; }
.badge--inactive { background: rgba(255, 193, 7, 0.85); color: #fff; }
.badge--blocked  { background: rgba(220, 53, 69, 0.85); color: #fff; }

.badge-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
  opacity: 0.9;
}

.wallet-header__actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
  flex: 0 0 auto;
}

/* ── Selector de billetera ── */
.wallet-selector {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: 1.25rem;
  padding-top: 1.25rem;
  border-top: 1px solid rgba(255, 255, 255, 0.15);
}

.wallet-selector__btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 16px;
  border-radius: 20px;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  border: 1px solid rgba(255, 255, 255, 0.35);
  background: rgba(255, 255, 255, 0.15);
  color: rgba(255, 255, 255, 0.85);
  transition: all 0.15s ease;
}

.wallet-selector__btn:hover {
  background: rgba(255, 255, 255, 0.25);
}

.wallet-selector__btn--active {
  background: rgba(255, 255, 255, 0.95);
  color: #667eea;
  border-color: transparent;
}

/* ── Botones de acción ── */
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
}

.action-btn--deposit {
  background: rgba(255, 255, 255, 0.95);
  color: #28a745;
}
.action-btn--deposit:hover { background: #fff; transform: translateY(-1px); }

.action-btn--withdraw {
  background: rgba(255, 255, 255, 0.15);
  color: #fff;
  border: 1px solid rgba(255, 255, 255, 0.3);
}
.action-btn--withdraw:hover { background: rgba(255, 255, 255, 0.25); }

.action-btn--disabled {
  opacity: 0.45;
  cursor: not-allowed;
}
.action-btn--disabled:hover { transform: none; }

/* ── Empty state ── */
.wallet-empty {
  background: #fff;
  border-radius: 0 0 16px 16px;
  box-shadow: 0 4px 24px rgba(102, 126, 234, 0.10);
  padding: 4rem 2rem;
  text-align: center;
}

.wallet-empty__icon {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: #f0f2f7;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  color: #8892a4;
  margin: 0 auto 1.5rem;
}

.wallet-empty__title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0 0 0.5rem;
}

.wallet-empty__text {
  color: #718096;
  margin: 0 0 1.5rem;
}

/* ── Grid principal ── */
.wallet-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0 1px;
  background: #fff;
  border-bottom: 1px solid #eaecf0;
}

/* ── Cards ── */
.wallet-card {
  background: #fff;
  padding: 1.5rem;
}

.kyc-card {
  border-radius: 0;
  border-top: 1px solid #f0f2f7;
  padding: 1rem 1.5rem;
}

.movements-card {
  border-radius: 0 0 16px 16px;
  box-shadow: 0 4px 24px rgba(102, 126, 234, 0.10);
  padding: 0;
  overflow: hidden;
}

.wallet-card__title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0 0 1rem;
  display: flex;
  align-items: center;
  gap: 8px;
}

.wallet-card__title i { color: #667eea; }

/* ── Resumen ── */
.summary-list { display: flex; flex-direction: column; gap: 10px; }

.summary-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  border-radius: 10px;
}

.summary-item--deposit    { background: #f0fdf4; }
.summary-item--prize      { background: #fffbeb; }
.summary-item--withdrawal { background: #eff6ff; }

.summary-item__icon {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  color: #fff;
  flex-shrink: 0;
}

.summary-item--deposit    .summary-item__icon { background: #22c55e; }
.summary-item--prize      .summary-item__icon { background: #f59e0b; }
.summary-item--withdrawal .summary-item__icon { background: #3b82f6; }

.summary-item__info { flex: 1; min-width: 0; }

.summary-item__label {
  font-size: 0.82rem;
  font-weight: 600;
  color: #2d3748;
  margin: 0;
}

.summary-item__sublabel {
  font-size: 0.72rem;
  color: #718096;
  margin: 0;
}

.summary-item__amount {
  font-size: 0.95rem;
  font-weight: 700;
  margin: 0;
  white-space: nowrap;
}

.summary-item__amount--deposit    { color: #16a34a; }
.summary-item__amount--prize      { color: #d97706; }
.summary-item__amount--withdrawal { color: #2563eb; }

/* ── Tipos de movimiento ── */
.movement-types { display: flex; flex-direction: column; gap: 8px; }

.movement-type-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  border-radius: 10px;
  background: #f8fafc;
  transition: box-shadow 0.15s;
}
.movement-type-item:hover { box-shadow: 0 2px 8px rgba(102, 126, 234, 0.12); }

.movement-type-item__icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: #fff;
  box-shadow: 0 1px 4px rgba(0,0,0,0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  flex-shrink: 0;
}

.meta--deposit      .movement-type-item__icon { color: #16a34a; }
.meta--prize        .movement-type-item__icon { color: #d97706; }
.meta--purchase     .movement-type-item__icon { color: #e11d48; }
.meta--withdrawal   .movement-type-item__icon { color: #2563eb; }
.meta--trivia-entry .movement-type-item__icon { color: #7c3aed; }
.meta--trivia-prize .movement-type-item__icon { color: #7c3aed; }

.movement-type-item__info { flex: 1; min-width: 0; }

.movement-type-item__label {
  font-size: 0.82rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0;
}

.movement-type-item__desc {
  font-size: 0.72rem;
  color: #718096;
  margin: 0;
}

.movement-type-item__sign {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  font-weight: 700;
  flex-shrink: 0;
}

.sign--positive { background: #dcfce7; color: #16a34a; }
.sign--negative { background: #ffe4e6; color: #e11d48; }

/* ── Movimientos ── */
.movements-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #eaecf0;
  background: #fafbff;
}

.movements-header .wallet-card__title { margin: 0; }

.movements-count {
  font-size: 0.75rem;
  font-weight: 600;
  color: #667eea;
  background: #eff0ff;
  padding: 3px 10px;
  border-radius: 20px;
}

.movements-loading,
.movements-empty {
  padding: 4rem 2rem;
  text-align: center;
  color: #718096;
}

.movements-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.movements-empty__icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: #f0f2f7;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: #8892a4;
  margin: 0 auto 1rem;
}

.movements-empty h3 { font-weight: 700; color: #2d3748; margin: 0 0 0.25rem; }
.movements-empty p  { margin: 0; font-size: 0.875rem; }
.movements-empty__hint { font-size: 0.78rem !important; color: #a0aec0; margin-top: 0.25rem !important; }

.movements-list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.movement-row {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #f0f2f7;
  transition: background 0.15s;
}
.movement-row:hover { background: #fafbff; }
.movement-row:last-child { border-bottom: none; }

.movement-row--first {
  border-left: 3px solid #667eea;
}

.movement-row__icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  flex-shrink: 0;
}

.movement-row__icon.meta--deposit      { background: #f0fdf4; color: #16a34a; }
.movement-row__icon.meta--prize        { background: #fffbeb; color: #d97706; }
.movement-row__icon.meta--purchase     { background: #fff1f2; color: #e11d48; }
.movement-row__icon.meta--withdrawal   { background: #eff6ff; color: #2563eb; }
.movement-row__icon.meta--trivia-entry { background: #f5f3ff; color: #7c3aed; }
.movement-row__icon.meta--trivia-prize { background: #f5f3ff; color: #7c3aed; }

.movement-row__info { flex: 1; min-width: 0; }

.movement-row__title-row {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
  margin-bottom: 2px;
}

.movement-row__label {
  font-size: 0.875rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.movement-row__desc {
  font-size: 0.78rem;
  color: #718096;
  margin: 0 0 2px;
}

.movement-row__date {
  font-size: 0.72rem;
  color: #a0aec0;
  display: flex;
  align-items: center;
  gap: 4px;
  margin: 0;
}

.badge-recent {
  font-size: 0.68rem;
  font-weight: 600;
  color: #667eea;
  background: #eff0ff;
  padding: 2px 8px;
  border-radius: 20px;
  white-space: nowrap;
}

.badge-status {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.68rem;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 20px;
  white-space: nowrap;
}
.badge-status--pending   { color: #92400e; background: #fef3c7; }
.badge-status--completed { color: #166534; background: #dcfce7; }
.badge-status--rejected  { color: #991b1b; background: #fee2e2; }
.badge-status--cancelled { color: #374151; background: #f3f4f6; }

.movement-row__amounts { text-align: right; flex-shrink: 0; }

.movement-row__amount {
  font-size: 1rem;
  font-weight: 700;
  margin: 0 0 2px;
}

.amount--positive { color: #16a34a; }
.amount--negative { color: #e11d48; }

.movement-row__balance {
  font-size: 0.72rem;
  color: #a0aec0;
  margin: 0;
}

/* ── Cargar más ── */
.movements-load-more {
  padding: 1.25rem 1.5rem;
  text-align: center;
  border-top: 1px solid #eaecf0;
  background: #fafbff;
}

.load-more-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 24px;
  border: 1px solid #c7d2fe;
  background: #fff;
  color: #667eea;
  border-radius: 10px;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
}
.load-more-btn:hover:not(:disabled) { background: #f6f7ff; border-color: #667eea; }
.load-more-btn:disabled { opacity: 0.5; cursor: not-allowed; }

/* ── Spinner ── */
.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #c7d2fe;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.spinner--sm {
  width: 16px;
  height: 16px;
  border-width: 2px;
}

.spinner--white {
  border-color: rgba(255,255,255,0.3);
  border-top-color: #fff;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* ── Verificando pendiente ── */
.op-pending-checking {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #718096;
  font-size: 0.85rem;
  padding: 1.5rem 0;
  justify-content: center;
}

/* ── Bloqueo por pendiente ── */
.op-pending-block {
  display: flex;
  gap: 14px;
  background: #fffbeb;
  border: 1.5px solid #fcd34d;
  border-radius: 14px;
  padding: 1.1rem 1.25rem;
  margin-bottom: 1rem;
}

.op-pending-block__icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #fef3c7;
  color: #d97706;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}

.op-pending-block__title {
  font-size: 0.9rem;
  font-weight: 700;
  color: #92400e;
  margin: 0 0 4px;
}

.op-pending-block__text {
  font-size: 0.82rem;
  color: #78350f;
  margin: 0 0 4px;
  line-height: 1.5;
}

.op-pending-block__hint {
  font-size: 0.75rem;
  color: #a16207;
  margin: 0;
}

/* ── Detalle del movimiento pendiente ── */
.op-pending-detail {
  background: #f8fafc;
  border: 1px solid #eaecf0;
  border-radius: 12px;
  overflow: hidden;
  margin-bottom: 0.25rem;
}

.op-pending-detail__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 9px 14px;
  font-size: 0.82rem;
  color: #4a5568;
  border-bottom: 1px solid #f0f2f7;
}
.op-pending-detail__row:last-child { border-bottom: none; }
.op-pending-detail__row span:first-child { color: #a0aec0; }
.op-pending-detail__row strong { color: #1a202c; }

.op-pending-detail__badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: #fef3c7;
  color: #d97706;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 2px 9px;
  border-radius: 20px;
}

.op-pending-detail__dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #f59e0b;
  animation: pulse-dot 1.5s ease-in-out infinite;
}

@keyframes pulse-dot {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.3; }
}

.text--deposit  { color: #16a34a; font-weight: 600; }
.text--withdraw { color: #d97706; font-weight: 600; }

/* ── Indicador de pasos ── */
.op-steps {
  margin-bottom: 1.25rem;
}

.op-steps__track {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0;
}

.op-steps__item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.op-steps__circle {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 2px solid #e2e8f0;
  background: #fff;
  color: #a0aec0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.82rem;
  font-weight: 700;
  transition: all 0.2s;
}

.op-steps__item--active .op-steps__circle {
  border-color: #667eea;
  background: #667eea;
  color: #fff;
}

.op-steps__item--done .op-steps__circle {
  border-color: #22c55e;
  background: #22c55e;
  color: #fff;
}

.op-steps__label {
  font-size: 0.7rem;
  font-weight: 600;
  color: #a0aec0;
}

.op-steps__item--active .op-steps__label,
.op-steps__item--done .op-steps__label {
  color: #4a5568;
}

.op-steps__line {
  flex: 1;
  height: 2px;
  background: #e2e8f0;
  margin: 0 8px;
  margin-bottom: 18px;
  transition: background 0.2s;
}

.op-steps__line--done { background: #22c55e; }

/* ── Resumen de monto (paso 2) ── */
.op-summary {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #f8fafc;
  border: 1px solid #eaecf0;
  border-radius: 12px;
  padding: 0.875rem 1rem;
  margin-bottom: 1.25rem;
}

.op-summary__icon {
  font-size: 1.25rem;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.op-summary__icon--deposit  { background: #f0fdf4; color: #16a34a; }
.op-summary__icon--withdraw { background: #fffbeb; color: #d97706; }

.op-summary__label  { font-size: 0.72rem; color: #718096; margin: 0; }
.op-summary__amount { font-size: 1.2rem; font-weight: 800; color: #1a202c; margin: 0; }

/* ── File drop ── */
.op-file-drop {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  border: 2px dashed #c7d2fe;
  border-radius: 12px;
  padding: 1.5rem 1rem;
  cursor: pointer;
  transition: border-color 0.15s, background 0.15s;
  background: #fafbff;
  text-align: center;
}

.op-file-drop:hover       { border-color: #667eea; background: #f6f7ff; }
.op-file-drop--selected   { border-color: #22c55e; border-style: solid; background: #f0fdf4; }

.op-file-input { display: none; }

.op-file-drop__icon {
  font-size: 1.75rem;
  color: #a5b4fc;
}

.op-file-drop__icon--ok { color: #22c55e; }

.op-file-drop__name {
  font-size: 0.82rem;
  font-weight: 600;
  color: #4a5568;
  margin: 0;
  word-break: break-all;
}

.op-file-drop__hint {
  font-size: 0.72rem;
  color: #a0aec0;
  margin: 0;
}

/* ── Receipt preview (paso 2) ── */
.op-receipt-preview {
  margin-top: 10px;
  border-radius: 10px;
  overflow: hidden;
  border: 2px solid #bbf7d0;
  background: #f0fdf4;
  text-align: center;
}
.op-receipt-preview__img {
  display: block;
  max-width: 100%;
  max-height: 220px;
  object-fit: contain;
  margin: 0 auto;
}

/* ── Textarea ── */
.op-field__textarea {
  width: 100%;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  padding: 10px 14px;
  font-size: 0.875rem;
  color: #1a202c;
  outline: none;
  transition: border-color 0.15s;
  resize: vertical;
  box-sizing: border-box;
  font-family: inherit;
}

.op-field__textarea:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.12); }

/* ── Labels auxiliares ── */
.op-required { color: #e11d48; font-weight: 700; }
.op-optional { color: #a0aec0; font-weight: 400; font-size: 0.75em; }

/* ── Directorios ── */
.op-dirs-loading {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #718096;
  font-size: 0.85rem;
  padding: 1rem 0;
}

.op-dirs-empty {
  background: #fffbeb;
  border: 1px solid #fde68a;
  border-radius: 12px;
  padding: 1rem 1.25rem;
  color: #92400e;
  font-size: 0.82rem;
  display: flex;
  align-items: flex-start;
  gap: 10px;
}

.op-dirs-empty__icon { margin-top: 2px; flex-shrink: 0; font-size: 1rem; }

.op-dirs-empty__text { margin: 0 0 6px; line-height: 1.4; }

.op-dirs-empty__link {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 0.82rem;
  font-weight: 600;
  color: #667eea;
  text-decoration: none;
  background: #eff0ff;
  padding: 4px 10px;
  border-radius: 8px;
  transition: background 0.15s;
}
.op-dirs-empty__link:hover { background: #e0e2ff; }

.op-dirs-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-height: 220px;
  overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: #cbd5e1 transparent;
}

.op-dir-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.15s;
  background: #fff;
}

.op-dir-card:hover            { border-color: #a5b4fc; background: #fafbff; }
.op-dir-card--selected        { border-color: #667eea; background: #f6f7ff; }

.op-dir-card__radio { display: none; }

.op-dir-card__icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: #eff0ff;
  color: #667eea;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  flex-shrink: 0;
}

.op-dir-card--selected .op-dir-card__icon { background: #667eea; color: #fff; }

.op-dir-card__info  { flex: 1; min-width: 0; }
.op-dir-card__label { font-size: 0.85rem; font-weight: 700; color: #2d3748; margin: 0; }
.op-dir-card__detail { font-size: 0.75rem; color: #718096; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.op-dir-card__holder { font-size: 0.72rem; color: #a0aec0; margin: 0; }

.op-dir-card__check { color: #667eea; font-size: 1.1rem; flex-shrink: 0; }

/* ── Confirmación (paso 3) ── */
.op-done {
  text-align: center;
  padding: 1rem 0 0.5rem;
}

.op-done__icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: linear-gradient(135deg, #22c55e, #16a34a);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  margin: 0 auto 1rem;
  box-shadow: 0 4px 16px rgba(34,197,94,0.3);
}

.op-done__title {
  font-size: 1.2rem;
  font-weight: 800;
  color: #1a202c;
  margin: 0 0 0.5rem;
}

.op-done__text {
  color: #718096;
  font-size: 0.875rem;
  margin: 0 0 1rem;
}

.op-done__detail {
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  border-radius: 12px;
  padding: 0.875rem 1rem;
  font-size: 0.78rem;
  color: #0369a1;
  display: flex;
  align-items: flex-start;
  gap: 8px;
  text-align: left;
}

.op-done__detail i { margin-top: 2px; flex-shrink: 0; }

/* ── Spinner variants ── */
.op-spinner--indigo {
  border-color: rgba(102,126,234,0.25);
  border-top-color: #667eea;
}

/* ── Contenido del modal (op-* para evitar colisión con Modal.vue) ── */
.op-balance {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #f8fafc;
  border: 1px solid #eaecf0;
  border-radius: 12px;
  padding: 1rem 1.25rem;
  margin-bottom: 1.25rem;
}

.op-balance__label  { font-size: 0.78rem; color: #718096; margin: 0 0 2px; }
.op-balance__amount { font-size: 1.35rem; font-weight: 800; color: #1a202c; margin: 0; }

.op-balance__icon {
  width: 36px;
  height: 36px;
  background: #eff0ff;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #667eea;
  font-size: 0.9rem;
}

.op-field { margin-bottom: 1.25rem; }

.op-field__label {
  display: block;
  font-size: 0.82rem;
  font-weight: 700;
  color: #2d3748;
  margin-bottom: 8px;
}

.op-field__input-wrap { position: relative; }

.op-field__symbol {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  font-weight: 700;
  font-size: 1rem;
  color: #718096;
  pointer-events: none;
}

.op-field__input {
  width: 100%;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  padding: 12px 14px 12px 44px;
  font-size: 1.1rem;
  font-weight: 600;
  color: #1a202c;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
  box-sizing: border-box;
}

.op-input--deposit:focus  { border-color: #22c55e; box-shadow: 0 0 0 3px rgba(34,197,94,0.15); }
.op-input--withdraw:focus { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,0.15); }

/* ── Quick amounts ── */
.op-quick { margin-top: 10px; }

.op-quick__label {
  font-size: 0.72rem;
  font-weight: 600;
  color: #a0aec0;
  margin: 0 0 8px;
}

.op-quick__grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
}

.op-quick__btn {
  padding: 8px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  color: #4a5568;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
}
.op-quick__btn:hover          { border-color: #667eea; color: #667eea; background: #f6f7ff; }
.op-quick__btn--active        { background: #667eea; color: #fff; border-color: #667eea; }

/* ── Mensajes ── */
.op-msg {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px;
  border-radius: 12px;
  font-size: 0.82rem;
  margin-bottom: 0.75rem;
}

.op-msg--error   { background: #fff1f2; border: 1px solid #fecdd3; color: #be123c; }
.op-msg--success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }

.op-msg__icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.op-msg--error   .op-msg__icon { background: #ffe4e6; }
.op-msg--success .op-msg__icon { background: #dcfce7; }

.op-msg__title { font-weight: 700; margin: 0 0 2px; }

/* ── Footer del modal ── */
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
.op-btn:disabled { opacity: 0.5; cursor: not-allowed; }

.op-btn--cancel {
  background: #fff;
  color: #718096;
  border: 1.5px solid #e2e8f0;
}
.op-btn--cancel:hover:not(:disabled) { background: #f8fafc; }

.op-btn--deposit {
  background: linear-gradient(135deg, #22c55e, #16a34a);
  color: #fff;
}
.op-btn--deposit:hover:not(:disabled)  { opacity: 0.9; transform: translateY(-1px); }

.op-btn--withdraw {
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: #fff;
}
.op-btn--withdraw:hover:not(:disabled) { opacity: 0.9; transform: translateY(-1px); }

.op-btn__loading { display: flex; align-items: center; gap: 8px; }

.op-spinner {
  display: inline-block;
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255,255,255,0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

/* ── Transición mensajes ── */
.msg-fade-enter-active, .msg-fade-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.msg-fade-enter-from,   .msg-fade-leave-to     { opacity: 0; transform: translateY(-4px); }

/* ── Live dot ── */
.live-dot {
  display: inline-block;
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #22c55e;
  margin-left: 6px;
  vertical-align: middle;
  animation: live-pulse 2s ease-in-out infinite;
}

@keyframes live-pulse {
  0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.5); }
  50%       { opacity: 0.7; box-shadow: 0 0 0 4px rgba(34, 197, 94, 0); }
}

/* ── Movements header right ── */
.movements-header__right {
  display: flex;
  align-items: center;
  gap: 8px;
}

/* ── Refresh button ── */
.refresh-btn {
  width: 28px;
  height: 28px;
  border: 1px solid #e2e8f0;
  background: #fff;
  border-radius: 8px;
  color: #667eea;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.72rem;
  cursor: pointer;
  transition: all 0.15s;
  flex-shrink: 0;
}
.refresh-btn:hover:not(:disabled) { background: #f6f7ff; border-color: #667eea; }
.refresh-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.refresh-btn--loading i { animation: spin 0.7s linear infinite; }

/* ── Responsive ── */
@media (max-width: 768px) {
  .wallet-header__inner { flex-direction: column; align-items: flex-start; gap: 1.25rem; }
  .wallet-header__balance { align-items: flex-start; }
  .wallet-header__actions { flex-direction: row; flex-wrap: wrap; }
  .wallet-grid { grid-template-columns: 1fr; }
}

/* ── Toast de resultado (aprobado / rechazado) ── */
.wt-toast {
  position: fixed;
  bottom: 1.5rem;
  right: 1.5rem;
  z-index: 9999;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.9rem 1.1rem;
  border-radius: 14px;
  min-width: 280px;
  max-width: 380px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.18);
  animation: wt-slide-in 0.35s cubic-bezier(0.34,1.56,0.64,1);
}

.wt-toast--approved {
  background: linear-gradient(135deg, #22c55e, #16a34a);
  color: #fff;
}

.wt-toast--rejected {
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: #fff;
}

.wt-toast__icon { font-size: 1.5rem; flex-shrink: 0; }

.wt-toast__body { flex: 1; min-width: 0; }

.wt-toast__title {
  font-size: 0.875rem;
  font-weight: 700;
  margin: 0 0 2px;
  line-height: 1.2;
}

.wt-toast__sub {
  font-size: 0.78rem;
  opacity: 0.88;
  margin: 0;
}

.wt-toast__close {
  background: rgba(255,255,255,0.25);
  border: none;
  color: #fff;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 0.65rem;
  flex-shrink: 0;
  transition: background 0.15s;
}
.wt-toast__close:hover { background: rgba(255,255,255,0.4); }

/* ── movement-row clickable ── */
.movement-row--clickable {
  cursor: pointer;
  transition: background 0.15s;
}
.movement-row--clickable:hover { background: #f7f8fc; }

/* ══ Modal de detalle de movimiento ══ */
.mv-detail__header {
  display: flex;
  align-items: center;
  gap: 0.9rem;
  background: #f7f8fc;
  border-radius: 12px;
  padding: 1rem 1.1rem;
  margin-bottom: 1rem;
}
.mv-detail__icon {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.mv-detail__header-info { flex: 1; min-width: 0; }
.mv-detail__type {
  font-size: 0.78rem;
  color: #718096;
  margin: 0 0 0.15rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: .04em;
}
.mv-detail__amount {
  font-size: 1.35rem;
  font-weight: 700;
  margin: 0;
  line-height: 1.2;
}
.mv-detail__badge { margin-left: auto; flex-shrink: 0; align-self: flex-start; }

/* Info grid */
.mv-detail__grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.6rem 1rem;
  margin-bottom: 1.2rem;
}
.mv-detail__cell { display: flex; flex-direction: column; gap: 0.15rem; }
.mv-detail__cell--full { grid-column: 1 / -1; }
.mv-detail__cell-label {
  font-size: 0.72rem;
  color: #a0aec0;
  text-transform: uppercase;
  letter-spacing: .04em;
  font-weight: 500;
}
.mv-detail__cell-value { font-size: 0.9rem; color: #2d3748; font-weight: 600; }
.mv-detail__cell-value--highlight { color: #667eea; }
.mv-detail__cell-sub { font-size: 0.72rem; color: #94a3b8; margin-top: 1px; }
.mv-detail__cell-value--mono {
  font-family: ui-monospace, monospace;
  font-size: 0.75rem;
  font-weight: 500;
  color: #475569;
  word-break: break-all;
}

/* Timeline */
.mv-detail__timeline { margin-bottom: 1rem; }
.mv-detail__timeline-title {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .05em;
  color: #a0aec0;
  margin: 0 0 0.75rem;
}
.mv-timeline { list-style: none; padding: 0; margin: 0; position: relative; }
.mv-timeline::before {
  content: '';
  position: absolute;
  left: 15px;
  top: 8px;
  bottom: 8px;
  width: 2px;
  background: linear-gradient(to bottom, #e2e8f0, #e2e8f0);
}
.mv-timeline__item {
  display: flex;
  align-items: flex-start;
  gap: 0.85rem;
  padding: 0 0 1.1rem 0;
  position: relative;
}
.mv-timeline__item:last-child { padding-bottom: 0; }

.mv-timeline__dot {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #f1f5f9;
  color: #94a3b8;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.7rem;
  flex-shrink: 0;
  position: relative;
  z-index: 1;
  border: 2px solid #e2e8f0;
  transition: background 0.2s, color 0.2s, border-color 0.2s;
}
.mv-timeline__item--done .mv-timeline__dot {
  background: #dcfce7;
  color: #16a34a;
  border-color: #bbf7d0;
}
.mv-timeline__item--active .mv-timeline__dot {
  background: #ede9fe;
  color: #7c3aed;
  border-color: #c4b5fd;
  animation: pulse-dot 1.6s ease-in-out infinite;
}
.mv-timeline__item--rejected .mv-timeline__dot {
  background: #fee2e2;
  color: #dc2626;
  border-color: #fecaca;
}
@keyframes pulse-dot {
  0%, 100% { box-shadow: 0 0 0 0 rgba(124,58,237,0.25); }
  50%       { box-shadow: 0 0 0 6px rgba(124,58,237,0); }
}

.mv-timeline__body { padding-top: 4px; flex: 1; }
.mv-timeline__label {
  font-size: 0.85rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 0.15rem;
  line-height: 1.3;
}
.mv-timeline__item--active .mv-timeline__label { color: #7c3aed; }
.mv-timeline__item--rejected .mv-timeline__label { color: #dc2626; }

.mv-timeline__sub {
  font-size: 0.78rem;
  color: #64748b;
  margin: 0 0 0.2rem;
  line-height: 1.4;
}
.mv-timeline__date {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.72rem;
  color: #94a3b8;
  margin: 0;
}
.mv-timeline__date-icon { font-size: 0.62rem; }

/* Comprobante en modal detalle usuario */
.mv-detail__receipt {
  padding: 0.75rem;
  background: #f8fafc;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.mv-detail__receipt-img {
  display: block;
  max-width: 100%;
  max-height: 200px;
  object-fit: contain;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  background: #fff;
}
.mv-detail__receipt-link {
  font-size: 0.82rem;
  color: #667eea;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.mv-detail__receipt-link:hover { text-decoration: underline; }
.mv-detail__ref { font-size: 0.75rem; color: #64748b; margin: 0; }
.mv-detail__ref-value { font-family: ui-monospace, monospace; color: #334155; }

/* Feedback contextual */
.mv-detail__feedback {
  display: flex;
  align-items: flex-start;
  gap: 0.65rem;
  padding: 0.85rem 1rem;
  border-radius: 10px;
  font-size: 0.83rem;
  line-height: 1.5;
}
.mv-detail__feedback i { font-size: 1rem; flex-shrink: 0; margin-top: 1px; }
.mv-detail__feedback--pending   { background: #fffbeb; color: #92400e; }
.mv-detail__feedback--completed { background: #f0fff4; color: #276749; }
.mv-detail__feedback--rejected  { background: #fff5f5; color: #c53030; }

/* Transición del toast */
.wt-toast-enter-active { animation: wt-slide-in 0.35s cubic-bezier(0.34,1.56,0.64,1); }
.wt-toast-leave-active { animation: wt-slide-out 0.25s ease forwards; }

@keyframes wt-slide-in {
  from { transform: translateX(110%); opacity: 0; }
  to   { transform: translateX(0);    opacity: 1; }
}

@keyframes wt-slide-out {
  from { transform: translateX(0);    opacity: 1; }
  to   { transform: translateX(110%); opacity: 0; }
}
</style>
