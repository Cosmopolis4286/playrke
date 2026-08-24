<script setup lang="ts">
  /**
   * Página Admin → Administradores (/admins)
   *
   * Solo accesible por SuperAdmin (middleware role:superadmin + UserPolicy).
   * "Crear" abre el formulario de alta de un nuevo administrador; "Editar"
   * abre el modal de edición de datos básicos + reset de contraseña.
   */

  import { computed, ref } from 'vue'
  import { usePage } from '@inertiajs/vue3'

  // ==========================================================================
  // Componentes
  // ==========================================================================

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import StatTile from '@/js/Components/Common/StatTile/StatTile.vue'
  import PageHeader from '@/js/Components/Common/PageHeader/PageHeader.vue'
  import RelativeTime from '@/js/Components/Common/RelativeTime/RelativeTime.vue'
  import AdministratorCreateForm from '@/js/Components/Roles/Admin/Administrator/AdministratorCreateForm.vue'
  import AdministratorEditForm from '@/js/Components/Roles/Admin/Administrator/AdministratorEditForm.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { useCrudEventListener } from '@/js/composables/form/useCrudEventListener'
  import { useModalForm } from '@/js/composables/common/useModalForm'
  import { useAdminAdministrator } from '@/js/composables/roles/admin/administrator/useAdminAdministrator'

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { AdminAdministratorPageProps } from '@/js/types/roles/admin/administrator-page'
  import type { AdministratorCreateData } from '@/js/types/roles/admin/administrator'
  import type { TableFilterInterface, TableFilterOption } from '@js/types/common/table/table-filter'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'

  const props = defineProps<AdminAdministratorPageProps>()

  const {
    admins,
    pagination,
    loading,
    lastQuery,
    editData,
    setInitialAdmins,
    onQueryChange,
    createAdministrator,
    editAdministrator,
    updateAdministrator,
    toggleStatus,
    remove,
    destroy,
    resetPassword,
  } = useAdminAdministrator()

  setInitialAdmins(props.admins.rows, props.admins.pagination)

  const page = usePage()
  const currentUserId = computed(() => String((page.props.auth as any)?.user?.id ?? ''))

  const breadcrumbItems = [
    { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
    { label: 'Administradores' },
  ]

  // Filas de acciones SENSIBLES bloqueadas (status/password/delete/destroy)
  // sobre la propia cuenta del actor — el backend ya lo bloquea
  // (UserPolicy::manageAdministrator), esto evita ofrecer en la UI una
  // acción que el servidor va a rechazar con 403. "Editar" datos básicos SÍ
  // se permite sobre uno mismo (UserPolicy::editAdministrator) — no entra
  // en este set.
  const disabledRowIds = computed<string[]>(() =>
    admins.value
      .filter(a => String(a.real_id ?? a.id) === currentUserId.value)
      .map(a => String(a.id))
  )

  function isProtectedSelection(ids: string[]): boolean {
    return ids.some(id => disabledRowIds.value.includes(String(id)))
  }

  function getProtectedSelectionReason(): string {
    return 'No puedes realizar esta acción sobre tu propio usuario'
  }

  const isEditingSelf = computed(() => !!editData.value.real_id && String(editData.value.real_id) === currentUserId.value)
  const togglingStatusId = ref<string | null>(null)

  async function handleToggleStatusFromForm() {
    if (!editData.value.id || isEditingSelf.value) return
    togglingStatusId.value = String(editData.value.id)
    try {
      const ok = await toggleStatus(String(editData.value.id))
      if (ok) {
        editData.value = { ...editData.value, status: editData.value.status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE' }
      }
    } finally {
      togglingStatusId.value = null
    }
  }

  // ==========================================================================
  // Modal de creación
  // ==========================================================================

  const createForm = ref<Partial<AdministratorCreateData>>({})

  async function submitCreate(): Promise<boolean> {
    if (!createForm.value.username) return false
    return createAdministrator(createForm.value as AdministratorCreateData)
  }

  const {
    showModalForm: showCreateModal,
    formRef: createFormRef,
    handleSubmit: handleCreateSubmit,
  } = useModalForm(createForm, submitCreate, { defaultTitle: 'Administrador', createTitle: 'Registrar administrador', editTitle: 'Registrar administrador' })

  // ==========================================================================
  // Modal de edición
  // ==========================================================================

  const {
    showModalForm: showEditModal,
    formRef: editFormRef,
    handleSubmit: handleEditSubmit,
  } = useModalForm(editData, updateAdministrator, { defaultTitle: 'Administrador', createTitle: 'Editar administrador', editTitle: 'Editar administrador' })

  function openEditModal(id: string) {
    editAdministrator(id)
    showEditModal.value = true
  }

  // ==========================================================================
  // CRUD global (eventos tabla)
  // ==========================================================================

  useCrudEventListener({
    create: () => {
      createForm.value = {}
      showCreateModal.value = true
    },

    edit: ids => {
      if (!ids || ids.length !== 1) return
      openEditModal(ids[0])
    },

    delete: async ids => {
      if (!ids) return
      await remove(ids)
    },

    destroy: async ids => {
      if (!ids) return
      await destroy(ids)
    },

    toggleStatus: async ids => {
      if (!ids) return
      for (const id of ids) await toggleStatus(id)
    },

    resetPassword: async ids => {
      if (!ids || ids.length !== 1) return
      await resetPassword(ids[0])
    },
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
      label: r.name.charAt(0).toUpperCase() + r.name.slice(1),
    }))
  )

  const filters = computed<readonly TableFilterInterface[]>(() => [
    {
      key: 'role_name',
      label: 'Rol',
      placeholder: 'Filtrar por rol',
      options: roleOptions.value,
    },
    {
      key: 'country_name',
      label: 'País',
      placeholder: 'Filtrar por país',
      options: countryOptions.value,
    },
    {
      key: 'status',
      label: 'Estado',
      placeholder: 'Filtrar por estado',
      options: [
        { value: 'ACTIVE', label: 'Activo' },
        { value: 'INACTIVE', label: 'Inactivo' },
      ],
    },
  ])

  // ==========================================================================
  // Estadísticas del header
  // ==========================================================================

  const stats = computed(() => ({
    total: pagination.value?.total ?? admins.value.length,
    active: admins.value.filter(a => a.status === 'ACTIVE').length,
    superadmins: admins.value.filter(a => a.role_name === 'superadmin').length,
  }))

  // ==========================================================================
  // Botones extra
  // ==========================================================================

  function isResetPasswordVisible(selectedIds: string[]): boolean {
    return selectedIds.length === 1 && !isProtectedSelection(selectedIds)
  }

  const extraButtons: ExtraButton[] = [
    {
      icon: 'fas fa-exchange-alt',
      class: 'btn-secondary',
      title: 'Cambiar estado',
      show: (ids: string[]) => ids.length > 0 && !isProtectedSelection(ids),
      action: 'toggleStatus',
    },
    {
      icon: 'fas fa-key',
      class: 'btn-dark',
      title: 'Restablecer contraseña',
      show: isResetPasswordVisible,
      action: 'resetPassword',
    },
    {
      icon: 'fas fa-trash-alt',
      class: 'btn-outline-danger',
      title: 'Eliminar permanentemente',
      show: (ids: string[]) => ids.length > 0 && !isProtectedSelection(ids),
      action: 'destroy',
    },
  ]
