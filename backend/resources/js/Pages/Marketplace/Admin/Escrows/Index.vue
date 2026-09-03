<script setup lang="ts">
/**
 * Moderación de Escrows del Marketplace — búsqueda por escrow_id/order_id/
 * comprador/vendedor, resolución de disputas. Ver
 * MARKETPLACE_ARCHITECTURE_AUDIT.md sección 27. Sin scroll anidado (solo el
 * scroll del layout principal) y sin columna de planes — ver memoria del
 * proyecto sobre restricciones de UI en pantallas admin.
 */
import { onMounted, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import PageHeader from '@/js/Components/Common/PageHeader/PageHeader.vue'
import { useEscrowAdmin, type AdminEscrow } from '@js/composables/marketplace/admin/useEscrowAdmin'

defineProps<{ title: string }>()

const { escrows, meta, loading, error, status, search, fetchList, resolve, setPage, applyFilters } = useEscrowAdmin()

const resolvingId = ref<string | null>(null)
const feedback = ref<{ type: 'success' | 'error'; message: string } | null>(null)

const STATUSES = ['CREATED', 'FUNDED', 'ASSET_LOCKED', 'READY_TO_SETTLE', 'RELEASED', 'CANCELLED', 'REFUNDED', 'DISPUTED', 'EXPIRED']

onMounted(() => {
  fetchList()
})

async function handleResolve(escrow: AdminEscrow, resolution: 'BUYER_WINS' | 'SELLER_WINS' | 'CANCELLED') {
  resolvingId.value = escrow.id
  const result = await resolve(escrow.id, resolution)
  feedback.value = { type: result.success ? 'success' : 'error', message: result.message }
  resolvingId.value = null
}

function statusClass(status: string): string {
  if (status === 'DISPUTED') return 'badge--danger'
  if (status === 'RELEASED') return 'badge--success'
  if (status === 'REFUNDED' || status === 'CANCELLED' || status === 'EXPIRED') return 'badge--muted'
  return 'badge--info'
}
</script>

<template>
  <Head :title="title" />

  <PageHeader
    icon="fa-scale-balanced"
    title="Escrows del Marketplace"
    subtitle="Moderación de operaciones P2P y resolución de disputas"
    :breadcrumb-items="[
      { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
      { label: 'Escrows' },
    ]"
  />

  <div class="escrow-admin">
    <div class="escrow-admin__filters">
      <input v-model="search" type="text" placeholder="Buscar por escrow, orden, email o usuario..." @keyup.enter="applyFilters" />
      <select v-model="status" @change="applyFilters">
        <option value="">Todos los estados</option>
        <option v-for="s in STATUSES" :key="s" :value="s">{{ s }}</option>
      </select>
      <button @click="applyFilters">Filtrar</button>
    </div>

    <div v-if="feedback" class="escrow-admin__feedback" :class="`escrow-admin__feedback--${feedback.type}`">
      {{ feedback.message }}
    </div>

    <div v-if="loading" class="escrow-admin__state">Cargando...</div>
    <div v-else-if="error" class="escrow-admin__state escrow-admin__state--error">{{ error }}</div>
    <div v-else-if="!escrows.length" class="escrow-admin__state">Sin escrows para los filtros actuales.</div>

    <table v-else class="escrow-admin__table">
      <thead>
        <tr>
          <th>Escrow</th>
          <th>Estado</th>
          <th>Activo</th>
          <th>Comprador</th>
          <th>Vendedor</th>
          <th>Monto</th>
          <th>Fee</th>
          <th>Disputa</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="escrow in escrows" :key="escrow.id">
          <td class="mono">{{ escrow.id }}</td>
          <td><span class="badge" :class="statusClass(escrow.status)">{{ escrow.status }}</span></td>
          <td>{{ escrow.asset.name }}</td>
          <td>{{ escrow.buyer.name }}</td>
          <td>{{ escrow.seller.name }}</td>
          <td>{{ escrow.currency_symbol }}{{ escrow.amount.toFixed(2) }}</td>
          <td>{{ escrow.currency_symbol }}{{ escrow.fee_amount.toFixed(2) }}</td>
          <td>{{ escrow.dispute_reason || '—' }}</td>
          <td>
            <div v-if="escrow.status === 'DISPUTED'" class="escrow-admin__actions">
              <button :disabled="resolvingId === escrow.id" class="btn--buyer" @click="handleResolve(escrow, 'BUYER_WINS')">Comprador gana</button>
              <button :disabled="resolvingId === escrow.id" class="btn--seller" @click="handleResolve(escrow, 'SELLER_WINS')">Vendedor gana</button>
              <button :disabled="resolvingId === escrow.id" class="btn--cancel" @click="handleResolve(escrow, 'CANCELLED')">Cancelar</button>
            </div>
            <span v-else-if="escrow.resolution">{{ escrow.resolution }} por {{ escrow.resolved_by?.name }}</span>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="meta.last_page > 1" class="escrow-admin__pagination">
      <button :disabled="meta.current_page <= 1" @click="setPage(meta.current_page - 1)">Anterior</button>
      <span>Página {{ meta.current_page }} de {{ meta.last_page }} ({{ meta.total }} total)</span>
      <button :disabled="meta.current_page >= meta.last_page" @click="setPage(meta.current_page + 1)">Siguiente</button>
    </div>
  </div>
</template>

<style scoped>
.escrow-admin { max-width: 1300px; margin: 0 auto; padding: 1.5rem; }

.escrow-admin__filters {
  display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap;
}
.escrow-admin__filters input {
  flex: 1; min-width: 240px; padding: 0.5rem 0.75rem; border-radius: 0.5rem; border: 1px solid #e5e7eb;
}
.escrow-admin__filters select {
  padding: 0.5rem 0.75rem; border-radius: 0.5rem; border: 1px solid #e5e7eb;
}
.escrow-admin__filters button {
  padding: 0.5rem 1rem; border-radius: 0.5rem; border: none; background: #667eea; color: #fff; cursor: pointer;
}

.escrow-admin__feedback { padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.9rem; }
.escrow-admin__feedback--success { background: #dcfce7; color: #166534; }
.escrow-admin__feedback--error { background: #fee2e2; color: #991b1b; }

.escrow-admin__state { padding: 2rem; text-align: center; color: #6b7280; }
.escrow-admin__state--error { color: #dc2626; }

/* Sin scroll anidado propio — la tabla fluye dentro del scroll del layout
   principal; en pantallas angostas simplemente se comprime, no se corta. */
.escrow-admin__table {
  width: 100%; border-collapse: collapse; font-size: 0.85rem;
}
.escrow-admin__table th, .escrow-admin__table td {
  padding: 0.6rem 0.75rem; border-bottom: 1px solid #f0f0f0; text-align: left;
}
.escrow-admin__table th { font-weight: 600; color: #6b7280; text-transform: uppercase; font-size: 0.7rem; }
.mono { font-family: monospace; font-size: 0.75rem; }

.badge { padding: 0.2rem 0.6rem; border-radius: 999px; font-size: 0.7rem; font-weight: 700; }
.badge--info { background: #dbeafe; color: #1e40af; }
.badge--success { background: #dcfce7; color: #166534; }
.badge--danger { background: #fee2e2; color: #991b1b; }
.badge--muted { background: #f3f4f6; color: #6b7280; }

.escrow-admin__actions { display: flex; gap: 0.3rem; flex-wrap: wrap; }
.escrow-admin__actions button {
  padding: 0.3rem 0.5rem; border-radius: 0.4rem; border: none; font-size: 0.75rem; cursor: pointer; color: #fff;
}
.btn--buyer { background: #0891b2; }
.btn--seller { background: #059669; }
.btn--cancel { background: #6b7280; }

.escrow-admin__pagination {
  display: flex; align-items: center; justify-content: center; gap: 1rem; margin-top: 1rem; font-size: 0.85rem;
}
.escrow-admin__pagination button {
  padding: 0.4rem 0.8rem; border-radius: 0.4rem; border: 1px solid #e5e7eb; background: #fff; cursor: pointer;
}
.escrow-admin__pagination button:disabled { opacity: 0.5; cursor: not-allowed; }
</style>
