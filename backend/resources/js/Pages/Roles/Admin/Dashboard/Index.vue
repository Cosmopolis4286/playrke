<script setup lang="ts">
  /**
   * Página Dashboard → Vendedor
   */

  import { computed, watch } from 'vue'

  // ==========================================================================
  // Componentes
  // ==========================================================================

  // ==========================================================================
  // Composables
  // ==========================================================================

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { DashboardAdminPageProps } from '@/js/types/roles/admin/dashboard/dashboard-admin'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================

  const props = defineProps<DashboardAdminPageProps>()
</script>

<template>
  <h1 class="mb-4 text-2xl font-bold">Dashboard Admin</h1>
  <p>
    Bienvenido al dashboard de administrador. Aquí podrás ver tus estadísticas y gestionar tus
    ventas.
  </p>
</template>
