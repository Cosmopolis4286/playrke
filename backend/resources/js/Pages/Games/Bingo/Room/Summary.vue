<script setup lang="ts">
  /**
   * Página Juego BINGO → GameRoom/Summary
   */

  import { computed, watch } from 'vue'

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

  import type { BingoRoomSummaryPageProps } from '@/js/types/games/bingo/room/summary/bingo-room-summary'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'
  import type { TableFilter } from '@js/types/common/table/table-filter'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================

  const props = defineProps<BingoRoomSummaryPageProps>()

  console.log('BingoRoomSummaryPageProps', props)

  // ==========================================================================
  // Lógica de negocio
  // ==========================================================================

  const {
    roomData,
    roomFormData,
    rooms,
    setInitialRooms,
    create,
    edit,
    toggleStatusBulk,
    removeBulk,
    submit
  } = useBingoRoom()

  setInitialRooms(props.rooms.rows)

  // ==========================================================================
  // Opciones de formulario
  // ==========================================================================

  const roomTypeOptions = [
    { value: 'PRIVATE', label: 'Público' },
    { value: 'PUBLIC', label: 'Privado' }
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
    defaultTitle: 'Sala de Bingo'
  })

  watch(showModalForm, isOpen => {
    if (!isOpen) {
      bingoGameRoomFormRef.value?.reset?.()
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
    }
  })

  // ==========================================================================
  // Filtros
  // ==========================================================================

  const filters = computed<readonly TableFilter[]>(() => [
    {
      key: 'room_type_description',
      label: 'tipo',
      placeholder: 'Filtrar por tipo',
      options: [
        { value: 'Público', label: 'Público' },
        { value: 'Privado', label: 'Privado' }
      ]
    },
    {
      key: 'status',
      label: 'Estado',
      placeholder: 'Filtrar por estado',
      options: [
        { value: 'AVAILABLE', label: 'Disponible' },
        { value: 'IN_PROGRESS', label: 'En Juego' },
        { value: 'FINISHED', label: 'Finalizada' },
        { value: 'REFUND', label: 'Reembolso' },
        { value: 'OFFLINE', label: 'Fuera de Línea' }
      ]
    }
  ])

  /**
   * Opciones para modos de juego.
   */
  const gameModeOptions = computed(() => {
    return (props.gameModes ?? []).map(g => ({
      value: g.id,
      label: g.description
    }))
  })

  /**
   * Opciones para tipos de prize pool.
   */
  const prizePoolTypeOptions = computed(() => {
    return (props.prizePoolTypes ?? []).map(p => ({
      value: p.id,
      label: p.description
    }))
  })

  /**
   * Opciones de monedas permitidas.
   */
  const allowedCurrenciesOptions = computed(() => {
    return (props.currencies ?? []).map(c => ({
      value: c.id,
      label: c.name
    }))
  })

  /**
   * Opciones de patrones de bingo.
   */
  const bingoPatternOptions = computed(() => {
    return (props.bingoPatterns ?? []).map(p => ({
      value: p.id,
      label: p.name
    }))
  })

  // ==========================================================================
  // Botones extra
  // ==========================================================================

  const extraButtons: ExtraButton[] = [
    {
      label: '',
      icon: 'fas fa-exchange-alt',
      class: 'btn-secondary',
      show: false,
      action: 'toggleStatus'
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
    search-placeholder="Buscar por código, tipo..."
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
      :room-type-options="roomTypeOptions"
      :game-mode-options="gameModeOptions"
      :prize-pool-type-options="prizePoolTypeOptions"
      :allowed-currencies-options="allowedCurrenciesOptions"
      :bingo-pattern-options="bingoPatternOptions"
    />
  </Modal>
</template>
