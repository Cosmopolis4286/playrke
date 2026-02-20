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
  import type { DirectoryVENPageProps } from '@/js/types/directory/directory-ven'
  import type { Row } from '@/js/types/common/table/column-row'

  /* ==========================================================================
   * Props
   * ========================================================================== */

  const props = defineProps<DirectoryVENPageProps>()

  /* ==========================================================================
   * Lógica de negocio – Directorio
   * ========================================================================== */

  const {
    directoryData,
    directories,
    setInitialDirectories,
    create,
    edit,
    submit,
    remove,
    changeStatus,
    fetchContext
  } = useDirectory(props.tokenId)

  // Inicializar directories con las props
  setInitialDirectories(props.directories)

  /* ==========================================================================
   * Estado local – Bancos y Tipos de Documento (reactivos al cambiar de país)
   * ========================================================================== */

  const localBanks = ref<any[]>(props.banks ?? [])
  const localDocumentTypes = ref<any[]>(props.documentTypes ?? [])

  /* ==========================================================================
   * Configuración de tabla (UI)
   * ========================================================================== */

  const columns = computed(() => directories.value.columns)
  const rows = computed(() => directories.value.rows)

  /**
   * Filtros dinámicos basados en los bancos del país activo
   */
  const filters = computed(() => [
    {
      key: 'bank_name',
      label: 'Banco',
      options: localBanks.value.map((bank: any) => ({
        label: bank.name,
        value: bank.name
      }))
    }
  ])

  /* ==========================================================================
   * Table Controller
   * ========================================================================== */

  const { handleFilter, selectedIds, filteredRows } = useTableController<Row>({
    columns,
    rows
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
   * Obtener países únicos de las monedas disponibles.
   */
  const countries = computed(() => {
    if (props.currencies?.length) {
      const uniqueCountries = new Map()

      props.currencies.forEach((currency: any) => {
        if (currency.country) {
          uniqueCountries.set(currency.country.iso_code, {
            id: currency.country.id,
            iso_code_2: currency.country.iso_code,
            name: currency.country.name
          })
        }
      })

      return Array.from(uniqueCountries.values())
    }

    return [{ id: 1, iso_code_2: 'VE', name: 'Venezuela' }]
  })

  const selectedCode = ref<string>('VE')

  const selectedCountry = computed(() =>
    countries.value.find((c: any) => c.iso_code_2 === selectedCode.value)
  )

  const title = computed(() => `Directorio ${selectedCountry.value?.name || selectedCode.value}`)

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

  function handleCreate(): void {
    create()
    showModalForm.value = true
  }

  async function handleEdit(id: string): Promise<void> {
    await edit(id)
    showModalForm.value = true
  }

  async function handleToggleStatus(ids: string[]): Promise<void> {
    for (const id of ids) {
      await changeStatus(id)
    }
  }

  async function handleDelete(ids: string[]): Promise<void> {
    for (const id of ids) {
      await remove(id)
    }
  }

  /**
   * Cambio de país: recarga bancos, documentTypes y directorios desde el backend
   */
  async function onTabChange(code: string): Promise<void> {
    selectedCode.value = code
    const ctx = await fetchContext(code)
    if (ctx) {
      localBanks.value = ctx.banks ?? []
      localDocumentTypes.value = ctx.documentTypes ?? []
    }
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
        class="mt-3"
        :selected-ids="selectedIds"
        show-create-button
        show-edit-button
        show-delete-button
        @create="handleCreate"
        @edit="handleEdit"
        @delete="handleDelete"
      >
        <template #action-buttons="{ selectedIds: ids }">
          <button
            v-if="ids.length > 0"
            class="btn btn-sm btn-secondary"
            @click="handleToggleStatus(ids)"
            title="Alternar estado"
          >
            <i class="fas fa-exchange-alt"></i>
          </button>
        </template>
      </TableActionButtons>

      <BaseTable
        table-id="directory-table"
        :columns="columns"
        :rows="filteredRows"
        empty-message="No se encontraron registros"
        selectable
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
        :total-count="rows.length"
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
      :banks="localBanks"
      :documentTypes="localDocumentTypes"
      :countryCode="selectedCode"
    />
  </Modal>
</template>
