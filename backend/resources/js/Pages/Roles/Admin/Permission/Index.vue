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
          <div class="col-sm-6">
            <Breadcrumb :items="breadcrumbItems" />
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">
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
</template>

<script setup lang="ts">
import PermissionMatrix from '@/js/Components/Roles/Admin/PermissionMatrix.vue'
import Breadcrumb from '@/js/Components/Common/Breadcrumb/Breadcrumb.vue'
import { usePermissions } from '@/js/composables/roles/admin/permission/usePermissions'
import Swal from 'sweetalert2'
import type { PermissionPageProps } from '@/js/types/roles/admin/permission-page'

const props = defineProps<PermissionPageProps>()

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

async function handleSave() {
  try {
    await saveChanges()
    Swal.fire({ icon: 'success', title: 'Éxito', text: 'Cambios guardados correctamente', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 })
  } catch {
    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron guardar los cambios' })
  }
}

async function handleCreate(data: { name: string; guard_name?: string; description?: string }) {
  try {
    await createPermission(data)
    Swal.fire({ icon: 'success', title: 'Éxito', text: 'Permiso creado correctamente', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 })
  } catch {
    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo crear el permiso' })
  }
}

async function handleDelete(permissionId: string) {
  try {
    await deletePermission(permissionId)
    Swal.fire({ icon: 'success', title: 'Éxito', text: 'Permiso eliminado correctamente', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 })
  } catch {
    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo eliminar el permiso' })
  }
}
</script>
