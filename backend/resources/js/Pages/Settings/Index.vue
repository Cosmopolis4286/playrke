<script setup lang="ts">
  import { computed } from 'vue'

  // ==========================================================================
  // Componentes
  // ==========================================================================
  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import PageHeader  from '@/js/Components/Common/PageHeader/PageHeader.vue'
  import Modal       from '@/js/Components/Common/Modal/Modal.vue'
  import SettingForm from '@/js/Components/Settings/SettingForm.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================
  import { useCrudEventListener } from '@/js/composables/form/useCrudEventListener'
  import { useModalForm }         from '@/js/composables/common/useModalForm'
  import { useSetting }           from '@/js/composables/settings/useSetting'

  // ==========================================================================
  // Types
  // ==========================================================================
  import type { SettingPageProps }        from '@js/types/setting'
  import type { Column }                  from '@js/types/common/table/column-row'
  import type { TableFilterInterface, TableFilterOption } from '@js/types/common/table/table-filter'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================
  const props = defineProps<SettingPageProps>()

  // ==========================================================================
  // Lógica de negocio
  // ==========================================================================
  const isPrivileged = computed(() => ['superadmin', 'admin'].includes(props.roleUser?.toLowerCase()))

  const {
    settingData,
    settings,
    setInitialSettings,
    create,
    edit,
    remove,
    toggleStatus,
    submit,
  } = useSetting()

  setInitialSettings(props.settings)

  // ==========================================================================
  // Modal
  // ==========================================================================
  const {
    showModalForm,
    modalTitle,
    formRef: settingFormRef,
    handleSubmit,
  } = useModalForm(settingData, submit, { defaultTitle: 'Parámetro de configuración' })

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
    { key: 'param',  label: 'Parámetro' },
    { key: 'value',  label: 'Valor' },
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
        { value: 'ACTIVE',   label: 'Activo' },
        { value: 'INACTIVE', label: 'Inactivo' },
      ] as TableFilterOption[],
    },
  ])
</script>

<template>
  <PageHeader
    icon="fa-sliders"
    title="Configuración del Sistema"
    subtitle="Parámetros globales de configuración de la plataforma."
    :breadcrumb-items="[
      { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
      { label: 'Configuración' },
    ]"
  />

  <TableWithFilterBridge
    table-id="settings-table"
    title="Configuración del Sistema"
    subtitle="Gestión de parámetros de configuración"
    :columns="columns"
    :rows="settings"
    :filters="filters"
    search-placeholder="Buscar por parámetro o valor..."
    :show-create-button="isPrivileged"
    show-edit-button
    show-delete-button
    empty-message="No hay parámetros de configuración"
  >
    <!-- Valor -->
    <template #cell-value="{ row }">
      <span v-if="row.value" class="font-monospace text-sm">{{ row.value }}</span>
      <span v-else class="text-muted">—</span>
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
    :confirm-disabled="!(settingFormRef?.isValid ?? false)"
  >
    <SettingForm
      ref="settingFormRef"
      v-model="settingData"
    />
  </Modal>
</template>
