<script setup lang="ts">
/** "Mi Perfil de Vendedor" — toda la ficha vive en RoleProfileView (común a los 3 roles de negocio). */
import RoleProfileView from '@/js/Components/Roles/Shared/Profile/RoleProfileView.vue'
import type { SellerProfilePageProps } from '@/js/types/roles/seller/profile/seller-profile'

const props = defineProps<SellerProfilePageProps>()
</script>

<template>
  <RoleProfileView :profile="props.seller" :is-admin-view="props.isAdminView" />
</template>
