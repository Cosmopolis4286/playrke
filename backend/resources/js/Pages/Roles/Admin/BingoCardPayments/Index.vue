<script setup lang="ts">
  import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
  import { router } from '@inertiajs/vue3'
  import Swal from 'sweetalert2'

  import PageHeader from '@/js/Components/Common/PageHeader/PageHeader.vue'
  import StatTile from '@/js/Components/Common/StatTile/StatTile.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import RelativeTime from '@/js/Components/Common/RelativeTime/RelativeTime.vue'
  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'

  import backendClient from '@/js/plugins/axios'
  import { showToast } from '@/js/utils/toastNotify'

  import type { Column } from '@/js/types/common/table/column-row'
  import type { TableFilterInterface } from '@/js/types/common/table/table-filter'
  import type {
    CardPaymentReviewPageProps,
    PaymentUrgency,
    PendingCardPayment,
    PendingPaymentPolicy,
  } from '@/js/types/games/bingo/payment/card-payment-review'

  /* -------------------------------------------------------------------------- */
  /* Props                                                                       */
  /* -------------------------------------------------------------------------- */

  const props = defineProps<CardPaymentReviewPageProps>()

  /** Minutos antes del inicio a partir de los cuales la sala se marca "pronto". */
  const SOON_MINUTES = 30

  const POLICY_OPTIONS: { value: PendingPaymentPolicy; icon: string; title: string; text: string }[] = [
    {
      value: 'keep_review',
      icon:  'fa-hourglass-half',
      title: 'Mantener en revisión',
      text:  'Sigue en esta cola marcado "pendiente tras el inicio". Si lo apruebas, el comprador entra con las bolas ya cantadas.',
    },
    {
      value: 'reject_refund',
      icon:  'fa-rotate-left',
      title: 'Rechazar con reembolso',
      text:  'Se anulan los cartones y se reembolsa al vendedor, que debe devolverle el dinero al comprador.',
    },
  ]

  const POLICY_LABELS: Record<PendingPaymentPolicy, string> = {
    keep_review:   'Mantener en revisión',
    reject_refund: 'Rechazar con reembolso',
  }

  const URGENCY: Record<PaymentUrgency, { label: string; badge: string; icon: string }> = {
    started: { label: 'Sorteo iniciado', badge: 'badge bg-danger',             icon: 'fa-circle-play' },
    soon:    { label: 'Inicia pronto',   badge: 'badge bg-warning text-dark',  icon: 'fa-stopwatch' },
    later:   { label: 'Programada',      badge: 'badge bg-success',            icon: 'fa-calendar-check' },
    manual:  { label: 'Inicio manual',   badge: 'badge bg-secondary',          icon: 'fa-hand-pointer' },
  }

  /* -------------------------------------------------------------------------- */
  /* Reloj (cuenta regresiva y urgencia)                                         */
  /* -------------------------------------------------------------------------- */

  const now = ref(Date.now())
  let timer: ReturnType<typeof setInterval> | undefined
  onMounted(() => { timer = setInterval(() => { now.value = Date.now() }, 30_000) })
  onBeforeUnmount(() => clearInterval(timer))

  function startsInMinutes(p: PendingCardPayment): number | null {
    return p.room_starts_at ? (new Date(p.room_starts_at).getTime() - now.value) / 60_000 : null
  }

  function urgencyOf(p: PendingCardPayment): PaymentUrgency {
    const minutes = startsInMinutes(p)
    if (p.room_started || (minutes !== null && minutes <= 0)) return 'started'
    if (minutes === null) return 'manual'
    return minutes <= SOON_MINUTES ? 'soon' : 'later'
  }

  function humanizeMinutes(totalMinutes: number): string {
    const m = Math.max(1, Math.round(totalMinutes))
    if (m < 60) return `${m} min`
    const h = Math.floor(m / 60)
    if (h < 24) return m % 60 ? `${h} h ${m % 60} min` : `${h} h`
    return `${Math.floor(h / 24)} d ${h % 24} h`
  }

  function countdownLabel(p: PendingCardPayment): string {
    const urgency = urgencyOf(p)
    if (urgency === 'started') return URGENCY.started.label
    if (urgency === 'manual') return URGENCY.manual.label
    return `Inicia en ${humanizeMinutes(startsInMinutes(p) ?? 0)}`
  }

  function formatDateTime(iso: string | null): string {
    if (!iso) return '—'
    return new Date(iso).toLocaleString('es-VE', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })
  }

  /* -------------------------------------------------------------------------- */
  /* Tabla                                                                       */
  /* -------------------------------------------------------------------------- */

  const columns: Column[] = [
    { key: 'room_short_code',      label: 'Sala' },
    { key: 'seller_name',          label: 'Vendedor' },
    { key: 'buyer_name',           label: 'Comprador' },
    { key: 'amount_label',         label: 'Monto' },
    { key: 'payment_method_label', label: 'Pago', hideBelow: 'md' },
    { key: 'created_at',           label: 'Recibido', sortable: true, searchable: false, hideBelow: 'lg' },
    { key: 'actions',              label: '', searchable: false },
  ]

  /** Campos planos para la búsqueda global y los filtros de la tabla. */
  const rows = computed(() => props.payments.map(p => ({
    ...p,
    buyer_name:   p.external_player ? `${p.external_player.name} ${p.external_player.phone ?? ''}`.trim() : '',
    urgency:      urgencyOf(p),
    late_pending: p.pending_at_start_at ? 'yes' : 'no',
  })))

  const filters = computed<readonly TableFilterInterface[]>(() => [
    {
      key:   'urgency',
      label: 'Inicio de la sala',
      options: (Object.keys(URGENCY) as PaymentUrgency[]).map(value => ({ value, label: URGENCY[value].label })),
    },
    {
      key:   'payment_method_label',
      label: 'Método',
      options: [...new Set(props.payments.map(p => p.payment_method_label).filter((v): v is string => !!v))]
        .map(value => ({ value, label: value })),
    },
    {
      key:   'late_pending',
      label: 'Pendiente tras el inicio',
      options: [{ value: 'yes', label: 'Sí' }, { value: 'no', label: 'No' }],
    },
  ])

  const stats = computed(() => {
    const byUrgency = rows.value.reduce<Record<PaymentUrgency, number>>(
      (acc, row) => ({ ...acc, [row.urgency]: acc[row.urgency] + 1 }),
      { started: 0, soon: 0, later: 0, manual: 0 },
    )
    return { ...byUrgency, total: props.summary.total }
  })

  /* -------------------------------------------------------------------------- */
  /* Regla al iniciar el sorteo (solo comercializadora)                          */
  /* -------------------------------------------------------------------------- */

  const policy = ref<PendingPaymentPolicy | null>(props.policy)
  const savingPolicy = ref(false)

  async function savePolicy(value: PendingPaymentPolicy): Promise<void> {
    if (savingPolicy.value || policy.value === value) return
    const previous = policy.value
    policy.value = value
    savingPolicy.value = true
    try {
      await backendClient.patch('/bingo/card-payments/policy', { policy: value })
      showToast(`Regla guardada: ${POLICY_LABELS[value]}`, 'success')
    } catch (error: any) {
      policy.value = previous
      showToast(error?.response?.data?.message ?? 'No se pudo guardar la regla', 'error')
    } finally {
      savingPolicy.value = false
    }
  }

  /* -------------------------------------------------------------------------- */
  /* Revisión                                                                    */
  /* -------------------------------------------------------------------------- */

  const selected = ref<PendingCardPayment | null>(null)
  const showDetail = ref(false)
  const notes = ref('')
  const approving = ref(false)
  const rejecting = ref(false)

  const selectedUrgency = computed(() => (selected.value ? urgencyOf(selected.value) : null))
  const isCash = computed(() => ['cash', 'seller_cash'].includes(selected.value?.payment_method ?? ''))

  function openDetail(id: string): void {
    selected.value = props.payments.find(p => p.id === id) ?? null
    notes.value = ''
    showDetail.value = !!selected.value
  }

  watch(showDetail, isOpen => {
    if (!isOpen) selected.value = null
  })

  function reloadQueue(): void {
    router.reload({ only: ['payments', 'summary', 'refundFailures'] })
  }

  async function handleApprove(): Promise<void> {
    const payment = selected.value
    if (!payment) return

    const started = urgencyOf(payment) === 'started'
    const confirmed = await Swal.fire({
      title: started ? '¿Aprobar con el sorteo iniciado?' : '¿Aprobar pago?',
      html: started
        ? `El sorteo de la sala <b>#${payment.room_short_code}</b> ya inició: los cartones entrarán con las bolas ya cantadas. Apruébalo solo si el comprador lo acepta.`
        : `Se liberarán <b>${payment.card_count ?? 0} cartón(es)</b> de <b>${payment.external_player?.name ?? 'el comprador'}</b> por <b>${payment.amount_label}</b>.`,
      icon: started ? 'warning' : 'question',
      showCancelButton: true,
      confirmButtonText: started ? 'Sí, aprobar igual' : 'Sí, aprobar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#16a34a',
      reverseButtons: true,
    })
    if (!confirmed.isConfirmed) return

    await sendApprove(payment, started)
  }

  async function sendApprove(payment: PendingCardPayment, confirmLate: boolean): Promise<void> {
    try {
      approving.value = true
      await backendClient.post(`/bingo/card-payments/${payment.id}/approve`, {
        admin_notes:  notes.value.trim() || null,
        confirm_late: confirmLate,
      })
      showToast('Pago aprobado: el comprador ya puede ver y jugar sus cartones', 'success')
      showDetail.value = false
      reloadQueue()
    } catch (error: any) {
      // El sorteo inició con la pantalla abierta: el backend pide la confirmación tardía.
      if (!confirmLate && error?.response?.data?.error_code === 'BINGO_PAYMENT_LATE_APPROVAL_CONFIRM') {
        approving.value = false
        const again = await Swal.fire({
          title: 'El sorteo ya inició',
          text: error.response.data.message,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Sí, aprobar igual',
          cancelButtonText: 'Cancelar',
          confirmButtonColor: '#16a34a',
          reverseButtons: true,
        })
        if (again.isConfirmed) await sendApprove(payment, true)
        return
      }
      showToast(error?.response?.data?.message ?? 'No se pudo aprobar el pago', 'error')
    } finally {
      approving.value = false
    }
  }

  async function handleReject(): Promise<void> {
    const payment = selected.value
    if (!payment) return

    const result = await Swal.fire({
      title: 'Rechazar pago',
      html: 'Los cartones se anulan y se reembolsa al vendedor, que debe devolverle el dinero al comprador. El vendedor recibe el motivo.',
      input: 'textarea',
      inputLabel: 'Motivo del rechazo',
      inputValue: notes.value,
      inputPlaceholder: 'Ej. el comprobante no corresponde, monto incompleto...',
      inputValidator: value => (!value || !value.trim() ? 'El motivo es obligatorio' : undefined),
      showCancelButton: true,
      confirmButtonText: 'Rechazar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#dc2626',
      reverseButtons: true,
    })
    if (!result.isConfirmed || !result.value) return

    try {
      rejecting.value = true
      await backendClient.post(`/bingo/card-payments/${payment.id}/reject`, { admin_notes: String(result.value).trim() })
      showToast('Pago rechazado: cartones anulados y reembolso en proceso', 'success')
      showDetail.value = false
      reloadQueue()
    } catch (error: any) {
      showToast(error?.response?.data?.message ?? 'No se pudo rechazar el pago', 'error')
    } finally {
      rejecting.value = false
    }
  }

  /* -------------------------------------------------------------------------- */
  /* Lightbox del comprobante                                                    */
  /* -------------------------------------------------------------------------- */

  const lightboxUrl = ref<string | null>(null)

  /* -------------------------------------------------------------------------- */
  /* Reembolsos con fallos                                                       */
  /* -------------------------------------------------------------------------- */

  const refundColumns: Column[] = [
    { key: 'room_uuid_code',    label: 'Sala' },
    { key: 'amount_label',      label: 'Monto' },
    { key: 'status',            label: 'Estado' },
    { key: 'attempts',          label: 'Intentos', searchable: false },
    { key: 'error_message',     label: 'Último error', truncate: true, hideBelow: 'md' },
    { key: 'last_attempted_at', label: 'Último intento', sortable: true, searchable: false },
  ]

  const refundFilters: readonly TableFilterInterface[] = [
    {
      key:   'status',
      label: 'Estado',
      options: [{ value: 'PENDING', label: 'En reintento' }, { value: 'ABANDONED', label: 'Abandonado' }],
    },
  ]
