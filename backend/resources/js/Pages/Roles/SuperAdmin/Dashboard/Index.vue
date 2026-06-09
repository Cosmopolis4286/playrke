<script setup lang="ts">
  /**
   * Página Dashboard → SuperAdmin
   * Stats: usuarios totales, administradores, sistemas activos, permisos.
   * Conteos de entidades por rol extendido.
   * Actividad reciente y salud del sistema.
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

  import { ref, onMounted }           from 'vue'
  import axios                         from 'axios'
  import { useSuperAdminDashboard }   from '@/js/composables/roles/superadmin/dashboard/useSuperAdminDashboard'

  // ==========================================================================
  // Types
  // ==========================================================================

  import type { DashboardSuperAdminPageProps } from '@/js/types/roles/superadmin/dashboard/dashboard-superadmin'

  interface RkeRoom {
    room_id:       string
    room_name:     string | null
    creator_name:  string
    total_sales:   number
    total_rke:     number
    rke_pct:       number
    first_sale_at: string | null
    last_sale_at:  string | null
    status:        string | null
  }

  interface RkeSummary {
    totals: { total_rke: number; total_sales: number; room_count: number }
    rooms:  RkeRoom[]
  }

  // ==========================================================================
  // Props (Inertia)
  // ==========================================================================

  const props = defineProps<DashboardSuperAdminPageProps>()

  // ==========================================================================
  // Composable
  // ==========================================================================

  const { statCards, sortedActivity, entityCounts, systemHealth, adminActivity } = useSuperAdminDashboard(props)

  // ==========================================================================
  // RKE Summary
  // ==========================================================================

  const rkeSummary    = ref<RkeSummary | null>(null)
  const rkeLoading    = ref(false)
  const rkeError      = ref<string | null>(null)

  async function loadRkeSummary() {
    rkeLoading.value = true
    rkeError.value   = null
    try {
      const { data } = await axios.get('/superadmin/rke-summary')
      rkeSummary.value = data
    } catch {
      rkeError.value = 'No se pudo cargar el resumen de comisiones RKE.'
    } finally {
      rkeLoading.value = false
    }
  }

  onMounted(loadRkeSummary)

  function formatUsd(v: number) {
    return new Intl.NumberFormat('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(v)
  }

  function formatDate(iso: string | null) {
    if (!iso) return '—'
    return new Intl.DateTimeFormat('es-VE', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(iso))
  }

  // ==========================================================================
  // Accesos rápidos
  // ==========================================================================

  const quickLinks: QuickLink[] = [
    {
      label:       'Administradores',
      description: 'Gestionar administradores del sistema',
      icon:        'fas fa-user-shield',
      href:        '/admins',
      variant:     'danger',
    },
    {
      label:       'Permisos',
      description: 'Configurar permisos de roles',
      icon:        'fas fa-key',
      href:        '/permissions',
      variant:     'warning',
    },
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
        :userName="props.userName ?? 'SuperAdmin'"
        roleName="Super Administrador"
        :message="props.period ? `Período: ${props.period}` : undefined"
        :actions="[
          { label: 'Ver administradores', icon: 'fas fa-user-shield', href: '/admins', variant: 'danger' },
          { label: 'Ver permisos', icon: 'fas fa-key', href: '/permissions', variant: 'warning' },
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
        subtitle="Gestión completa del sistema"
      >
        <DashboardQuickLinks :links="quickLinks" :columns="4" />
      </DashboardSection>

      <!-- Fila inferior -->
      <div class="row g-3">

        <!-- Actividad reciente -->
        <div class="col-12 col-lg-7">
          <DashboardSection
            title="Actividad reciente"
            icon="fas fa-history"
            subtitle="Últimos usuarios registrados"
            class="ds-scroll-section"
          >
            <DashboardActivityFeed :items="sortedActivity" :maxItems="10" class="ds-activity-compact" />
          </DashboardSection>
        </div>

        <!-- Conteos por entidad -->
        <div class="col-12 col-lg-5">
          <DashboardSection
            title="Entidades por rol"
            icon="fas fa-layer-group"
            subtitle="Totales registrados"
            class="ds-scroll-section"
          >
            <DashboardEntityList :items="entityCounts" />
          </DashboardSection>
        </div>

      </div>

      <!-- ── Comisiones RKE ──────────────────────────────────────────── -->
      <div class="rke-section">

        <!-- Header -->
        <div class="rke-section__header">
          <div class="rke-section__title">
            <i class="fas fa-percentage rke-section__icon"></i>
            <div>
              <h2 class="rke-section__h2">Comisiones RKE</h2>
              <p class="rke-section__sub">Ingresos de plataforma por sala — cobrado en cada venta de cartón</p>
            </div>
          </div>
          <button
            class="rke-section__refresh"
            :class="{ 'rke-section__refresh--spin': rkeLoading }"
            :disabled="rkeLoading"
            @click="loadRkeSummary"
          >
            <i class="fas fa-sync-alt"></i>
          </button>
        </div>

        <!-- KPIs -->
        <div v-if="rkeSummary" class="rke-kpis">
          <div class="rke-kpi rke-kpi--rke">
            <div class="rke-kpi__icon"><i class="fas fa-coins"></i></div>
            <div>
              <p class="rke-kpi__label">Total RKE cobrado</p>
              <p class="rke-kpi__value">$ {{ formatUsd(rkeSummary.totals.total_rke) }}</p>
            </div>
          </div>
          <div class="rke-kpi rke-kpi--sales">
            <div class="rke-kpi__icon"><i class="fas fa-shopping-cart"></i></div>
            <div>
              <p class="rke-kpi__label">Total ventas</p>
              <p class="rke-kpi__value">$ {{ formatUsd(rkeSummary.totals.total_sales) }}</p>
            </div>
          </div>
          <div class="rke-kpi rke-kpi--rooms">
            <div class="rke-kpi__icon"><i class="fas fa-door-open"></i></div>
            <div>
              <p class="rke-kpi__label">Salas con ventas</p>
              <p class="rke-kpi__value">{{ rkeSummary.totals.room_count }}</p>
            </div>
          </div>
        </div>

        <!-- Loading -->
        <div v-if="rkeLoading && !rkeSummary" class="rke-state rke-state--loading">
          <span class="rke-spinner"></span>
          <span>Cargando comisiones...</span>
        </div>

        <!-- Error -->
        <div v-else-if="rkeError" class="rke-state rke-state--error">
          <i class="fas fa-exclamation-triangle"></i>
          {{ rkeError }}
        </div>

        <!-- Tabla por sala -->
        <div v-else-if="rkeSummary && rkeSummary.rooms.length" class="rke-table-wrap">
          <table class="rke-table">
            <thead>
              <tr>
                <th>Sala</th>
                <th>Anfitrión</th>
                <th class="rke-table__num">Total vendido</th>
                <th class="rke-table__num">RKE cobrado</th>
                <th class="rke-table__num">% RKE</th>
                <th>Última venta</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="room in rkeSummary.rooms" :key="room.room_id">
                <td>
                  <span class="rke-table__code">{{ room.room_name ?? ('…' + room.room_id.slice(-6)) }}</span>
                </td>
                <td>{{ room.creator_name }}</td>
                <td class="rke-table__num">$ {{ formatUsd(room.total_sales) }}</td>
                <td class="rke-table__num rke-table__rke">$ {{ formatUsd(room.total_rke) }}</td>
                <td class="rke-table__num">
                  <span class="rke-badge">{{ room.rke_pct }}%</span>
                </td>
                <td class="rke-table__date">{{ formatDate(room.last_sale_at) }}</td>
                <td>
                  <span
                    class="rke-status"
                    :class="{
                      'rke-status--active':    room.status === 'ACTIVE',
                      'rke-status--progress':  room.status === 'IN_PROGRESS',
                      'rke-status--finished':  room.status === 'FINISHED',
                      'rke-status--other':     !['ACTIVE','IN_PROGRESS','FINISHED'].includes(room.status ?? ''),
                    }"
                  >
                    {{
                      room.status === 'ACTIVE'      ? 'Activa' :
                      room.status === 'IN_PROGRESS' ? 'En juego' :
                      room.status === 'FINISHED'    ? 'Finalizada' :
                      (room.status ?? '—')
                    }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty -->
        <div v-else-if="rkeSummary && !rkeSummary.rooms.length" class="rke-state rke-state--empty">
          <i class="fas fa-inbox"></i>
          Sin ventas registradas todavía.
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

/* Altura igual con scroll solo en el contenido (card-body) */
.ds-scroll-section :deep(.card-body) {
  max-height: 320px;
  overflow-y: auto;
}

