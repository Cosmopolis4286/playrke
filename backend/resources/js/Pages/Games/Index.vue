<script setup lang="ts">
  import { computed } from 'vue'

  // ==========================================================================
  // Componentes
  // ==========================================================================
  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import Modal    from '@/js/Components/Common/Modal/Modal.vue'
  import GameForm from '@/js/Components/Games/GameForm.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================
  import { useCrudEventListener } from '@/js/composables/form/useCrudEventListener'
  import { useModalForm }         from '@/js/composables/common/useModalForm'
  import { useGame }              from '@/js/composables/games/useGame'

  // ==========================================================================
  // Types
  // ==========================================================================
  import type { GamePageProps }          from '@js/types/game'
  import type { Column }                 from '@js/types/common/table/column-row'
  import type { TableFilterInterface, TableFilterOption } from '@js/types/common/table/table-filter'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================
  const props = defineProps<GamePageProps>()

  // ==========================================================================
  // Lógica de negocio
  // ==========================================================================
  const isPrivileged = computed(() => ['superadmin', 'admin'].includes(props.roleUser))

  const {
    gameData,
    games,
    setInitialGames,
    create,
    edit,
    remove,
    toggleStatus,
    submit,
  } = useGame()

  setInitialGames(props.games)

  // ==========================================================================
  // Modal
  // ==========================================================================
  const {
    showModalForm,
    modalTitle,
    formRef: gameFormRef,
    handleSubmit,
  } = useModalForm(gameData, submit, { defaultTitle: 'Juego' })

  // ==========================================================================
  // Eventos de tabla (CRUD global)
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
    },
  })

  // ==========================================================================
  // Columnas
  // ==========================================================================
  const columns: Column[] = [
    { key: 'name',   label: 'Nombre' },
    { key: 'status', label: 'Estado' },
  ]

  // ==========================================================================
  // Filtros
  // ==========================================================================
  const filters = computed<readonly TableFilterInterface[]>(() => [
    {
      key: 'status',
      label: 'Estado',
      placeholder: 'Filtrar por estado',
      options: [
        { value: 'ACTIVE',      label: 'Activo' },
        { value: 'INACTIVE',    label: 'Inactivo' },
        { value: 'MAINTENANCE', label: 'Mantenimiento' },
      ] as TableFilterOption[],
    },
  ])
</script>

<template>
  <TableWithFilterBridge
    table-id="games-table"
    title="Catálogo de Juegos"
    subtitle="Gestión de juegos disponibles en la plataforma"
    :columns="columns"
    :rows="games"
    :filters="filters"
    search-placeholder="Buscar por nombre..."
    :show-create-button="isPrivileged"
    show-edit-button
    show-delete-button
    empty-message="No hay juegos registrados"
  >
    <!-- Estado -->
    <template #cell-status="{ row }">
      <span :class="['badge', {
        'badge-success':   row.status === 'ACTIVE',
        'badge-secondary': row.status === 'INACTIVE',
        'badge-warning':   row.status === 'MAINTENANCE',
      }]">
        {{ row.status === 'ACTIVE' ? 'Activo' : row.status === 'INACTIVE' ? 'Inactivo' : 'Mantenimiento' }}
      </span>
    </template>
  </TableWithFilterBridge>

  <Modal
    v-model="showModalForm"
    :title="modalTitle"
    static
    show-confirm
    :on-submit="handleSubmit"
    :confirm-disabled="!(gameFormRef?.isValid ?? false)"
  >
    <GameForm
      ref="gameFormRef"
      v-model="gameData"
    />
  </Modal>
</template>
