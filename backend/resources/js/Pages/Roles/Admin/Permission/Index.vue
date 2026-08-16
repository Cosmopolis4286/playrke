<template>
    <div class="content-header">
      <div class="container-fluid">
        <div class="row">
          <div class="col-sm-6">
            <h1 class="m-0 font-weight-bold">Matriz de Permisos</h1>
            <p class="text-muted mb-0 mt-1">
              <i class="fas fa-lock text-danger mr-1"></i>
              Configura qué acciones puede realizar cada rol. Los permisos con candado son del sistema y no pueden modificarse.
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
          <div class="col-6 col-lg">
            <StatTile label="Permisos totales" :value="stats.total" icon="fa-key" />
          </div>
          <div class="col-6 col-lg">
            <StatTile label="Permisos del sistema" :value="stats.system" icon="fa-lock" variant="warning" />
          </div>
          <div class="col-6 col-lg">
            <StatTile label="Permisos huérfanos" :value="stats.orphans" icon="fa-exclamation-triangle" variant="danger" />
          </div>
          <div class="col-6 col-lg">
            <StatTile label="Roles activos" :value="stats.activeRoles" icon="fa-user-shield" variant="info" />
          </div>
          <div class="col-6 col-lg">
            <StatTile label="Módulos apagados" :value="stats.inactiveModules" icon="fa-power-off" variant="danger" />
          </div>
        </div>

        <ul class="nav nav-tabs section-tabs">
          <li class="nav-item">
            <button class="nav-link" :class="{ active: activeTab === 'matrix' }" @click="activeTab = 'matrix'">
              <i class="fas fa-table mr-2"></i>Matriz de Permisos
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link" :class="{ active: activeTab === 'hierarchy' }" @click="onHierarchyTabClick">
              <i class="fas fa-sitemap mr-2"></i>Jerarquía de Roles
            </button>
          </li>
        </ul>

        <div v-show="activeTab === 'matrix'">
          <div class="row">
            <div class="col-12">
              <ModulesPanel
                :modules="modules"
                @toggle="handleToggleModule"
              />
            </div>
          </div>
          <div class="row">
            <div class="col-12">
              <PermissionMatrix
                :permissions="permissions"
                :roles="roles"
                :loading="loading"
                :has-changes="hasChanges"
                :pending-change-count="pendingChangeCount"
                :has-permission="hasPermission"
                @toggle-permission="togglePermission"
                @reload="reload"
                @save="handleSave"
                @create-permission="handleCreate"
                @update-permission="handleUpdate"
                @delete-permission="handleDelete"
                @copy-permissions="handleCopy"
              />
            </div>
          </div>
        </div>

        <div v-show="activeTab === 'hierarchy'" class="row">
          <div class="col-12">
            <RoleHierarchyTree
              :tree="hierarchyTree"
              :loading="hierarchyLoading"
              :on-save="handleHierarchySave"
              @reload="fetchHierarchy"
            />
          </div>
        </div>
      </div>
    </section>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import PermissionMatrix from '@/js/Components/Roles/Admin/PermissionMatrix.vue'
import ModulesPanel from '@/js/Components/Roles/Admin/ModulesPanel.vue'
import RoleHierarchyTree from '@/js/Components/Roles/Admin/RoleHierarchyTree.vue'
import Breadcrumb from '@/js/Components/Common/Breadcrumb/Breadcrumb.vue'
import StatTile from '@/js/Components/Common/StatTile/StatTile.vue'
import { usePermissions } from '@/js/composables/roles/admin/permission/usePermissions'
import { useModules } from '@/js/composables/roles/admin/module/useModules'
import { useRoleHierarchy } from '@/js/composables/roles/admin/role/useRoleHierarchy'
import { useToast } from 'vue-toastification'
import type { PermissionPageProps } from '@/js/types/roles/admin/permission-page'
import type { RoleMetaUpdate } from '@/js/types/roles/admin/role'

const toast = useToast()

const props = defineProps<PermissionPageProps>()

const activeTab = ref<'matrix' | 'hierarchy'>('matrix')

const breadcrumbItems = [
  { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
  { label: 'Usuarios', route: '/admins', icon: 'fas fa-users' },
  { label: 'Permisos' },
]

const {
  permissions,
  roles,
  loading,
  hasChanges,
  pendingChangeCount,
  hasPermission,
  togglePermission,
  reload,
  saveChanges,
  createPermission,
  updatePermission,
  deletePermission,
  copyPermissions,
} = usePermissions({ permissions: props.permissions, roles: props.roles })

const { modules, toggleModuleStatus } = useModules(props.modules)

const { tree: hierarchyTree, loading: hierarchyLoading, fetchHierarchy, updateRoleMeta } = useRoleHierarchy()

let hierarchyLoaded = false
function onHierarchyTabClick() {
  activeTab.value = 'hierarchy'
  if (!hierarchyLoaded) {
    hierarchyLoaded = true
    fetchHierarchy()
  }
}

async function handleHierarchySave(roleId: string, attributes: RoleMetaUpdate) {
  await updateRoleMeta(roleId, attributes)
}

const stats = computed(() => ({
  total: permissions.value.rows?.length ?? 0,
  system: permissions.value.rows?.filter(p => p.is_system).length ?? 0,
  orphans: permissions.value.rows?.filter(p => p.is_orphan).length ?? 0,
  activeRoles: roles.value.length,
  inactiveModules: modules.value.filter(m => !m.is_active).length,
}))

async function handleToggleModule(moduleId: string) {
  try {
    await toggleModuleStatus(moduleId)
  } catch {
    toast.error('No se pudo actualizar el módulo')
  }
}

async function handleSave() {
  try {
    await saveChanges()
    toast.success('Cambios guardados correctamente')
  } catch {
    toast.error('No se pudieron guardar los cambios')
  }
}

async function handleCreate(data: { name: string; guard_name?: string; description?: string }) {
  try {
    await createPermission(data)
    toast.success('Permiso creado correctamente')
  } catch {
    toast.error('No se pudo crear el permiso')
  }
}

async function handleUpdate(permissionId: string, data: { name: string; guard_name?: string; description?: string }) {
  try {
    await updatePermission(permissionId, data)
    toast.success('Permiso actualizado correctamente')
  } catch {
    toast.error('No se pudo actualizar el permiso')
  }
}

async function handleDelete(permissionId: string) {
  try {
    await deletePermission(permissionId)
    toast.success('Permiso eliminado correctamente')
  } catch {
    toast.error('No se pudo eliminar el permiso')
  }
}

async function handleCopy(sourceRoleId: string, targetRoleId: string, replace: boolean) {
  try {
    const message = await copyPermissions(sourceRoleId, targetRoleId, replace)
    toast.success(message)
  } catch {
    toast.error('No se pudieron copiar los permisos')
  }
}
</script>

<style scoped>
.stats-row {
  margin-bottom: 1rem;
  row-gap: 0.75rem;
}

.section-tabs {
  border-bottom: 1px solid var(--ds-border);
  margin-bottom: 1rem;
  gap: 0.25rem;
}
.section-tabs .nav-link {
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  color: var(--ds-text-muted);
  font-size: 0.88rem;
  font-weight: 600;
  padding: 0.6rem 0.9rem;
  cursor: pointer;
}
.section-tabs .nav-link:hover { color: var(--ds-text); }
.section-tabs .nav-link.active {
  color: var(--ds-primary, #2563eb);
  border-bottom-color: var(--ds-primary, #2563eb);
}
</style>