/* Items de actividad más compactos y minimalistas */
.ds-activity-compact :deep(.activity-feed__item) {
  padding: 0.5rem 0.75rem;
  gap: 0.75rem;
  border: none;
  border-bottom: 1px solid #f0f0f0;
  border-radius: 0;
  background: transparent;
}

.ds-activity-compact :deep(.activity-feed__item:last-child) {
  border-bottom: none;
}

.ds-activity-compact :deep(.activity-feed__item:hover) {
  background: #f8f9fa;
  box-shadow: none;
  transform: none;
}

.ds-activity-compact :deep(.activity-feed__icon) {
  width: 32px !important;
  height: 32px !important;
  font-size: 0.75rem;
}

.ds-activity-compact :deep(.activity-feed__item p) {
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}

.ds-activity-compact :deep(.activity-feed__item .small) {
  font-size: 0.75rem;
}

/* ── Sección RKE ── */
.rke-section {
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(102, 126, 234, 0.08);
  padding: 1.5rem;
  margin-top: 0.25rem;
}

.rke-section__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.rke-section__title {
  display: flex;
  align-items: center;
  gap: 0.875rem;
}

.rke-section__icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}

.rke-section__h2 {
  font-size: 1.05rem;
  font-weight: 700;
  color: #1a202c;
  margin: 0 0 2px;
}

