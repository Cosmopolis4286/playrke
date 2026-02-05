<script setup lang="ts">
  /* ==========================================================================
   * Imports
   * ========================================================================== */

  import { ref, watch, computed } from 'vue'

  // UI
  import CardHeader from '@js/Components/Common/Card/CardHeader.vue'
  import CountryTabs from '@/js/Components/Directory/CountryTabs.vue'
  import TableActionButtons from '@/js/Components/Common/Table/TableActionButtons.vue'
  import TableFilter from '@/js/Components/Common/Table/TableFilter.vue'
  import BaseTable from '@/js/Components/Common/Table/BaseTable.vue'
  import TableFooterInfo from '@/js/Components/Common/Table/TableFooterInfo.vue'
  import DirectoryForm from '@/js/Components/Directory/DirectoryForm.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'

  // Composables
  import { useDirectory } from '@/js/composables/directory/useDirectory'
  import { useTableController } from '@/js/composables/common/table/useTableController'
  import { useModalForm } from '@/js/composables/common/useModalForm'

  // Types
  import type { DirectoryVEN, DirectoryVENPageProps } from '@/js/types/directory/directory-ven'
  import type { Column } from '@/js/types/common/table/column-row'

  /* ==========================================================================
   * Props
   * ========================================================================== */

  /**
   * Props provenientes de Inertia
   */
  const props = defineProps<DirectoryVENPageProps>()

  /* ==========================================================================
   * Lógica de negocio – Directorio
   * ========================================================================== */

  const { directoryData, directories, setInitialDirectories, create, edit, submit, remove } =
    useDirectory(props.tokenId)

  // Inicializar directories con las props
  setInitialDirectories(props.directories)

  /* ==========================================================================
   * Configuración de tabla (UI)
   * ========================================================================== */

  /**
   * Filtros disponibles para la tabla
   */
  const filters = [
    {
      key: 'bank_id',
      label: 'Banco',
      options: props.banks.map(bank => ({
        label: bank.name,
        value: String(bank.id)
      }))
    }
  ]

  /* ==========================================================================
   * Table Controller
   * ========================================================================== */

  /**
   * Controller que maneja:
   * - filtros
   * - selección
   * - filas filtradas
   */
  const { handleFilter, selectedIds, filteredRows } = useTableController<DirectoryVEN>({
    columns: directories['columns'],
    rows: directories['rows']
  })

  /* ==========================================================================
   * Modal + Form handler
   * ========================================================================== */

  const {
    showModalForm,
    modalTitle,
    formRef: directoryFormRef,
    handleSubmit
  } = useModalForm(directoryData, submit, {
    defaultTitle: 'Directorio'
  })

  /**
   * Al cerrar el modal:
   * - resetea el formulario
   * - vuelve al modo creación
   */
  watch(showModalForm, isOpen => {
    if (!isOpen) {
      directoryFormRef.value?.reset?.()
      create()
    }
  })

  /* ==========================================================================
   * Estado local – Países
   * ========================================================================== */

  /**
   * Obtener países únicos de las monedas disponibles
   */
  const countries = computed(() => {
    const uniqueCountries = new Map()

    props.currencies?.forEach((currency: any) => {
      if (currency.country) {
        uniqueCountries.set(currency.country.iso_code, {
          id: currency.country.id,
          iso_code_2: currency.country.iso_code,
          name: currency.country.name
        })
      }
    })

    return Array.from(uniqueCountries.values())
  })

  /**
   * País seleccionado (ISO code)
   */
  const selectedCode = ref<string>('')

  /**
   * Inicializar país seleccionado
   */
  watch(
    countries,
    newCountries => {
      if (newCountries.length > 0 && !selectedCode.value) {
        const ve = newCountries.find((c: any) => c.iso_code_2 === 'VE')
        selectedCode.value = ve?.iso_code_2 ?? newCountries[0].iso_code_2
      }
    },
    { immediate: true }
  )

  /**
   * País seleccionado completo
   */
  const selectedCountry = computed(() =>
    countries.value.find((c: any) => c.iso_code_2 === selectedCode.value)
  )

  /**
   * Título dinámico
   */
  const title = computed(() => `Directorio ${selectedCountry.value?.name || selectedCode.value}`)

  /**
   * Subtítulo dinámico por método de pago
   */
  const subtitle = computed(() => {
    const paymentMethods: Record<string, string> = {
      VE: 'Pago Móvil',
      BR: 'PIX',
      CO: 'Nequi / Daviplata',
      US: 'Zelle'
    }

    return paymentMethods[selectedCode.value] ? `Método: ${paymentMethods[selectedCode.value]}` : ''
  })

  /* ==========================================================================
   * Handlers
   * ========================================================================== */

  /**
   * Abrir modal en modo creación
   */
  function handleCreate(): void {
    create()
    showModalForm.value = true
  }

  /**
   * Abrir modal en modo edición
   */
  async function handleEdit(id: string | number): Promise<void> {
    await edit(id)
    showModalForm.value = true
  }

  /**
   * Eliminar directorio
   */
  async function handleDelete(ids: string[]): Promise<void> {
    for (const id of ids) {
      await remove(id)
    }
  }

  /**
   * Cambio de país
   */
  function onTabChange(code: string): void {
    selectedCode.value = code
    // Aquí puedes filtrar por país o recargar desde API
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

      <TableFilter
        search-placeholder="Buscar en la tabla..."
        :filters="filters"
        :debounce-delay="300"
        @filter="handleFilter"
      />

      <TableActionButtons
        :selected-ids="selectedIds"
        show-create-button
        show-edit-button
        show-delete-button
        @create="handleCreate"
        @edit="handleEdit"
        @delete="handleDelete"
      />

      <BaseTable
        table-id="directory-table"
        :columns="columns"
        :rows="filteredRows"
        empty-message="No se encontraron registros"
        :selected-ids="selectedIds"
        @selection-change="ids => (selectedIds = ids)"
      >
        <template
          v-for="column in columns"
          :key="column.key"
          v-slot:[`cell-${column.key}`]="{ row, value }"
        >
          <slot :name="`cell-${column.key}`" :row="row" :value="value" />
        </template>
      </BaseTable>

      <TableFooterInfo
        :filtered-count="filteredRows.length"
        :total-count="directories.rows.length"
        :selected-count="selectedIds.length"
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
      v-model="directoryData"
      :banks="props.banks"
      :documentTypes="props.documentTypes"
    />
  </Modal>
</template>
