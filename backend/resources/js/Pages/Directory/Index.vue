<script setup lang="ts">
  /* ==========================================================================
   * Imports
   * ========================================================================== */

  import { ref, watch, computed } from 'vue'

  // UI
  import CardHeader from '@js/Components/Common/Card/CardHeader.vue'
  import CountryTabs from '@/js/Components/Directory/CountryTabs.vue'
  import TableActionButtons from '@/js/Components/Common/Table/TableActionButtons.vue'
  import BaseTable from '@/js/Components/Common/Table/BaseTable.vue'
  import TableFooterInfo from '@/js/Components/Common/Table/TableFooterInfo.vue'
  import DirectoryForm from '@/js/Components/Directory/DirectoryForm.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'

  // Composables
  import { useDirectory } from '@/js/composables/directory/useDirectory'
  import { useTableController } from '@/js/composables/common/table/useTableController'
  import { useModalForm } from '@/js/composables/common/useModalForm'

  // Types
  import type { DirectoryPageProps } from '@/js/types/directory'

  /* ==========================================================================
   * Props
   * ========================================================================== */

  /**
   * Props provenientes de Inertia
   */
  const props = defineProps<DirectoryPageProps>()

  /* ==========================================================================
   * Lógica de negocio
   * ========================================================================== */

  const { directoryData, directories, setInitialDirectories, create, edit, submit, remove } =
    useDirectory(props.tokenId)

  // Inicializar directories con las props
  setInitialDirectories(props.directories)

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
   * Estado local
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
   * País seleccionado - inicializado con el primer país disponible
   * Se actualiza reactivamente cuando cambian las monedas
   */
  const selectedCode = ref<string>('')

  /** IDs seleccionados */
  const selectedIds = ref<string[]>([])

  /**
   * Watcher para inicializar selectedCode cuando countries cambie
   */
  watch(
    countries,
    newCountries => {
      if (newCountries.length > 0 && !selectedCode.value) {
        // Intentar obtener VE primero
        const veCountry = newCountries.find((c: any) => c.iso_code_2 === 'VE')
        if (veCountry) {
          selectedCode.value = veCountry.iso_code_2
        } else {
          // Si no existe VE, usar el primer país
          selectedCode.value = newCountries[0]?.iso_code_2 || ''
        }
      }
    },
    { immediate: true }
  )

  /**
   * País seleccionado completo
   */
  const selectedCountry = computed(() => {
    return countries.value.find((c: any) => c.iso_code_2 === selectedCode.value)
  })

  /**
   * Título dinámico basado en el país seleccionado
   */
  const title = computed(() => {
    return `Directorio ${selectedCountry.value?.name || selectedCode.value}`
  })

  /**
   * Subtítulo dinámico basado en el método de pago específico del país
   */
  const subtitle = computed(() => {
    // Mapeo de países a sus métodos de pago específicos
    const paymentMethods: Record<string, string> = {
      VE: 'Pago Móvil',
      BR: 'PIX',
      CO: 'Nequi / Daviplata',
      US: 'Zelle'
    }

    const method = paymentMethods[selectedCode.value]
    return method ? `Método: ${method}` : ''
  })

  /* ==========================================================================
   * Watchers
   * ========================================================================== */

  /* ==========================================================================
   * Handlers
   * ========================================================================== */

  /**
   * Abre el modal en modo creación
   */
  function handleCreate(): void {
    create()
    showModalForm.value = true
  }

  /**
   * Abre el modal en modo edición
   *
   * @param id Identificador del registro
   */
  async function handleEdit(id: string | number): Promise<void> {
    await edit(id)
    showModalForm.value = true
  }

  /**
   * Maneja la eliminación de un directorio
   *
   * @param id Identificador del registro
   */
  async function handleRemove(id: string | number): Promise<void> {
    await remove(id)
  }

  /**
   * Maneja el cambio de tab por país
   *
   * @param code ISO code del país
   */
  function onTabChange(code: string): void {
    selectedCode.value = code

    // Aquí puedes:
    // - filtrar data por país
    // - hacer router.push
    // - llamar API para cargar directorios específicos del país
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
        :show-create-button="true"
        :show-edit-button="true"
        :show-delete-button="true"
        @create="$emit('create')"
        @edit="$emit('edit', $event)"
        @delete="$emit('delete', $event)"
      >
        <template #action-buttons="{ selectedIds }">
          <slot name="action-buttons" :selectedIds="selectedIds" />
        </template>
      </TableActionButtons>

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
