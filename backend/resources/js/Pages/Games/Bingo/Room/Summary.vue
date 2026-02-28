<script setup lang="ts">
  /**
   * Página Juego BINGO → GameRoom/Summary
   */

  import { computed } from 'vue'

  // ==========================================================================
  // Componentes
  // ==========================================================================

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import BingoRoomForm from '@/js/Components/Games/Bingo/Room/Form/BingoRoomForm.vue'
  // ==========================================================================
  // Composables
  // ==========================================================================

  import { useCrudEventListener } from '@/js/composables/form/useCrudEventListener'
  import { useModalForm } from '@/js/composables/common/useModalForm'
  import { useBingoRoom } from '@/js/composables/games/bingo/room/default/useBingoRoom'

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { BingoRoomSummaryPageProps } from '@/js/types/games/bingo/room/summary/bingo-room-summary-page'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'
  import type { TableFilter } from '@js/types/common/table/table-filter'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================

  const props = defineProps<BingoRoomSummaryPageProps>()

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
    copyRoom,
    toggleStatusBulk,
    removeBulk,
    submit
  } = useBingoRoom()

  setInitialRooms(props.rooms.rows)

  // ==========================================================================
  // Modal + Form handler
  // ==========================================================================

  const {
    showModalForm,
    modalTitle,
    formRef: gameBingoRoomFormRef,
    handleSubmit
  } = useModalForm(roomData, submit, {
    defaultTitle: 'Sala de Bingo'
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

    copy: async ids => {
      if (!ids || ids.length !== 1) return
      await copyRoom(ids[0])
    }
  })

  /**
   * Opciones para tipos de sala.
   */
  const roomTypeOptions = computed(() => {
    return (props.roomTypes ?? []).map(r => ({
      value: String(r.id),
      label: r.label
    }))
  })

  /**
   * Opciones para modos de juego.
   */
  const gameModeOptions = computed(() => {
    return (props.gameModes ?? []).map(g => ({
      value: String(g.id),
      label: g.label
    }))
  })

  /**
   * Opciones para tipos de prize pool.
   */
  const prizePoolTypeOptions = computed(() => {
    return (props.prizePoolTypes ?? []).map(p => ({
      value: String(p.id),
      code: p.code,
      label: p.label
    }))
  })

  /**
   * Opciones de monedas permitidas.
   */
  const allowedCurrenciesOptions = computed(() => {
    return (props.currencies ?? []).map(c => ({
      value: String(c.id),
      label: c.iso_code
    }))
  })

  /**
   * Opciones de patrones de bingo.
   */
  const bingoPatternOptions = computed(() => {
    return (props.bingoPatterns ?? []).map(p => ({
      value: String(p.id),
      label: p.name
    }))
  })

  // ==========================================================================
  // Filtros
  // ==========================================================================

  const filters = computed<readonly TableFilter[]>(() => [
    {
      key: 'room_type_description',
      label: 'tipo',
      placeholder: 'Filtrar por tipo',
      options: roomTypeOptions.value
    },
    {
      key: 'status',
      label: 'Estado',
      placeholder: 'Filtrar por estado',
      options: [
        { value: 'ACTIVE', label: 'Disponible' },
        { value: 'IN_PROGRESS', label: 'En Juego' },
        { value: 'FINISHED', label: 'Finalizada' },
        { value: 'REFUND', label: 'Reembolso' },
        { value: 'OFFLINE', label: 'Fuera de Línea' }
      ]
    }
  ])

  // ==========================================================================
  // Botones extra
  // ==========================================================================

  const extraButtons: ExtraButton[] = [
    {
      label: 'Salas Automáticas',
      class: 'btn-secondary',
      show: (ids: string[]) => !ids.length,
      onClick: (ids: string[]) => {
        if (ids.length) return
        window.location.href = `/bingo-automatic-rooms`
      }
    },
    {
      icon: 'fas fa-exchange-alt',
      class: 'btn-secondary',
      show: false,
      action: 'toggleStatus'
    },
    {
      icon: 'fas fa-copy',
      class: 'btn-info text-white',
      show: (ids: string[]) => ids.length === 1,
      action: 'copy'
    }
  ]

  const subtitle = `<a title="Ir a configuración del Bingo" href="/bingo/config" class="text-primary font-weight-bold">Ir a configuración</a>`
</script>

<template>
  <TableWithFilterBridge
    tableId="bingo-room-table"
    :title="props.title"
    :subtitle="subtitle"
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
    :confirm-disabled="!(gameBingoRoomFormRef?.isValid ?? false)"
  >
    <BingoRoomForm
      ref="gameBingoRoomFormRef"
      v-model="roomFormData"
      :room-type-options="roomTypeOptions"
      :game-mode-options="gameModeOptions"
      :prize-pool-type-options="prizePoolTypeOptions"
      :allowed-currencies-options="allowedCurrenciesOptions"
      :bingo-pattern-options="bingoPatternOptions"
    />

    <template #footer>
      <div class="custom-control custom-switch mr-auto">
        <input
          type="checkbox"
          class="custom-control-input"
          id="isDraft"
          :checked="!(roomFormData.isDraft ?? true)"
          @change="roomFormData.isDraft = !(($event.target as HTMLInputElement).checked)"
        />
        <label class="custom-control-label" for="isDraft">Activar sala</label>
      </div>
      <button class="btn btn-sm btn-secondary" @click="showModalForm = false">Cerrar</button>
      <button
        class="btn btn-sm btn-primary"
        :disabled="!(gameBingoRoomFormRef?.isValid ?? false)"
        @click="handleSubmit"
      >
        Guardar
      </button>
    </template>
  </Modal>
</template>
