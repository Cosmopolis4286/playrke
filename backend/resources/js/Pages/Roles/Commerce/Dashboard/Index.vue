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

  import type { DashboardCommercePageProps } from '@/js/types/roles/commerce/dashboard/dashboard-commerce'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================

  const props = defineProps<DashboardCommercePageProps>()
</script>

<template>
  <h1 class="mb-4 text-2xl font-bold">Dashboard Comercio</h1>
  <p>
    Bienvenido al dashboard de comercio. Aquí podrás ver tus estadísticas y gestionar tus ventas.
  </p>
</template>
