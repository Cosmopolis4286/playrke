<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import backendClient from '@js/plugins/axios'
import { useToast } from 'vue-toastification'
import { showToastConfirmAction } from '@js/utils/toastNotify'

interface ExternalPlayerInfo {
  name: string
  phone: string | null
}

type PendingPolicy = 'reject_refund' | 'keep_review'

interface PendingPayment {
  id: string
  room_uuid_code: string | null
  room_short_code: string | null
  room_status: string | null
  room_status_label: string | null
  room_starts_at: string | null
  room_started: boolean
  seller_name: string | null
  seller_network: string | null
  commerce_name: string | null
  pending_policy: PendingPolicy
  external_player: ExternalPlayerInfo | null
  card_count: number | null
  payment_amount: number
  currency_code: string | null
  amount_label: string
  transaction_reference: string | null
  payment_method: string | null
  payment_method_label: string | null
  payment_proof_url: string | null
  created_at: string | null
  pending_at_start_at: string | null
}

interface Meta {
  current_page: number
  last_page: number
  total: number
}

interface RefundFailure {
  id: string
  room_uuid_code: string | null
  amount: number
  status: 'PENDING' | 'ABANDONED'
  attempts: number
  error_message: string | null
  last_attempted_at: string | null
}

const props = defineProps<{
  title: string
  payments: PendingPayment[]
  refundFailures: RefundFailure[]
  meta: Meta
  /** Regla propia (solo comercializadora); null para admin. */
  policy: PendingPolicy | null
}>()

const POLICY_LABELS: Record<PendingPolicy, string> = {
  keep_review: 'Mantener en revisión',
  reject_refund: 'Rechazar con reembolso',
}

const toast = useToast()
const localPayments = ref<PendingPayment[]>([...props.payments])
const actionLoadingId = ref<string | null>(null)
const notesById = ref<Record<string, string>>({})
const policy = ref<PendingPolicy | null>(props.policy)
const savingPolicy = ref(false)
const proofUrl = ref<string | null>(null)

// Reloj para cuentas regresivas y antigüedad (cada 30 s basta).
const now = ref(Date.now())
let timer: ReturnType<typeof setInterval> | undefined
onMounted(() => { timer = setInterval(() => { now.value = Date.now() }, 30_000) })
onBeforeUnmount(() => clearInterval(timer))

const startedCount = computed(() => localPayments.value.filter((p) => isStarted(p)).length)

function isStarted(p: PendingPayment): boolean {
  return p.room_started || (!!p.room_starts_at && new Date(p.room_starts_at).getTime() <= now.value)
}

function humanizeMinutes(totalMinutes: number): string {
  const m = Math.max(0, Math.round(totalMinutes))
  if (m < 60) return `${m} min`
  const h = Math.floor(m / 60)
  if (h < 24) return `${h} h ${m % 60} min`
  return `${Math.floor(h / 24)} d ${h % 24} h`
}

/** Texto y nivel de urgencia del inicio de la sala. */
function startInfo(p: PendingPayment): { text: string; level: 'started' | 'soon' | 'later' | 'manual' } {
  if (isStarted(p)) return { text: 'Sorteo iniciado', level: 'started' }
  if (!p.room_starts_at) return { text: 'Inicio manual', level: 'manual' }
  const minutes = (new Date(p.room_starts_at).getTime() - now.value) / 60_000
  return { text: `Inicia en ${humanizeMinutes(minutes)}`, level: minutes <= 30 ? 'soon' : 'later' }
}

function formatTime(iso: string | null): string {
  if (!iso) return '—'
  return new Date(iso).toLocaleString(undefined, { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })
}

function ageLabel(iso: string | null): string {
  if (!iso) return '—'
  return `hace ${humanizeMinutes((now.value - new Date(iso).getTime()) / 60_000)}`
}

