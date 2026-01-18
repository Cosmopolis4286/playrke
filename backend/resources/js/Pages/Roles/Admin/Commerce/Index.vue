<script setup lang="ts">
  import { computed } from 'vue'
  import TableWithFilterBridge from '@js/Components/Common/Table/TableWithFilterBridge.vue'
  import type { AdminCommercePageProps } from '@js/types/roles/admin/admin-commerce'
  import type { ExtraButton } from '@js/types/common/table/extra-buttons'

  /**
   * Tipos locales
   */
  interface Option {
    value: string
    label: string
  }

  /**
   * Props tipadas correctamente para Inertia
   */
  const props = defineProps<AdminCommercePageProps>()

  const commerces = props.commerces

  const countryOptions = computed<Option[]>
  props.countries.map(c => ({
    value: c.name,
    label: c.name
  }))

  const filters = [
    {
      key: 'country_name',
      label: 'País',
      placeholder: 'Filtrar por país',
      options: countryOptions
    },
    {
      key: 'status',
      label: 'Estado',
      placeholder: 'Filtrar por estado',
      options: [
        { value: 'ACTIVE', label: 'Activo' },
        { value: 'INACTIVE', label: 'Inactivo' },
        { value: 'DELETED', label: 'Eliminado' }
      ] as Option[]
    }
  ]

  const extraButtons: ExtraButton[] = [
    {
      label: '',
      icon: 'fas fa-exchange-alt',
      class: 'btn-secondary',
      show: false,
      action: 'changeStatus'
    },
    { label: '', icon: 'fas fa-key', class: 'btn-dark', show: false, action: 'resetPassword' }
  ]

  /**
   * Composable
   */
  //   const { loading, create, edit, changeStatus, remove, updateAdminCommerces } = useAdminCommerce(
  //     props.creator
  //   )
</script>

<template>
  <TableWithFilterBridge
    tableId="commerce-table"
    title="Comercializadoras"
    :columns="['Nombre', 'País', 'Teléfono', 'Email', 'Status']"
    :rows="commerces"
    :row-keys="['name', 'Country.name', 'phone', 'User.email', 'status_label']"
    :filters="filters"
    search-placeholder="Buscar por nombre, email, teléfono..."
    :extra-buttons="extraButtons"
  />
</template>
