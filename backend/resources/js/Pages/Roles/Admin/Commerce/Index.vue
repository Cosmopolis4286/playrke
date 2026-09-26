<script setup lang="ts">
  /**
   * Página Admin → Comercializadoras
   */

  import { computed, onMounted, ref } from 'vue'
  import { usePage, router } from '@inertiajs/vue3'
  import Swal from 'sweetalert2'

  // ==========================================================================
  // Componentes
  // ==========================================================================

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import PageHeader from '@/js/Components/Common/PageHeader/PageHeader.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import AdminCommerceForm from '@/js/Components/Roles/Admin/Commerce/AdminCommerceForm.vue'
  import CommerceDetailModal from '@/js/Components/Roles/Admin/Commerce/CommerceDetailModal.vue'
  import StatTile from '@/js/Components/Common/StatTile/StatTile.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { useCrudEventListener } from '@/js/composables/form/useCrudEventListener'
  import { useModalForm } from '@/js/composables/common/useModalForm'
  import { useAdminCommerce } from '@/js/composables/roles/admin/commerce/useAdminCommerce'
  import { useCommerceDetail } from '@/js/composables/roles/admin/commerce/useCommerceDetail'

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { AdminCommercePageProps } from '@js/types/roles/admin/admin-commerce'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'
  import type { TableFilterInterface, TableFilterOption } from '@js/types/common/table/table-filter'
  import type { ServerQuery } from '@/js/composables/common/table/useTableController'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================

  const props = defineProps<AdminCommercePageProps>()

  // ==========================================================================
  // Permisos — commerces.set-default es exclusivo de superadmin (backend ya
  // lo exige vía multiPermission, esto solo oculta la UI a quien de todos
  // modos sería rechazado). Mismo patrón que Admin/User/Index.vue.
  // ==========================================================================

  const page = usePage()
  const isSuperadmin = computed(() =>
    (page.props.auth as any)?.user?.userRoles?.some((r: any) => r.role?.name?.toLowerCase() === 'superadmin') ?? false
  )

  // ==========================================================================
  // Lógica de negocio
  // ==========================================================================

  const {
    commerceData,
    commerces,
    pagination,
    loading,
    lastQuery,
    setInitialCommerces,
    create,
    edit,
    toggleStatusBulk,
    resetPassword,
    removeBulk,
    setAsDefault,
    unsetAsDefault,
    submit,
    onQueryChange,
  } = useAdminCommerce()

  setInitialCommerces(props.commerces.rows, props.commerces_pagination)

  // ==========================================================================
  // Stats — "total" viene de pagination (todo el catálogo filtrado, server-
  // side), no de commerces.value (solo la página cargada). El resto de
  // métricas sí son "de esta página" — mismo criterio ya usado en
  // Admin/User/Index.vue, documentado explícitamente en el label para no
  // ser engañoso.
  // ==========================================================================
  const stats = computed(() => ({
    total: pagination.value?.total ?? commerces.value.length,
    active: commerces.value.filter(c => c.status === 'ACTIVE').length,
    blocked: commerces.value.filter(c => c.status === 'BLOCKED').length,
  }))

  // ==========================================================================
  // Comercio principal — estado local inicializado desde la prop de Inertia
  // y mutado optimistamente tras marcar uno nuevo (evita depender de un
  // router.reload solo para refrescar este dato).
  //
  // Solo se bloquean Eliminar/Cambiar estado (el backend ya rechaza ambos
  // para el comercio principal, ver AdminCommerceCrudService::delete()/
  // toggleStatus()) — Editar queda siempre disponible, incluyendo poder
  // gestionar la propia marca de "principal" desde el form. Antes esto se
  // hacía con disabledRowIds, que deshabilita el checkbox de SELECCIÓN
  // completo — eso también bloqueaba sin querer el botón Editar (que
  // requiere 1 fila seleccionada).
  // ==========================================================================

  const defaultCommerceId = ref<string | null>(props.default_commerce_id)

  /** Id de fila (hasheado) del comercio principal, si está en la página actual. */
  const defaultCommerceRowId = computed(() =>
    commerces.value.find(c => c.real_id === defaultCommerceId.value)?.id
  )

  function isDeleteDisabled(selectedIds: string[]): boolean {
    return !!defaultCommerceRowId.value && selectedIds.includes(defaultCommerceRowId.value)
  }

  function getDeleteDisabledReason(selectedIds: string[]): string {
    return isDeleteDisabled(selectedIds)
      ? 'Esta comercializadora es la principal de la plataforma — reasigna la marca antes de eliminarla.'
      : ''
  }

  async function handleSetDefault(): Promise<void> {
    if (!commerceDetail.value?.id) return
    const hashedId = commerceDetail.value.id
    const ok = await setAsDefault(hashedId)
    if (!ok) return

    // CommerceDetail no expone real_id (solo el id hasheado) — se resuelve
    // contra la fila de tabla ya cargada, que sí lo trae (ver
    // AdminCommerceContextService::resolveCommerces()). Si el comercio no
    // está en la página actual, fetchCommerces() lo trae vía onQueryChange
    // más abajo tras refrescar la ficha.
    await fetchCommerceDetail(hashedId)
    defaultCommerceId.value = commerces.value.find(c => c.id === hashedId)?.real_id ?? defaultCommerceId.value
  }

  async function handleUnsetDefault(): Promise<void> {
    if (!commerceDetail.value?.id) return
    const hashedId = commerceDetail.value.id
    const ok = await unsetAsDefault(hashedId)
    if (!ok) return

    await fetchCommerceDetail(hashedId)
    defaultCommerceId.value = null
  }

  // ==========================================================================
  // Modal + Form handler
  // ==========================================================================

  /**
   * Envuelve submit() para reflejar en la tabla, sin recargar la página, el
   * checkbox "Marcar/desmarcar como principal" del form si cambió durante
   * esta edición — submit() ya resetea commerceData.value al terminar, así
   * que el id/flag se capturan ANTES de delegar, no se pueden leer después.
   */
  async function submitAndSyncDefault(): Promise<boolean> {
    const hashedId = commerceData.value.id
    const isNowDefault = commerceData.value.is_default_commerce === true

    const ok = await submit()
    if (ok && hashedId) {
      defaultCommerceId.value = isNowDefault
        ? (commerces.value.find(c => c.id === hashedId)?.real_id ?? defaultCommerceId.value)
        : (defaultCommerceId.value === commerces.value.find(c => c.id === hashedId)?.real_id ? null : defaultCommerceId.value)
    }
    return ok
  }

  const {
    showModalForm,
    modalTitle,
    formRef: adminCommerceFormRef,
    handleSubmit
  } = useModalForm(commerceData, submitAndSyncDefault, {
    defaultTitle: 'Comercializadora'
  })

  // Auto-abre el detalle de una comercializadora al llegar desde el botón
  // "Ver más" de Admin > Usuarios (?open={commerceId} -> props.openId).
  onMounted(async () => {
    if (!props.openId) return
    const ok = await edit(props.openId)
    if (ok) showModalForm.value = true
  })

  // ==========================================================================
  // Ficha de detalle (solo lectura — comisión, código único, monedas/juegos, vendedores)
  // ==========================================================================

  const showDetailModal = ref(false)
  const {
    loading: commerceDetailLoading,
    detail: commerceDetail,
    error: commerceDetailError,
    fetchDetail: fetchCommerceDetail,
    retryFetchDetail: retryCommerceDetail,
    clearDetail: clearCommerceDetail,
  } = useCommerceDetail()

  async function openCommerceDetail(rowId: string) {
    showDetailModal.value = true
    await fetchCommerceDetail(rowId)
  }

  function handleDetailVisibility(open: boolean) {
    showDetailModal.value = open
    if (!open) clearCommerceDetail()
  }

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
  // Export CSV
  // ==========================================================================

  function handleExport(): void {
    const params = new URLSearchParams()
    if (lastQuery.value.search) params.set('search', lastQuery.value.search)
    if (lastQuery.value.filters.country_name) params.set('country_name', lastQuery.value.filters.country_name)
    if (lastQuery.value.filters.status) params.set('status', lastQuery.value.filters.status)
    window.location.href = `/admin/commerce/export?${params.toString()}`
  }

  // ==========================================================================
  // Filtros
  // ==========================================================================

  const countryOptions = computed<TableFilterOption[]>(() =>
    props.countries.map(country => ({
      value: country.name,
      label: country.name
    }))
  )

  const filters = computed<readonly TableFilterInterface[]>(() => [
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
        { value: 'BLOCKED', label: 'Bloqueado' },
        { value: 'DELETED', label: 'Eliminado' }
      ]
    }
  ])

  // ==========================================================================
  // Impersonación (solo SuperAdmin — ver ImpersonationService)
  // ==========================================================================

  async function handleImpersonate(rowId: string) {
    const commerce = commerces.value.find(c => c.id === rowId)
    if (!commerce?.user_hashed_id) return
    const name = commerce.name ?? 'este comercio'

    const confirmed = await Swal.fire({
      title: '¿Iniciar sesión de soporte?',
      html: `Vas a ver la plataforma como <b>${name}</b>. Se registrará en auditoría y no podrás retirar fondos ni cambiar la contraseña mientras dure la sesión.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, impersonar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#d97706',
      reverseButtons: true,
    })
    if (!confirmed.isConfirmed) return

    // POST tradicional (no Inertia/AJAX): el controlador responde con
    // redirect() a '/' como el propio comercio, que Inertia no puede navegar
    // como una respuesta JSON — un submit real deja que el navegador siga
    // la redirección y cargue la sesión ya autenticada como el comercio.
    const form = document.createElement('form')
    form.method = 'POST'
    form.action = `/admin/impersonation/${commerce.user_hashed_id}/start`
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
    const csrfInput = document.createElement('input')
    csrfInput.type = 'hidden'
    csrfInput.name = '_token'
    csrfInput.value = csrfToken
    form.appendChild(csrfInput)
    document.body.appendChild(form)
    form.submit()
  }

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
    const commerce = commerces.value.find(c => c.id === selectedIds[0])
    return commerce?.status !== 'ACTIVE'
  }

  /**
   * Retorna la razón por la cual resetPassword está deshabilitado.
   */
  function getResetPasswordDisabledReason(selectedIds: string[]): string {
    const commerce = commerces.value.find(c => c.id === selectedIds[0])
    if (commerce?.status !== 'ACTIVE') {
      return 'Solo se puede resetear la contraseña de comercializadoras activas'
    }
    return ''
  }

  const extraButtons: ExtraButton[] = [
    {
      icon: 'fas fa-id-card',
      class: 'btn-outline-secondary',
      title: 'Ver ficha de la comercializadora',
      show: (ids: string[]) => ids.length === 1,
      onClick: (ids: string[]) => {
        if (ids.length === 1) {
          openCommerceDetail(ids[0])
        }
      }
    },
    {
      icon: 'fas fa-address-card',
      class: 'btn-outline-info',
      title: 'Ver perfil (Mi Cuenta del comercio)',
      show: (ids: string[]) => ids.length === 1,
      onClick: (ids: string[]) => {
        if (ids.length !== 1) return
        const commerce = commerces.value.find(c => c.id === ids[0])
        if (!commerce?.user_id) return
        router.visit(`/commerce/profile?target_user_id=${commerce.user_id}`)
      }
    },
    {
      icon: 'fas fa-user-secret',
      class: 'btn-warning text-white',
      title: 'Impersonar (sesión de soporte)',
      // Solo SuperAdmin (mismo criterio que el backend, role:superadmin en
      // admin/impersonation) y solo comercios activos.
      show: (ids: string[]) => isSuperadmin.value && ids.length === 1 && commerces.value.find(c => c.id === ids[0])?.status === 'ACTIVE',
      onClick: (ids: string[]) => {
        if (ids.length === 1) {
          handleImpersonate(ids[0])
        }
      }
    },
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

  async function handleQueryChange(query: ServerQuery): Promise<void> {
    await onQueryChange(query)
  }
</script>

<template>
  <PageHeader
    icon="fa-building"
    title="Comercializadoras"
    subtitle="Administra las empresas comercializadoras y sus datos de contacto."
    :breadcrumb-items="[
      { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
      { label: 'Comercializadoras' },
    ]"
  />

  <div class="admin-commerce-stats">
    <StatTile label="Comercializadoras totales" :value="stats.total" icon="fa-building" />
    <StatTile label="Activas (esta página)" :value="stats.active" icon="fa-circle-check" variant="success" />
    <StatTile label="Bloqueadas (esta página)" :value="stats.blocked" icon="fa-ban" variant="danger" />
  </div>

  <TableWithFilterBridge
    tableId="commerce-table"
    title="Comercializadoras"
    :columns="props.commerces.columns"
    :rows="commerces"
    :filters="filters"
    search-placeholder="Buscar por nombre, email, teléfono..."
    :loading="loading"
    server-side
    :server-total-pages="pagination?.last_page ?? 1"
    showCreateButton
    showEditButton
    showDeleteButton
    :extra-buttons="extraButtons"
    :disable-delete="isDeleteDisabled"
    :disable-delete-reason="getDeleteDisabledReason"
    @query-change="handleQueryChange"
  >
    <template #action-buttons>
      <button class="btn btn-sm btn-outline-secondary" title="Exportar comercializadoras filtradas a CSV" @click="handleExport">
        <i class="fas fa-file-csv me-1"></i>Exportar
      </button>
    </template>
    <template #cell-name="{ row, value }">
      <span v-if="row.real_id && row.real_id === defaultCommerceId" title="Comercializadora principal de la plataforma">
        <i class="fas fa-star text-warning me-1"></i>{{ value }}
      </span>
      <span v-else>{{ value }}</span>
    </template>
  </TableWithFilterBridge>

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
      :countries="props.countries"
      :currencies="props.currencies"
      :time-zones="props.timeZones"
      :games="props.games"
      :can-manage-default-commerce="isSuperadmin"
    />
  </Modal>

  <CommerceDetailModal
    :model-value="showDetailModal"
    :loading="commerceDetailLoading"
    :detail="commerceDetail"
    :error="commerceDetailError"
    :can-manage-default-commerce="isSuperadmin"
    @update:model-value="handleDetailVisibility"
    @retry="retryCommerceDetail"
    @set-default="handleSetDefault"
    @unset-default="handleUnsetDefault"
  />
</template>

<style scoped>
.admin-commerce-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 0.75rem;
  margin-bottom: 1rem;
}
</style>