async function savePolicy(value: PendingPolicy) {
  const previous = policy.value
  policy.value = value
  savingPolicy.value = true
  try {
    await backendClient.patch('/bingo/card-payments/policy', { policy: value })
    toast.success(`Regla guardada: ${POLICY_LABELS[value]}.`)
  } catch (e: any) {
    policy.value = previous
    toast.error(e?.response?.data?.message ?? 'No se pudo guardar la regla.')
  } finally {
    savingPolicy.value = false
  }
}

async function approve(payment: PendingPayment) {
  const started = isStarted(payment)
  const confirmed = await showToastConfirmAction({
    title: started ? 'Aprobar con el sorteo iniciado' : 'Aprobar pago',
    text: started
      ? 'El sorteo ya inició: los cartones entrarán con las bolas ya cantadas. Apruébalo solo si el comprador lo acepta.'
      : 'Se liberarán los cartones y el comprador podrá jugar de inmediato. Esta acción no se puede deshacer.',
    icon: 'warning',
    confirmButtonText: started ? 'Sí, aprobar igual' : 'Sí, aprobar',
  })
  if (!confirmed) return

  await sendApprove(payment, started)
}

async function sendApprove(payment: PendingPayment, confirmLate: boolean) {
  actionLoadingId.value = payment.id
  try {
    await backendClient.post(`/bingo/card-payments/${payment.id}/approve`, {
      admin_notes: notesById.value[payment.id] ?? null,
      confirm_late: confirmLate,
    })
    localPayments.value = localPayments.value.filter((p) => p.id !== payment.id)
    toast.success('Pago aprobado — el comprador ya puede ver y jugar sus cartones.')
  } catch (e: any) {
    // El sorteo inició mientras la pantalla estaba abierta: pedir la confirmación tardía.
    if (!confirmLate && e?.response?.data?.error_code === 'BINGO_PAYMENT_LATE_APPROVAL_CONFIRM') {
      actionLoadingId.value = null
      payment.room_started = true
      const again = await showToastConfirmAction({
        title: 'El sorteo ya inició',
        text: e.response.data.message,
        icon: 'warning',
        confirmButtonText: 'Sí, aprobar igual',
      })
      if (again) await sendApprove(payment, true)
      return
    }
    toast.error(e?.response?.data?.message ?? 'No se pudo aprobar el pago.')
  } finally {
    if (actionLoadingId.value === payment.id) actionLoadingId.value = null
  }
}

async function reject(payment: PendingPayment) {
  const confirmed = await showToastConfirmAction({
    title: 'Rechazar pago',
    text: '¿Rechazar este pago? Los cartones se anularán y se reembolsará al vendedor, que debe devolverle el dinero al comprador.',
    icon: 'warning',
    confirmButtonText: 'Sí, rechazar',
  })
  if (!confirmed) return

  actionLoadingId.value = payment.id
  try {
    await backendClient.post(`/bingo/card-payments/${payment.id}/reject`, {
      admin_notes: notesById.value[payment.id] ?? null,
    })
    localPayments.value = localPayments.value.filter((p) => p.id !== payment.id)
    toast.success('Pago rechazado — cartones anulados, reembolso en proceso.')
  } catch (e: any) {
    toast.error(e?.response?.data?.message ?? 'No se pudo rechazar el pago.')
  } finally {
    actionLoadingId.value = null
  }
}
</script>

