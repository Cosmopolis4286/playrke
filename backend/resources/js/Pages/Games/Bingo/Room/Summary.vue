<script setup lang="ts">
  /**
   * Página Juego BINGO → GameRoom/Summary
   */

  import { computed, ref, watch } from 'vue'

  // ==========================================================================
  // Componentes
  // ==========================================================================

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import BingoRoomForm from '@/js/Components/Games/Bingo/Room/BingoRoomForm.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { useCrudEventListener } from '@/js/composables/form/useCrudEventListener'
  import { useModalForm } from '@/js/composables/common/useModalForm'
  import { useBingoRoom } from '@/js/composables/games/bingo/room/useBingoRoom'

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { BingoRoomSummaryPageProps } from '@/js/types/games/bingo/room/bingo-room-summary'
  import type { BingoRoomFormData } from '@/js/types/games/bingo/bingo-room'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'
  import type { TableFilter, TableFilterOption } from '@js/types/common/table/table-filter'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================

  const props = defineProps<BingoRoomSummaryPageProps>()

  // ==========================================================================
  // Lógica de negocio
  // ==========================================================================

  const {
    roomData,
    rooms,
    setInitialRooms,
    create,
    edit,
    toggleStatusBulk,
    resetPassword,
    removeBulk,
    submit
  } = useBingoRoom()

  setInitialRooms(props.rooms.rows)

  // ==========================================================================
  // Form data para creación de sala
  // ==========================================================================

  const emptyFormData = (): BingoRoomFormData => ({
    date: null,
    time: null,
    roomType: 'PV',
    gameMode: null,
    prizePoolType: null,
    allowedCurrencies: [],
    amountAddedPrizePool: null,
    roomCommn: 20,
    jackpotPct: null,
    timeChip: 5,
    rounds: 1,
    roundsConfigActive: false,
    loadingRoundsConfig: false,
    roundsPct: null,
    serialsPct: 5,
    numSerials: 5,
    ctnPrice: 1,
    roundsConfig: []
  })

  const roomFormData = ref<BingoRoomFormData>(emptyFormData())

  const roomTypeOptions = [
    { value: 'PV', label: 'Público' },
    { value: 'PB', label: 'Privado' }
  ]

  // ==========================================================================
  // Modal + Form handler
  // ==========================================================================

  const {
    showModalForm,
    modalTitle,
    formRef: bingoGameRoomFormRef,
    handleSubmit
  } = useModalForm(roomData, submit, {
    defaultTitle: 'Sala de Bingo',
  })

  watch(showModalForm, isOpen => {
    if (!isOpen) {
      bingoGameRoomFormRef.value?.reset?.()
      roomFormData.value = emptyFormData()
      create()
    }
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
  // Filtros
  // ==========================================================================

  const countryOptions = computed<TableFilterOption[]>(() =>
    props.countries.map(country => ({
      value: country.name,
      label: country.name
    }))
  )

  const filters = computed<readonly TableFilter[]>(() => [
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
    const commerce = rooms.value.find(c => c.id === selectedIds[0])
    return commerce?.status !== 'ACTIVE'
  }

  /**
   * Retorna la razón por la cual resetPassword está deshabilitado.
   */
  function getResetPasswordDisabledReason(selectedIds: string[]): string {
    const commerce = rooms.value.find(c => c.id === selectedIds[0])
    if (commerce?.status !== 'ACTIVE') {
      return 'Solo se puede resetear la contraseña de comercializadoras activas'
    }
    return ''
  }

  const extraButtons: ExtraButton[] = [
    {
      label: '',
      icon: 'fas fa-exchange-alt',
      class: 'btn-secondary',
      show: false,
      action: 'toggleStatus'
    },
    {
      label: '',
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
    tableId="bingo-room-table"
    :title="props.title"
    :columns="props.rooms.columns"
    :rows="rooms"
    :filters="filters"
    search-placeholder="Buscar por nombre, email, teléfono..."
    showCreateButton
    showEditButton
    showDeleteButton
    :extra-buttons="extraButtons"
  />

  <Modal
    v-model="showModalForm"
    :title="modalTitle"
    size="lg"
    static
    show-confirm
    :on-submit="handleSubmit"
    :confirm-disabled="!(bingoGameRoomFormRef?.isValid ?? false)"
  >
    <BingoRoomForm
      ref="bingoGameRoomFormRef"
      v-model="roomFormData"
      :creator="props.creator ?? { type: 'admin', id: '' }"
      :room-type-options="roomTypeOptions"
      :game-mode-options="(props.gameModes ?? []).map(g => ({ value: g.id, label: g.description }))"
      :prize-pool-type-options="(props.prizePoolTypes ?? []).map(p => ({ value: p.id, label: p.description }))"
      :allowed-currencies-options="props.currencies.map(c => ({ value: c.id, label: c.name }))"
      :bingo-pattern-options="(props.bingoPatterns ?? []).map(p => ({ value: p.id, label: p.name }))"
    />
  </Modal>
</template>
