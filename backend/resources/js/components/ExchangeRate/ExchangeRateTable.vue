<script setup lang="ts">
  import type { ExchangeRate, UsedCurrency } from '@js/types/exchange-rate'

  /**
   * Props del componente ExchangeRateTable
   */
  const props = defineProps<{
    usedCurrencies?: UsedCurrency[]
    exchangeRates: ExchangeRate[]
    hasPrivilegedRole: boolean
  }>()

  /**
   * Eventos emitidos por el componente
   */
  defineEmits<{
    (e: 'edit', id: number | string): void
    (e: 'change-status', id: number | string): void
    (e: 'delete', id: number | string): void
  }>()

  type CurrencyValueKey = 'name' | 'iso_code' | 'country_iso_code'

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
</script>

<template>
  <div class="table-responsive mt-3">
    <table class="table-striped table-bordered w-100 table">
      <thead>
        <tr>
          <th>Moneda</th>
          <th>Cambio</th>
          <th>%</th>
          <th>Status</th>
          <th>Acción</th>
        </tr>
      </thead>

      <tbody>
        <tr v-for="item in exchangeRates" :key="item.id">
          <!-- Moneda -->
          <td>
            {{ getValueByCurrencyId(item?.currency_id, 'name') }}
          </td>

          <!-- Cambio -->
          <td>
            <template v-if="!hasPrivilegedRole">
              {{ getValueByCurrencyId(item?.currency_id, 'iso_code') }}
            </template>
            {{ Number(item.amount).toFixed(2) }}
          </td>

          <!-- Porcentaje -->
          <td>
            {{ item.percentage !== 0 ? `${item.percentage}%` : '-' }}
          </td>

          <!-- Status -->
          <td>
            <template v-if="statusMap[item.status]">
              <a
                v-if="statusMap[item.status].isLink"
                :href="(statusMap[item.status] as { getUrl: Function }).getUrl(item)"
                :class="['badge', statusMap[item.status].class]"
                :title="(statusMap[item.status] as { title: string }).title"
              >
                {{ statusMap[item.status].text }}
              </a>
              <span v-else :class="['badge', statusMap[item.status].class]">
                {{ statusMap[item.status].text }}
              </span>
            </template>
            <span v-else class="badge badge-light">Desconocido</span>
          </td>

          <!-- Acciones -->
          <td>
            <div class="btn-group btn-group-toggle">
              <button
                v-if="item.status === 'ACTIVE'"
                class="btn btn-sm btn-primary"
                type="button"
                title="Editar Tasa de Cambio"
                @click="$emit('edit', item.id)"
              >
                <i class="fas fa-edit"></i>
              </button>

              <button
                v-if="item.amount > 0"
                class="btn btn-sm btn-secondary"
                type="button"
                title="Cambiar Status"
                @click="$emit('change-status', item.id)"
              >
                <i class="fa-solid fa-repeat"></i>
              </button>

              <button
                class="btn btn-sm btn-danger"
                type="button"
                title="Eliminar"
                @click="$emit('delete', item.id)"
              >
                <i class="fa-solid fa-trash-can"></i>
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
