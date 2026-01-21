<script setup lang="ts">
  import { computed } from 'vue'
  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import type { AdminCommercePageProps } from '@js/types/roles/admin/admin-commerce'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'
  import type { TableFilter, TableFilterOption } from '@js/types/common/table/table-filter'

  /**
   * Props tipadas para Inertia
   */
  const props = defineProps<AdminCommercePageProps>()

  /**
   * Filas de la tabla
   */
  const commerces = props.commerces

  /**
   * Opciones de país normalizadas
   * Se expone como array plano (NO computed en filtros)
   */
  const countryOptions = computed<TableFilterOption[]>(() =>
    props.countries.map(country => ({
      value: country.name,
      label: country.name
    }))
  )

  /**
   * Filtros de tabla
   * ⚠️ options debe ser siempre un array, no un ComputedRef
   */
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

  /**
   * Botones extra de acciones
   */
  const extraButtons: ExtraButton[] = [
    {
      label: '',
      icon: 'fas fa-exchange-alt',
      class: 'btn-secondary',
      show: false,
      action: 'changeStatus'
    },
    {
      label: '',
      icon: 'fas fa-key',
      class: 'btn-dark',
      show: false,
      action: 'resetPassword'
    }
  ]
</script>

<template>
  <TableWithFilterBridge
    tableId="commerce-table"
    title="Comercializadoras"
    :columns="columns"
    :rows="commerces"
    :filters="filters"
    search-placeholder="Buscar por nombre, email, teléfono..."
    :extra-buttons="extraButtons"
  />
</template>
