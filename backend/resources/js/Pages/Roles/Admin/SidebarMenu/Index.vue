<template>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row">
          <div class="col-sm-6">
            <h1 class="m-0 font-weight-bold">Menú del Sidebar</h1>
            <p class="text-muted mb-0 mt-1">
              <i class="fas fa-info-circle text-primary mr-1"></i>
              Configura qué items y submenús del sidebar puede ver cada rol.
            </p>
          </div>
          <div class="col-sm-6 d-flex flex-column align-items-sm-end">
            <Breadcrumb :items="breadcrumbItems" />
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">
        <div class="row stats-row">
          <div class="col-6 col-md-4">
            <StatTile label="Items del menú" :value="stats.totalItems" icon="fa-bars" />
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
            <SidebarMenuVisibilityMatrix
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
import SidebarMenuVisibilityMatrix from '@/js/Components/Roles/Admin/SidebarMenuVisibilityMatrix.vue'
import Breadcrumb from '@/js/Components/Common/Breadcrumb/Breadcrumb.vue'
import StatTile from '@/js/Components/Common/StatTile/StatTile.vue'
import { useSidebarMenuVisibility } from '@/js/composables/roles/admin/sidebarMenu/useSidebarMenuVisibility'
import { useToast } from 'vue-toastification'
import type { SidebarMenuPageProps } from '@/js/types/roles/admin/sidebar-menu'

const toast = useToast()

const props = defineProps<SidebarMenuPageProps>()

const breadcrumbItems = [
  { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
  { label: 'Sistema', icon: 'fas fa-cogs' },
  { label: 'Menú del Sidebar' },
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
} = useSidebarMenuVisibility({ items: props.items, roles: props.roles, visibility: props.visibility })

const stats = computed(() => ({
  totalItems: items.value.length,
  exceptions: Object.values(visibility.value).reduce((total, roleMap) => total + Object.keys(roleMap).length, 0),
  activeRoles: roles.value.length,
}))

async function handleSave() {
  try {
    await saveChanges()
    toast.success('Visibilidad del menú actualizada')
  } catch {
    toast.error('No se pudo actualizar la visibilidad del menú')
  }
}
</script>

<style scoped>
.stats-row {
  margin-bottom: 1rem;
  row-gap: 0.75rem;
}
</style>
