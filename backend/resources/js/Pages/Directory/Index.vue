<script setup lang="ts">
  import { ref, computed, watch } from 'vue'

  import CardHeader from '@js/Components/Common/Card/CardHeader.vue'
  import CountryTabs from '@/js/Components/Directory/CountryTabs.vue'
  import DirectoryButtons from '@js/Components/Directory/DirectoryButtons.vue'
  import DirectoryTable from '@js/Components/Directory/DirectoryTable.vue'
  import DirectoryForm from '@/js/Components/Directory/DirectoryForm.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'

  import { useDirectory } from '@/js/composables/directory/useDirectory'
  import { useModalForm } from '@/js/composables/common/useModalForm'

  import type { DirectoryPageProps, UsedCurrency } from '@js/types/directory'

  /**
   * Props Inertia
   */
  const props = defineProps<DirectoryPageProps>()

  /**
   * Determina si el usuario tiene privilegios (company o customer)
   */
  const hasPrivilegedRole = computed(() => ['company', 'customer'].includes(props.roleUser))

  /**
   * Composable de negocio para manejo de tasas
   */
  const {
    exchangeRateData,
    exchangeRates,
    setInitialDirectorys,
    create,
    edit,
    changeStatus,
    remove,
    updateDirectorys,
    submit
  } = useDirectory(props.tokenId)

  // Inicializar exchangeRates con las props
  setInitialDirectorys(props.exchangeRates)

  /**
   * Propiedades y estado local
   */
  const selectedCurrency = ref<string[]>([])
  const currencies = props.currencies
  const usedCurrencies = ref<UsedCurrency[]>([])

  /**
   * Loading exclusivo para la acción "Actualizar tasas"
   * Vive en el padre porque aquí vive la operación async
   */
  const updatingDirectorys = ref(false)

  /**
   * Genera las monedas usadas en las tasas para presentación
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
   * Composable para el modal y formulario
   */
  const {
    showModalForm,
    modalTitle,
    formRef: directoryFormRef,
    handleSubmit
  } = useModalForm(exchangeRateData, submit)

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
  async function handleEdit(id: string | number): Promise<void> {
    await edit(id)
    showModalForm.value = true
  }

  /**
   * Ejecuta la sincronización masiva de tasas de cambio
   * y controla el loading del botón "Actualizar".
   */
  async function handleUpdateDirectorys(): Promise<void> {
    if (updatingDirectorys.value) return

    updatingDirectorys.value = true
    try {
      await updateDirectorys()
    } finally {
      updatingDirectorys.value = false
    }
  }

  /**
   * Navega a la vista histórica filtrando por las monedas seleccionadas
   */
  function goHistorical(): void {
    if (!selectedCurrency.value.length) return

    window.location.href =
      `/directory/historical/${selectedCurrency.value}` + `?tokenId=${props.tokenId}`
  }

  const countries = [
    { id: 1, iso_code_2: 'US' },
    { id: 2, iso_code_2: 'BR' },
    { id: 3, iso_code_2: 'FR' }
  ]

  const selectedCode = ref('US')

  const title = ref(`Directorio ${selectedCode.value}`)

  const subtitle = ref('Método: PagoMovil')

  function onTabChange(code: string) {
    // Aquí puedes hacer redirección, llamada a API o router.push(...)
    console.log('Tab seleccionado:', code)
  }
</script>
<template>
  <div class="card">
    <CardHeader :title="title" :subtitle="subtitle" />

    <div class="card-body">
      <CountryTabs
        class="mt-n2"
        :items="countries"
        v-model:activeCode="selectedCode"
        @tabSelected="onTabChange"
      />

      <DirectoryButtons
        :currencies="currencies"
        :usedCurrencies="usedCurrencies"
        :has-privileged-role="hasPrivilegedRole"
        :selected-currency="selectedCurrency"
        :updating-directorys="updatingDirectorys"
        @create="handleCreate"
        @historical="goHistorical"
        @updateDirectorys="handleUpdateDirectorys"
      />

      <DirectoryTable
        :columns="columns"
        @edit="handleEdit"
        @change-status="changeStatus"
        @delete="remove"
      />
    </div>
  </div>

  <Modal
    v-model="showModalForm"
    :title="modalTitle"
    static
    show-confirm
    :on-submit="handleSubmit"
    :confirm-disabled="!(directoryFormRef?.isValid ?? false)"
  >
    <DirectoryForm
      ref="directoryFormRef"
      v-model="exchangeRateData"
      :currencies="currencies"
      :usedCurrencies="usedCurrencies"
      :has-privileged-role="hasPrivilegedRole"
    />
  </Modal>
</template>
