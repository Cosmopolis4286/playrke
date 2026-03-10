<script setup lang="ts">
  import { ref, computed } from 'vue'

  import CardHeader from '@js/Components/Common/Card/CardHeader.vue'
  import CurrencyTable from '@js/Components/Currency/CurrencyTable.vue'
  import CurrencyForm from '@/js/Components/Currency/CurrencyForm.vue'
  import TableActionButtons from '@/js/Components/Common/Table/TableActionButtons.vue'
  import TableFooterInfo from '@/js/Components/Common/Table/TableFooterInfo.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'

  import { useCurrency } from '@/js/composables/currency/useCurrency'
  import { useModalForm } from '@/js/composables/common/useModalForm'

  import type { CurrencyPageProps } from '@js/types/currency'

  /**
   * Props Inertia
   */
  const props = defineProps<CurrencyPageProps>()

  /**
   * Determina si el usuario tiene privilegios para gestionar monedas
   */
  const hasPrivilegedRole = computed(() => ['company', 'customer', 'admin'].includes(props.roleUser))

  /**
   * Indica si es posible registrar una nueva moneda
   */
  const canCreate = computed(() => hasPrivilegedRole.value)

  /**
   * Composable de negocio para manejo de monedas
   */
  const {
    currencyData,
    currencies,
    setInitialCurrencies,
    create,
    edit,
    remove,
    submit
  } = useCurrency(props.tokenId)

  // Inicializar currencies con las props
  setInitialCurrencies(props.currencies)

  /**
   * IDs seleccionados en la tabla
   */
  const selectedIds = ref<string[]>([])

  /**
   * Composable para el modal y formulario
   */
  const {
    showModalForm,
    modalTitle,
    formRef: currencyFormRef,
    handleSubmit
  } = useModalForm(currencyData, submit, {
    defaultTitle: 'Moneda'
  })

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
   * @param id Identificador de la moneda a editar
   */
  async function handleEdit(id: string): Promise<void> {
    await edit(id)
    showModalForm.value = true
  }

  /**
   * Elimina las monedas seleccionadas
   */
  async function handleDelete(ids: string[]): Promise<void> {
    for (const id of ids) {
      await remove(id)
    }
    selectedIds.value = []
  }
</script>
<template>
  <div class="card">
    <CardHeader title="Monedas" subtitle="Gestión de monedas del sistema" />

    <div class="card-body">
      <TableActionButtons
        :selected-ids="selectedIds"
        :show-create-button="canCreate"
        show-edit-button
        show-delete-button
        @create="handleCreate"
        @edit="handleEdit"
        @delete="handleDelete"
      />

      <CurrencyTable
        :currencies="currencies"
        :has-privileged-role="hasPrivilegedRole"
        :selected-ids="selectedIds"
        @selection-change="ids => (selectedIds = ids)"
        @edit="handleEdit"
        @delete="(id) => handleDelete([id])"
      />

      <TableFooterInfo
        :filtered-count="currencies.length"
        :total-count="currencies.length"
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
    :confirm-disabled="!(currencyFormRef?.isValid ?? false)"
  >
    <CurrencyForm
      ref="currencyFormRef"
      v-model="currencyData"
      :currencies="currencies"
    />
  </Modal>
</template>
