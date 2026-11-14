<script setup lang="ts">
/**
 * Tesorería: depósitos USDT que no se acreditaron solos (pago tardío o
 * reportado con txid) y transferencias recibidas sin orden que coincida.
 * Antes de aprobar, verificar el txid en Tronscan: que llegó a nuestra
 * dirección, el monto y que no fue acreditado antes.
 */
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import PageHeader from '@/js/Components/Common/PageHeader/PageHeader.vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'

interface ReviewOrder {
  id: string
  requested_amount: number
  pay_amount: string
  tx_hash: string | null
  received_amount: number | null
  from_address: string | null
  review_reason: string | null
  created_at: string | null
  user: { id: string, name: string | null, email: string | null }
}

interface UnmatchedTransfer {
  id: string
  tx_hash: string
  from_address: string
  amount: number
  block_time: string | null
}

defineProps<{
  orders: ReviewOrder[]
  unmatched: UnmatchedTransfer[]
  receivingAddress: string | null
}>()

const busyId = ref<string | null>(null)

const tronscan = (txHash: string) => `https://tronscan.org/#/transaction/${txHash}`
const fmtDate = (iso: string | null) => (iso ? new Date(iso).toLocaleString() : '—')

function errorMessage(error: unknown, fallback: string): string {
  const data = (error as { response?: { data?: { message?: string } } })?.response?.data
  return data?.message ?? fallback
}

async function approve(order: ReviewOrder) {
  const suggested = order.received_amount != null ? Math.floor(order.received_amount * 100) / 100 : order.requested_amount
  const result = await Swal.fire({
    title: 'Acreditar depósito',
    html: `Verifica el txid en Tronscan antes de acreditar.<br><small>Pidió ${order.requested_amount} USDT · recibido ${order.received_amount ?? 'sin ver aún'}</small>`,
    input: 'number',
    inputLabel: 'Monto a acreditar (USDT)',
    inputValue: suggested,
    inputAttributes: { min: '0.01', step: '0.01' },
    showCancelButton: true,
    confirmButtonText: 'Acreditar',
    cancelButtonText: 'Cancelar',
    inputValidator: value => (!value || Number(value) <= 0 ? 'Ingresa un monto válido' : null)
  })
  if (!result.isConfirmed) return

  busyId.value = order.id
  try {
    const { data } = await backendClient.post<{ message: string }>(`/usdt-deposits/${order.id}/approve`, { amount: Number(result.value) })
    showToast(data.message, 'success')
    router.reload({ only: ['orders', 'unmatched'] })
  } catch (error) {
    showToast(errorMessage(error, 'No se pudo acreditar.'), 'error')
  } finally {
    busyId.value = null
  }
}

async function reject(order: ReviewOrder) {
  const result = await Swal.fire({
    title: 'Rechazar reporte',
    input: 'text',
    inputLabel: 'Motivo (lo verá el jugador)',
    showCancelButton: true,
    confirmButtonText: 'Rechazar',
    cancelButtonText: 'Cancelar',
    inputValidator: value => (!value || value.trim().length < 5 ? 'Escribe un motivo (mínimo 5 caracteres)' : null)
  })
  if (!result.isConfirmed) return

  busyId.value = order.id
  try {
    const { data } = await backendClient.post<{ message: string }>(`/usdt-deposits/${order.id}/reject`, { reason: result.value })
    showToast(data.message, 'success')
    router.reload({ only: ['orders', 'unmatched'] })
  } catch (error) {
    showToast(errorMessage(error, 'No se pudo rechazar.'), 'error')
  } finally {
    busyId.value = null
  }
}

async function ignore(transfer: UnmatchedTransfer) {
  const result = await Swal.fire({
    title: 'Archivar transferencia',
    text: 'Úsalo cuando ya la resolviste por fuera (por ejemplo, la devolviste).',
    input: 'text',
    inputLabel: 'Nota',
    showCancelButton: true,
    confirmButtonText: 'Archivar',
    cancelButtonText: 'Cancelar',
    inputValidator: value => (!value || value.trim().length < 5 ? 'Escribe una nota (mínimo 5 caracteres)' : null)
  })
  if (!result.isConfirmed) return

  busyId.value = transfer.id
  try {
    const { data } = await backendClient.post<{ message: string }>(`/usdt-transfers/${transfer.id}/ignore`, { note: result.value })
    showToast(data.message, 'success')
    router.reload({ only: ['orders', 'unmatched'] })
  } catch (error) {
    showToast(errorMessage(error, 'No se pudo archivar.'), 'error')
  } finally {
    busyId.value = null
  }
}
</script>