</script>

<template>
  <PageHeader
    icon="fa-receipt"
    :title="title"
    subtitle="Pagos de compradores externos (sin cuenta) por confirmar. Sus cartones no se ven ni juegan hasta que los apruebes."
    :breadcrumb-items="[
      { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
      { label: 'Bingo' },
      { label: 'Revisión de pagos' },
    ]"
  >
    <template #actions>
      <div class="bcp-stats">
        <StatTile label="Por revisar" :value="stats.total" icon="fa-hourglass-half" :variant="stats.total > 0 ? 'warning' : 'default'" />
        <StatTile label="Monto en revisión" :value="summary.amount ?? '—'" icon="fa-money-bill-wave" variant="info" />
        <StatTile label="Sorteo ya iniciado" :value="stats.started" icon="fa-circle-play" :variant="stats.started > 0 ? 'danger' : 'default'" />
        <StatTile :label="`Inician en ≤ ${SOON_MINUTES} min`" :value="stats.soon" icon="fa-stopwatch" :variant="stats.soon > 0 ? 'warning' : 'default'" />
      </div>
    </template>
  </PageHeader>

  <!-- Regla para los pagos que siguen pendientes cuando inicia el sorteo -->
  <section v-if="policy" class="bcp-policy">
    <div class="bcp-policy__head">
      <span class="bcp-policy__icon"><i class="fas fa-gavel"></i></span>
      <div>
        <h3 class="bcp-policy__title">Si el sorteo inicia y un pago sigue sin revisar</h3>
        <p class="bcp-policy__hint">Te avisamos al cerrar la venta, minutos antes del inicio. Esta regla aplica a toda tu red.</p>
      </div>
      <span v-if="savingPolicy" class="spinner-border spinner-border-sm text-muted ml-auto"></span>
    </div>
    <div class="bcp-policy__options" role="radiogroup" aria-label="Regla al iniciar el sorteo">
      <button
        v-for="option in POLICY_OPTIONS"
        :key="option.value"
        type="button"
        role="radio"
        class="bcp-policy__option"
        :class="{ 'is-active': policy === option.value }"
        :aria-checked="policy === option.value"
        :disabled="savingPolicy"
        @click="savePolicy(option.value)"
      >
        <i class="fas bcp-policy__option-icon" :class="option.icon"></i>
        <span class="bcp-policy__option-body">
          <strong>{{ option.title }}</strong>
          <small>{{ option.text }}</small>
        </span>
        <i class="fas bcp-policy__check" :class="policy === option.value ? 'fa-circle-check' : 'fa-circle'"></i>
      </button>
    </div>
  </section>

  <div v-if="stats.started" class="alert alert-danger bcp-alert" role="alert">
    <i class="fas fa-triangle-exclamation mr-2"></i>
    <strong>{{ stats.started }} pago(s)</strong> de salas cuyo sorteo ya inició. Si los apruebas, los cartones entran con las bolas ya cantadas.
  </div>

  <div v-if="summary.truncated" class="alert alert-warning bcp-alert" role="alert">
    <i class="fas fa-circle-info mr-2"></i>
    Se muestran los {{ payments.length }} más urgentes de {{ summary.total }}. Resuelve estos para ver el resto.
  </div>

  <TableWithFilterBridge
    table-id="bingo-card-payments-table"
    title="Cola de revisión"
    subtitle="Ordenada por la sala que inicia antes"
    search-placeholder="Buscar por sala, vendedor, comprador o teléfono..."
    :columns="columns"
    :rows="rows"
    :filters="filters"
    :page-size="15"
    empty-message="No hay pagos pendientes de revisión"
    row-vertical-align="middle"
    :show-create-button="false"
    :show-edit-button="false"
    :show-delete-button="false"
  >
    <template #cell-room_short_code="{ row }">
      <div class="bcp-room">
        <span class="bcp-code" :title="row.room_uuid_code ?? ''">#{{ row.room_short_code ?? '—' }}</span>
        <span class="text-muted small">{{ row.room_starts_at ? formatDateTime(row.room_starts_at) : 'Sin hora' }}</span>
        <span :class="URGENCY[row.urgency as PaymentUrgency].badge" class="bcp-badge">
          <i class="fas mr-1" :class="URGENCY[row.urgency as PaymentUrgency].icon"></i>{{ countdownLabel(row as PendingCardPayment) }}
        </span>
        <span v-if="row.pending_at_start_at" class="badge bcp-badge bcp-badge--late">
          Pendiente tras el inicio
        </span>
      </div>
    </template>

    <template #cell-seller_name="{ row }">
      <div class="font-weight-bold">{{ row.seller_name ?? '—' }}</div>
      <div class="text-muted small">{{ row.seller_network ?? row.commerce_name ?? '' }}</div>
      <div v-if="!policy && row.commerce_name" class="text-muted small">
        <i class="fas fa-gavel mr-1"></i>{{ POLICY_LABELS[row.pending_policy as PendingPaymentPolicy] }}
      </div>
    </template>

    <template #cell-buyer_name="{ row }">
      <div class="font-weight-bold">{{ row.external_player?.name ?? '—' }}</div>
      <div class="text-muted small">{{ row.external_player?.phone ?? '' }}</div>
    </template>

    <template #cell-amount_label="{ row }">
      <div class="font-weight-bold text-nowrap">{{ row.amount_label }}</div>
      <div class="text-muted small">{{ row.card_count ?? 0 }} cartón(es)</div>
    </template>

    <template #cell-payment_method_label="{ row }">
      <div>{{ row.payment_method_label ?? '—' }}</div>
      <div v-if="row.transaction_reference" class="text-muted small">Ref. {{ row.transaction_reference }}</div>
    </template>

    <template #cell-created_at="{ value }">
      <RelativeTime :value="value" />
    </template>

    <template #cell-actions="{ row }">
      <div class="d-flex align-items-center justify-content-end bcp-gap">
        <button
          v-if="row.payment_proof_url"
          type="button"
          class="bcp-thumb"
          title="Ver comprobante"
          @click="lightboxUrl = row.payment_proof_url"
        >
          <img :src="row.payment_proof_url" alt="Comprobante" loading="lazy" />
        </button>
        <button type="button" class="btn btn-sm btn-primary text-nowrap" @click="openDetail(String(row.id))">
          <i class="fas fa-magnifying-glass mr-1"></i>Revisar
        </button>
      </div>
    </template>
  </TableWithFilterBridge>

  <!-- Reembolsos con fallos: informativo, el reintento es automático
       (RetryFailedBingoRefundsCommand, cada 5 min). -->
  <div v-if="refundFailures.length" class="mt-4">
    <TableWithFilterBridge
      table-id="bingo-refund-failures-table"
      title="Reembolsos con fallos"
      subtitle="En reintento: se reintenta solo cada 5 minutos. Abandonado: agotó los reintentos y requiere acreditación manual (ya se avisó a superadmin)."
      search-placeholder="Buscar por sala o error..."
      :columns="refundColumns"
      :rows="refundFailures"
      :filters="refundFilters"
      :page-size="10"
      empty-message="No hay reembolsos con fallos"
      :show-create-button="false"
      :show-edit-button="false"
      :show-delete-button="false"
    >
      <template #cell-status="{ value }">
        <span :class="value === 'ABANDONED' ? 'badge bg-danger' : 'badge bg-warning text-dark'">
          {{ value === 'ABANDONED' ? 'Abandonado' : 'En reintento' }}
        </span>
      </template>
      <template #cell-amount_label="{ value }">
        <span class="font-weight-bold text-nowrap">{{ value }}</span>
      </template>
      <template #cell-last_attempted_at="{ value }">
        <RelativeTime :value="value" />
      </template>
    </TableWithFilterBridge>
  </div>

  <!-- Modal: revisión del pago -->
  <Modal v-model="showDetail" title="Revisión de pago" icon="fa-receipt" size="lg">
    <template v-if="selected">
      <div v-if="selectedUrgency === 'started'" class="alert alert-danger py-2 small">
        <i class="fas fa-triangle-exclamation mr-1"></i>
        El sorteo ya inició: si apruebas, el comprador entra con las bolas ya cantadas.
      </div>

      <div class="row">
        <div class="col-md-5">
          <small class="text-muted d-block mb-1">Comprobante</small>
          <button v-if="selected.payment_proof_url" type="button" class="bcp-proof" @click="lightboxUrl = selected.payment_proof_url">
            <img :src="selected.payment_proof_url" alt="Comprobante de pago" />
            <span class="bcp-proof__zoom"><i class="fas fa-magnifying-glass-plus"></i></span>
          </button>
          <div v-else class="bcp-proof bcp-proof--empty">
            <i class="fas" :class="isCash ? 'fa-money-bill-wave' : 'fa-image'"></i>
            <span>{{ isCash ? 'Pago en efectivo: confirma que el vendedor te entregó el dinero.' : 'Sin comprobante adjunto' }}</span>
          </div>
        </div>

        <div class="col-md-7">
          <div class="bcp-amount-box">
            <span class="text-muted small">Monto</span>
            <span class="bcp-amount-box__value">{{ selected.amount_label }}</span>
            <span class="text-muted small">{{ selected.card_count ?? 0 }} cartón(es) · {{ selected.payment_method_label ?? '—' }}</span>
          </div>

          <dl class="bcp-dl">
            <dt>Referencia</dt>
            <dd>{{ selected.transaction_reference ?? '—' }}</dd>
            <dt>Sala</dt>
            <dd>
              <span class="bcp-code">#{{ selected.room_short_code ?? '—' }}</span>
              · {{ selected.room_starts_at ? formatDateTime(selected.room_starts_at) : 'Inicio manual' }}
              <span :class="URGENCY[urgencyOf(selected)].badge" class="bcp-badge ml-1">{{ countdownLabel(selected) }}</span>
            </dd>
            <dt>Comprador</dt>
            <dd>{{ selected.external_player?.name ?? '—' }} <span class="text-muted">{{ selected.external_player?.phone ?? '' }}</span></dd>
            <dt>Vendedor</dt>
            <dd>{{ selected.seller_name ?? '—' }} <span class="text-muted">{{ selected.seller_network ?? selected.commerce_name ?? '' }}</span></dd>
            <dt>Recibido</dt>
            <dd><RelativeTime :value="selected.created_at" /></dd>
          </dl>

          <label for="bcp-notes" class="small text-muted mb-1">Notas para el vendedor (opcional)</label>
          <textarea id="bcp-notes" v-model="notes" class="form-control form-control-sm" rows="2" maxlength="255" placeholder="Se incluyen en el aviso al vendedor"></textarea>
        </div>
      </div>
    </template>

    <template #footer>
      <button type="button" class="btn btn-outline-secondary" @click="showDetail = false">Cerrar</button>
      <button type="button" class="btn btn-danger" :disabled="rejecting || approving" @click="handleReject">
        <span v-if="rejecting" class="spinner-border spinner-border-sm mr-1"></span>
        <i v-else class="fas fa-times mr-1"></i>Rechazar
      </button>
      <button type="button" class="btn btn-success" :disabled="approving || rejecting" @click="handleApprove">
        <span v-if="approving" class="spinner-border spinner-border-sm mr-1"></span>
        <i v-else class="fas fa-check mr-1"></i>{{ selectedUrgency === 'started' ? 'Aprobar igual' : 'Aprobar' }}
      </button>
    </template>
  </Modal>

  <!-- Lightbox: comprobante a pantalla completa -->
  <Teleport to="body">
    <div v-if="lightboxUrl" class="bcp-lightbox" @click.self="lightboxUrl = null">
      <button type="button" class="bcp-lightbox__close" title="Cerrar" @click="lightboxUrl = null">
        <i class="fas fa-times"></i>
      </button>
      <figure class="bcp-lightbox__figure">
        <img :src="lightboxUrl" alt="Comprobante de pago" class="bcp-lightbox__image" />
        <figcaption>
          <a :href="lightboxUrl" target="_blank" rel="noopener" class="bcp-lightbox__link">
            <i class="fas fa-up-right-from-square mr-1"></i>Abrir en otra pestaña
          </a>
        </figcaption>
      </figure>
    </div>
  </Teleport>
