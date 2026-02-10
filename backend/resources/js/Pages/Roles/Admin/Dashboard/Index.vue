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

  import type { DashboardSellerPageProps } from '@/js/types/roles/seller/dashboard/dashboard-seller'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================

  const props = defineProps<DashboardSellerPageProps>()
</script>

<template>
  <h1 class="mb-4 text-2xl font-bold">Dashboard Vendedor</h1>
  <p>
    Bienvenido al dashboard de vendedor. Aquí podrás ver tus estadísticas y gestionar tus ventas.
  </p>
</template>
