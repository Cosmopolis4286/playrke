<script setup lang="ts">
import { ref } from 'vue'
import backendClient from '@js/plugins/axios'
import { useToast } from 'vue-toastification'
import { showToastConfirmAction } from '@js/utils/toastNotify'

interface ExternalPlayerInfo {
  name: string
  phone: string | null
}

interface PendingPayment {
  id: string
  room_uuid_code: string | null
  seller_name: string | null
  external_player: ExternalPlayerInfo | null
  card_count: number | null
  payment_amount: number
  transaction_reference: string | null
  payment_method: string | null
  payment_method_label: string | null
  payment_proof_url: string | null
  created_at: string | null
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
}>()

const toast = useToast()
const localPayments = ref<PendingPayment[]>([...props.payments])
const actionLoadingId = ref<string | null>(null)
const notesById = ref<Record<string, string>>({})

async function approve(payment: PendingPayment) {
  const confirmed = await showToastConfirmAction({
    title: 'Aprobar pago',
    text: 'Se liberarán los cartones y el comprador podrá jugar de inmediato. Esta acción no se puede deshacer.',
    icon: 'warning',
    confirmButtonText: 'Sí, aprobar',
  })
  if (!confirmed) return

  actionLoadingId.value = payment.id
  try {
    await backendClient.post(`/bingo/card-payments/${payment.id}/approve`, {
      admin_notes: notesById.value[payment.id] ?? null,
    })
    localPayments.value = localPayments.value.filter((p) => p.id !== payment.id)
    toast.success('Pago aprobado — el comprador ya puede ver y jugar sus cartones.')
  } catch (e: any) {
    toast.error(e?.response?.data?.message ?? 'No se pudo aprobar el pago.')
  } finally {
    actionLoadingId.value = null
  }
}

async function reject(payment: PendingPayment) {
  const confirmed = await showToastConfirmAction({
    title: 'Rechazar pago',
    text: '¿Rechazar este pago? Los cartones se anularán y se reembolsará al vendedor.',
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
      visibles ni jugables hasta que se aprueben aquí.
    </p>

    <div v-if="!localPayments.length" class="bcp-empty">
      No hay pagos pendientes de revisión.
    </div>

    <table v-else class="bcp-table">
      <thead>
        <tr>
          <th>Sala</th>
          <th>Vendedor</th>
          <th>Comprador externo</th>
          <th>Cartones</th>
          <th>Monto</th>
          <th>Método</th>
          <th>Referencia</th>
          <th>Comprobante</th>
          <th>Notas</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="payment in localPayments" :key="payment.id">
          <td>{{ payment.room_uuid_code ?? '—' }}</td>
          <td>{{ payment.seller_name ?? '—' }}</td>
          <td>
            <div>{{ payment.external_player?.name ?? '—' }}</div>
            <div class="bcp-muted">{{ payment.external_player?.phone ?? '' }}</div>
          </td>
          <td>{{ payment.card_count ?? '—' }}</td>
          <td>{{ payment.payment_amount.toFixed(2) }}</td>
          <td>{{ payment.payment_method_label ?? '—' }}</td>
          <td>{{ payment.transaction_reference ?? '—' }}</td>
          <td>
            <a v-if="payment.payment_proof_url" :href="payment.payment_proof_url" target="_blank" rel="noopener">
              Ver imagen
            </a>
            <span v-else-if="payment.payment_method === 'cash'" class="bcp-muted">
              Efectivo: confirma que el vendedor te entregó el dinero
            </span>
            <span v-else class="bcp-muted">Sin comprobante</span>
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
</style>
