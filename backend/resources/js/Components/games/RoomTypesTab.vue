<script setup lang="ts">
  import { computed } from 'vue'

  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import RoomTypeForm from '@/js/Components/RoomType/RoomTypeForm.vue'

  import { useCrudEventListener } from '@/js/composables/form/useCrudEventListener'
  import { useModalForm } from '@/js/composables/common/useModalForm'
  import { useRoomType } from '@/js/composables/roomType/useRoomType'

  import type { RoomType } from '@js/types/roomType'
  import type { Column } from '@js/types/common/table/column-row'
  import type { TableFilterInterface, TableFilterOption } from '@js/types/common/table/table-filter'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'

  const props = defineProps<{
    roomTypes: RoomType[]
  }>()

  const {
    roomTypeData,
    roomTypes,
    setInitialRoomTypes,
    create,
    edit,
    remove,
    toggleStatus,
    submit
  } = useRoomType()

  setInitialRoomTypes(props.roomTypes)

  const {
    showModalForm,
    modalTitle,
    formRef: roomTypeFormRef,
    handleSubmit
  } = useModalForm(roomTypeData, submit, {
    defaultTitle: 'Tipo de Sala'
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
    { key: 'label',  label: 'Nombre' },
    { key: 'code',   label: 'Código' },
    { key: 'status', label: 'Estado' }
  ]

  const filters = computed<readonly TableFilterInterface[]>(() => [
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
    table-id="room-type-table"
    title="Tipos de Sala"
    subtitle="Catálogo global de tipos de sala (Privada, Pública, VIP, DEMO)"
    :columns="columns"
    :rows="roomTypes"
    :filters="filters"
    :extra-buttons="extraButtons"
    search-placeholder="Buscar por nombre o código..."
    show-create-button
    show-edit-button
    show-delete-button
    empty-message="No hay tipos de sala registrados"
  >
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
    :confirm-disabled="!(roomTypeFormRef?.isValid ?? false)"
  >
    <RoomTypeForm
      ref="roomTypeFormRef"
      v-model="roomTypeData"
    />
  </Modal>
</template>
