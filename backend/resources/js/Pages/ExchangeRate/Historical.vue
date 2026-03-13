<script setup lang="ts">
  import { computed } from 'vue'
  import CardHeader from '@js/Components/Common/Card/CardHeader.vue'
  import BaseTable from '@js/Components/Common/Table/BaseTable.vue'
  import TableFooterInfo from '@/js/Components/Common/Table/TableFooterInfo.vue'

  import type { Column } from '@/js/types/common/table/column-row'
  import type { ExchangeRateHistoricalPageProps } from '@js/types/exchange-rate'

  const props = defineProps<ExchangeRateHistoricalPageProps>()

  const isAdmin = computed(() => props.roleUser === 'admin')

  const columns = computed<Column[]>(() => {
    const base: Column[] = [
      { key: 'currency_name', label: 'Moneda' },
      { key: 'amount', label: 'Cambio' },
      { key: 'percentage', label: '%' },
      { key: 'status', label: 'Status' },
      { key: 'created_at', label: 'Fecha' }
    ]
    if (isAdmin.value) {
      base.push({ key: 'user_name', label: 'Registrado por' })
    }
    return base
  })

  const statusMap: Record<string, { text: string; class: string }> = {
    ACTIVE: { text: 'Activa', class: 'badge-success' },
    INACTIVE: { text: 'Inactiva', class: 'badge-secondary' },
    OLD: { text: 'Obsoleta', class: 'badge-warning' },
    DELETED: { text: 'Eliminada', class: 'badge-danger' },
    DIRECTORY: { text: 'Directorio', class: 'badge-info' }
  }

  function formatDate(dateStr?: string): string {
    if (!dateStr) return '-'
    return new Date(dateStr).toLocaleDateString('es-VE', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit'
    })
  }
</script>

<template>
  <div class="card">
    <CardHeader :title="title" subtitle="Registro histórico de variaciones" />

    <div class="card-body">
      <BaseTable
        table-id="exchange-rate-historical-table"
        :columns="columns"
        :rows="(exchangeRates as Record<string, any>[])"
        empty-message="No hay registros históricos para esta moneda"
      >
        <template #cell-currency_name="{ value }">
          {{ value }}
        </template>

        <template #cell-amount="{ row }">
          {{ Number(row.amount).toFixed(4) }}
        </template>

        <template #cell-percentage="{ row }">
          {{ row.percentage != 0 ? `${row.percentage}%` : '-' }}
        </template>

        <template #cell-status="{ row }">
          <span
            v-if="statusMap[row.status]"
            :class="['badge', statusMap[row.status].class]"
          >
            {{ statusMap[row.status].text }}
          </span>
          <span v-else class="badge badge-light">{{ row.status }}</span>
        </template>

        <template #cell-created_at="{ row }">
          {{ formatDate(row.created_at) }}
        </template>

        <template v-if="isAdmin" #cell-user_name="{ row }">
          {{ row.user_name ?? '-' }}
        </template>
      </BaseTable>

      <TableFooterInfo
        :filtered-count="exchangeRates.length"
        :total-count="exchangeRates.length"
      />
    </div>
  </div>
</template>
