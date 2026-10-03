<script setup lang="ts">
/** "Mi Agencia" — toda la ficha vive en RoleProfileView (común a los 3 roles de negocio). */
import RoleProfileView from '@/js/Components/Roles/Shared/Profile/RoleProfileView.vue'
import type { SubsidiaryProfilePageProps } from '@/js/types/roles/subsidiary/profile/subsidiary-profile'

const props = defineProps<SubsidiaryProfilePageProps>()
</script>

<template>
  <RoleProfileView :profile="props.subsidiary" :is-admin-view="props.isAdminView" />
</template>
