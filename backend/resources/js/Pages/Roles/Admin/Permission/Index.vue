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
            <button class="btn btn-outline-primary btn-sm mt-2" @click="showRoleManager = true">
              <i class="fas fa-user-shield mr-2"></i>Gestionar roles
            </button>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">
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
              @delete-permission="handleDelete"
            />
          </div>
        </div>
      </div>
    </section>

    <RoleManagerModal v-model="showRoleManager" />
</template>

<script setup lang="ts">
import { ref } from 'vue'
import PermissionMatrix from '@/js/Components/Roles/Admin/PermissionMatrix.vue'
import ModulesPanel from '@/js/Components/Roles/Admin/ModulesPanel.vue'
import RoleManagerModal from '@/js/Components/Roles/Admin/RoleManagerModal.vue'
import Breadcrumb from '@/js/Components/Common/Breadcrumb/Breadcrumb.vue'
import { usePermissions } from '@/js/composables/roles/admin/permission/usePermissions'
import { useModules } from '@/js/composables/roles/admin/module/useModules'
import { useToast } from 'vue-toastification'
import type { PermissionPageProps } from '@/js/types/roles/admin/permission-page'

const toast = useToast()

const props = defineProps<PermissionPageProps>()

const showRoleManager = ref(false)

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
  deletePermission,
} = usePermissions({ permissions: props.permissions, roles: props.roles })

const { modules, toggleModuleStatus } = useModules(props.modules)

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

async function handleDelete(permissionId: string) {
  try {
    await deletePermission(permissionId)
    toast.success('Permiso eliminado correctamente')
  } catch {
    toast.error('No se pudo eliminar el permiso')
  }
}
</script>
