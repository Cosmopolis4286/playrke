<script setup lang="ts">
  /**
   * Página Admin → Sucursales
   */

  import { computed, onMounted, watch } from 'vue'

  // ==========================================================================
  // Componentes
  // ==========================================================================

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import PageHeader from '@/js/Components/Common/PageHeader/PageHeader.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import AdminSubsidiaryForm from '@/js/Components/Roles/Admin/Subsidiary/AdminSubsidiaryForm.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { useCrudEventListener } from '@/js/composables/form/useCrudEventListener'
  import { useModalForm } from '@/js/composables/common/useModalForm'
  import { useAdminSubsidiary } from '@/js/composables/roles/admin/subsidiary/useAdminSubsidiary'

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { AdminSubsidiaryPageProps } from '@/js/types/roles/admin/admin-subsidiary'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'
  import type { TableFilterInterface, TableFilterOption } from '@js/types/common/table/table-filter'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================

  const props = defineProps<AdminSubsidiaryPageProps>()

  // ==========================================================================
  // Lógica de negocio
  // ==========================================================================

  const {
    subsidiaryData,
    subsidiaries,
    setInitialSubsidiaries,
    create,
    edit,
    toggleStatusBulk,
    resetPassword,
    removeBulk,
    submit
  } = useAdminSubsidiary()

  setInitialSubsidiaries(props.subsidiaries.rows)

  // ==========================================================================
  // Modal + Form handler
  // ==========================================================================

  const {
    showModalForm,
    modalTitle,
    formRef: adminSubsidiaryFormRef,
    handleSubmit
  } = useModalForm(subsidiaryData, submit, {
    defaultTitle: 'Sucursal'
  })

  watch(showModalForm, isOpen => {
    if (!isOpen) {
      adminSubsidiaryFormRef.value?.reset?.()
      create()
    }
  })

  // Auto-abre el detalle de una sucursal al llegar desde el botón "Ver más"
  // de Admin > Usuarios (?open={subsidiaryId} -> props.openId).
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

  const commerceOptions = computed<TableFilterOption[]>(() =>
    props.commerces.map(commerce => ({
      value: commerce.name,
      label: commerce.name
    }))
  )

  const filters = computed<readonly TableFilterInterface[]>(() => [
    {
      key: 'commerce_name',
      label: 'Comercio',
      placeholder: 'Filtrar por comercio',
      options: commerceOptions.value
    },
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
    const subsidiary = subsidiaries.value.find(c => c.id === selectedIds[0])
    return subsidiary?.status !== 'ACTIVE'
  }

  /**
   * Retorna la razón por la cual resetPassword está deshabilitado.
   */
  function getResetPasswordDisabledReason(selectedIds: string[]): string {
    const subsidiary = subsidiaries.value.find(c => c.id === selectedIds[0])
    if (subsidiary?.status !== 'ACTIVE') {
      return 'Solo se puede resetear la contraseña de sucursales activas'
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
  <PageHeader
    icon="fa-code-branch"
    title="Sucursales"
    subtitle="Administra las agencias/sucursales de las comercializadoras."
    :breadcrumb-items="[
      { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
      { label: 'Sucursales' },
    ]"
  />

  <TableWithFilterBridge
    tableId="subsidiary-table"
    title="Sucursales"
    :columns="props.subsidiaries.columns"
    :rows="subsidiaries"
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
    :confirm-disabled="!(adminSubsidiaryFormRef?.isValid ?? false)"
  >
    <AdminSubsidiaryForm
      ref="adminSubsidiaryFormRef"
      v-model="subsidiaryData"
      :commerces="props.commerces"
      :countries="props.countries"
      :currencies="props.currencies"
    />
  </Modal>
</template>
