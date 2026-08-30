<template>
    <PageHeader
      icon="fa-table-cells"
      title="Widgets del Dashboard"
      subtitle="Configura qué estadísticas, conteos y accesos rápidos puede ver cada rol en su dashboard."
      :breadcrumb-items="breadcrumbItems"
    />

    <section class="content">
      <div class="container-fluid">
        <div class="row stats-row">
          <div class="col-6 col-md-4">
            <StatTile label="Widgets configurables" :value="stats.totalItems" icon="fa-table-cells" />
          </div>
          <div class="col-6 col-md-4">
            <StatTile label="Excepciones configuradas" :value="stats.exceptions" icon="fa-eye-slash" variant="warning" />
          </div>
          <div class="col-6 col-md-4">
            <StatTile label="Roles activos" :value="stats.activeRoles" icon="fa-user-shield" variant="info" />
          </div>
        </div>

        <div class="row">
          <div class="col-12">
            <DashboardWidgetVisibilityMatrix
              :items="items"
              :roles="roles"
              :loading="loading"
              :has-changes="hasChanges"
              :pending-change-count="pendingChangeCount"
              :is-visible="isVisible"
              @toggle-visibility="toggleVisibility"
              @reload="reload"
              @save="handleSave"
            />
          </div>
        </div>
      </div>
    </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import DashboardWidgetVisibilityMatrix from '@/js/Components/Roles/Admin/DashboardWidgetVisibilityMatrix.vue'
import PageHeader from '@/js/Components/Common/PageHeader/PageHeader.vue'
import StatTile from '@/js/Components/Common/StatTile/StatTile.vue'
import { useDashboardWidgetVisibility } from '@/js/composables/roles/admin/dashboardWidgets/useDashboardWidgetVisibility'
import { useToast } from 'vue-toastification'
import type { DashboardWidgetsPageProps } from '@/js/types/roles/admin/dashboard-widgets'

const toast = useToast()

const props = defineProps<DashboardWidgetsPageProps>()

const breadcrumbItems = [
  { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
  { label: 'Sistema', icon: 'fas fa-cogs' },
  { label: 'Widgets del Dashboard' },
]

const {
  items,
  roles,
  visibility,
  loading,
  hasChanges,
  pendingChangeCount,
  isVisible,
  toggleVisibility,
  reload,
  saveChanges,
} = useDashboardWidgetVisibility({ items: props.items, roles: props.roles, visibility: props.visibility })

const stats = computed(() => ({
  totalItems: items.value.length,
  exceptions: Object.values(visibility.value).reduce((total, roleMap) => total + Object.keys(roleMap).length, 0),
  activeRoles: roles.value.length,
}))

async function handleSave() {
  try {
    await saveChanges()
    toast.success('Visibilidad del dashboard actualizada')
  } catch {
    toast.error('No se pudo actualizar la visibilidad del dashboard')
  }
}
</script>

<style scoped>
.stats-row {
  margin-bottom: 1rem;
  row-gap: 0.75rem;
}
</style>
