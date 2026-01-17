<script setup lang="ts">
  import { computed } from 'vue'
  import type { ExchangeRate } from '@js/types/exchange-rate'

  /**
   * Props del componente ExchangeRateTable
   */
  const props = defineProps<{
    exchangeRates: ExchangeRate[]
    isExternalUser: boolean
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
   * Indica si un usuario externo
   */
  const isExternalUser = computed(() => props.isExternalUser === true)

  /**
   * Obtiene el ISO del país de forma segura
   * @param item ExchangeRate
   */
  function getCountryIso(item: ExchangeRate): string | undefined {
    return item.Currency?.Country?.iso_code_2?.toLowerCase()
  }
</script>

<template>
  <div class="table-responsive">
    <h6>Valores en base a Un Dólar (1 USD)</h6>

    <table class="table-striped table-bordered w-100 my-2 table">
      <thead>
        <tr>
          <th v-if="!isExternalUser">País</th>
          <th>Moneda</th>
          <th>Cambio</th>
          <th>%</th>
          <th>Status</th>
          <th>Acción</th>
        </tr>
      </thead>

      <tbody>
        <tr v-for="item in exchangeRates" :key="item.id">
          <!-- País -->
          <td v-if="!isExternalUser">
            {{ item.Currency?.Country?.name || 'Todos' }}
          </td>

          <!-- Moneda -->
          <td>
            {{ isExternalUser ? item.currency : item.Currency?.description }}
          </td>

          <!-- Cambio -->
          <td>
            <template v-if="!isExternalUser">
              {{ item.Currency?.symbol }}
            </template>
            {{ item.exchange_rate }}
          </td>

          <!-- Porcentaje -->
          <td>
            {{ item.pct !== 0 ? `${item.pct}%` : '-' }}
          </td>

          <!-- Status -->
          <td>
            <span v-if="item.status === 'INACTIVE'" class="badge badge-secondary"> No/Disp </span>

            <span v-else-if="item.status === 'ACTIVE'" class="badge badge-success">
              Disponible
            </span>

            <a
              v-else-if="item.status === 'DIRECTORY' && getCountryIso(item)"
              :href="`/directory/${getCountryIso(item)}`"
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
                v-if="item.status === 'ACTIVE' || item.exchange_rate === 0"
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
                v-if="isExternalUser"
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
