<script setup lang="ts">
  import { computed, ref } from 'vue'
  import { router } from '@inertiajs/vue3'
  import CardHeader from '@js/Components/Common/Card/CardHeader.vue'
  import BaseTable from '@js/Components/Common/Table/BaseTable.vue'
  import TableFooterInfo from '@/js/Components/Common/Table/TableFooterInfo.vue'
  import ExchangeRateTrendChart from '@/js/Components/Common/ExchangeRate/ExchangeRateTrendChart.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import ExchangeRateForm from '@/js/Components/ExchangeRate/ExchangeRateForm.vue'

  import { useExchangeRate } from '@/js/composables/exchange-rate/useExchangeRate'
  import { useModalForm } from '@/js/composables/common/useModalForm'

  import type { Column, Row } from '@/js/types/common/table/column-row'
  import type { ExchangeRate, ExchangeRateHistoricalPageProps } from '@js/types/exchange-rate'

  const props = defineProps<ExchangeRateHistoricalPageProps>()

  const isAdmin = computed(() => ['admin', 'superadmin'].includes(props.roleUser))

  // ==========================================================================
  // Editar la tasa vigente — mismo modal/composable que /exchange-rate
  // (ver Index.vue), reutilizado acá para no salir del histórico a editar.
  // ==========================================================================

  const {
    exchangeRateData,
    edit,
    submit,
  } = useExchangeRate(props.tokenId)

  /**
   * submit() del composable solo refresca SU lista interna (fetch-list),
   * irrelevante acá porque esta página renderiza props.exchangeRates
   * (Inertia), no ese estado — sin este wrapper, tras guardar el modal se
   * cerraba pero la tabla/gráfico seguían mostrando el amount/percentage
   * viejos hasta la siguiente navegación manual. router.reload() solo pide
   * de nuevo los props de ESTA página (no una recarga completa del
   * navegador).
   */
  async function submitAndReload(): Promise<boolean> {
    const ok = await submit()
    if (ok) {
      router.reload({ only: ['exchangeRates'] })
    }
    return ok
  }

  const {
    showModalForm,
    modalTitle,
    formRef: exchangeRateFormRef,
    handleSubmit,
  } = useModalForm(exchangeRateData, submitAndReload, {
    defaultTitle: 'Tasa de Cambio',
    createTitle: 'Editar Tasa de Cambio',
    editTitle: 'Editar Tasa de Cambio',
  })

  /**
   * La fila ACTIVE es la que se edita — exchangeRates viene
   * orderByDesc('updated_at'), así que si existe una ACTIVE es la primera
   * de la lista (OLD/INACTIVE nunca deberían editarse desde acá, son
   * historial cerrado).
   */
  const activeRate = computed(() => props.exchangeRates.find(r => r.status === 'ACTIVE'))

  async function handleEditActive(): Promise<void> {
    if (!activeRate.value?.id) return
    await edit(activeRate.value.id)
    showModalForm.value = true
  }

  // ==========================================================================
  // Filtro de rango de fechas — client-side sobre los datos ya cargados
  // (el histórico completo de UNA moneda es un volumen bajo, no justifica
  // una ida y vuelta al backend por cada cambio de rango).
  // ==========================================================================

  type RangeOption = '7' | '30' | '90' | 'all'

  const selectedRange = ref<RangeOption>('all')

  const rangeOptions: { value: RangeOption; label: string }[] = [
    { value: '7', label: 'Últimos 7 días' },
    { value: '30', label: 'Últimos 30 días' },
    { value: '90', label: 'Últimos 90 días' },
    { value: 'all', label: 'Todo el histórico' },
  ]

  /** exchangeRates viene orderByDesc('updated_at') — más reciente primero. */
  const filteredRates = computed<ExchangeRate[]>(() => {
    if (selectedRange.value === 'all') return props.exchangeRates

    const days = Number(selectedRange.value)
    const cutoff = new Date()
    cutoff.setDate(cutoff.getDate() - days)

    return props.exchangeRates.filter(r => r.created_at && new Date(r.created_at) >= cutoff)
  })

  /**
   * getHistoryByCurrency() (backend) trae orderByDesc('updated_at') — más
   * reciente primero, el orden correcto para la tabla ("qué pasó último"
   * arriba) pero invertido para el gráfico (que necesita antiguo→reciente
   * de izquierda a derecha) — ver reverse=true en ExchangeRateTrendChart.
   */
  const trendData = computed(() =>
    filteredRates.value
      .filter(r => r.created_at)
      .map(r => ({ date: r.created_at as string, amount: Number(r.amount) }))
  )

  /**
   * Resumen rápido del rango mostrado — filteredRates ya viene
   * orderByDesc('updated_at') (más reciente primero), así que el actual es
   * el primero y el más antiguo el último de la lista visible.
   */
  const summary = computed(() => {
    const amounts = filteredRates.value.map(r => Number(r.amount)).filter(n => !isNaN(n))

    if (!amounts.length) {
      return null
    }

    const current = amounts[0]
    const oldest = amounts[amounts.length - 1]
    const min = Math.min(...amounts)
    const max = Math.max(...amounts)
    const periodChange = oldest > 0 ? ((current - oldest) / oldest) * 100 : 0

    return { current, min, max, periodChange }
  })

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

  function formatAmount(n: number): string {
    return n.toLocaleString('es-VE', { minimumFractionDigits: 4, maximumFractionDigits: 4 })
  }

  /**
   * GET simple hacia el endpoint de export (no axios/fetch, ni
   * router.visit de Inertia): la descarga de un archivo real necesita
   * navegación de browser nativa — Inertia interceptaría la respuesta CSV
   * como si fuera una página y fallaría.
   */
  function exportCsv(): void {
    window.location.href = `/exchange-rate/historical/${props.currencyId}/export`
  }

  /**
   * router.visit (no CardHeader's returnBtn, que va fijo a /dashboard) —
   * vuelve al listado de tasas, de donde realmente se navega hacia acá
   * (ver Index.vue::goHistorical()).
   */
  function goBack(): void {
    router.visit('/exchange-rate')
  }
