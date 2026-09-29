<script setup lang="ts">
  import { computed } from 'vue'
  import CardHeader from '@js/Components/Common/Card/CardHeader.vue'
  import BaseTable from '@js/Components/Common/Table/BaseTable.vue'
  import TableFooterInfo from '@/js/Components/Common/Table/TableFooterInfo.vue'
  import ExchangeRateTrendChart from '@/js/Components/Common/ExchangeRate/ExchangeRateTrendChart.vue'

  import type { Column, Row } from '@/js/types/common/table/column-row'
  import type { ExchangeRateHistoricalPageProps } from '@js/types/exchange-rate'

  const props = defineProps<ExchangeRateHistoricalPageProps>()

  const isAdmin = computed(() => ['admin', 'superadmin'].includes(props.roleUser))

  /**
   * getHistoryByCurrency() (backend) trae orderByDesc('updated_at') — más
   * reciente primero, el orden correcto para la tabla ("qué pasó último"
   * arriba) pero invertido para el gráfico (que necesita antiguo→reciente
   * de izquierda a derecha) — ver reverse=true en ExchangeRateTrendChart.
   */
  const trendData = computed(() =>
    props.exchangeRates
      .filter(r => r.created_at)
      .map(r => ({ date: r.created_at as string, amount: Number(r.amount) }))
  )

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
      <div class="mb-4">
        <ExchangeRateTrendChart :data="trendData" label="Valor de cambio" reverse :height="220" />
      </div>

      <BaseTable
        table-id="exchange-rate-historical-table"
        :columns="columns"
        :rows="(exchangeRates as unknown as Row[])"
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
          <span v-if="row.source === 'system'" class="er-hist-source er-hist-source--system">
            <i class="fas fa-robot"></i> Automático
          </span>
          <span v-else>{{ row.user_name ?? '-' }}</span>
        </template>
      </BaseTable>

      <TableFooterInfo
        :filtered-count="exchangeRates.length"
        :total-count="exchangeRates.length"
      />
    </div>
  </div>
</template>

<style scoped>
.er-hist-source {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.8rem;
}
.er-hist-source--system {
  color: #a78bfa;
  font-weight: 600;
}
</style>
