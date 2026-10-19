<script setup lang="ts">
  import BaseTable from '@js/Components/Common/Table/BaseTable.vue'
  import ExchangeRateTrendChart from '@js/Components/Common/ExchangeRate/ExchangeRateTrendChart.vue'
  import type { Column, Row } from '@/js/types/common/table/column-row'
  import type { ExchangeRate, UsedCurrency } from '@js/types/exchange-rate'

  const props = defineProps<{
    usedCurrencies?: UsedCurrency[]
    exchangeRates: ExchangeRate[]
    hasPrivilegedRole: boolean
    selectedIds: string[]
  }>()

  defineEmits<{
    (e: 'selection-change', ids: string[]): void
  }>()

  // Origen/sparkline solo aportan valor con volumen histórico real (tasa
  // OFICIAL de admin/superadmin/treasurer) — ver
  // ExchangeRateContextService::getAll(), que solo puebla `history` para
  // ese grupo de roles.
  const columns: Column[] = props.hasPrivilegedRole
    ? [
        { key: 'currency', label: 'Moneda' },
        { key: 'amount', label: 'Cambio' },
        { key: 'percentage', label: '%' },
        { key: 'trend', label: 'Tendencia', hideBelow: 'md' },
        { key: 'user_name', label: 'Registrado por', hideBelow: 'lg' },
        { key: 'status', label: 'Status' }
      ]
    : [
        { key: 'currency', label: 'Moneda' },
        { key: 'amount', label: 'Cambio' },
        { key: 'percentage', label: '%' },
        { key: 'status', label: 'Status' }
      ]

  type CurrencyValueKey = 'name' | 'description' | 'iso_code' | 'country_iso_code'

  function getValueByCurrencyId(
    currencyId?: number | string,
    key: CurrencyValueKey = 'name'
  ): string | undefined {
    if (currencyId == null) return undefined

    const currency = props.usedCurrencies?.find(c => c.id == currencyId)
    if (!currency) return undefined

    const map: Record<CurrencyValueKey, string | undefined> = {
      name: currency.name,
      description: currency.description,
      iso_code: currency.iso_code,
      country_iso_code: currency.country?.name
    }

    return map[key]
  }

  type StatusInfo =
    | { text: string; class: string; isLink: false }
    | {
        text: string
        class: string
        isLink: true
        getUrl: (item: ExchangeRate) => string
        title: string
      }

  const statusMap: Record<string, StatusInfo> = {
    INACTIVE: { text: 'Inactiva', class: 'badge-secondary', isLink: false },
    ACTIVE: { text: 'Disponible', class: 'badge-success', isLink: false },
    DIRECTORY: {
      text: 'Falta Directorio',
      class: 'badge-danger',
      isLink: true,
      getUrl: (item: ExchangeRate) =>
        `/directory/${getValueByCurrencyId(item.currency_id, 'country_iso_code')}`,
      title: 'Registrar Datos Bancarios'
    }
  }

  // El compilador de plantillas de Vue no propaga el narrowing de un type
  // guard entre v-if y los bindings del mismo elemento — se resuelven aquí
  // como funciones que devuelven '' cuando el status no es de tipo link, en
  // vez de castear el tipo dentro del template.
  /** Monto con separadores locales; 2 decimales salvo tasas pequeñas (4). */
  function formatAmount(value: number | string): string {
    const n = Number(value)
    return n.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: n >= 100 ? 2 : 4 })
  }

  function statusUrl(row: Row): string {
    const status = statusMap[row.status]
    return status?.isLink ? status.getUrl(row as unknown as ExchangeRate) : ''
  }

  function statusTitle(row: Row): string {
    const status = statusMap[row.status]
    return status?.isLink ? status.title : ''
  }
</script>

<template>
  <BaseTable
    table-id="exchange-rate-table"
    class="er-table"
    row-vertical-align="middle"
    :columns="columns"
    :rows="exchangeRates as Row[]"
    :selected-ids="selectedIds"
    selectable
    empty-message="No hay tasas de cambio registradas"
    @selection-change="$emit('selection-change', $event)"
  >
    <!-- Moneda -->
    <template #cell-currency="{ row }">
      <span class="er-cur">
        <span class="er-cur__code">{{ getValueByCurrencyId(row.currency_id, 'iso_code') }}</span>
        <span class="er-cur__name">{{ getValueByCurrencyId(row.currency_id, 'description') }}</span>
      </span>
    </template>

    <!-- Cambio -->
    <template #cell-amount="{ row }">
      <span class="er-amount">{{ formatAmount(row.amount) }}</span>
    </template>

    <!-- Porcentaje -->
    <template #cell-percentage="{ row }">
      <span
        v-if="row.percentage != 0"
        class="er-pct"
        :class="row.percentage > 0 ? 'er-pct--up' : 'er-pct--down'"
      >
        <i :class="row.percentage > 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
        {{ Math.abs(Number(row.percentage)).toLocaleString('es-VE', { maximumFractionDigits: 2 }) }}%
      </span>
      <span v-else class="er-pct er-pct--flat">—</span>
    </template>

    <!-- Tendencia (sparkline) -->
    <template v-if="hasPrivilegedRole" #cell-trend="{ row }">
      <ExchangeRateTrendChart
        v-if="(row.history?.length ?? 0) > 1"
        :data="row.history"
        :height="24"
        compact
      />
      <span v-else class="text-muted">—</span>
    </template>

    <!-- Registrado por / Origen -->
    <template v-if="hasPrivilegedRole" #cell-user_name="{ row }">
      <span v-if="row.source === 'system'" class="er-source er-source--system">
        <i class="fas fa-robot"></i> PlayRKE
      </span>
      <span v-else>{{ row.user_name ?? '-' }}</span>
    </template>

    <!-- Status -->
    <template #cell-status="{ row }">
      <template v-if="statusMap[row.status]">
        <a
          v-if="statusMap[row.status].isLink"
          :href="statusUrl(row)"
          :class="['badge', statusMap[row.status].class]"
          :title="statusTitle(row)"
        >
          {{ statusMap[row.status].text }}
        </a>
        <span v-else :class="['badge', statusMap[row.status].class]">
          {{ statusMap[row.status].text }}
        </span>
      </template>
      <span v-else class="badge badge-light">Desconocido</span>
    </template>
  </BaseTable>
</template>

<style scoped>
.er-pct {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  font-weight: 600;
}
.er-pct i { font-size: 0.75em; }
/* Sube la tasa = la moneda local se devalúa: rojo, igual que el widget. */
.er-pct--up { color: #dc2626; }
.er-pct--down { color: #16a34a; }
.er-pct--flat { color: var(--ds-text-muted, #8a94a6); font-weight: 400; }

.er-source--system {
  color: #a78bfa;
  font-weight: 600;
}

.er-cur { display: inline-flex; align-items: baseline; gap: 0.45rem; min-width: 0; }
.er-cur__code { font-size: 0.72rem; font-weight: 700; letter-spacing: 0.04em; padding: 0.1rem 0.4rem; border-radius: 6px; background: var(--ds-surface-2); color: var(--ds-text-2); }
.er-cur__name { color: var(--ds-text-2); font-size: 0.82rem; }
.er-amount { font-weight: 700; font-variant-numeric: tabular-nums; }

/* Filas más bajas */
.er-table :deep(td),
.er-table :deep(th) { padding-top: 0.4rem; padding-bottom: 0.4rem; }
.er-table :deep(.badge) { font-size: 0.68rem; font-weight: 600; }
</style>
