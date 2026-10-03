<script setup lang="ts">
/** "Mi Comercio" — toda la ficha vive en RoleProfileView (común a los 3 roles de negocio). */
import RoleProfileView from '@/js/Components/Roles/Shared/Profile/RoleProfileView.vue'
import type { CommerceProfilePageProps } from '@/js/types/roles/commerce/profile/commerce-profile'

const props = defineProps<CommerceProfilePageProps>()
</script>

<template>
  <RoleProfileView :profile="props.commerce" :is-admin-view="props.isAdminView" />
</template>
