<script setup lang="ts">
  import { computed } from 'vue'

  // ==========================================================================
  // Componentes
  // ==========================================================================

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import GameModeForm from '@/js/Components/GameMode/GameModeForm.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { useCrudEventListener } from '@/js/composables/form/useCrudEventListener'
  import { useModalForm } from '@/js/composables/common/useModalForm'
  import { useGameMode } from '@/js/composables/gameMode/useGameMode'

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { GameModePageProps } from '@js/types/gameMode'
  import type { Column } from '@js/types/common/table/column-row'
  import type { TableFilterInterface, TableFilterOption } from '@js/types/common/table/table-filter'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================

  const props = defineProps<GameModePageProps>()

  // ==========================================================================
  // Lógica de negocio
  // ==========================================================================

  const {
    gameModeData,
    gameModes,
    setInitialGameModes,
    create,
    edit,
    remove,
    toggleStatus,
    submit
  } = useGameMode()

  setInitialGameModes(props.gameModes)

  const gameById = computed(() => {
    const map: Record<string, (typeof props.games)[number]> = {}
    for (const g of props.games) map[g.id] = g
    return map
  })

  const GAME_STATUS_LABEL: Record<string, string> = {
    ACTIVE: 'Activo',
    INACTIVE: 'Inactivo',
    MAINTENANCE: 'Mantenimiento'
  }

  // ==========================================================================
  // Modal + Form handler
  // ==========================================================================

  const {
    showModalForm,
    modalTitle,
    formRef: gameModeFormRef,
    handleSubmit
  } = useModalForm(gameModeData, submit, {
    defaultTitle: 'Modo de Juego'
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
      for (const id of ids) await remove(id)
    },

    toggleStatus: async ids => {
      if (!ids) return
      for (const id of ids) await toggleStatus(id)
    }
  })

  // ==========================================================================
  // Columnas
  // ==========================================================================

  const columns: Column[] = [
    { key: 'label',   label: 'Nombre' },
    { key: 'code',    label: 'Código' },
    { key: 'game_id', label: 'Juego' },
    { key: 'status',  label: 'Estado' }
  ]

  // ==========================================================================
  // Filtros
  // ==========================================================================

  const filters = computed<readonly TableFilterInterface[]>(() => [
    {
      key: 'game_id',
      label: 'Juego',
      placeholder: 'Filtrar por juego',
      options: props.games.map(g => ({ value: g.id, label: g.name })) as TableFilterOption[]
    },
    {
      key: 'status',
      label: 'Estado',
      placeholder: 'Filtrar por estado',
      options: [
        { value: 'ACTIVE',   label: 'Activo' },
        { value: 'INACTIVE', label: 'Inactivo' }
      ] as TableFilterOption[]
    }
  ])

  // ==========================================================================
  // Botones extra
  // ==========================================================================

  const extraButtons: ExtraButton[] = [
    {
      icon: 'fas fa-exchange-alt',
      class: 'btn-secondary',
      show: false,
      action: 'toggleStatus'
    }
  ]
</script>

<template>
  <TableWithFilterBridge
    table-id="game-mode-table"
    title="Modos de Juego"
    subtitle="Catálogo de modalidades de participación por juego (Solitario, Múltiple, Ranking, etc.)"
    :columns="columns"
    :rows="gameModes"
    :filters="filters"
    :extra-buttons="extraButtons"
    search-placeholder="Buscar por nombre o código..."
    show-create-button
    show-edit-button
    show-delete-button
    empty-message="No hay modos de juego registrados"
  >
    <!-- Juego -->
    <template #cell-game_id="{ row }">
      {{ gameById[row.game_id]?.name ?? row.game_id }}
      <span
        v-if="gameById[row.game_id] && gameById[row.game_id].status !== 'ACTIVE'"
        class="badge badge-warning ml-1"
      >
        {{ GAME_STATUS_LABEL[gameById[row.game_id].status] }}
      </span>
    </template>

    <!-- Estado -->
    <template #cell-status="{ row }">
      <span :class="['badge', row.status === 'ACTIVE' ? 'badge-success' : 'badge-secondary']">
        {{ row.status === 'ACTIVE' ? 'Activo' : 'Inactivo' }}
      </span>
    </template>
  </TableWithFilterBridge>

  <Modal
    v-model="showModalForm"
    :title="modalTitle"
    static
    show-confirm
    :on-submit="handleSubmit"
    :confirm-disabled="!(gameModeFormRef?.isValid ?? false)"
  >
    <GameModeForm
      ref="gameModeFormRef"
      v-model="gameModeData"
      :games="props.games"
    />
  </Modal>
</template>
