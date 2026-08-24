<script setup lang="ts">
  /* ==========================================================================
   * Imports
   * ========================================================================== */

  import { ref, watch, computed } from 'vue'

  // UI
  import CardHeader from '@js/Components/Common/Card/CardHeader.vue'
  import PageHeader from '@/js/Components/Common/PageHeader/PageHeader.vue'
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
  import type { DirectoryPageProps } from '@/js/types/directory/directory'
  import type { Currency } from '@/js/types/currency'

  /** `level` es agregado dinámicamente por CurrencyService::getAvailableCurrenciesWithLevel() (pivot). */
  type CurrencyWithLevel = Currency & { level?: number }

  /* ==========================================================================
   * Props
   * ========================================================================== */

  const props = defineProps<DirectoryPageProps>()

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

  const { handleFilter, selectedIds, filteredRows } = useTableController({
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
   * Obtener países únicos de las monedas disponibles, ordenados por level del pivot.
   * level=1 (moneda principal) aparece primero.
   */
  const countries = computed(() => {
    if (props.currencies?.length) {
      // Map: iso_code_2 → { country info, minLevel }
      const uniqueCountries = new Map<string, { id: string; iso_code_2: string; name: string; level: number }>()

      props.currencies.forEach(currency => {
        const country = currency.country
        if (!country?.iso_code_2) return

        const code = country.iso_code_2
        const level = (currency as CurrencyWithLevel).level ?? 99

        // Guardar solo el level más bajo (principal) por país
        if (!uniqueCountries.has(code) || level < uniqueCountries.get(code)!.level) {
          uniqueCountries.set(code, {
            id: country.id,
            iso_code_2: code,
            name: country.name,
            level
          })
        }
      })

      return Array.from(uniqueCountries.values())
        .sort((a, b) => a.level - b.level)
    }

    return [{ id: '1', iso_code_2: 'VE', name: 'Venezuela', level: 1 }]
  })

  // El tab inicial es el país con level=1 (o 'VE' como fallback)
  const defaultCode = props.currencies
    ?.slice()
    .sort((a, b) => ((a as CurrencyWithLevel).level ?? 99) - ((b as CurrencyWithLevel).level ?? 99))
    .find(c => c.country?.iso_code_2)
    ?.country?.iso_code_2 ?? 'VE'

  const selectedCode = ref<string>(defaultCode)

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
  <PageHeader
    icon="fa-address-book"
    title="Directorio de Pagos"
    subtitle="Datos bancarios y de pago disponibles por país."
    :breadcrumb-items="[
      { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
      { label: 'Directorio' },
    ]"
  />

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
        :has-extra-actions="selectedIds.length > 0"
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
