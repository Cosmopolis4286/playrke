<script setup lang="ts">
  import { computed } from 'vue'
  import type { ExchangeRate, UsedCurrency } from '@js/types/exchange-rate'

  /**
   * Props del componente ExchangeRateTable
   */
  const props = defineProps<{
    usedCurrencies?: UsedCurrency[]
    exchangeRates: ExchangeRate[]
    roleUser: string
  }>()

  /**
   * Eventos emitidos por el componente
   */
  defineEmits<{
    (e: 'edit', id: number | string): void
    (e: 'change-status', id: number | string): void
    (e: 'delete', id: number | string): void
  }>()

  /**
   * Indica si un usuario con rol externo
   */
  const extUser = computed(() => props.roleUser === 'company' || props.roleUser === 'customer')

  type CurrencyValueKey = 'name' | 'iso_code' | 'country_iso_code'

  /**
   * Obtiene un valor de la moneda de forma segura por ID
   * @param currencyId ID de la moneda
   * @param key Valor a obtener
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
</script>

<template>
  <div class="table-responsive">
    <table class="table-striped table-bordered w-100 my-2 table">
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
            <template v-if="!extUser">
              {{ getValueByCurrencyId(item?.currency_id, 'iso_code') }}
            </template>
            {{ Number(item.exchange_rate).toFixed(2) }}
          </td>

          <!-- Porcentaje -->
          <td>
            {{ item.percentage !== 0 ? `${item.percentage}%` : '-' }}
          </td>

          <!-- Status -->
          <td>
            <span v-if="item.status === 'INACTIVE'" class="badge badge-secondary"> No/Disp </span>

            <span v-else-if="item.status === 'ACTIVE'" class="badge badge-success">
              Disponible
            </span>

            <a
              v-else-if="item.status === 'DIRECTORY'"
              :href="`/directory/${getValueByCurrencyId(item?.currency_id, 'country_iso_code')}`"
              class="badge badge-danger"
              title="Registrar Datos Bancarios"
            >
              Falta Directorio
            </a>
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
                v-if="item.exchange_rate > 0"
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
