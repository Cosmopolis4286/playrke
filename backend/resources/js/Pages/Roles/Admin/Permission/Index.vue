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
            <PermissionTable
              :permissions="permissions"
              :roles="roles"
              :loading="loading"
              :selected-ids="selectedIds"
              :current-role="currentRole"
              @update:selected-ids="selectedIds = $event"
              @refresh="loadPermissionsAndRoles"
              @assign-permissions="handleAssignPermissions"
              @revoke-permissions="handleRevokePermissions"
              @create-permission="handleCreatePermission"
            />
          </div>
        </div>
      </div>
    </section>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import PermissionTable from '@/js/Components/Roles/Admin/PermissionTable.vue'
import Breadcrumb from '@/js/Components/Common/Breadcrumb/Breadcrumb.vue'
import { usePermissions } from '@/js/composables/roles/admin/permission/usePermissions'
import Swal from 'sweetalert2'
import type { PermissionPageProps } from '@/js/types/roles/admin/permission-page'

// Props
const props = defineProps<PermissionPageProps>()

// Breadcrumb items
const breadcrumbItems = [
  { label: 'Dashboard', route: '/dashboard', icon: 'fas fa-home' },
  { label: 'Usuarios', route: '/admins', icon: 'fas fa-users' },
  { label: 'Permisos' }
]

// Composable
const {
  permissions,
  roles,
  loading,
  selectedIds,
  currentRole,
  fetchPermissions,
  fetchRolesWithPermissions,
  assignPermissions,
  revokePermissions,
  createPermission,
  reset,
} = usePermissions()

// Métodos
const loadPermissionsAndRoles = async () => {
  try {
    await Promise.all([fetchPermissions(), fetchRolesWithPermissions()])
  } catch (error) {
    console.error('Error loading data:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'No se pudieron cargar los datos',
    })
  }
}

const handleAssignPermissions = async (roleId: string, permissionIds: string[]) => {
  try {
    await assignPermissions(roleId, permissionIds)
    await fetchRolesWithPermissions()
  } catch (error) {
    console.error('Error assigning permissions:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'No se pudieron asignar los permisos',
    })
  }
}

const handleRevokePermissions = async (roleId: string, permissionIds: string[]) => {
  try {
    await revokePermissions(roleId, permissionIds)
    await fetchRolesWithPermissions()
  } catch (error) {
    console.error('Error revoking permissions:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'No se pudieron revocar los permisos',
    })
  }
}

const handleCreatePermission = async (data: { name: string, guard_name: string, description?: string }) => {
  try {
    await createPermission(data)
    await loadPermissionsAndRoles()
  } catch (error) {
    console.error('Error creating permission:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'No se pudo crear el permiso',
    })
  }
}

// Inicialización
onMounted(async () => {
  await loadPermissionsAndRoles()
})
</script>

<style scoped>
.content-wrapper {
  min-height: calc(100vh - 57px);
}

.card {
  box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
}

.card-header {
  background-color: #fff;
  border-bottom: 1px solid rgba(0,0,0,.125);
}

.card-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: #495057;
}
</style>
