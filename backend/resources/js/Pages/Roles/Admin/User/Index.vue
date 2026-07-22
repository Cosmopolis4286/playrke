<script setup lang="ts">
  /**
   * Página Admin → Usuarios
   *
   * Vista de todos los usuarios del sistema.
   * El botón "Crear" abre un modal para seleccionar el rol y redirige a la
   * vista específica de ese rol; "Editar" abre el modal de edición de datos
   * básicos + rol del usuario seleccionado.
   */

  import { computed, ref } from 'vue'
  import { usePage } from '@inertiajs/vue3'

  // ==========================================================================
  // Componentes
  // ==========================================================================

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import WalletLoadModal from '@js/Components/Common/Modal/WalletLoadModal.vue'
  import UserPermissionsModal from '@js/Components/Common/Modal/UserPermissionsModal.vue'
  import UserEditForm from '@/js/Components/Roles/Admin/User/UserEditForm.vue'
  import UserCreateRoleForm from '@/js/Components/Roles/Admin/User/UserCreateRoleForm.vue'
  import XpGrantForm from '@/js/Components/Roles/Admin/User/XpGrantForm.vue'
  import type { XpGrantData } from '@/js/Components/Roles/Admin/User/XpGrantForm.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { useCrudEventListener } from '@/js/composables/form/useCrudEventListener'
  import { useModalForm } from '@/js/composables/common/useModalForm'
  import { useAdminUser } from '@/js/composables/roles/admin/user/useAdminUser'

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { AdminUserPageProps, AdminUserEditData } from '@js/types/roles/admin/admin-user'
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
    userEditData,
    setInitialUsers,
    fetchUsers,
    redirectToRoleCreation,
    editUser,
    updateUser,
    toggleStatusBulk,
    resetPassword,
    removeBulk,
    loadWalletBalance,
    getWalletBalance,
    grantXp,
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

  // Estado del modal de permisos específicos por usuario (solo superadmin)
  const page = usePage()
  const isSuperadmin = computed(() =>
    (page.props.auth as any)?.user?.userRoles?.some((r: any) => r.role?.prefix === 'superadmin') ?? false
  )
  const currentUserId = computed(() => String((page.props.auth as any)?.user?.id ?? ''))

  // Filas protegidas: cuentas superadmin (si el actor no lo es) y la propia
  // cuenta del actor — mismo criterio que el guard de AdminUserService, para
  // que la UI nunca ofrezca una acción que el servidor va a rechazar.
  const disabledRowIds = computed<string[]>(() =>
    users.value
      .filter(u => String(u.id) === currentUserId.value || (u.role_name === 'superadmin' && !isSuperadmin.value))
      .map(u => String(u.id))
  )

  function isProtectedSelection(ids: string[]): boolean {
    return ids.some(id => disabledRowIds.value.includes(String(id)))
  }

  function getProtectedSelectionReason(ids: string[]): string {
    if (ids.some(id => String(id) === currentUserId.value)) {
      return 'No puedes realizar esta acción sobre tu propio usuario'
    }
    if (ids.some(id => disabledRowIds.value.includes(String(id)))) {
      return 'No tienes permisos para modificar una cuenta Super Administrador'
    }
    return ''
  }

  const showPermissionsModal = ref(false)
  const permissionsUserId = ref<string | number>('')
  const permissionsUserName = ref('')

  function openPermissionsModal(userId: string | number, userName: string) {
    permissionsUserId.value = userId
    permissionsUserName.value = userName
    showPermissionsModal.value = true
  }

  function closePermissionsModal() {
    showPermissionsModal.value = false
    permissionsUserId.value = ''
    permissionsUserName.value = ''
  }

  // Modal de edición de usuario (Modal.vue + useModalForm, patrón estándar del proyecto)
  const {
    showModalForm: showEditModal,
    formRef: userEditFormRef,
    handleSubmit: handleEditSubmit,
  } = useModalForm(userEditData, updateUser, { defaultTitle: 'Usuario', createTitle: 'Editar Usuario', editTitle: 'Editar Usuario' })

  const editingUser = computed(() => users.value.find(u => u.id === userEditData.value.id) ?? null)
  const isEditingSelf = computed(() => !!userEditData.value.id && String(userEditData.value.id) === currentUserId.value)
  const isEditingTargetSuperadmin = computed(() => editingUser.value?.role_name === 'superadmin')

  function openEditModal(userId: string | number) {
    editUser(userId)
    showEditModal.value = true
  }

  // Modal de otorgar XP (Modal.vue + useModalForm) — solo aplica a usuarios
  // con rol Jugador (validado también en el backend vía AdminUserService::grantXp).
  const xpGrantTarget = ref<{ id?: string | number; name: string }>({ name: '' })
  const xpGrantForm = ref<XpGrantData & { id?: string | number }>({ amount: '', reason: '' })

  async function submitXpGrant(): Promise<boolean> {
    if (!xpGrantTarget.value.id) return false
    const amount = parseInt(xpGrantForm.value.amount, 10)
    return grantXp(xpGrantTarget.value.id, amount, xpGrantForm.value.reason || undefined)
  }

  const {
    showModalForm: showXpModal,
    formRef: xpGrantFormRef,
    handleSubmit: handleXpGrantSubmit,
  } = useModalForm(xpGrantForm, submitXpGrant, { defaultTitle: 'XP', createTitle: 'Otorgar XP', editTitle: 'Otorgar XP' })

  function openXpModal(userId: string | number, userName: string) {
    xpGrantTarget.value = { id: userId, name: userName }
    xpGrantForm.value = { amount: '', reason: '' }
    showXpModal.value = true
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

  // Modal de selección de rol para registrar usuario (Modal.vue + useModalForm)
  const roleSelection = ref<{ id?: string; role: string | null }>({ role: null })

  async function submitRoleSelection(): Promise<boolean> {
    if (!roleSelection.value.role) return false
    redirectToRoleCreation(roleSelection.value.role)
    return true
  }

  const {
    showModalForm: showCreateRoleModal,
    formRef: createRoleFormRef,
    handleSubmit: handleCreateRoleSubmit,
  } = useModalForm(roleSelection, submitRoleSelection, { defaultTitle: 'Usuario', createTitle: 'Registrar nuevo usuario', editTitle: 'Registrar nuevo usuario' })

  function onRoleSelected(roleName: string) {
    roleSelection.value = { role: roleName }
  }

  useCrudEventListener({
    // "Crear" abre el modal de selección de rol → redirige a la vista de creación
    create: () => {
      roleSelection.value = { role: null }
      showCreateRoleModal.value = true
    },

    edit: ids => {
      if (!ids || ids.length !== 1) return
      openEditModal(ids[0])
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
    },
    {
      icon: 'fas fa-user-shield',
      class: 'btn-info text-white',
      title: 'Permisos específicos',
      // Backend ya protege con UserPolicy::managePermissions (role:superadmin) —
      // ocultar el botón para otros roles evita un 403 innecesario en la UI.
      show: (ids: string[]) => ids.length === 1 && isSuperadmin.value,
      onClick: (ids: string[]) => {
        if (ids.length === 1) {
          const user = users.value.find(u => u.id === ids[0])
          if (user) {
            openPermissionsModal(user.id, user.full_name || user.username)
          }
        }
      }
    },
    {
      icon: 'fas fa-star',
      class: 'btn-warning text-white',
      title: 'Otorgar XP',
      // Solo aplica a usuarios con rol Jugador — el backend también lo exige
      // (AdminUserService::grantXp), ocultarlo aquí evita un 422 innecesario.
      show: (ids: string[]) => ids.length === 1 && users.value.find(u => u.id === ids[0])?.role_name === 'player',
      onClick: (ids: string[]) => {
        if (ids.length === 1) {
          const user = users.value.find(u => u.id === ids[0])
          if (user) {
            openXpModal(user.id, user.full_name || user.username)
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
  <TableWithFilterBridge
    tableId="user-table"
    title="Usuarios"
    :columns="props.users.columns"
    :rows="users"
    :filters="filters"
    search-placeholder="Buscar por nombre, username, email..."
    showCreateButton
    showEditButton
    showDeleteButton
    :disable-edit="isProtectedSelection"
    :disable-edit-reason="getProtectedSelectionReason"
    :disable-delete="isProtectedSelection"
    :disable-delete-reason="getProtectedSelectionReason"
    :disabled-row-ids="disabledRowIds"
    disabled-row-reason="Cuenta protegida: no puede modificarse desde aquí"
    :extra-buttons="extraButtons"
  />

  <!-- Modal de selección de rol para registrar usuario -->
  <Modal
    v-model="showCreateRoleModal"
    title="Registrar nuevo usuario"
    icon="fa-user-plus"
    static
    show-confirm
    confirm-text="Ir a registrar"
    :on-submit="handleCreateRoleSubmit"
    :confirm-disabled="!(createRoleFormRef?.isValid ?? false)"
  >
    <UserCreateRoleForm
      ref="createRoleFormRef"
      :roles="props.roles"
      @select="onRoleSelected"
    />
  </Modal>

  <!-- Modal de edición de usuario -->
  <Modal
    v-model="showEditModal"
    title="Editar Usuario"
    icon="fa-user-edit"
    static
    show-confirm
    :on-submit="handleEditSubmit"
    :confirm-disabled="!(userEditFormRef?.isValid ?? false)"
  >
    <UserEditForm
      ref="userEditFormRef"
      v-model="userEditData"
      :roles="props.roles"
      :countries="props.countries"
      :is-actor-superadmin="isSuperadmin"
      :is-target-superadmin="isEditingTargetSuperadmin"
      :is-self="isEditingSelf"
      @reset-password="() => editingUser && resetPassword(editingUser.id)"
    />
  </Modal>

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

  <!-- Modal para permisos específicos por usuario (solo superadmin) -->
  <UserPermissionsModal
    :show="showPermissionsModal"
    :user-id="permissionsUserId"
    :user-name="permissionsUserName"
    @close="closePermissionsModal"
    @success="closePermissionsModal"
  />

  <!-- Modal para otorgar XP -->
  <Modal
    v-model="showXpModal"
    title="Otorgar XP"
    icon="fa-star"
    static
    show-confirm
    confirm-text="Otorgar XP"
    :on-submit="handleXpGrantSubmit"
    :confirm-disabled="!(xpGrantFormRef?.isValid ?? false)"
  >
    <XpGrantForm
      ref="xpGrantFormRef"
      v-model="xpGrantForm"
      :user-name="xpGrantTarget.name"
    />
  </Modal>
</template>
