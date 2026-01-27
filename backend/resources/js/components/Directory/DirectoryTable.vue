<script setup lang="ts">
  import BaseTable from '../Common/Table/BaseTable.vue'
  import type { Column, Row } from '@/js/types/common/table/column-row'

  /**
   * Props del componente ExchangeRateTable
   */
  const props = defineProps<{
    columns: Column[]
    rows: Row[]
  }>()

  /**
   * Eventos emitidos por el componente
   */
  defineEmits<{
    (e: 'edit', id: number | string): void
    (e: 'change-status', id: number | string): void
    (e: 'delete', id: number | string): void
  }>()

  type CurrencyValueKey = 'name' | 'description' | 'iso_code' | 'country_iso_code'

  /**
   * Obtiene un valor de la moneda de forma segura por ID
   * @param currencyId ID de la moneda
   * @param key Valor a obtener
   * @returns Valor correspondiente o undefined
   */
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

  // Mapeo para status: texto, clase, y si es link, url y title
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
  const parsedRows = computed(() =>
    typeof props.rows === 'string' ? JSON.parse(props.rows) : props.rows
  )
</script>

<template>
  <BaseTable
    table-id="directory-table"
    :columns="columns"
    :rows="filteredRows"
    :empty-message="emptyMessage"
    :selected-ids="selectedIds"
    @selection-change="handleSelectionChange"
  >
    <template
      v-for="column in columns"
      :key="column.key"
      v-slot:[`cell-${column.key}`]="{ row, value }"
    >
      <slot :name="`cell-${column.key}`" :row="row" :value="value" />
    </template>
  </BaseTable>
</template>
