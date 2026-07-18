<script setup lang="ts">
  /**
   * Página Admin → Usuarios
   *
   * Vista de todos los usuarios del sistema.
   * No tiene formulario de creación propio: el botón "Crear" abre un SweetAlert
   * para seleccionar el rol y redirige a la vista específica de ese rol.
   */

  import { computed, ref } from 'vue'

  // ==========================================================================
  // Componentes
  // ==========================================================================

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import WalletLoadModal from '@js/Components/Common/Modal/WalletLoadModal.vue'

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
  import type { TableFilterInterface, TableFilterOption } from '@js/types/common/table/table-filter'

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
    fetchUsers,
    selectRoleAndRedirect,
    toggleStatusBulk,
    resetPassword,
    removeBulk,
    loadWalletBalance,
    getWalletBalance,
  } = useAdminUser()

  setInitialUsers(props.users.rows)

  // Estado del modal de carga de saldo
  const showWalletModal = ref(false)
  const selectedUserId = ref<string | number>('')
  const selectedUserName = ref('')
  const selectedUserBalance = ref<number | null>(null)
  const selectedUserCurrency = ref<any>(null)

  async function openWalletModal(userId: string | number, userName: string) {
    selectedUserId.value = userId
    selectedUserName.value = userName
    
    // Obtener balance del usuario
    const walletData = await getWalletBalance(userId)
    if (walletData) {
      selectedUserBalance.value = typeof walletData.balance === 'string' 
        ? parseFloat(walletData.balance) 
        : walletData.balance
      selectedUserCurrency.value = walletData.currency
    } else {
      selectedUserBalance.value = 0
      selectedUserCurrency.value = null
    }
    
    showWalletModal.value = true
  }

  function closeWalletModal() {
    showWalletModal.value = false
    selectedUserId.value = ''
    selectedUserName.value = ''
    selectedUserBalance.value = null
    selectedUserCurrency.value = null
  }

  function onWalletLoadSuccess() {
    // Refrescar el balance del usuario actual
    refreshUserBalance()
    // No cerrar el modal para que el usuario vea el balance actualizado
    // closeWalletModal()
    fetchUsers() // Refrescar la lista de usuarios
  }

  async function refreshUserBalance() {
    if (!selectedUserId.value) return
    
    // Obtener balance actualizado del usuario
    const walletData = await getWalletBalance(selectedUserId.value)
    if (walletData) {
      selectedUserBalance.value = typeof walletData.balance === 'string' 
        ? parseFloat(walletData.balance) 
        : walletData.balance
      selectedUserCurrency.value = walletData.currency
    }
  }

  // Variable para almacenar el usuario seleccionado
  const selectedUser = computed(() => {
    return users.value.find(u => u.id === selectedUserId.value)
  })

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

  const filters = computed<readonly TableFilterInterface[]>(() => [
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
    },
    {
      icon: 'fas fa-wallet',
      class: 'btn-success text-white',
      title: 'Cargar Saldo',
      show: isLoadWalletBalanceVisible,
      onClick: (ids: string[]) => {
        if (ids.length === 1) {
          const user = users.value.find(u => u.id === ids[0])
          if (user) {
            openWalletModal(user.id, user.full_name || user.username)
          }
        }
      }
    }
  ]

  function isLoadWalletBalanceVisible(selectedIds: string[]): boolean {
    return selectedIds.length === 1
  }
</script>

<template>
  <div class="ds-page">
    <div class="ds-inner">
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
    </div>
  </div>

  <!-- Modal para carga de saldo -->
  <WalletLoadModal
    :show="showWalletModal"
    :user-name="selectedUserName"
    :user-id="selectedUserId"
    :user-balance="selectedUserBalance"
    :user-currency="selectedUserCurrency?.iso_code"
    :user-currency-symbol="selectedUserCurrency?.symbol"
    @close="closeWalletModal"
    @success="onWalletLoadSuccess"
  />
</template>
