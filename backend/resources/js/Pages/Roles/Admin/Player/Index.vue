<script setup lang="ts">
  /**
   * Página Admin → Jugadores
   */

  import { computed, onMounted, ref } from 'vue'
  import { router } from '@inertiajs/vue3'

  // ==========================================================================
  // Componentes
  // ==========================================================================

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import AdminPlayerForm from '@/js/Components/Roles/Admin/Player/AdminPlayerForm.vue'
  import UserDetailModal from '@/js/Components/Roles/Admin/User/UserDetailModal.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { useCrudEventListener } from '@/js/composables/form/useCrudEventListener'
  import { useModalForm } from '@/js/composables/common/useModalForm'
  import { useAdminPlayer } from '@/js/composables/roles/admin/player/useAdminPlayer'
  import { useAdminUserDetail } from '@/js/composables/roles/admin/user/useAdminUserDetail'

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { AdminPlayerPageProps } from '@js/types/roles/admin/admin-player'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'
  import type { TableFilterInterface, TableFilterOption } from '@js/types/common/table/table-filter'
  import type { ServerQuery } from '@/js/composables/common/table/useTableController'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================

  const props = defineProps<AdminPlayerPageProps>()

  // ==========================================================================
  // Lógica de negocio
  // ==========================================================================

  const {
    playerData,
    players,
    pagination,
    loading,
    lastQuery,
    setInitialPlayers,
    create,
    edit,
    toggleStatusBulk,
    resetPassword,
    removeBulk,
    submit,
    onQueryChange,
  } = useAdminPlayer()

  setInitialPlayers(props.players.rows, props.players_pagination)

  // ==========================================================================
  // Modal + Form handler
  // ==========================================================================

  const {
    showModalForm,
    modalTitle,
    formRef: adminPlayerFormRef,
    handleSubmit
  } = useModalForm(playerData, submit, {
    defaultTitle: 'Jugador'
  })

  // Auto-abre el detalle de un jugador al llegar desde el botón "Ver más" de
  // Admin > Usuarios (?open={userId} -> props.openId; 'player' no tiene
  // modelo de dominio propio, domain_id ES el User.id).
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
      if (ok) showModalForm.value = true
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
  // Ficha de detalle (reusa UserDetailModal.vue / useAdminUserDetail.ts de
  // /admin/users — un jugador es un User, GET admin/user/{id}/show funciona
  // igual sin importar desde qué pantalla se llame)
  // ==========================================================================

  const showPlayerDetailModal = ref(false)
  const {
    loading: playerDetailLoading,
    detail: playerDetail,
    error: playerDetailError,
    fetchDetail: fetchPlayerDetail,
    retryFetchDetail: retryPlayerDetail,
    clearDetail: clearPlayerDetail,
    loadingMoreAudits: playerDetailLoadingMoreAudits,
    loadMoreAudits: loadMorePlayerDetailAudits,
  } = useAdminUserDetail()

  async function openPlayerDetail(rowId: string) {
    showPlayerDetailModal.value = true
    // rowId ya es el hashed_id de la fila (HasHashedRouteKey::getHashedIdAttribute()
    // vía HasHashedCollection::hashCollectionIds() — mismo hash global sin
    // importar el prefijo de ruta), válido directo contra admin/user/{id}/show.
    await fetchPlayerDetail(rowId)
  }

  function handlePlayerDetailVisibility(open: boolean) {
    showPlayerDetailModal.value = open
    if (!open) clearPlayerDetail()
  }

  function handleViewFullHistory(userRealId: string) {
    showPlayerDetailModal.value = false
    clearPlayerDetail()
    router.visit('/audit', {
      data: { entity: 'App\\Models\\User', auditable_id: String(userRealId) },
    })
  }

  // ==========================================================================
  // Export CSV
  // ==========================================================================

  function handleExport(): void {
    const params = new URLSearchParams()
    if (lastQuery.value.search) params.set('search', lastQuery.value.search)
    if (lastQuery.value.filters.country_name) params.set('country_name', lastQuery.value.filters.country_name)
    if (lastQuery.value.filters.status) params.set('status', lastQuery.value.filters.status)
    window.location.href = `/admin/player/export?${params.toString()}`
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
        { value: 'DELETED', label: 'Eliminado' }
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
    const player = players.value.find(p => p.id === selectedIds[0])
    return player?.status !== 'ACTIVE'
  }

  function getResetPasswordDisabledReason(selectedIds: string[]): string {
    const player = players.value.find(p => p.id === selectedIds[0])
    if (player?.status !== 'ACTIVE') {
      return 'Solo se puede resetear la contraseña de jugadores activos'
    }
    return ''
  }

  const extraButtons: ExtraButton[] = [
    {
      icon: 'fas fa-id-card',
      class: 'btn-outline-secondary',
      title: 'Ver ficha del jugador',
      show: (ids: string[]) => ids.length === 1,
      onClick: (ids: string[]) => {
        if (ids.length === 1) {
          openPlayerDetail(ids[0])
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
    tableId="player-table"
    title="Jugadores"
    :columns="props.players.columns"
    :rows="players"
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
      <button class="btn btn-sm btn-outline-secondary" title="Exportar jugadores filtrados a CSV" @click="handleExport">
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
    :confirm-disabled="!(adminPlayerFormRef?.isValid ?? false)"
  >
    <AdminPlayerForm
      ref="adminPlayerFormRef"
      v-model="playerData"
      :countries="props.countries"
      :time-zones="props.timeZones"
    />
  </Modal>

  <!-- Ficha de detalle del jugador (datos básicos + wallet + nivel + stats de juego + actividad reciente) -->
  <UserDetailModal
    :model-value="showPlayerDetailModal"
    :loading="playerDetailLoading"
    :detail="playerDetail"
    :error="playerDetailError"
    :loading-more-audits="playerDetailLoadingMoreAudits"
    @update:model-value="handlePlayerDetailVisibility"
    @view-full-history="handleViewFullHistory"
    @retry="retryPlayerDetail"
    @load-more-audits="loadMorePlayerDetailAudits"
  />
</template>
