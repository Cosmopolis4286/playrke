<script setup lang="ts">
/** "Mi Tesorería" — toda la ficha vive en RoleProfileView (común a todos los roles con entidad propia). */
import RoleProfileView from '@/js/Components/Roles/Shared/Profile/RoleProfileView.vue'
import type { TreasurerProfilePageProps } from '@/js/types/roles/treasurer/profile/treasurer-profile'

const props = defineProps<TreasurerProfilePageProps>()
</script>

<template>
  <RoleProfileView :profile="props.treasurer" :is-admin-view="props.isAdminView" />
</template>
