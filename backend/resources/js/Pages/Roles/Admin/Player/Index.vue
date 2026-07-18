<script setup lang="ts">
  /**
   * Página Admin → Jugadores
   */

  import { computed } from 'vue'

  // ==========================================================================
  // Componentes
  // ==========================================================================

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import AdminPlayerForm from '@/js/Components/Roles/Admin/Player/AdminPlayerForm.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { useCrudEventListener } from '@/js/composables/form/useCrudEventListener'
  import { useModalForm } from '@/js/composables/common/useModalForm'
  import { useAdminPlayer } from '@/js/composables/roles/admin/player/useAdminPlayer'

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { AdminPlayerPageProps } from '@js/types/roles/admin/admin-player'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'
  import type { TableFilterInterface, TableFilterOption } from '@js/types/common/table/table-filter'

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
    setInitialPlayers,
    create,
    edit,
    toggleStatusBulk,
    resetPassword,
    removeBulk,
    submit
  } = useAdminPlayer()

  setInitialPlayers(props.players.rows)

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
  <div class="ds-page">
    <div class="ds-inner">
      <TableWithFilterBridge
        tableId="player-table"
        title="Jugadores"
        :columns="props.players.columns"
        :rows="players"
        :filters="filters"
        search-placeholder="Buscar por nombre, email, teléfono..."
        showCreateButton
        showEditButton
        showDeleteButton
        :extra-buttons="extraButtons"
      />
    </div>
  </div>

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
</template>
