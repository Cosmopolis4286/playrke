<script setup lang="ts">
  /**
   * Reporte financiero consolidado de la red propia de un Commerce — ventas
   * y comisión real (no la tasa configurada, el monto generado) por moneda
   * y por vendedor, en cualquier rango de fechas. Antes solo existía el
   * widget del dashboard (acotado al mes en curso) y bingo.sales-report
   * (solo lo que el propio commerce vendía con su cuenta, sin su red de
   * vendedores/agencias — ver Commerce::networkUserIds()).
   */
  import { ref, computed } from 'vue'
  import backendClient from '@js/plugins/axios'

  import DashboardSection         from '@/js/Components/Common/Dashboard/DashboardSection.vue'
  import DashboardStatCard        from '@/js/Components/Common/Dashboard/DashboardStatCard.vue'
  import DashboardSellerSalesList from '@/js/Components/Common/Dashboard/DashboardSellerSalesList.vue'
  import DashboardEmptyState      from '@/js/Components/Common/Dashboard/DashboardEmptyState.vue'

  import { useDashboardFormatters } from '@/js/composables/common/dashboard/useDashboardFormatters'

  import type { EconomySummaryPageProps, CommerceEconomySummary } from '@/js/types/roles/commerce/report/economy-summary'

  const props = defineProps<EconomySummaryPageProps>()

  const { formatUsd } = useDashboardFormatters()

  // ── Filtro de rango de fechas ──────────────────────────────────────────
  const startDate = ref(props.filters.start_date ?? '')
  const endDate   = ref(props.filters.end_date ?? '')
  const loading   = ref(false)

  const summary = ref<CommerceEconomySummary>(props.summary)

  async function applyFilter() {
    loading.value = true
    try {
      const { data } = await backendClient.get('/commerce/economy-summary/fetch', {
        params: {
          start_date: startDate.value || undefined,
          end_date:   endDate.value || undefined,
        },
      })
      if (data.success) summary.value = data.data
    } catch {
      // silencioso — el reporte conserva los últimos datos cargados correctamente
    } finally {
      loading.value = false
    }
  }

  function clearFilter() {
    startDate.value = ''
    endDate.value = ''
    applyFilter()
  }

  function handleExport() {
    const params = new URLSearchParams()
    if (startDate.value) params.set('start_date', startDate.value)
    if (endDate.value) params.set('end_date', endDate.value)
    window.location.href = `/commerce/economy-summary/export-by-seller?${params.toString()}`
  }

  const hasCurrencyData = computed(() => summary.value.by_currency.length > 0)
</script>