</template>

<style scoped>
.bcp-stats {
  display: grid;
  grid-template-columns: repeat(4, minmax(140px, 1fr));
  gap: 0.6rem;
}
@media (max-width: 992px) {
  .bcp-stats { grid-template-columns: repeat(2, minmax(140px, 1fr)); }
}

/* ── Regla ─────────────────────────────────────────────────────────────── */
.bcp-policy {
  margin-bottom: 1.25rem;
  padding: 1rem 1.1rem;
  border: 1px solid var(--ds-border);
  border-radius: 12px;
  background: var(--ds-surface);
}
.bcp-policy__head { display: flex; align-items: flex-start; gap: 0.75rem; margin-bottom: 0.85rem; }
.bcp-policy__icon {
  width: 38px; height: 38px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  border-radius: 10px;
  background: rgba(37,99,235,.15); color: #60a5fa;
}
.bcp-policy__title { margin: 0; font-size: 0.95rem; font-weight: 700; color: var(--ds-text); }
.bcp-policy__hint { margin: 0.15rem 0 0; font-size: 0.78rem; color: var(--ds-text-muted); }

.bcp-policy__options { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.6rem; }
@media (max-width: 768px) {
  .bcp-policy__options { grid-template-columns: 1fr; }
}
.bcp-policy__option {
  display: flex; align-items: flex-start; gap: 0.7rem;
  padding: 0.75rem 0.85rem;
  text-align: left;
  border: 1px solid var(--ds-border);
  border-radius: 10px;
  background: var(--ds-surface-2);
  color: var(--ds-text);
  cursor: pointer;
  transition: border-color .15s, box-shadow .15s;
}
.bcp-policy__option:hover:not(:disabled) { border-color: #60a5fa; }
.bcp-policy__option:disabled { opacity: .7; cursor: progress; }
.bcp-policy__option.is-active { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.15); }
.bcp-policy__option-icon { margin-top: 2px; color: var(--ds-text-2); }
.bcp-policy__option-body { display: flex; flex-direction: column; gap: 0.2rem; flex: 1; min-width: 0; }
.bcp-policy__option-body small { color: var(--ds-text-muted); line-height: 1.3; }
.bcp-policy__check { margin-top: 2px; color: var(--ds-text-muted); }
.bcp-policy__option.is-active .bcp-policy__check { color: #2563eb; }

.bcp-alert { border-radius: 10px; font-size: 0.85rem; }

/* ── Tabla ─────────────────────────────────────────────────────────────── */
.bcp-room { display: flex; flex-direction: column; align-items: flex-start; gap: 0.2rem; }
.bcp-code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-weight: 700; color: var(--ds-text); }
.bcp-badge { font-size: 0.68rem; font-weight: 600; }
.bcp-badge--late { background: rgba(220,38,38,.12); color: #dc2626; border: 1px solid rgba(220,38,38,.35); }
.bcp-gap { gap: 0.5rem; }

.bcp-thumb {
  width: 40px; height: 40px; padding: 0; flex-shrink: 0;
  border: 1px solid var(--ds-border); border-radius: 8px;
  background: var(--ds-surface-2); overflow: hidden; cursor: zoom-in;
}
.bcp-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }

