<script setup lang="ts">
/** "Mi Cliente" — toda la ficha vive en RoleProfileView (común a todos los roles con entidad propia). */
import RoleProfileView from '@/js/Components/Roles/Shared/Profile/RoleProfileView.vue'
import type { CustomerProfilePageProps } from '@/js/types/roles/customer/profile/customer-profile'

const props = defineProps<CustomerProfilePageProps>()
</script>

<template>
  <RoleProfileView :profile="props.customer" :is-admin-view="props.isAdminView" />
</template>
