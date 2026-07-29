<script setup lang="ts">
  import { computed } from 'vue'

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import PrizePoolTypeForm from '@/js/Components/PrizePoolType/PrizePoolTypeForm.vue'

  import { useCrudEventListener } from '@/js/composables/form/useCrudEventListener'
  import { useModalForm } from '@/js/composables/common/useModalForm'
  import { usePrizePoolType } from '@/js/composables/prizePoolType/usePrizePoolType'

  import type { PrizePoolType } from '@js/types/prizePoolType'
  import type { GameModeGameOption } from '@js/types/gameMode'
  import type { Column } from '@js/types/common/table/column-row'
  import type { TableFilterInterface, TableFilterOption } from '@js/types/common/table/table-filter'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'

  const props = defineProps<{
    prizePoolTypes: PrizePoolType[]
    games: GameModeGameOption[]
  }>()

  const {
    prizePoolTypeData,
    prizePoolTypes,
    setInitialPrizePoolTypes,
    create,
    edit,
    remove,
    toggleStatus,
    submit
  } = usePrizePoolType()

  setInitialPrizePoolTypes(props.prizePoolTypes)

  const gameById = computed(() => {
    const map: Record<string, GameModeGameOption> = {}
    for (const g of props.games) map[g.id] = g
    return map
  })

  const GAME_STATUS_LABEL: Record<string, string> = {
    ACTIVE: 'Activo',
    INACTIVE: 'Inactivo',
    MAINTENANCE: 'Mantenimiento'
  }

  const {
    showModalForm,
    modalTitle,
    formRef: prizePoolTypeFormRef,
    handleSubmit
  } = useModalForm(prizePoolTypeData, submit, {
    defaultTitle: 'Tipo de Bote'
  })

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

  const columns: Column[] = [
    { key: 'label',   label: 'Nombre' },
    { key: 'code',    label: 'Código' },
    { key: 'game_id', label: 'Juego' },
    { key: 'status',  label: 'Estado' }
  ]

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
    table-id="prize-pool-type-table"
    title="Tipos de Bote"
    subtitle="Catálogo de tipos de pool de premios por juego (Bote Fijo, Acumulativo, Mixto, etc.)"
    :columns="columns"
    :rows="prizePoolTypes"
    :filters="filters"
    :extra-buttons="extraButtons"
    search-placeholder="Buscar por nombre o código..."
    show-create-button
    show-edit-button
    show-delete-button
    empty-message="No hay tipos de bote registrados"
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
    :confirm-disabled="!(prizePoolTypeFormRef?.isValid ?? false)"
  >
    <PrizePoolTypeForm
      ref="prizePoolTypeFormRef"
      v-model="prizePoolTypeData"
      :games="props.games"
    />
  </Modal>
</template>
