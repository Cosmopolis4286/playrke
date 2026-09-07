<script setup lang="ts">
/**
 * Panel admin de órdenes de pago USDT-TRC20 — búsqueda por id/dirección/
 * usuario, reevaluación manual contra la blockchain. Ver
 * PAYMENTS_USDT_TRC20_DESIGN.md sección 10 (Fase G). Sin scroll anidado
 * (solo el scroll del layout principal) y sin columna de planes — ver
 * memoria del proyecto sobre restricciones de UI en pantallas admin.
 */
import { onMounted, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import PageHeader from '@/js/Components/Common/PageHeader/PageHeader.vue'
import { usePaymentOrderAdmin, type AdminPaymentOrder } from '@js/composables/payments/admin/usePaymentOrderAdmin'

defineProps<{ title: string }>()

const { orders, meta, loading, error, status, search, fetchList, reevaluate, setPage, applyFilters } = usePaymentOrderAdmin()

const reevaluatingId = ref<string | null>(null)
const feedback = ref<{ type: 'success' | 'error'; message: string } | null>(null)

const STATUSES = ['PENDING', 'DETECTED', 'CONFIRMING', 'CONFIRMED', 'UNDERPAID', 'OVERPAID', 'EXPIRED', 'FAILED', 'CANCELLED', 'CREDITED']
const TERMINAL = ['EXPIRED', 'FAILED', 'CANCELLED', 'CREDITED']

onMounted(() => {
  fetchList()
})

async function handleReevaluate(order: AdminPaymentOrder) {
  reevaluatingId.value = order.id
  const result = await reevaluate(order.id)
  feedback.value = { type: result.success ? 'success' : 'error', message: result.message }
  reevaluatingId.value = null
}

function statusClass(status: string): string {
  if (status === 'CREDITED') return 'badge--success'
  if (['UNDERPAID', 'OVERPAID', 'FAILED'].includes(status)) return 'badge--danger'
  if (['EXPIRED', 'CANCELLED'].includes(status)) return 'badge--muted'
  return 'badge--info'
}
</script>

<template>
  <Head :title="title" />

  <PageHeader
    icon="fa-money-bill-transfer"
    title="Órdenes de pago USDT-TRC20"
    subtitle="Seguimiento de pagos en TRON y reevaluación manual contra la blockchain"
    :breadcrumb-items="[
      { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
      { label: 'Pagos USDT' },
    ]"
  />

  <div class="payments-admin">
    <div class="payments-admin__filters">
      <input v-model="search" type="text" placeholder="Buscar por orden, dirección, email o usuario..." @keyup.enter="applyFilters" />
      <select v-model="status" @change="applyFilters">
        <option value="">Todos los estados</option>
        <option v-for="s in STATUSES" :key="s" :value="s">{{ s }}</option>
      </select>
      <button @click="applyFilters">Filtrar</button>
    </div>

    <div v-if="feedback" class="payments-admin__feedback" :class="`payments-admin__feedback--${feedback.type}`">
      {{ feedback.message }}
    </div>

    <div v-if="loading" class="payments-admin__state">Cargando...</div>
    <div v-else-if="error" class="payments-admin__state payments-admin__state--error">{{ error }}</div>
    <div v-else-if="!orders.length" class="payments-admin__state">Sin órdenes para los filtros actuales.</div>

    <table v-else class="payments-admin__table">
      <thead>
        <tr>
          <th>Orden</th>
          <th>Estado</th>
          <th>Usuario</th>
          <th>Producto</th>
          <th>Dirección</th>
          <th>Esperado</th>
          <th>Recibido</th>
          <th>Expira</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="order in orders" :key="order.id">
          <td class="mono">{{ order.id }}</td>
          <td><span class="badge" :class="statusClass(order.status)">{{ order.status }}</span></td>
          <td>{{ order.user?.name ?? '—' }}</td>
          <td>{{ order.product?.name ?? '—' }}</td>
          <td class="mono">{{ order.deposit_address }}</td>
          <td>{{ order.expected_amount }} {{ order.token }}</td>
          <td>{{ order.received_amount ?? '—' }}</td>
          <td>{{ order.expires_at ? new Date(order.expires_at).toLocaleString() : '—' }}</td>
          <td>
            <button
              v-if="!TERMINAL.includes(order.status)"
              :disabled="reevaluatingId === order.id"
              class="btn--reevaluate"
              @click="handleReevaluate(order)"
            >
              Reevaluar
            </button>
            <span v-else>—</span>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="meta.last_page > 1" class="payments-admin__pagination">
      <button :disabled="meta.current_page <= 1" @click="setPage(meta.current_page - 1)">Anterior</button>
      <span>Página {{ meta.current_page }} de {{ meta.last_page }} ({{ meta.total }} total)</span>
      <button :disabled="meta.current_page >= meta.last_page" @click="setPage(meta.current_page + 1)">Siguiente</button>
    </div>
  </div>
</template>

<style scoped>
.payments-admin { max-width: 1300px; margin: 0 auto; padding: 1.5rem; }

.payments-admin__filters {
  display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap;
}
.payments-admin__filters input {
  flex: 1; min-width: 240px; padding: 0.5rem 0.75rem; border-radius: 0.5rem; border: 1px solid #e5e7eb;
}
.payments-admin__filters select {
  padding: 0.5rem 0.75rem; border-radius: 0.5rem; border: 1px solid #e5e7eb;
}
.payments-admin__filters button {
  padding: 0.5rem 1rem; border-radius: 0.5rem; border: none; background: #667eea; color: #fff; cursor: pointer;
}

.payments-admin__feedback { padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.9rem; }
.payments-admin__feedback--success { background: #dcfce7; color: #166534; }
.payments-admin__feedback--error { background: #fee2e2; color: #991b1b; }

.payments-admin__state { padding: 2rem; text-align: center; color: #6b7280; }
.payments-admin__state--error { color: #dc2626; }

.payments-admin__table {
  width: 100%; border-collapse: collapse; font-size: 0.85rem;
}
.payments-admin__table th, .payments-admin__table td {
  padding: 0.6rem 0.75rem; border-bottom: 1px solid #f0f0f0; text-align: left;
}
.payments-admin__table th { font-weight: 600; color: #6b7280; text-transform: uppercase; font-size: 0.7rem; }
.mono { font-family: monospace; font-size: 0.75rem; }

.badge { padding: 0.2rem 0.6rem; border-radius: 999px; font-size: 0.7rem; font-weight: 700; }
.badge--info { background: #dbeafe; color: #1e40af; }
.badge--success { background: #dcfce7; color: #166534; }
.badge--danger { background: #fee2e2; color: #991b1b; }
.badge--muted { background: #f3f4f6; color: #6b7280; }

.btn--reevaluate {
  padding: 0.3rem 0.6rem; border-radius: 0.4rem; border: none; font-size: 0.75rem; cursor: pointer;
  background: #667eea; color: #fff;
}
.btn--reevaluate:disabled { opacity: 0.5; cursor: not-allowed; }

.payments-admin__pagination {
  display: flex; align-items: center; justify-content: center; gap: 1rem; margin-top: 1rem; font-size: 0.85rem;
}
.payments-admin__pagination button {
  padding: 0.4rem 0.8rem; border-radius: 0.4rem; border: 1px solid #e5e7eb; background: #fff; cursor: pointer;
}
.payments-admin__pagination button:disabled { opacity: 0.5; cursor: not-allowed; }
</style>