/* ── Modal ─────────────────────────────────────────────────────────────── */
.bcp-proof {
  position: relative; display: block; width: 100%; padding: 0;
  border: 1px solid var(--ds-border); border-radius: 10px;
  background: var(--ds-surface-2); overflow: hidden; cursor: zoom-in;
}
.bcp-proof img { display: block; width: 100%; max-height: 300px; object-fit: contain; transition: filter .15s; }
.bcp-proof:hover img { filter: brightness(.85); }
.bcp-proof__zoom {
  position: absolute; inset: 0;
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: 1.4rem; opacity: 0; transition: opacity .15s; pointer-events: none;
}
.bcp-proof:hover .bcp-proof__zoom { opacity: 1; }
.bcp-proof--empty {
  display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem;
  min-height: 180px; padding: 1rem; text-align: center; cursor: default;
  color: var(--ds-text-muted); font-size: 0.82rem;
}
.bcp-proof--empty i { font-size: 1.6rem; }

.bcp-amount-box {
  display: flex; flex-direction: column;
  padding: 0.7rem 0.9rem; margin-bottom: 0.75rem;
  border: 1px solid var(--ds-border); border-radius: 10px;
  background: var(--ds-surface-2);
}
.bcp-amount-box__value { font-size: 1.35rem; font-weight: 700; color: var(--ds-text); line-height: 1.2; }

