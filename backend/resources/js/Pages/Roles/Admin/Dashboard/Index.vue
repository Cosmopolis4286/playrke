<script setup lang="ts">
  /**
   * Página Dashboard → Admin
   * Stats: usuarios totales, nuevos del mes, cartones hoy, salas activas.
   * Conteos de entidades por rol: comercios, vendedores, clientes, tesoreros.
   * Actividad reciente: últimos usuarios registrados.
   */

  // ==========================================================================
  // Componentes
  // ==========================================================================

  import DashboardWelcomeBanner from '@/js/Components/Common/Dashboard/DashboardWelcomeBanner.vue'
  import DashboardStatCard      from '@/js/Components/Common/Dashboard/DashboardStatCard.vue'
  import DashboardSection       from '@/js/Components/Common/Dashboard/DashboardSection.vue'
  import DashboardActivityFeed  from '@/js/Components/Common/Dashboard/DashboardActivityFeed.vue'
  import DashboardQuickLinks    from '@/js/Components/Common/Dashboard/DashboardQuickLinks.vue'
  import type { QuickLink }     from '@/js/Components/Common/Dashboard/DashboardQuickLinks.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { useAdminDashboard } from '@/js/composables/roles/admin/dashboard/useAdminDashboard'

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { DashboardAdminPageProps } from '@/js/types/roles/admin/dashboard/dashboard-admin'

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================

  const props = defineProps<DashboardAdminPageProps>()

  // ==========================================================================
  // Composable
  // ==========================================================================

  const { statCards, sortedActivity, entityCounts } = useAdminDashboard(props)

  // ==========================================================================
  // Accesos rápidos
  // ==========================================================================

  const quickLinks: QuickLink[] = [
    {
      label:       'Usuarios',
      description: 'Gestionar todos los usuarios',
      icon:        'fas fa-users',
      href:        '/admin/users',
      variant:     'primary',
    },
    {
      label:       'Comercios',
      description: 'Gestionar comercializadoras',
      icon:        'fas fa-building',
      href:        '/admin/commerces',
      variant:     'info',
    },
    {
      label:       'Vendedores',
      description: 'Gestionar vendedores',
      icon:        'fas fa-user-tie',
      href:        '/admin/sellers',
      variant:     'success',
    },
    {
      label:       'Clientes',
      description: 'Gestionar clientes',
      icon:        'fas fa-handshake',
      href:        '/admin/customers',
      variant:     'warning',
    },
    {
      label:       'Jugadores',
      description: 'Gestionar jugadores',
      icon:        'fas fa-gamepad',
      href:        '/admin/players',
      variant:     'secondary',
    },
    {
      label:       'Tesoreros',
      description: 'Gestionar tesoreros',
      icon:        'fas fa-piggy-bank',
      href:        '/admin/treasurers',
      variant:     'secondary',
    },
  ]
</script>

<template>
  <div class="ds-page">
    <div class="ds-inner">

      <!-- Bienvenida -->
      <DashboardWelcomeBanner
        :userName="props.userName ?? 'Admin'"
        roleName="Administrador"
        :message="props.period ? `Período: ${props.period}` : undefined"
        :actions="[
          { label: 'Ver usuarios', icon: 'fas fa-users', href: '/admin/users', variant: 'primary' },
        ]"
      />

      <!-- Stats principales -->
      <div class="row g-3 mt-0">
        <div
          v-for="(stat, i) in statCards"
          :key="i"
          class="col-12 col-sm-6 col-xl-3"
        >
          <DashboardStatCard v-bind="stat" />
        </div>
      </div>

      <!-- Accesos rápidos -->
      <DashboardSection
        title="Accesos rápidos"
        icon="fas fa-bolt"
        subtitle="Gestión del sistema"
      >
        <DashboardQuickLinks :links="quickLinks" :columns="3" />
      </DashboardSection>

      <!-- Fila inferior -->
      <div class="row g-3">

        <!-- Actividad reciente -->
        <div class="col-12 col-lg-7">
          <DashboardSection
            title="Actividad reciente"
            icon="fas fa-history"
            subtitle="Últimos usuarios registrados"
          >
            <DashboardActivityFeed :items="sortedActivity" :maxItems="10" />
          </DashboardSection>
        </div>

        <!-- Conteos por entidad -->
        <div class="col-12 col-lg-5">
          <DashboardSection
            title="Entidades por rol"
            icon="fas fa-layer-group"
            subtitle="Totales registrados"
          >
            <div v-if="entityCounts.length" class="ds-entity-list">
              <div
                v-for="(entity, i) in entityCounts"
                :key="i"
                class="ds-entity-item"
              >
                <span class="ds-entity-icon" :class="`text-${entity.variant}`">
                  <i :class="entity.icon"></i>
                </span>
                <span class="ds-entity-label">{{ entity.label }}</span>
                <span class="ds-entity-value badge" :class="`bg-${entity.variant}`">
                  {{ entity.value }}
                </span>
              </div>
            </div>
            <div v-else class="ds-empty">
              <i class="fas fa-layer-group"></i>
              <p class="mb-0">Sin datos disponibles.</p>
            </div>
          </DashboardSection>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
.ds-page {
  width: 100%;
  min-height: 100%;
  overflow-x: hidden;
  padding: 1.25rem 1rem;
  box-sizing: border-box;
}

.ds-inner {
  max-width: 1400px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* Lista de entidades */
.ds-entity-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.ds-entity-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.6rem 0.75rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.ds-entity-icon {
  font-size: 1.1rem;
  width: 1.5rem;
  text-align: center;
  flex-shrink: 0;
}

.ds-entity-label {
  flex: 1;
  font-size: 0.875rem;
  font-weight: 500;
  color: #495057;
}

.ds-entity-value {
  font-size: 0.8rem;
  font-weight: 600;
  min-width: 2rem;
  text-align: center;
}

/* Empty state */
.ds-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 2.5rem 1rem;
  color: #adb5bd;
  font-size: 0.85rem;
  text-align: center;
}
.ds-empty i { font-size: 2rem; }
</style>