<template>
  <div class="bcp-page">
    <h1 class="bcp-title">{{ title }}</h1>
    <p class="bcp-subtitle">
      Pagos de compradores externos (sin cuenta) esperando confirmación — sus cartones no son
      visibles ni jugables hasta que se aprueben aquí. Ordenados por la sala que inicia antes.
    </p>

    <!-- Regla para los pagos que siguen pendientes cuando inicia el sorteo -->
    <section v-if="policy" class="bcp-policy">
      <div class="bcp-policy__head">
        <strong>Si el sorteo inicia y un pago sigue sin revisar:</strong>
        <span class="bcp-muted">Recibes un aviso al cerrar la venta (minutos antes del inicio).</span>
      </div>
      <label class="bcp-policy__option">
        <input type="radio" name="policy" value="keep_review" :checked="policy === 'keep_review'" :disabled="savingPolicy" @change="savePolicy('keep_review')" />
        <span>
          <strong>Mantener en revisión</strong>
          <span class="bcp-muted">Sigue en esta cola marcado "pendiente tras el inicio". Si lo apruebas, el comprador entra con las bolas ya cantadas.</span>
        </span>
      </label>
      <label class="bcp-policy__option">
        <input type="radio" name="policy" value="reject_refund" :checked="policy === 'reject_refund'" :disabled="savingPolicy" @change="savePolicy('reject_refund')" />
        <span>
          <strong>Rechazar con reembolso</strong>
          <span class="bcp-muted">Se anulan los cartones y se reembolsa al vendedor, que debe devolverle el dinero al comprador.</span>
        </span>
      </label>
    </section>

    <div v-if="startedCount" class="bcp-alert">
      {{ startedCount }} pago(s) de salas cuyo sorteo ya inició. Aprobarlos activa cartones que ya perdieron bolas.
    </div>

    <div v-if="!localPayments.length" class="bcp-empty">
      No hay pagos pendientes de revisión.
    </div>

    <div v-else class="bcp-table-wrap">
      <table class="bcp-table">
        <thead>
          <tr>
            <th>Sala</th>
            <th>Vendedor</th>
            <th>Comprador externo</th>
            <th>Cartones</th>
            <th>Monto</th>
            <th>Método / ref.</th>
            <th>Comprobante</th>
            <th>Recibido</th>
            <th>Notas</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="payment in localPayments" :key="payment.id" :class="{ 'bcp-row--started': isStarted(payment) }">
            <td>
              <div class="bcp-code" :title="payment.room_uuid_code ?? ''">#{{ payment.room_short_code ?? '—' }}</div>
              <div class="bcp-muted">{{ payment.room_starts_at ? formatTime(payment.room_starts_at) : 'Sin hora' }}</div>
              <span class="bcp-badge" :class="`bcp-badge--${startInfo(payment).level}`">{{ startInfo(payment).text }}</span>
              <div v-if="payment.pending_at_start_at" class="bcp-badge bcp-badge--danger bcp-badge--block">
                Pendiente tras el inicio
              </div>
            </td>
            <td>
              <div>{{ payment.seller_name ?? '—' }}</div>
              <div class="bcp-muted">{{ payment.seller_network ?? payment.commerce_name ?? '' }}</div>
              <div v-if="!policy && payment.commerce_name" class="bcp-muted">
                Regla: {{ POLICY_LABELS[payment.pending_policy] }}
              </div>
            </td>
            <td>
              <div>{{ payment.external_player?.name ?? '—' }}</div>
              <div class="bcp-muted">{{ payment.external_player?.phone ?? '' }}</div>
            </td>
            <td>{{ payment.card_count ?? '—' }}</td>
            <td class="bcp-amount">
              <div>{{ payment.amount_label }}</div>
              <div class="bcp-muted">{{ payment.currency_code ?? '' }}</div>
            </td>
            <td>
              <div>{{ payment.payment_method_label ?? '—' }}</div>
              <div class="bcp-muted">{{ payment.transaction_reference ?? '' }}</div>
            </td>
            <td>
              <button v-if="payment.payment_proof_url" type="button" class="bcp-proof-thumb" @click="proofUrl = payment.payment_proof_url">
                <img :src="payment.payment_proof_url" alt="Comprobante" loading="lazy" />
              </button>
              <span v-else-if="payment.payment_method === 'cash' || payment.payment_method === 'seller_cash'" class="bcp-muted">
                Efectivo: confirma que el vendedor te entregó el dinero
              </span>
              <span v-else class="bcp-muted">Sin comprobante</span>
            </td>
            <td>
              <div>{{ ageLabel(payment.created_at) }}</div>
              <div class="bcp-muted">{{ formatTime(payment.created_at) }}</div>
            </td>
            <td>
              <input
                v-model="notesById[payment.id]"
                type="text"
                placeholder="Notas (opcional)"
                class="bcp-notes-input"
              />
            </td>
            <td class="bcp-actions">
              <button
                type="button"
                class="bcp-btn bcp-btn--approve"
                :disabled="actionLoadingId === payment.id"
                @click="approve(payment)"
              >
                Aprobar
              </button>
              <button
                type="button"
                class="bcp-btn bcp-btn--reject"
                :disabled="actionLoadingId === payment.id"
                @click="reject(payment)"
              >
                Rechazar
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="meta.last_page > 1" class="bcp-pager">
      <Link v-if="meta.current_page > 1" :href="`/bingo/card-payments?page=${meta.current_page - 1}`" class="bcp-btn">← Anterior</Link>
      <Link v-if="meta.current_page < meta.last_page" :href="`/bingo/card-payments?page=${meta.current_page + 1}`" class="bcp-btn">Siguiente →</Link>
    </div>

    <!-- Vista previa del comprobante -->
    <div v-if="proofUrl" class="bcp-modal" @click.self="proofUrl = null">
      <div class="bcp-modal__body">
        <img :src="proofUrl" alt="Comprobante de pago" />
        <div class="bcp-modal__actions">
          <a :href="proofUrl" target="_blank" rel="noopener" class="bcp-btn">Abrir en otra pestaña</a>
          <button type="button" class="bcp-btn bcp-btn--reject" @click="proofUrl = null">Cerrar</button>
        </div>
      </div>
    </div>

    <p v-if="meta.total" class="bcp-meta">
      Página {{ meta.current_page }} de {{ meta.last_page }} — {{ meta.total }} pago(s) pendiente(s) en total.
    </p>

    <!-- ── Reembolsos con fallos ──
         Puramente informativo: el reintento automático (cada 5 min, ver
         RetryFailedBingoRefundsCommand) sigue siendo el único mecanismo que
         acredita estos reembolsos — no hay acción manual aquí. -->
    <h2 class="bcp-title bcp-title--secondary">Reembolsos con fallos</h2>
    <p class="bcp-subtitle">
      PENDING se reintenta automáticamente cada 5 minutos. ABANDONED agotó los reintentos y
      requiere acreditación manual (ya se notificó a superadmin).
    </p>

    <div v-if="!refundFailures.length" class="bcp-empty">
      No hay reembolsos con fallos registrados.
    </div>

    <table v-else class="bcp-table">
      <thead>
        <tr>
          <th>Sala</th>
          <th>Monto</th>
          <th>Estado</th>
          <th>Intentos</th>
          <th>Último error</th>
          <th>Último intento</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="failure in refundFailures" :key="failure.id">
          <td>{{ failure.room_uuid_code ?? '—' }}</td>
          <td>{{ failure.amount.toFixed(2) }}</td>
          <td>
            <span :class="failure.status === 'ABANDONED' ? 'bcp-badge bcp-badge--danger' : 'bcp-badge bcp-badge--warning'">
              {{ failure.status }}
            </span>
          </td>
          <td>{{ failure.attempts }}</td>
          <td class="bcp-error-cell">{{ failure.error_message ?? '—' }}</td>
          <td>{{ failure.last_attempted_at ? new Date(failure.last_attempted_at).toLocaleString() : '—' }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.bcp-page { padding: 20px; }
.bcp-title { font-size: 20px; font-weight: 700; margin: 0 0 6px; }
.bcp-subtitle { font-size: 13px; color: #6a86ac; margin: 0 0 20px; max-width: 640px; }
.bcp-empty { padding: 40px; text-align: center; color: #6a86ac; }

.bcp-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.bcp-table th, .bcp-table td {
  padding: 8px 10px;
  border-bottom: 1px solid rgba(255,255,255,.08);
  text-align: left;
  vertical-align: top;
}
.bcp-muted { font-size: 11px; color: #6a86ac; }

.bcp-notes-input {
  width: 140px;
  padding: 4px 6px;
  border-radius: 6px;
  border: 1px solid rgba(255,255,255,.15);
  background: rgba(255,255,255,.04);
  color: inherit;
  font-size: 12px;
}

.bcp-actions { display: flex; gap: 6px; }
.bcp-btn {
  padding: 5px 10px;
  border-radius: 6px;
  border: none;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}
.bcp-btn:disabled { opacity: .5; cursor: not-allowed; }
.bcp-btn--approve { background: #2af598; color: #06121a; }
.bcp-btn--reject { background: #ff5a5a; color: #fff; }

.bcp-meta { margin-top: 14px; font-size: 12px; color: #6a86ac; }

.bcp-title--secondary { margin-top: 36px; font-size: 16px; }

.bcp-badge {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
}
.bcp-badge--warning { background: rgba(255,187,32,.15); color: #ffbb20; }
.bcp-badge--danger { background: rgba(255,90,90,.15); color: #ff5a5a; }

.bcp-error-cell { max-width: 260px; font-size: 11px; color: #ff8a8a; word-break: break-word; }
.bcp-table-wrap { overflow-x: auto; }
.bcp-code { font-family: ui-monospace, monospace; font-weight: 700; }
.bcp-amount { white-space: nowrap; font-weight: 700; }
.bcp-row--started { background: rgba(255,90,90,.05); }

.bcp-policy {
  display: grid;
  gap: 8px;
  max-width: 760px;
  margin: 0 0 18px;
  padding: 12px 14px;
  border: 1px solid rgba(255,255,255,.1);
  border-radius: 10px;
  background: rgba(255,255,255,.03);
  font-size: 13px;
}
.bcp-policy__head { display: flex; flex-direction: column; gap: 2px; }
.bcp-policy__option { display: flex; gap: 8px; align-items: flex-start; cursor: pointer; }
.bcp-policy__option > span { display: flex; flex-direction: column; }

.bcp-alert {
  margin: 0 0 14px;
  padding: 10px 12px;
  border-radius: 8px;
  background: rgba(255,90,90,.12);
  color: #ff8a8a;
  font-size: 13px;
}

.bcp-badge--started { background: rgba(255,90,90,.15); color: #ff5a5a; }
.bcp-badge--soon { background: rgba(255,187,32,.15); color: #ffbb20; }
.bcp-badge--later { background: rgba(42,245,152,.12); color: #2af598; }
.bcp-badge--manual { background: rgba(106,134,172,.15); color: #6a86ac; }
.bcp-badge--block { display: block; width: fit-content; margin-top: 4px; }

.bcp-proof-thumb { padding: 0; border: 1px solid rgba(255,255,255,.15); border-radius: 6px; background: none; cursor: zoom-in; }
.bcp-proof-thumb img { display: block; width: 56px; height: 56px; object-fit: cover; border-radius: 6px; }

.bcp-pager { display: flex; gap: 8px; margin-top: 10px; }
.bcp-pager .bcp-btn { background: rgba(255,255,255,.08); color: inherit; text-decoration: none; }

.bcp-modal {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  background: rgba(0,0,0,.75);
}
.bcp-modal__body { display: flex; flex-direction: column; gap: 10px; max-width: 100%; max-height: 100%; }
.bcp-modal__body img { max-width: min(900px, 100%); max-height: 80vh; object-fit: contain; border-radius: 8px; }
.bcp-modal__actions { display: flex; gap: 8px; justify-content: flex-end; }
.bcp-modal__actions .bcp-btn { text-decoration: none; background: rgba(255,255,255,.12); color: #fff; }
.bcp-modal__actions .bcp-btn--reject { background: #ff5a5a; }
</style>
