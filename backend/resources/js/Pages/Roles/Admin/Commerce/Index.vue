<script setup lang="ts">
  /**
   * Página Admin → Comercializadoras
   */

  import { computed, onMounted, ref } from 'vue'

  // ==========================================================================
  // Componentes
  // ==========================================================================

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import AdminCommerceForm from '@/js/Components/Roles/Admin/Commerce/AdminCommerceForm.vue'
  import CommerceDetailModal from '@/js/Components/Roles/Admin/Commerce/CommerceDetailModal.vue'

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
    submit,
    onQueryChange,
  } = useAdminCommerce()

  setInitialCommerces(props.commerces.rows, props.commerces_pagination)

  // ==========================================================================
  // Modal + Form handler
  // ==========================================================================

  const {
    showModalForm,
    modalTitle,
    formRef: adminCommerceFormRef,
    handleSubmit
  } = useModalForm(commerceData, submit, {
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
    @query-change="handleQueryChange"
  >
    <template #action-buttons>
      <button class="btn btn-sm btn-outline-secondary" title="Exportar comercializadoras filtradas a CSV" @click="handleExport">
        <i class="fas fa-file-csv me-1"></i>Exportar
      </button>
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
    />
  </Modal>

  <CommerceDetailModal
    :model-value="showDetailModal"
    :loading="commerceDetailLoading"
    :detail="commerceDetail"
    :error="commerceDetailError"
    @update:model-value="handleDetailVisibility"
    @retry="retryCommerceDetail"
  />
</template>
