<script setup lang="ts">
  /**
   * Página Admin → Usuarios
   *
   * Vista de todos los usuarios del sistema.
   * No tiene formulario de creación propio: el botón "Crear" abre un SweetAlert
   * para seleccionar el rol y redirige a la vista específica de ese rol.
   */

  import { computed } from 'vue'

  // ==========================================================================
  // Componentes
  // ==========================================================================

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { useCrudEventListener } from '@/js/composables/form/useCrudEventListener'
  import { useAdminUser } from '@/js/composables/roles/admin/user/useAdminUser'

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { AdminUserPageProps } from '@js/types/roles/admin/admin-user'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'
  import type { TableFilter, TableFilterOption } from '@js/types/common/table/table-filter'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================

  const props = defineProps<AdminUserPageProps>()

  // ==========================================================================
  // Lógica de negocio
  // ==========================================================================

  const {
    users,
    setInitialUsers,
    selectRoleAndRedirect,
    toggleStatusBulk,
    resetPassword,
    removeBulk,
  } = useAdminUser()

  setInitialUsers(props.users.rows)

  // ==========================================================================
  // CRUD global (eventos tabla)
  // ==========================================================================

  useCrudEventListener({
    // "Crear" abre SweetAlert de selección de rol → redirige
    create: () => {
      selectRoleAndRedirect(props.roles)
    },

    // Sin edición en esta vista — se gestiona en la vista específica del rol
    edit: () => {},

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
    props.countries.map(c => ({ value: c.name, label: c.name }))
  )

  const roleOptions = computed<TableFilterOption[]>(() =>
    props.roles.map(r => ({
      value: r.name.charAt(0).toUpperCase() + r.name.slice(1),
      label: r.name.charAt(0).toUpperCase() + r.name.slice(1)
    }))
  )

  const filters = computed<readonly TableFilter[]>(() => [
    {
      key: 'role_name',
      label: 'Rol',
      placeholder: 'Filtrar por rol',
      options: roleOptions.value
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
        { value: 'DELETED', label: 'Eliminado' },
        { value: 'SUSPENDED', label: 'Suspendido' },
        { value: 'PENDING_VERIFICATION', label: 'Pendiente' }
      ]
    }
  ])

  // ==========================================================================
  // Botones extra
  // ==========================================================================

  function isResetPasswordVisible(selectedIds: string[]): boolean {
    return selectedIds.length === 1
  }

  function isResetPasswordDisabled(selectedIds: string[]): boolean {
    const user = users.value.find(u => u.id === selectedIds[0])
    return user?.status !== 'ACTIVE'
  }

  function getResetPasswordDisabledReason(selectedIds: string[]): string {
    const user = users.value.find(u => u.id === selectedIds[0])
    if (user?.status !== 'ACTIVE') {
      return 'Solo se puede resetear la contraseña de usuarios activos'
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
    tableId="user-table"
    title="Usuarios"
    :columns="props.users.columns"
    :rows="users"
    :filters="filters"
    search-placeholder="Buscar por nombre, username, email..."
    showCreateButton
    showDeleteButton
    :extra-buttons="extraButtons"
  />
</template>
