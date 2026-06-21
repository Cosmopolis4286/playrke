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
  import DashboardEntityList    from '@/js/Components/Common/Dashboard/DashboardEntityList.vue'
  import type { QuickLink }     from '@/js/Components/Common/Dashboard/DashboardQuickLinks.vue'

  // ==========================================================================
  // Composables
  // ==========================================================================

  import { useAdminDashboard }        from '@/js/composables/roles/admin/dashboard/useAdminDashboard'
  import { useAdminActivityRealtime } from '@/js/composables/common/dashboard/useAdminActivityRealtime'

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

  const { statCards, sortedActivity, entityCounts, prependActivity } = useAdminDashboard(props)
  const { isLive, newUsersToday, cardsSoldToday } = useAdminActivityRealtime(prependActivity)

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
        roleKey="admin"
        :message="props.period ? `Período: ${props.period}` : undefined"
        :actions="[
          { label: 'Usuarios', icon: 'fas fa-users', href: '/admin/users', variant: 'outline-primary' },
        ]"
      >
        <template #highlights>
          <div class="wb-stat" v-for="item in entityCounts?.slice(0, 2)" :key="item.label">
            <span class="wb-stat__label">{{ item.label }}</span>
            <span class="wb-stat__value">{{ item.value }}</span>
          </div>
          <div class="wb-stat">
            <span class="wb-stat__label">
              Nuevos hoy
              <span v-if="isLive" class="wb-live-dot" title="Escuchando en vivo"></span>
            </span>
            <span class="wb-stat__value wb-stat__value--accent">{{ newUsersToday }}</span>
          </div>
          <div class="wb-stat">
            <span class="wb-stat__label">Cartones vendidos (sesión)</span>
            <span class="wb-stat__value">{{ cardsSoldToday }}</span>
          </div>
        </template>
      </DashboardWelcomeBanner>

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
            <DashboardEntityList :items="entityCounts" />
          </DashboardSection>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
.wb-stat { display: flex; flex-direction: column; gap: 1px; }
.wb-stat__label { font-size: 0.68rem; color: #8a94a6; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; white-space: nowrap; display: inline-flex; align-items: center; gap: 4px; }
.wb-stat__value { font-size: 0.95rem; font-weight: 700; color: #1a202c; transition: color 0.2s; }
.wb-stat__value--accent { color: #667eea; }

.wb-live-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: #22c55e; box-shadow: 0 0 5px #22c55e;
  animation: wbLivePulse 1.8s ease-in-out infinite;
  display: inline-block;
}
@keyframes wbLivePulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50%       { opacity: 0.5; transform: scale(0.8); }
}

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
</style>
