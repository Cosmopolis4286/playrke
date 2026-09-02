<script setup lang="ts">
  import { computed } from 'vue'

  // ==========================================================================
  // Componentes
  // ==========================================================================
  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import PageHeader       from '@/js/Components/Common/PageHeader/PageHeader.vue'
  import Modal             from '@/js/Components/Common/Modal/Modal.vue'
  import AppParameterForm  from '@/js/Components/AppParameters/AppParameterForm.vue'
  import StatTile          from '@/js/Components/Common/StatTile/StatTile.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================
  import { useCrudEventListener } from '@/js/composables/form/useCrudEventListener'
  import { useModalForm }         from '@/js/composables/common/useModalForm'
  import { useAppParameter }      from '@/js/composables/app-parameters/useAppParameter'
  import { getParamMeta }         from '@/js/composables/app-parameters/appParameterCatalog'

  // ==========================================================================
  // Types
  // ==========================================================================
  import type { AppParameterPageProps } from '@js/types/app-parameter'
  import type { Column }                from '@js/types/common/table/column-row'
  import type { TableFilterInterface, TableFilterOption } from '@js/types/common/table/table-filter'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================
  const props = defineProps<AppParameterPageProps>()

  // ==========================================================================
  // Lógica de negocio
  // ==========================================================================
  const isPrivileged = computed(() => ['superadmin', 'admin'].includes(props.roleUser?.toLowerCase()))

  const {
    loading,
    parameterData,
    parameters,
    setInitialParameters,
    create,
    edit,
    remove,
    toggleStatus,
    submit,
  } = useAppParameter()

  setInitialParameters(props.parameters)

  // ==========================================================================
  // Filas enriquecidas con la categoría del catálogo (ver appParameterCatalog.ts).
  // Necesario como campo REAL del objeto de fila (no solo en el slot visual
  // #cell-category) porque el filtro por columna de TableWithFilter resuelve
  // el valor a filtrar vía getNestedValue(row, 'category') — sin este campo
  // en la fila, el filtro de categoría nunca haría match.
  // ==========================================================================
  const parameterRows = computed(() =>
    parameters.value.map(p => ({ ...p, category: getParamMeta(p.param).category }))
  )

  // ==========================================================================
  // Stats — sobre el catálogo completo (esta página no pagina server-side,
  // parameters.value ya trae todos los parámetros), no una muestra parcial
  // como en Admin/User/Index.vue.
  // ==========================================================================
  const stats = computed(() => ({
    total:    parameters.value.length,
    active:   parameters.value.filter(p => p.status === 'ACTIVE').length,
    inactive: parameters.value.filter(p => p.status === 'INACTIVE').length,
    critical: parameters.value.filter(p => props.critical_params.includes(p.param)).length,
  }))

  // ==========================================================================
  // Parámetros críticos — no se pueden seleccionar para eliminar/desactivar
  // (el backend ya lo protege en delete(); esto evita el viaje de red y el
  // toast de error para el caso esperado, mismo mecanismo disabledRowIds/
  // disabledRowReason ya usado en Comercios).
  // ==========================================================================
  const disabledRowIds = computed(() =>
    parameters.value.filter(p => props.critical_params.includes(p.param)).map(p => p.id)
  )

  // ==========================================================================
  // Modal
  // ==========================================================================
  const {
    showModalForm,
    modalTitle,
    formRef: parameterFormRef,
    handleSubmit,
  } = useModalForm(parameterData, submit, { defaultTitle: 'Parámetro de configuración' })

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
    { key: 'param',    label: 'Parámetro' },
    { key: 'category', label: 'Categoría' },
    { key: 'value',    label: 'Valor' },
    { key: 'status',   label: 'Estado' },
  ]

  // ==========================================================================
  // Filtros — categoría es client-side (la tabla nunca pagina, ~21-30 filas
  // totales cargadas de una vez, ver fetchParameters() sin params de página).
  // ==========================================================================
  const categoryOptions = computed<TableFilterOption[]>(() => {
    const cats = new Set(parameters.value.map(p => getParamMeta(p.param).category))
    return Array.from(cats).sort().map(c => ({ value: c, label: c }))
  })

  const filters = computed<readonly TableFilterInterface[]>(() => [
    {
      key: 'category',
      label: 'Categoría',
      placeholder: 'Filtrar por categoría',
      options: categoryOptions.value,
    },
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

  <div class="app-parameters-stats">
    <StatTile label="Parámetros totales" :value="stats.total" icon="fa-sliders" />
    <StatTile label="Activos" :value="stats.active" icon="fa-circle-check" variant="success" />
    <StatTile label="Inactivos" :value="stats.inactive" icon="fa-circle-pause" />
    <StatTile label="Críticos del sistema" :value="stats.critical" icon="fa-shield-halved" variant="warning" />
  </div>

  <TableWithFilterBridge
    table-id="app-parameters-table"
    title="Configuración del Sistema"
    subtitle="Gestión de parámetros de configuración"
    :columns="columns"
    :rows="parameterRows"
    :filters="filters"
    :loading="loading"
    search-placeholder="Buscar por parámetro o valor..."
    :show-create-button="isPrivileged"
    show-edit-button
    show-delete-button
    empty-message="No hay parámetros de configuración. Crea el primero con el botón de arriba."
    :disabled-row-ids="disabledRowIds"
    disabled-row-reason="Este es un parámetro crítico del sistema — no se puede eliminar ni desactivar desde aquí."
  >
    <!-- Categoría -->
    <template #cell-category="{ value }">
      <span class="text-muted text-sm">{{ value }}</span>
    </template>

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
    :confirm-disabled="!(parameterFormRef?.isValid ?? false)"
  >
    <AppParameterForm
      ref="parameterFormRef"
      v-model="parameterData"
    />
  </Modal>
</template>

<style scoped>
.app-parameters-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 0.75rem;
  margin-bottom: 1rem;
}
</style>
