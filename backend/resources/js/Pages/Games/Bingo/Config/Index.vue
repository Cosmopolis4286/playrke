<script setup lang="ts">
  import { ref, computed, watch } from 'vue'

  import CardHeader from '@js/Components/Common/Card/CardHeader.vue'
  import BingoConfigForm from '@/js/Components/Games/Bingo/Config/BingoConfigForm.vue'

  import { useBingoConfig } from '@/js/composables/games/bingo/config/useBingoConfig'

  import type { ExchangeRatePageProps } from '@js/types/exchange-rate'

  /**
   * Props Inertia
   */
  const props = defineProps<ExchangeRatePageProps>()

  /**
   * Composable de negocio para manejo de la configuracion
   */
  const { } = useBingoConfig(props.tokenId)

  /**
   * IDs seleccionados en la tabla
   */
  const selectedIds = ref<string[]>([])

  /**
   * Propiedades y estado local
   */
  const currencies = props.currencies
  const usedCurrencies = ref<UsedCurrency[]>([])

  /**
   * Loading exclusivo para la acción "Actualizar la configuracion"
   * Vive en el padre porque aquí vive la operación async
   */
  const updatingExchangeRates = ref(false)

  /**
   * Genera las monedas usadas en las la configuracion para presentación
   */
  watch(
    exchangeRates,
    newRates => {
      usedCurrencies.value = newRates.map(rate => {
        const currency = currencies.find(c => c.id === rate.currency_id)

        return {
          id: rate.currency_id,
          name: currency?.name ?? 'Desconocido',
          description: currency?.description ?? 'Desconocido',
          iso_code: currency?.iso_code ?? 'Desconocido',
          type: currency?.type,
          country: currency?.country
            ? {
                id: currency.country.id,
                name: currency.country.name,
                iso_code: currency.country.iso_code_3
              }
            : null
        }
      })
    },
    { immediate: true }
  )

  /**
   * Abre el modal en modo creación, inicializando datos
   */
  function handleCreate(): void {
    create()
    showModalForm.value = true
  }

  /**
   * Abre el modal en modo edición cargando datos existentes
   *
   * @param id Identificador de la tasa a editar
   */
  async function handleEdit(id: string): Promise<void> {
    await edit(id)
    showModalForm.value = true
  }

  /**
   * Alterna el status de las la configuracion seleccionadas
   */
  async function handleChangeStatus(ids: string[]): Promise<void> {
    for (const id of ids) {
      await toggleStatus(id)
    }
    selectedIds.value = []
  }

  /**
   * Elimina las la configuracion seleccionadas
   */
  async function handleDelete(ids: string[]): Promise<void> {
    for (const id of ids) {
      await remove(id)
    }
    selectedIds.value = []
  }

  /**
   * Navega a la vista histórica de la tasa seleccionada
   */
  function goHistorical(): void {
    if (!selectedIds.value.length) return

    const selected = exchangeRates.value.find(er => String(er.id) === selectedIds.value[0])
    if (!selected?.currency_id) return

    const params = props.tokenId ? `?tokenId=${props.tokenId}` : ''
    window.location.href = `/exchange-rate/historical/${selected.currency_id}${params}`
  }
</script>
<template>
  <div class="card">
    <CardHeader title="Tasas de Cambio" subtitle="Valores en base a Un Dólar (1 USD)" />
    <div class="card-body">
      <BingoConfigForm
        ref="exchangeRateFormRef"
        v-model="exchangeRateData"
        :currencies="currencies"
        :usedCurrencies="usedCurrencies"
        :has-privileged-role="hasPrivilegedRole"
      />
    </div>
  </div>
</template>