.rke-section__sub {
  font-size: 0.78rem;
  color: #718096;
  margin: 0;
}

.rke-section__refresh {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  color: #667eea;
  cursor: pointer;
  transition: all 0.15s;
  flex-shrink: 0;
}
.rke-section__refresh:hover:not(:disabled) { background: #eff0ff; border-color: #c7d2fe; }
.rke-section__refresh:disabled { opacity: 0.5; cursor: not-allowed; }
.rke-section__refresh--spin i { animation: spin 0.7s linear infinite; }

/* KPIs */
.rke-kpis {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
  margin-bottom: 1.25rem;
}

@media (max-width: 600px) {
  .rke-kpis { grid-template-columns: 1fr; }
}

.rke-kpi {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 0.875rem 1rem;
  border-radius: 10px;
}

.rke-kpi--rke   { background: #f5f3ff; }
.rke-kpi--sales { background: #f0fdf4; }
.rke-kpi--rooms { background: #eff6ff; }

.rke-kpi__icon {
  width: 38px;
  height: 38px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  flex-shrink: 0;
}
.rke-kpi--rke   .rke-kpi__icon { background: #ddd6fe; color: #7c3aed; }
.rke-kpi--sales .rke-kpi__icon { background: #bbf7d0; color: #16a34a; }
.rke-kpi--rooms .rke-kpi__icon { background: #bfdbfe; color: #2563eb; }

.rke-kpi__label {
  font-size: 0.73rem;
  color: #718096;
  margin: 0 0 2px;
  font-weight: 500;
}

.rke-kpi__value {
  font-size: 1.05rem;
  font-weight: 700;
  color: #1a202c;
  margin: 0;
}

/* Tabla */
.rke-table-wrap {
  overflow-x: auto;
  border-radius: 10px;
  border: 1px solid #eaecf0;
}

.rke-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.82rem;
}

.rke-table thead tr {
  background: #f8fafc;
  border-bottom: 1px solid #eaecf0;
}

.rke-table th {
  padding: 0.65rem 0.875rem;
  font-weight: 600;
  color: #718096;
  text-align: left;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  white-space: nowrap;
}

.rke-table td {
  padding: 0.65rem 0.875rem;
  border-bottom: 1px solid #f0f2f7;
  color: #2d3748;
  vertical-align: middle;
}

.rke-table tbody tr:last-child td { border-bottom: none; }
.rke-table tbody tr:hover td { background: #fafbff; }

.rke-table__num { text-align: right; }
.rke-table__rke { color: #7c3aed !important; font-weight: 600; }
.rke-table__code { font-family: monospace; font-size: 0.78rem; color: #4a5568; }
.rke-table__date { color: #a0aec0; font-size: 0.78rem; white-space: nowrap; }

.rke-badge {
  display: inline-block;
  background: #f5f3ff;
  color: #7c3aed;
  font-size: 0.73rem;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 20px;
}

.rke-status {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.72rem;
  font-weight: 600;
  padding: 2px 9px;
  border-radius: 20px;
}
.rke-status--active   { background: #dcfce7; color: #166534; }
.rke-status--progress { background: #dbeafe; color: #1e40af; }
.rke-status--finished { background: #f3e8ff; color: #6b21a8; }
.rke-status--other    { background: #f3f4f6; color: #374151; }

/* Estado vacío / carga / error */
.rke-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 2.5rem;
  color: #718096;
  font-size: 0.875rem;
}

.rke-state--error { color: #dc2626; }
.rke-state--empty i { font-size: 1.5rem; color: #c7d2fe; }

.rke-spinner {
  width: 20px;
  height: 20px;
  border: 3px solid #c7d2fe;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  flex-shrink: 0;
}
</style>
