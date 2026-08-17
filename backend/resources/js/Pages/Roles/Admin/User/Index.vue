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
  import { router, usePage } from '@inertiajs/vue3'

  // ==========================================================================
  // Componentes
  // ==========================================================================

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import StatTile from '@/js/Components/Common/StatTile/StatTile.vue'
  import Breadcrumb from '@/js/Components/Common/Breadcrumb/Breadcrumb.vue'
  import Button from '@/js/Components/Common/Button/Button.vue'
  import RelativeTime from '@/js/Components/Common/RelativeTime/RelativeTime.vue'
  import RoleManagerModal from '@/js/Components/Roles/Admin/RoleManagerModal.vue'
  import RoleHierarchyTree from '@/js/Components/Roles/Admin/RoleHierarchyTree.vue'
  import UserEditForm from '@/js/Components/Roles/Admin/User/UserEditForm.vue'
  import UserCreateRoleForm from '@/js/Components/Roles/Admin/User/UserCreateRoleForm.vue'
  import XpGrantForm from '@/js/Components/Roles/Admin/User/XpGrantForm.vue'
  import type { XpGrantData } from '@/js/Components/Roles/Admin/User/XpGrantForm.vue'
  import WalletLoadForm from '@/js/Components/Roles/Admin/User/WalletLoadForm.vue'
  import type { WalletLoadData } from '@/js/Components/Roles/Admin/User/WalletLoadForm.vue'
  import UserPermissionsForm from '@/js/Components/Roles/Admin/User/UserPermissionsForm.vue'
  import UserDetailModal from '@/js/Components/Roles/Admin/User/UserDetailModal.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { useCrudEventListener } from '@/js/composables/form/useCrudEventListener'
  import { useModalForm } from '@/js/composables/common/useModalForm'
  import { useAdminUser } from '@/js/composables/roles/admin/user/useAdminUser'
  import { useAdminUserPermissions } from '@/js/composables/roles/admin/user/useAdminUserPermissions'
  import { useAdminUserDetail } from '@/js/composables/roles/admin/user/useAdminUserDetail'
  import { useRoleHierarchy } from '@/js/composables/roles/admin/role/useRoleHierarchy'
  import type { CatalogPermission } from '@/js/composables/roles/admin/user/useAdminUserPermissions'

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { AdminUserPageProps, AdminUserEditData } from '@js/types/roles/admin/admin-user'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'
  import type { TableFilterInterface, TableFilterOption } from '@js/types/common/table/table-filter'
  import type { RoleMetaUpdate } from '@js/types/roles/admin/role'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================

  const props = defineProps<AdminUserPageProps>()

  // ==========================================================================
  // Lógica de negocio
  // ==========================================================================

  const {
    users,
    pagination,
    loading,
    lastQuery,
    userEditData,
    setInitialUsers,
    fetchUsers,
    onQueryChange,
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

  setInitialUsers(props.users.rows, props.users_pagination)

  // Modal de carga de saldo (Modal.vue + useModalForm) — solo aplica a
  // usuarios con rol Comercio, Vendedor o Jugador (validado también en el
  // backend vía AdminUserService::loadWalletBalance).
  const selectedUserId = ref<string | number>('')
  const selectedUserName = ref('')
  const selectedUserBalance = ref<number | null>(null)
  const selectedUserCurrency = ref<any>(null)
  const walletLoadForm = ref<WalletLoadData & { id?: string | number }>({ amount: '', reason: '' })

  async function submitWalletLoad(): Promise<boolean> {
    if (!selectedUserId.value) return false
    const amount = parseFloat(walletLoadForm.value.amount)
    const ok = await loadWalletBalance(selectedUserId.value, amount, walletLoadForm.value.reason || undefined)
    if (ok) await refreshUserBalance()
    return ok
  }

  const {
    showModalForm: showWalletModal,
    formRef: walletLoadFormRef,
    handleSubmit: handleWalletLoadSubmit,
  } = useModalForm(walletLoadForm, submitWalletLoad, { defaultTitle: 'Saldo', createTitle: 'Cargar Saldo', editTitle: 'Cargar Saldo' })

  async function openWalletModal(userId: string | number, userName: string) {
    selectedUserId.value = userId
    selectedUserName.value = userName
    walletLoadForm.value = { amount: '', reason: '' }

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

  // Estado del modal de permisos específicos por usuario (solo superadmin)
  // Se compara por role.name (minúscula, ej. 'superadmin'), NO por
  // role.prefix — prefix se guarda en MAYÚSCULA ('SUPERADMIN') a nivel de
  // datos, así que compararlo contra un literal en minúscula nunca hacía match.
  const page = usePage()
  const isSuperadmin = computed(() =>
    (page.props.auth as any)?.user?.userRoles?.some((r: any) => r.role?.name?.toLowerCase() === 'superadmin') ?? false
  )
  const currentUserId = computed(() => String((page.props.auth as any)?.user?.id ?? ''))

  // Modal "Gestionar roles" (RoleManagerModal.vue) — mismo componente que usa
  // /permissions, para editar descripción/estado de roles desde el listado
  // de usuarios sin tener que navegar a la matriz de permisos.
  const showRoleManager = ref(false)

  // Tabs "Listado" / "Jerarquía de Roles" — mismo patrón y mismo componente
  // (RoleHierarchyTree.vue) que usa /admin/permissions, para no navegar fuera
  // de /admin/users solo para consultar el organigrama de roles.
  const activeTab = ref<'list' | 'hierarchy'>('list')
  const { tree: hierarchyTree, loading: hierarchyLoading, fetchHierarchy, updateRoleMeta } = useRoleHierarchy()

  let hierarchyLoaded = false
  function onHierarchyTabClick() {
    activeTab.value = 'hierarchy'
    if (!hierarchyLoaded) {
      hierarchyLoaded = true
      fetchHierarchy()
    }
  }

  async function handleHierarchySave(roleId: string, attributes: RoleMetaUpdate) {
    await updateRoleMeta(roleId, attributes)
  }

  const breadcrumbItems = [
    { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
    { label: 'Usuarios' },
  ]

  // Filas protegidas: cuentas superadmin (si el actor no lo es), cuentas
  // admin (si el actor no es superadmin — un admin no puede modificar a
  // OTRO admin) y la propia cuenta del actor — mismo criterio que el guard
  // de AdminUserService::guardMutation(), para que la UI nunca ofrezca una
  // acción que el servidor va a rechazar.
  const disabledRowIds = computed<string[]>(() =>
    users.value
      .filter(u =>
        String(u.id) === currentUserId.value ||
        (u.role_name === 'superadmin' && !isSuperadmin.value) ||
        (u.role_name === 'admin' && !isSuperadmin.value)
      )
      .map(u => String(u.id))
  )

  function isProtectedSelection(ids: string[]): boolean {
    return ids.some(id => disabledRowIds.value.includes(String(id)))
  }

  function getProtectedSelectionReason(ids: string[]): string {
    if (ids.some(id => String(id) === currentUserId.value)) {
      return 'No puedes realizar esta acción sobre tu propio usuario'
    }
    if (ids.some(id => users.value.find(u => u.id === id)?.role_name === 'superadmin')) {
      return 'No tienes permisos para modificar una cuenta Super Administrador'
    }
    if (ids.some(id => users.value.find(u => u.id === id)?.role_name === 'admin')) {
      return 'Solo un Super Administrador puede modificar la cuenta de otro Administrador'
    }
    return ''
  }

  // Modal de permisos específicos por usuario (Modal.vue + useModalForm,
  // solo superadmin — protegido también en el backend vía
  // UserPolicy::managePermissions + middleware role:superadmin).
  const { fetchPermissions, savePermissions } = useAdminUserPermissions()

  const permissionsUserId = ref<string | number>('')
  const permissionsUserName = ref('')
  const permissionsCatalog = ref<CatalogPermission[]>([])
  const permissionsViaRoles = ref<string[]>([])
  const permissionsForm = ref<{ id?: string | number; specific: string[]; reason: string }>({ specific: [], reason: '' })
  const permissionsLoading = ref(false)

  async function submitPermissions(): Promise<boolean> {
    if (!permissionsUserId.value) return false
    return savePermissions(permissionsUserId.value, permissionsForm.value.specific, permissionsForm.value.reason)
  }

  const {
    showModalForm: showPermissionsModal,
    formRef: permissionsFormRef,
    handleSubmit: handlePermissionsSubmit,
  } = useModalForm(permissionsForm, submitPermissions, { defaultTitle: 'Permisos', createTitle: 'Permisos específicos', editTitle: 'Permisos específicos' })

  async function openPermissionsModal(userId: string | number, userName: string) {
    permissionsUserId.value = userId
    permissionsUserName.value = userName
    permissionsForm.value = { specific: [], reason: '' }
    permissionsCatalog.value = []
    permissionsViaRoles.value = []

    permissionsLoading.value = true
    const data = await fetchPermissions(userId, userName)
    permissionsLoading.value = false

    if (data) {
      permissionsCatalog.value = data.catalog
      permissionsViaRoles.value = data.viaRoles
      permissionsForm.value = { id: userId, specific: data.specific, reason: '' }
    }

    showPermissionsModal.value = true
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
  // Un admin no puede editar a OTRO admin (solo superadmin puede) — el
  // backend refuerza esto en AdminUserService::guardMutation(); ocultar/
  // bloquear aquí evita que la UI ofrezca una acción que el servidor rechazará.
  const isEditingTargetAdmin = computed(() => editingUser.value?.role_name === 'admin')

  /**
   * Botón "Ver más" del modal de edición: navega a la página dedicada del
   * rol (ej. /admin/commerces) pasando ?open={domainId} para que esa página
   * auto-abra la ficha del usuario al montar (ver onMounted en cada
   * Roles/Admin/{Rol}/Index.vue). domainId es null para roles sin auto-open
   * (ej. 'admin' -> /admins, que no tiene modal de edición) — ahí es pura
   * navegación, sin query param.
   */
  function handleViewRoleDetail(route: string, domainId: string | number | null) {
    router.visit(route, domainId ? { data: { open: domainId } } : {})
  }

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

  // Modal de ficha de usuario (datos básicos + wallet + nivel + actividad reciente)
  const showUserDetailModal = ref(false)
  const { loading: userDetailLoading, detail: userDetail, fetchDetail: fetchUserDetail, clearDetail: clearUserDetail } = useAdminUserDetail()

  async function openUserDetail(rowId: string) {
    const user = users.value.find(u => u.id === rowId)
    if (!user?.real_id) return
    showUserDetailModal.value = true
    await fetchUserDetail(user.real_id)
  }

  function handleUserDetailVisibility(open: boolean) {
    showUserDetailModal.value = open
    if (!open) clearUserDetail()
  }

  function handleViewFullHistory(userRealId: string) {
    showUserDetailModal.value = false
    clearUserDetail()
    router.visit('/audit', {
      data: { entity: 'App\\Models\\User', auditable_id: String(userRealId) },
    })
  }

  /** Exporta a CSV los usuarios que coincidan con los filtros/búsqueda activos en la tabla. */
  function handleExport() {
    const params = new URLSearchParams()
    if (lastQuery.value.search) params.set('search', lastQuery.value.search)
    if (lastQuery.value.filters.role_name) params.set('role_name', lastQuery.value.filters.role_name)
    if (lastQuery.value.filters.country_name) params.set('country_name', lastQuery.value.filters.country_name)
    if (lastQuery.value.filters.status) params.set('status', lastQuery.value.filters.status)
    if (lastQuery.value.filters.has_specific_permissions) params.set('has_specific_permissions', lastQuery.value.filters.has_specific_permissions)
    window.location.href = `/admin/user/export?${params.toString()}`
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
    },
    {
      key: 'has_specific_permissions',
      label: 'Permisos individuales',
      placeholder: 'Filtrar por permisos',
      options: [
        { value: 'true', label: 'Con permisos individuales' },
        { value: 'false', label: 'Sin permisos individuales' }
      ]
    }
  ])

  // ==========================================================================
  // Estadísticas del header (derivadas de datos ya presentes, sin llamadas nuevas)
  // ==========================================================================

  // Los totales vienen de pagination (todo el catálogo), no de users.value
  // (solo la página cargada) — antes "Usuarios totales/activos" reflejaban
  // únicamente los 15 registros en memoria, una métrica engañosa que no
  // representaba el sistema real.
  const stats = computed(() => ({
    total: pagination.value?.total ?? users.value.length,
    active: users.value.filter(u => u.status === 'ACTIVE').length,
    withSpecificPermissions: users.value.filter(u => u.has_specific_permissions).length,
    inactiveRolesAvailable: props.roles.length,
  }))

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
      // Solo aplica a usuarios con rol Comercio, Vendedor o Jugador — el
      // backend también lo exige (AdminUserService::loadWalletBalance),
      // ocultarlo aquí evita un 422 innecesario.
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
      label: 'Permisos individuales',
      class: 'btn-info text-white admin-user-btn--permissions',
      title: 'Otorgar o revocar permisos específicos a este usuario, además de los que ya tiene por su rol',
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
      icon: 'fas fa-id-card',
      class: 'btn-outline-secondary',
      title: 'Ver ficha del usuario',
      show: (ids: string[]) => ids.length === 1,
      onClick: (ids: string[]) => {
        if (ids.length === 1) {
          openUserDetail(ids[0])
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

  const WALLET_LOAD_ELIGIBLE_ROLES = ['commerce', 'seller', 'player']

  function isLoadWalletBalanceVisible(selectedIds: string[]): boolean {
    if (selectedIds.length !== 1) return false
    const user = users.value.find(u => u.id === selectedIds[0])
    return !!user && WALLET_LOAD_ELIGIBLE_ROLES.includes(user.role_name)
  }
</script>

<template>
  <div class="content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-6">
          <h1 class="m-0 font-weight-bold">Usuarios</h1>
          <p class="text-muted mb-0 mt-1">
            <i class="fas fa-users text-primary mr-1"></i>
            Administra las cuentas del sistema: rol, estado, saldo y permisos individuales.
          </p>
        </div>
        <div class="col-sm-6 d-flex flex-column align-items-sm-end">
          <Breadcrumb :items="breadcrumbItems" />
          <Button
            v-if="isSuperadmin"
            class="mt-2"
            variant="primary"
            outline
            icon="fa-user-shield"
            @click="showRoleManager = true"
          >
            Gestionar roles
          </Button>
        </div>
      </div>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <ul class="nav nav-tabs section-tabs">
        <li class="nav-item">
          <button class="nav-link" :class="{ active: activeTab === 'list' }" @click="activeTab = 'list'">
            <i class="fas fa-table mr-2"></i>Listado
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link" :class="{ active: activeTab === 'hierarchy' }" @click="onHierarchyTabClick">
            <i class="fas fa-sitemap mr-2"></i>Jerarquía de Roles
          </button>
        </li>
      </ul>

      <div v-show="activeTab === 'list'">
        <div class="admin-users-stats">
          <StatTile label="Usuarios totales" :value="stats.total" icon="fa-users" />
          <StatTile label="Activos (esta página)" :value="stats.active" icon="fa-user-check" variant="success" />
          <StatTile label="Con permisos individuales (esta página)" :value="stats.withSpecificPermissions" icon="fa-user-shield" variant="info" />
          <StatTile label="Roles disponibles" :value="stats.inactiveRolesAvailable" icon="fa-user-tag" />
        </div>

        <TableWithFilterBridge
          tableId="user-table"
          title="Usuarios"
          :columns="props.users.columns"
          :rows="users"
          :filters="filters"
          search-placeholder="Buscar por nombre, username, email..."
          :loading="loading"
          server-side
          :server-total-pages="pagination?.last_page ?? 1"
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
          row-vertical-align="middle"
          @query-change="onQueryChange"
        >
          <template #cell-full_name="{ row }">
            <div class="admin-user-cell">
              <div class="admin-user-cell__top">
                <span class="admin-user-cell__name">{{ row.full_name }}</span>
                <span v-html="row.role_badge"></span>
              </div>
              <span class="admin-user-cell__meta">
                <i class="fas fa-at"></i>{{ row.username }}
                <span class="admin-user-cell__sep">·</span>
                <i class="fas fa-envelope"></i>{{ row.email }}
                <template v-if="row.phone_number">
                  <span class="admin-user-cell__sep">·</span>
                  <i class="fas fa-phone-alt"></i>{{ row.phone_number }}
                </template>
              </span>
            </div>
          </template>

          <template #cell-permissions_badge="{ row }">
            <span v-html="row.permissions_badge"></span>
          </template>

          <template #cell-registered_at="{ row }">
            <RelativeTime :value="row.registered_at" />
          </template>

          <template #action-buttons>
            <button class="btn btn-sm btn-outline-secondary" title="Exportar usuarios filtrados a CSV" @click="handleExport">
              <i class="fas fa-file-csv mr-1"></i>Exportar
            </button>
          </template>
        </TableWithFilterBridge>
      </div>

      <div v-show="activeTab === 'hierarchy'">
        <RoleHierarchyTree
          :tree="hierarchyTree"
          :loading="hierarchyLoading"
          :on-save="handleHierarchySave"
          @reload="fetchHierarchy"
        />
      </div>
    </div>
  </section>

  <RoleManagerModal v-if="isSuperadmin" v-model="showRoleManager" />

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
      :is-target-admin="isEditingTargetAdmin"
      :is-self="isEditingSelf"
      :target-role-name="editingUser?.role_name"
      :target-domain-id="editingUser?.domain_id"
      @reset-password="() => editingUser && resetPassword(editingUser.id)"
      @view-role-detail="handleViewRoleDetail"
    />
  </Modal>

  <!-- Modal para carga de saldo -->
  <Modal
    v-model="showWalletModal"
    title="Cargar Saldo"
    icon="fa-wallet"
    static
    show-confirm
    confirm-text="Cargar Saldo"
    :on-submit="handleWalletLoadSubmit"
    :confirm-disabled="!(walletLoadFormRef?.isValid ?? false)"
  >
    <WalletLoadForm
      ref="walletLoadFormRef"
      v-model="walletLoadForm"
      :user-name="selectedUserName"
      :user-balance="selectedUserBalance"
      :user-currency="selectedUserCurrency?.iso_code"
      :user-currency-symbol="selectedUserCurrency?.symbol"
    />
  </Modal>

  <!-- Modal para permisos específicos por usuario (solo superadmin) -->
  <Modal
    v-model="showPermissionsModal"
    :title="`Permisos de ${permissionsUserName}`"
    icon="fa-user-shield"
    size="lg"
    static
    show-confirm
    confirm-text="Guardar cambios"
    :on-submit="handlePermissionsSubmit"
    :confirm-disabled="permissionsLoading"
  >
    <div v-if="permissionsLoading" class="text-center py-4">
      <span class="spinner-border spinner-border-sm text-primary mr-2"></span>
      Cargando permisos...
    </div>
    <UserPermissionsForm
      v-else
      ref="permissionsFormRef"
      v-model="permissionsForm.specific"
      v-model:reason="permissionsForm.reason"
      :catalog="permissionsCatalog"
      :via-roles="permissionsViaRoles"
    />
  </Modal>

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

  <!-- Modal de ficha de usuario -->
  <UserDetailModal
    :model-value="showUserDetailModal"
    :loading="userDetailLoading"
    :detail="userDetail"
    @update:model-value="handleUserDetailVisibility"
    @view-full-history="handleViewFullHistory"
  />
</template>

<style scoped>
.section-tabs {
  border-bottom: 1px solid var(--ds-border);
  margin-bottom: 1rem;
  gap: 0.25rem;
}
.section-tabs .nav-link {
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  color: var(--ds-text-muted);
  font-size: 0.88rem;
  font-weight: 600;
  padding: 0.6rem 0.9rem;
  cursor: pointer;
}
.section-tabs .nav-link:hover { color: var(--ds-text); }
.section-tabs .nav-link.active {
  color: var(--ds-primary, #2563eb);
  border-bottom-color: var(--ds-primary, #2563eb);
}

.admin-users-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 0.75rem;
  margin-bottom: 1rem;
}

/* Celda "Usuario" acoplada: rol + nombre + username + email en un solo bloque */
.admin-user-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  min-width: 0;
}
.admin-user-cell__top {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}
.admin-user-cell__name {
  font-weight: 600;
  color: var(--ds-text);
}
.admin-user-cell__meta {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.78rem;
  color: var(--ds-text-2);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.admin-user-cell__meta i {
  font-size: 0.7rem;
  opacity: 0.75;
}
.admin-user-cell__sep {
  opacity: 0.5;
}

/* Diferencia visualmente el botón de permisos individuales del resto de
   acciones extra (reset password, saldo, XP) — es una acción sensible y
   poco frecuente, no otra acción rutinaria más en la barra. */
:deep(.admin-user-btn--permissions) {
  margin-left: 0.5rem;
  border-left: 2px solid rgba(255, 255, 255, 0.4);
  padding-left: 0.75rem !important;
}
</style>
