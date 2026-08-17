<script setup lang="ts">
  import { computed } from 'vue'

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import JackpotTypeForm from '@/js/Components/Jackpot/JackpotTypeForm.vue'

  import { useCrudEventListener } from '@/js/composables/form/useCrudEventListener'
  import { useModalForm } from '@/js/composables/common/useModalForm'
  import { useJackpotType } from '@/js/composables/jackpotType/useJackpotType'

  import type { JackpotType } from '@js/types/jackpotType'
  import type { GameModeGameOption } from '@js/types/gameMode'
  import type { GameCurrencyOption } from '@js/types/game'
  import type { Column } from '@js/types/common/table/column-row'
  import type { TableFilterInterface, TableFilterOption } from '@js/types/common/table/table-filter'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'

  const props = defineProps<{
    jackpotTypes: JackpotType[]
    games: GameModeGameOption[]
    currencies: GameCurrencyOption[]
  }>()

  const {
    jackpotTypeData,
    jackpotTypes,
    setInitialJackpotTypes,
    create,
    edit,
    remove,
    toggleStatus,
    submit
  } = useJackpotType()

  setInitialJackpotTypes(props.jackpotTypes)

  const gameById = computed(() => {
    const map: Record<string, GameModeGameOption> = {}
    for (const g of props.games) map[g.id] = g
    return map
  })

  const currencyById = computed(() => {
    const map: Record<string, GameCurrencyOption> = {}
    for (const c of props.currencies) map[c.id] = c
    return map
  })

  const GAME_STATUS_LABEL: Record<string, string> = {
    ACTIVE: 'Activo',
    INACTIVE: 'Inactivo',
    MAINTENANCE: 'Mantenimiento'
  }

  const PERIODICITY_LABEL: Record<JackpotType['periodicity'], string> = {
    PER_ROOM: 'Por sala',
    WEEKLY: 'Semanal',
    BIWEEKLY: 'Quincenal',
    MONTHLY: 'Mensual'
  }

  const {
    showModalForm,
    modalTitle,
    formRef: jackpotTypeFormRef,
    handleSubmit
  } = useModalForm(jackpotTypeData, submit, {
    defaultTitle: 'Jackpot'
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
    { key: 'label',          label: 'Nombre' },
    { key: 'code',           label: 'Código' },
    { key: 'game_id',        label: 'Juego' },
    { key: 'currency_id',    label: 'Moneda' },
    { key: 'periodicity',    label: 'Periodicidad' },
    { key: 'current_amount', label: 'Saldo actual' },
    { key: 'status',         label: 'Estado' }
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
    table-id="jackpot-type-table"
    title="Jackpots"
    subtitle="Catálogo de jackpots por juego y moneda — las salas de Bingo aportan a estos pozos según el % configurado en cada sala"
    :columns="columns"
    :rows="jackpotTypes"
    :filters="filters"
    :extra-buttons="extraButtons"
    search-placeholder="Buscar por nombre o código..."
    show-create-button
    show-edit-button
    show-delete-button
    empty-message="No hay jackpots registrados"
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

    <!-- Moneda -->
    <template #cell-currency_id="{ row }">
      {{ currencyById[row.currency_id]?.iso_code ?? row.currency_id }}
    </template>

    <!-- Periodicidad -->
    <template #cell-periodicity="{ row }">
      {{ PERIODICITY_LABEL[row.periodicity as JackpotType['periodicity']] ?? row.periodicity }}
    </template>

    <!-- Saldo actual (solo lectura) -->
    <template #cell-current_amount="{ row }">
      {{ Number(row.current_amount ?? 0).toFixed(2) }}
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
    :confirm-disabled="!(jackpotTypeFormRef?.isValid ?? false)"
  >
    <JackpotTypeForm
      ref="jackpotTypeFormRef"
      v-model="jackpotTypeData"
      :games="props.games"
      :currencies="props.currencies"
    />
  </Modal>
</template>