</script>

<template>
  <div class="card">
    <CardHeader :title="title" subtitle="Registro histórico de variaciones" :return-btn="false" />

    <div class="card-body">
      <div class="er-hist-toolbar">
        <button type="button" class="btn btn-sm btn-outline-secondary" @click="goBack">
          <i class="fas fa-arrow-left mr-1"></i> Volver a Tasas de Cambio
        </button>
        <div class="er-hist-toolbar__right">
          <button
            v-if="activeRate"
            type="button"
            class="btn btn-sm btn-warning"
            title="Editar la tasa vigente"
            @click="handleEditActive"
          >
            <i class="fas fa-pen mr-1"></i> Editar Tasa
          </button>
          <button type="button" class="btn btn-sm btn-outline-primary" @click="exportCsv">
            <i class="fas fa-file-csv mr-1"></i> Exportar CSV
          </button>
        </div>
      </div>

      <!-- Resumen rápido del rango mostrado -->
      <div v-if="summary" class="er-hist-stats">
        <div class="er-hist-stat">
          <span class="er-hist-stat__label">Tasa actual</span>
          <span class="er-hist-stat__value">{{ formatAmount(summary.current) }}</span>
        </div>
        <div class="er-hist-stat">
          <span class="er-hist-stat__label">Variación del período</span>
          <span
            class="er-hist-stat__value er-hist-pct"
            :class="summary.periodChange > 0 ? 'er-hist-pct--up' : summary.periodChange < 0 ? 'er-hist-pct--down' : 'er-hist-pct--flat'"
          >
            <i v-if="summary.periodChange !== 0" :class="summary.periodChange > 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
            {{ summary.periodChange === 0 ? '—' : `${Math.abs(summary.periodChange).toFixed(2)}%` }}
          </span>
        </div>
        <div class="er-hist-stat">
          <span class="er-hist-stat__label">Mínimo del período</span>
          <span class="er-hist-stat__value">{{ formatAmount(summary.min) }}</span>
        </div>
        <div class="er-hist-stat">
          <span class="er-hist-stat__label">Máximo del período</span>
          <span class="er-hist-stat__value">{{ formatAmount(summary.max) }}</span>
        </div>
      </div>

      <!-- Filtro de rango de fechas -->
      <div class="er-hist-range">
        <button
          v-for="opt in rangeOptions"
          :key="opt.value"
          type="button"
          class="btn btn-sm"
          :class="selectedRange === opt.value ? 'btn-primary' : 'btn-outline-secondary'"
          @click="selectedRange = opt.value"
        >
          {{ opt.label }}
        </button>
      </div>

      <div class="mb-4">
        <ExchangeRateTrendChart :data="trendData" label="Valor de cambio" reverse :height="220" />
      </div>

      <BaseTable
        table-id="exchange-rate-historical-table"
        :columns="columns"
        :rows="(filteredRates as unknown as Row[])"
        empty-message="No hay registros históricos en el rango seleccionado"
      >
        <template #cell-currency_name="{ value }">
          {{ value }}
        </template>

        <template #cell-amount="{ row }">
          {{ Number(row.amount).toFixed(4) }}
        </template>

        <template #cell-percentage="{ row }">
          <span
            v-if="row.percentage != 0"
            class="er-hist-pct"
            :class="row.percentage > 0 ? 'er-hist-pct--up' : 'er-hist-pct--down'"
          >
            <i :class="row.percentage > 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
            {{ Math.abs(row.percentage) }}%
          </span>
          <span v-else class="er-hist-pct er-hist-pct--flat">—</span>
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
            <i class="fas fa-robot"></i> PlayRKE
          </span>
          <span v-else>{{ row.user_name ?? '-' }}</span>
        </template>
      </BaseTable>

      <TableFooterInfo
        :filtered-count="filteredRates.length"
        :total-count="exchangeRates.length"
      />
    </div>
  </div>

  <Modal
    v-model="showModalForm"
    :title="modalTitle"
    static
    show-confirm
    :on-submit="handleSubmit"
    :confirm-disabled="!(exchangeRateFormRef?.isValid ?? false)"
  >
    <ExchangeRateForm
      ref="exchangeRateFormRef"
      v-model="exchangeRateData"
      :currencies="currencies"
    />
  </Modal>
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

.er-hist-pct {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  font-weight: 600;
}
.er-hist-pct i { font-size: 0.75em; }
.er-hist-pct--up { color: #22c55e; }
.er-hist-pct--down { color: #ef4444; }
.er-hist-pct--flat { color: var(--ds-text-muted, #8a94a6); font-weight: 400; }

.er-hist-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.er-hist-stat {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  padding: 0.75rem 1rem;
  background: var(--ds-surface-2, #f8f9fa);
  border-radius: 10px;
}

.er-hist-stat__label {
  font-size: 0.72rem;
  color: var(--ds-text-muted, #8a94a6);
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.er-hist-stat__value {
  font-size: 1.1rem;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}

.er-hist-range {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  margin-bottom: 1rem;
}

.er-hist-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.er-hist-toolbar__right {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
</style>
