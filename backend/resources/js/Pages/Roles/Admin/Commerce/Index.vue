<script setup lang="ts">
  /**
   * Página Admin → Comercializadoras
   *
   * Responsabilidades:
   * - Renderizar tabla con filtros y acciones CRUD
   * - Delegar acciones CRUD a eventos globales (`table:crud`)
   * - Manejar modal de creación / edición
   */

  /* ========================================================================== */
  /* Imports                                                                    */
  /* ========================================================================== */

  import { computed } from 'vue'
  // Componentes
  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import AdminCommerceForm from '@/js/Components/Roles/Admin/Commerce/AdminCommerceForm.vue'
  // Composables
  import { useCrudEventListener } from '@/js/composables/form/useCrudEventListener'
  import { useModalForm } from '@/js/composables/common/useModalForm'
  import { useAdminCommerce } from '@/js/composables/roles/admin/commerce/useAdminCommerce'
  // Types
  import type { AdminCommercePageProps } from '@js/types/roles/admin/admin-commerce'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'
  import type { TableFilter, TableFilterOption } from '@js/types/common/table/table-filter'

  /* ========================================================================== */
  /* Props (Inertia)                                                            */
  /* ========================================================================== */

  /**
   * Props tipadas recibidas desde Inertia
   */
  const props = defineProps<AdminCommercePageProps>()

  /* ========================================================================== */
  /* Composable de negocio                                                      */
  /* ========================================================================== */

  /**
   * Lógica de negocio para tasas de cambio / comercializadoras
   */
  const {
    commerceData,
    commerces,
    setInitialCommerces,
    create,
    edit,
    changeStatus,
    remove,
    submit
  } = useAdminCommerce()

  // Inicializar commerces con las props
  setInitialCommerces(props.commerces)

  /* ========================================================================== */
  /* Inicialización CRUD global                                                 */
  /* ========================================================================== */

  /**
   * Mapea acciones `table:crud` a funciones del composable useAdminCommerce
   */
  useCrudEventListener({
    create: () => {
      create()
      showModalForm.value = true
    },
    edit: async ids => {
      if (!ids || ids.length !== 1) return
      await edit(ids[0])
      showModalForm.value = true
    },
    delete: async ids => {
      if (!ids) return
      for (const id of ids) {
        await remove(id)
      }
    },
    changeStatus: async ids => {
      if (!ids) return
      for (const id of ids) {
        await changeStatus(id)
      }
    }
  })

  /* ========================================================================== */
  /* Filtros de tabla                                                           */
  /* ========================================================================== */

  /**
   * Opciones de países normalizadas
   * ⚠️ Se expone como array plano (NO ComputedRef en filters.options)
   */
  const countryOptions = computed<TableFilterOption[]>(() =>
    props.countries.map(country => ({
      value: country.name,
      label: country.name
    }))
  )

  /**
   * Definición de filtros de la tabla
   */
  const filters = computed<readonly TableFilter[]>(() => [
    {
      key: 'country_name',
      label: 'País',
      placeholder: 'Filtrar por país',
      options: countryOptions.value
    },
    {
      key: 'status',
      label: 'Estado',
      placeholder: 'Filtrar por estado',
      options: [
        { value: 'ACTIVE', label: 'Activo' },
        { value: 'INACTIVE', label: 'Inactivo' },
        { value: 'DELETED', label: 'Eliminado' }
      ]
    }
  ])

  /* ========================================================================== */
  /* Botones extra (acciones globales)                                         */
  /* ========================================================================== */

  /**
   * Botones de acciones adicionales.
   * Las acciones se delegan vía eventos `table:crud`
   */
  const extraButtons: ExtraButton[] = [
    {
      label: '',
      icon: 'fas fa-exchange-alt',
      class: 'btn-secondary',
      show: false,
      action: 'changeStatus'
    },
    {
      label: '',
      icon: 'fas fa-key',
      class: 'btn-dark',
      show: false,
      action: 'resetPassword'
    }
  ]

  /* ========================================================================== */
  /* Modal y formulario                                                        */
  /* ========================================================================== */

  /**
   * Manejo del modal de creación / edición
   */
  const {
    showModalForm,
    modalTitle,
    formMode,
    formRef: adminCommerceFormRef,
    handleSubmit
  } = useModalForm(commerceData, submit)
</script>

<template>
  <TableWithFilterBridge
    tableId="commerce-table"
    title="Comercializadoras"
    :columns="columns"
    :rows="commerces"
    :filters="filters"
    search-placeholder="Buscar por nombre, email, teléfono..."
    showCreateButton
    showEditButton
    showDeleteButton
    :extra-buttons="extraButtons"
  />

  <Modal
    v-model="showModalForm"
    :title="modalTitle"
    static
    show-confirm
    :on-submit="handleSubmit"
    :confirm-disabled="!(adminCommerceFormRef?.isValid ?? false)"
  >
    <AdminCommerceForm
      ref="adminCommerceFormRef"
      v-model="commerceData"
      :currencies="currencies"
      :mode="formMode"
    />
  </Modal>
</template>