<template>
  <PageHeader
    icon="fa-coins"
    title="Revisión de depósitos USDT"
    subtitle="Pagos que no se acreditaron solos y transferencias sin dueño."
    :breadcrumb-items="[
      { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
      { label: 'Revisión USDT' },
    ]"
  />

  <p v-if="receivingAddress" class="small text-muted">
    Dirección receptora: <span class="text-monospace">{{ receivingAddress }}</span>
  </p>

  <div class="card">
    <div class="card-header">
      <h3 class="card-title mb-0">
        <i class="fas fa-user-check mr-1" /> En revisión ({{ orders.length }})
      </h3>
    </div>
    <div class="card-body p-0 table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead>
          <tr>
            <th>Jugador</th>
            <th>Pidió</th>
            <th>Recibido</th>
            <th>txid</th>
            <th>Motivo</th>
            <th class="text-right">
              Acciones
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!orders.length">
            <td colspan="6" class="text-center text-muted py-4">
              No hay depósitos en revisión.
            </td>
          </tr>
          <tr v-for="order in orders" :key="order.id">
            <td>
              {{ order.user.name ?? order.user.id }}
              <small class="d-block text-muted">{{ order.user.email }}</small>
            </td>
            <td>
              {{ order.requested_amount }}
              <small class="d-block text-muted">pagar {{ order.pay_amount }}</small>
            </td>
            <td>{{ order.received_amount ?? '—' }}</td>
            <td class="text-monospace small text-break">
              <a
                v-if="order.tx_hash"
                :href="tronscan(order.tx_hash)"
                target="_blank"
                rel="noopener"
              >{{ order.tx_hash.slice(0, 16) }}…</a>
              <span v-else>—</span>
            </td>
            <td class="small">
              {{ order.review_reason }}<small class="d-block text-muted">{{ fmtDate(order.created_at) }}</small>
            </td>
            <td class="text-right text-nowrap">
              <button
                type="button"
                class="btn btn-xs btn-success mr-1"
                :disabled="busyId === order.id"
                @click="approve(order)"
              >
                <i class="fas fa-check" /> Acreditar
              </button>
              <button
                type="button"
                class="btn btn-xs btn-outline-danger"
                :disabled="busyId === order.id"
                @click="reject(order)"
              >
                <i class="fas fa-times" /> Rechazar
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h3 class="card-title mb-0">
        <i class="fas fa-question-circle mr-1" /> Transferencias sin dueño ({{ unmatched.length }})
      </h3>
    </div>
    <div class="card-body p-0 table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Monto</th>
            <th>Desde</th>
            <th>txid</th>
            <th class="text-right">
              Acciones
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!unmatched.length">
            <td colspan="5" class="text-center text-muted py-4">
              Todas las transferencias tienen dueño.
            </td>
          </tr>
          <tr v-for="transfer in unmatched" :key="transfer.id">
            <td class="small">
              {{ fmtDate(transfer.block_time) }}
            </td>
            <td>{{ transfer.amount }}</td>
            <td class="text-monospace small text-break">
              {{ transfer.from_address }}
            </td>
            <td class="text-monospace small">
              <a :href="tronscan(transfer.tx_hash)" target="_blank" rel="noopener">{{ transfer.tx_hash.slice(0, 16) }}…</a>
            </td>
            <td class="text-right">
              <button
                type="button"
                class="btn btn-xs btn-outline-secondary"
                :disabled="busyId === transfer.id"
                @click="ignore(transfer)"
              >
                <i class="fas fa-archive" /> Archivar
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="card-footer small text-muted">
      Si un jugador reporta su txid, la transferencia pasa a "En revisión" vinculada a su orden.
    </div>
  </div>
</template>
