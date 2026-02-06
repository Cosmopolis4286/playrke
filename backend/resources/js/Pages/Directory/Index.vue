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

  /**
   * Props provenientes de Inertia
   */
  const props = defineProps<DirectoryVENPageProps>()

  /* ==========================================================================
   * Lógica de negocio – Directorio
   * ========================================================================== */

  const { directoryData, directories, setInitialDirectories, create, edit, submit, remove, changeStatus } =
    useDirectory(props.tokenId)

  // Inicializar directories con las props
  setInitialDirectories(props.directories)

  /* ==========================================================================
   * Configuración de tabla (UI)
   * ========================================================================== */

  /**
   * Filtros disponibles para la tabla
   * Nota: key debe coincidir con la columna en las rows (bank_name viene de Bank.name)
   */
  const filters = [
    {
      key: 'bank_name',
      label: 'Banco',
      options: props.banks.map(bank => ({
        label: bank.name,
        value: bank.name
      }))
    }
  ]

  /* ==========================================================================
   * Computed refs para tabla (requeridos por useTableController)
   * ========================================================================== */

  const columns = computed(() => directories.value.columns)
  const rows = computed(() => directories.value.rows)

  /* ==========================================================================
   * Table Controller
   * ========================================================================== */

  /**
   * Controller que maneja:
   * - filtros
   * - selección
   * - filas filtradas
   */
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
   * Si no se proveen currencies (backend no las envía actualmente),
   * se usa Venezuela como país por defecto.
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

    // Default: Venezuela (esta página es para Pago Móvil)
    return [{ id: 1, iso_code_2: 'VE', name: 'Venezuela' }]
  })

  /**
   * País seleccionado (ISO code) — por defecto VE
   */
  const selectedCode = ref<string>('VE')

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
  async function handleEdit(id: string): Promise<void> {
    await edit(id)
    showModalForm.value = true
  }

  /**
   * Cambiar estado del directorio
   */
  async function handleToggleStatus(ids: string[]): Promise<void> {
    for (const id of ids) {
      await changeStatus(id)
    }
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
      >
        <template #action-buttons="{ selectedIds: ids }">
          <button
            v-if="ids.length > 0"
            class="btn btn-sm btn-secondary ml-2"
            @click="handleToggleStatus(ids)"
            title="Cambiar estado"
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
      :banks="props.banks"
      :documentTypes="props.documentTypes"
    />
  </Modal>
</template>
