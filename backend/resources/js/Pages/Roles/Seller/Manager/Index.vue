<script setup lang="ts">
  /**
   * Página Manager → Vendedores
   */

  import { computed, onMounted, watch } from 'vue'

  // ==========================================================================
  // Componentes
  // ==========================================================================

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import ManagerSellerForm from '@/js/Components/Roles/Seller/Manager/ManagerSellerForm.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { useCrudEventListener } from '@/js/composables/form/useCrudEventListener'
  import { useModalForm } from '@/js/composables/common/useModalForm'
  import { useManagerSeller } from '@/js/composables/roles/seller/manager/useManagerSeller'

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { ManagerSellerPageProps } from '@/js/types/roles/seller/manager/manager-seller'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'
  import type { TableFilterInterface, TableFilterOption } from '@js/types/common/table/table-filter'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================

  const props = defineProps<ManagerSellerPageProps>()

  // ==========================================================================
  // Lógica de negocio
  // ==========================================================================

  const {
    sellerData,
    sellers,
    setInitialSellers,
    create,
    edit,
    toggleStatusBulk,
    resetPassword,
    removeBulk,
    submit
  } = useManagerSeller()

  setInitialSellers(props.sellers.rows)

  // ==========================================================================
  // Modal + Form handler
  // ==========================================================================

  const {
    showModalForm,
    modalTitle,
    formRef: managerSellerFormRef,
    handleSubmit
  } = useModalForm(sellerData, submit, {
    defaultTitle: 'Vendedor',
  })

  watch(showModalForm, isOpen => {
    if (!isOpen) {
      managerSellerFormRef.value?.reset?.()
      create()
    }
  })

  // Auto-abre el detalle de un vendedor al llegar desde el botón "Ver más"
  // de Admin > Usuarios (?open={sellerId} -> props.openId).
  onMounted(async () => {
    if (!props.openId) return
    const ok = await edit(props.openId)
    if (ok) showModalForm.value = true
  })

  // ==========================================================================
  // CRUD global (eventos tabla)
  // ==========================================================================

  useCrudEventListener({
    create: () => {
      create()
      showModalForm.value = true
    },

    edit: async ids => {
      if (!ids || ids.length !== 1) return

      const ok = await edit(ids[0])
      if (ok) {
        showModalForm.value = true
      }
    },

    delete: async ids => {
      if (!ids) return
      await removeBulk(ids)
    },

    toggleStatus: async ids => {
      if (!ids) return
      await toggleStatusBulk(ids)
    },
    resetPassword: async ids => {
      if (!ids || ids.length !== 1) return
      await resetPassword(ids[0])
    }
  })

  // ==========================================================================
  // Filtros
  // ==========================================================================

  const countryOptions = computed<TableFilterOption[]>(() =>
    props.countries.map(country => ({
      value: country.name,
      label: country.name
    }))
  )

  // Solo la vista admin trae manager_name (comercio/agencia padre) en cada
  // fila (ver ManagerSellerContextService::resolveSellers()) — derivado de
  // los rows ya cargados, mismo criterio "filtro client-side sobre datos
  // precargados" que el resto de filtros de esta tabla, sin fetch aparte.
  const managerOptions = computed<TableFilterOption[]>(() => {
    if (props.roleUser !== 'admin') return []
    const names = new Set(sellers.value.map(s => s.manager_name).filter((n): n is string => !!n))
    return [...names].sort().map(name => ({ value: name, label: name }))
  })

  const filters = computed<readonly TableFilterInterface[]>(() => [
    ...(props.roleUser === 'admin' ? [{
      key: 'manager_name',
      label: 'Comercio/Agencia',
      placeholder: 'Filtrar por comercio o agencia',
      options: managerOptions.value
    }] : []),
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

  // ==========================================================================
  // Botones extra
  // ==========================================================================

  /**
   * Verifica si el botón resetPassword debe mostrarse.
   * Solo visible cuando hay exactamente 1 elemento seleccionado.
   */
  function isResetPasswordVisible(selectedIds: string[]): boolean {
    return selectedIds.length === 1
  }

  /**
   * Verifica si el botón resetPassword debe estar deshabilitado.
   * Deshabilitado cuando el elemento seleccionado no está en status ACTIVE.
   */
  function isResetPasswordDisabled(selectedIds: string[]): boolean {
    const commerce = sellers.value.find(c => c.id === selectedIds[0])
    return commerce?.status !== 'ACTIVE'
  }

  /**
   * Retorna la razón por la cual resetPassword está deshabilitado.
   */
  function getResetPasswordDisabledReason(selectedIds: string[]): string {
    const commerce = sellers.value.find(c => c.id === selectedIds[0])
    if (commerce?.status !== 'ACTIVE') {
      return 'Solo se puede resetear la contraseña de comercializadoras activas'
    }
    return ''
  }

  const extraButtons: ExtraButton[] = [
    {
      icon: 'fas fa-exchange-alt',
      class: 'btn-secondary',
      show: false,
      action: 'toggleStatus'
    },
    {
      icon: 'fas fa-key',
      class: 'btn-dark',
      show: isResetPasswordVisible,
      action: 'resetPassword',
      disabled: isResetPasswordDisabled,
      disabledReason: getResetPasswordDisabledReason
    }
  ]
</script>

<template>
  <TableWithFilterBridge
    tableId="seller-table"
    title="Vendedores"
    :columns="props.sellers.columns"
    :rows="sellers"
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
    :confirm-disabled="!(managerSellerFormRef?.isValid ?? false)"
  >
    <ManagerSellerForm
      ref="managerSellerFormRef"
      v-model="sellerData"
      :countries="props.countries"
      :currencies="props.currencies"
      :time-zones="props.timeZones"
      :games="props.games"
      :role-user="props.roleUser"
    />
  </Modal>
</template>
