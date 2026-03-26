<script setup lang="ts" name="AdministratorTable">
  import { computed } from 'vue'

  // Components
  import TableWithFilterBridge from '@/js/Components/Common/Table/TableWithFilterBridge.vue'

  // Composables
  import { useAdminAdministrator } from '@/js/composables/roles/admin/administrator/useAdminAdministrator'

  // Types
  import type { AdminAdministratorPageProps } from '@/js/types/roles/admin/administrator-page'
  import type { ExtraButton } from '@/js/types/common/table/extra-buttons'

  // =========================================================================
  // Props (Inertia)
  // =========================================================================
  const props = defineProps<AdminAdministratorPageProps>()

  // =========================================================================
  // Lógica de negocio
  // =========================================================================
  const {
    administrators,
    loading,
    selectedIds,
    hasSelected,
    allSelected,
    fetchAdministrators,
    toggleStatus,
    resetPassword,
    remove,
    destroy,
    toggleSelection,
    selectAll
  } = useAdminAdministrator()

  // Inicializar datos desde props
  administrators.value = props.administrators.rows

  // =========================================================================
  // Configuración de tabla
  // =========================================================================
  const tableConfig = {
    columns: [
      { key: 'role_badge', label: 'Rol', sortable: false },
      { key: 'full_name', label: 'Nombre', sortable: true },
      { key: 'username', label: 'Username', sortable: true },
      { key: 'email', label: 'Email', sortable: true },
      { key: 'country_name', label: 'País', sortable: false },
      { key: 'status_badge', label: 'Estado', sortable: false }
    ],
    extraButtons: [
      {
        action: 'toggleStatus',
        class: 'btn-warning',
        icon: 'fas fa-exchange-alt',
        title: 'Cambiar Estado',
        show: (selectedIds: string[]) => selectedIds.length > 0
      },
      {
        action: 'resetPassword',
        class: 'btn-info',
        icon: 'fas fa-key',
        title: 'Restablecer Contraseña',
        show: (selectedIds: string[]) => selectedIds.length > 0
      },
      {
        action: 'delete',
        class: 'btn-danger',
        icon: 'fas fa-trash',
        title: 'Eliminar',
        show: (selectedIds: string[]) => selectedIds.length > 0
      },
      {
        action: 'destroy',
        class: 'btn-dark',
        icon: 'fas fa-trash-alt',
        title: 'Eliminar Permanentemente',
        show: (selectedIds: string[]) => selectedIds.length > 0
      }
    ]
  }
</script>

<template>
  <TableWithFilterBridge
    table-id="administrators-table"
    title="Administradores"
    :columns="tableConfig.columns"
    :rows="administrators"
    :extra-buttons="tableConfig.extraButtons"
    :loading="loading"
    @selection-change="ids => selectedIds = ids"
  />
</template>