.bcp-dl { display: grid; grid-template-columns: 95px 1fr; gap: 0.35rem 0.75rem; margin: 0 0 0.85rem; font-size: 0.85rem; }
.bcp-dl dt { font-weight: 500; color: var(--ds-text-muted); }
.bcp-dl dd { margin: 0; color: var(--ds-text); min-width: 0; overflow-wrap: anywhere; }

/* ── Lightbox (mismo patrón que KYC) ───────────────────────────────────── */
.bcp-lightbox {
  position: fixed; inset: 0; z-index: 1200;
  display: flex; align-items: center; justify-content: center;
  padding: 2rem; background: rgba(0,0,0,.85); cursor: zoom-out;
}
.bcp-lightbox__close {
  position: absolute; top: 1.25rem; right: 1.25rem;
  width: 40px; height: 40px; border-radius: 50%; border: none;
  display: flex; align-items: center; justify-content: center;
  background: rgba(255,255,255,.12); color: #fff; font-size: 1.1rem; cursor: pointer;
}
.bcp-lightbox__close:hover { background: rgba(255,255,255,.22); }
.bcp-lightbox__figure { margin: 0; max-width: 90vw; max-height: 90vh; text-align: center; }
.bcp-lightbox__image { max-width: 100%; max-height: 80vh; border-radius: 8px; cursor: default; }
.bcp-lightbox__link { display: inline-block; margin-top: 0.75rem; color: #e5e7eb; font-size: 0.85rem; }
</style>