<template>
  <div class="ds-page">
    <div class="ds-inner">

      <!-- Filtro de rango de fechas -->
      <div class="es-filter-bar">
        <div class="es-filter-field">
          <label class="es-filter-label">Desde</label>
          <input v-model="startDate" type="date" class="es-filter-input" />
        </div>
        <div class="es-filter-field">
          <label class="es-filter-label">Hasta</label>
          <input v-model="endDate" type="date" class="es-filter-input" />
        </div>
        <button class="es-btn es-btn--primary" :disabled="loading" @click="applyFilter">
          <i class="fas fa-filter me-1"></i>{{ loading ? 'Cargando...' : 'Filtrar' }}
        </button>
        <button class="es-btn es-btn--ghost" :disabled="loading" @click="clearFilter">
          <i class="fas fa-times me-1"></i>Limpiar
        </button>
        <button class="es-btn es-btn--ghost ms-auto" @click="handleExport">
          <i class="fas fa-file-csv me-1"></i>Exportar por vendedor
        </button>
      </div>

      <!-- Totales -->
      <div class="row g-3 mt-0">
        <div class="col-12 col-sm-6 col-xl-4">
          <DashboardStatCard
            label="Ingresos totales"
            :value="`$ ${formatUsd(summary.totals.total_income)}`"
            icon="fas fa-shopping-cart"
            variant="primary"
            description="Ventas de toda tu red (directas + agencias)"
          />
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
          <DashboardStatCard
            label="Comisión generada"
            :value="`$ ${formatUsd(summary.totals.total_commission)}`"
            icon="fas fa-percentage"
            variant="success"
            description="RKE + sala + vendedor, ya desglosadas"
          />
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
          <DashboardStatCard
            label="Pool de premios"
            :value="`$ ${formatUsd(summary.totals.total_prize_pool)}`"
            icon="fas fa-trophy"
            variant="info"
            description="Repartido entre ganadores"
          />
        </div>
      </div>

      <!-- Desglose por moneda -->
      <DashboardSection
        title="Por moneda"
        icon="fas fa-coins"
        subtitle="Ingresos y comisión real generada"
      >
        <div v-if="hasCurrencyData" class="es-table-wrap">
          <table class="es-table">
            <thead>
              <tr>
                <th>Moneda</th>
                <th class="es-table__num">Ingresos</th>
                <th class="es-table__num">Comisión</th>
                <th class="es-table__num">Pool de premios</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in summary.by_currency" :key="row.currency_id">
                <td>{{ row.iso_code }}</td>
                <td class="es-table__num">{{ row.symbol }} {{ formatUsd(row.income) }}</td>
                <td class="es-table__num es-table__commission">{{ row.symbol }} {{ formatUsd(row.commission) }}</td>
                <td class="es-table__num">{{ row.symbol }} {{ formatUsd(row.prize_pool) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <DashboardEmptyState
          v-else
          icon="fas fa-coins"
          title="Sin ventas en este rango"
          description="Ajusta el filtro de fechas o espera a que tu red registre ventas."
        />
      </DashboardSection>

      <!-- Desglose por vendedor -->
      <DashboardSection
        title="Por vendedor"
        icon="fas fa-user-tie"
        subtitle="Toda tu red: vendedores directos y los de tus agencias"
      >
        <DashboardSellerSalesList :rows="summary.by_seller" :format-usd="formatUsd" />
      </DashboardSection>

    </div>
  </div>
</template>

<style scoped>
.es-filter-bar {
  display: flex;
  align-items: flex-end;
  gap: 12px;
  flex-wrap: wrap;
  background: var(--ds-surface);
  border: 1px solid var(--ds-border);
  border-radius: 10px;
  padding: 0.9rem 1rem;
}

.es-filter-field { display: flex; flex-direction: column; gap: 4px; }
.es-filter-label { font-size: 0.7rem; font-weight: 600; color: var(--ds-text-2); text-transform: uppercase; letter-spacing: .03em; }
.es-filter-input {
  border: 1.5px solid var(--ds-border-2);
  border-radius: 8px;
  padding: 6px 10px;
  font-size: 0.85rem;
  color: var(--ds-text);
  background: var(--ds-surface-2);
}

.es-btn {
  display: inline-flex;
  align-items: center;
  padding: 8px 16px;
  border-radius: 9px;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: all 0.15s;
  white-space: nowrap;
}
.es-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.es-btn--primary { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; }
.es-btn--primary:hover:not(:disabled) { filter: brightness(1.08); }
.es-btn--ghost { background: transparent; color: var(--ds-text-2); border: 1.5px solid var(--ds-border-2); }
.es-btn--ghost:hover:not(:disabled) { background: var(--ds-surface-2); }

.ms-auto { margin-left: auto; }

.es-table-wrap {
  overflow-x: auto;
  border-radius: 10px;
  border: 1px solid var(--ds-border);
}

.es-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
}

.es-table thead tr {
  background: var(--ds-surface-2);
  border-bottom: 1px solid var(--ds-border);
}

.es-table th {
  padding: 0.65rem 0.875rem;
  font-weight: 600;
  color: var(--ds-text-2);
  text-align: left;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  white-space: nowrap;
}

.es-table td {
  padding: 0.65rem 0.875rem;
  border-bottom: 1px solid var(--ds-border);
  color: var(--ds-text);
  vertical-align: middle;
}

.es-table tbody tr:last-child td { border-bottom: none; }
.es-table tbody tr:hover td { background: var(--ds-surface-2); }

.es-table__num { text-align: right; }
.es-table__commission { font-weight: 600; color: #10b981; }
</style>
