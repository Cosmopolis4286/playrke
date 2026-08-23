<script setup lang="ts">
import type { SellerSalesRow } from '@/js/types/roles/commerce/dashboard/dashboard-commerce'
import DashboardEmptyState from '@/js/Components/Common/Dashboard/DashboardEmptyState.vue'

defineProps<{
  rows: SellerSalesRow[]
  formatUsd: (v: number, decimals?: number) => string
}>()
</script>

<template>
  <div v-if="rows.length" class="ssl-table-wrap">
    <table class="ssl-table">
      <thead>
        <tr>
          <th>Vendedor</th>
          <th class="ssl-table__num">Cartones</th>
          <th class="ssl-table__num">Total vendido</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in rows" :key="row.seller_user_id ?? 'direct'">
          <td>{{ row.seller_name }}</td>
          <td class="ssl-table__num">{{ row.total_cards }}</td>
          <td class="ssl-table__num ssl-table__amount">$ {{ formatUsd(row.total_sales) }}</td>
        </tr>
      </tbody>
    </table>
  </div>
  <DashboardEmptyState
    v-else
    icon="fas fa-user-tie"
    title="Sin ventas este mes"
    description="El desglose por vendedor aparecerá aquí en cuanto haya ventas registradas."
  />
</template>

<style scoped>
.ssl-table-wrap {
  overflow-x: auto;
  border-radius: 10px;
  border: 1px solid var(--ds-border);
}

.ssl-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
}

.ssl-table thead tr {
  background: var(--ds-surface-2);
  border-bottom: 1px solid var(--ds-border);
}

.ssl-table th {
  padding: 0.65rem 0.875rem;
  font-weight: 600;
  color: var(--ds-text-2);
  text-align: left;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  white-space: nowrap;
}

.ssl-table td {
  padding: 0.65rem 0.875rem;
  border-bottom: 1px solid var(--ds-border);
  color: var(--ds-text);
  vertical-align: middle;
}

.ssl-table tbody tr:last-child td { border-bottom: none; }
.ssl-table tbody tr:hover td { background: var(--ds-surface-2); }

.ssl-table__num { text-align: right; }
.ssl-table__amount { font-weight: 600; color: var(--ds-text); }
</style>