</script>

<template>
  <PageHeader
    icon="fa-user-shield"
    title="Administradores"
    subtitle="Gestiona las cuentas con acceso al panel de administración."
    :breadcrumb-items="breadcrumbItems"
  />

  <section class="content">
    <div class="container-fluid">
      <div class="admin-administrator-stats">
        <StatTile label="Administradores totales" :value="stats.total" icon="fa-user-shield" />
        <StatTile label="Activos" :value="stats.active" icon="fa-user-check" variant="success" />
        <StatTile label="SuperAdmins (esta página)" :value="stats.superadmins" icon="fa-crown" variant="warning" />
      </div>

      <TableWithFilterBridge
        tableId="admins-table"
        title="Administradores"
        :columns="props.admins.columns"
        :rows="admins"
        :filters="filters"
        search-placeholder="Buscar por nombre, username, email..."
        :loading="loading"
        server-side
        :server-total-pages="pagination?.last_page ?? 1"
        showCreateButton
        showEditButton
        showDeleteButton
        :disable-delete="isProtectedSelection"
        :disable-delete-reason="getProtectedSelectionReason"
        :extra-buttons="extraButtons"
        row-vertical-align="middle"
        @query-change="onQueryChange"
      >
        <template #cell-full_name="{ row }">
          <div class="admin-administrator-cell">
            <div class="admin-administrator-cell__top">
              <span class="admin-administrator-cell__name">{{ row.full_name }}</span>
              <span v-html="row.role_badge"></span>
            </div>
            <span class="admin-administrator-cell__meta">
              <i class="fas fa-at"></i>{{ row.username }}
              <span class="admin-administrator-cell__sep">·</span>
              <i class="fas fa-envelope"></i>{{ row.email }}
            </span>
          </div>
        </template>

        <template #cell-registered_at="{ row }">
          <RelativeTime :value="row.registered_at" />
        </template>

        <template #cell-last_login_at="{ row }">
          <RelativeTime :value="row.last_login_at" icon="fas fa-sign-in-alt" />
        </template>
      </TableWithFilterBridge>
    </div>
  </section>

  <!-- Modal de alta de administrador -->
  <Modal
    v-model="showCreateModal"
    title="Registrar administrador"
    icon="fa-user-shield"
    static
    show-confirm
    confirm-text="Registrar"
    :on-submit="handleCreateSubmit"
    :confirm-disabled="!(createFormRef?.isValid ?? false)"
  >
    <AdministratorCreateForm
      ref="createFormRef"
      v-model="createForm"
      :countries="props.countries"
    />
  </Modal>

  <!-- Modal de edición de administrador -->
  <Modal
    v-model="showEditModal"
    title="Editar administrador"
    icon="fa-user-edit"
    static
    show-confirm
    :on-submit="handleEditSubmit"
    :confirm-disabled="!(editFormRef?.isValid ?? false)"
  >
    <AdministratorEditForm
      ref="editFormRef"
      v-model="editData"
      :countries="props.countries"
      :is-self="isEditingSelf"
      :toggling-status="!!togglingStatusId"
      @reset-password="() => editData.id && !isEditingSelf && resetPassword(String(editData.id))"
      @toggle-status="handleToggleStatusFromForm"
    />
  </Modal>
</template>

<style scoped>
.admin-administrator-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.admin-administrator-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  min-width: 0;
}
.admin-administrator-cell__top {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}
.admin-administrator-cell__name {
  font-weight: 600;
  color: var(--ds-text);
}
.admin-administrator-cell__meta {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.78rem;
  color: var(--ds-text-2);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.admin-administrator-cell__meta i {
  font-size: 0.7rem;
  opacity: 0.75;
}
.admin-administrator-cell__sep {
  opacity: 0.5;
}
</style>
