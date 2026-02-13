<script setup lang="ts">
  import BaseTable from '@js/Components/Common/Table/BaseTable.vue'
  import type { Column } from '@/js/types/common/table/column-row'
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

  const columns: Column[] = [
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
</script>

<template>
  <BaseTable
    table-id="exchange-rate-table"
    :columns="columns"
    :rows="(exchangeRates as Record<string, any>[])"
    :selected-ids="selectedIds"
    selectable
    empty-message="No hay tasas de cambio registradas"
    @selection-change="$emit('selection-change', $event)"
  >
    <!-- Moneda -->
    <template #cell-currency="{ row }">
      {{ getValueByCurrencyId(row.currency_id, 'description') }}
    </template>

    <!-- Cambio -->
    <template #cell-amount="{ row }">
      <template v-if="!hasPrivilegedRole">
        {{ getValueByCurrencyId(row.currency_id, 'iso_code') }}
      </template>
      {{ Number(row.amount).toFixed(2) }}
    </template>

    <!-- Porcentaje -->
    <template #cell-percentage="{ row }">
      {{ row.percentage != 0 ? `${row.percentage}%` : '-' }}
    </template>

    <!-- Status -->
    <template #cell-status="{ row }">
      <template v-if="statusMap[row.status]">
        <a
          v-if="statusMap[row.status].isLink"
          :href="(statusMap[row.status] as { getUrl: Function }).getUrl(row)"
          :class="['badge', statusMap[row.status].class]"
          :title="(statusMap[row.status] as { title: string }).title"
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
