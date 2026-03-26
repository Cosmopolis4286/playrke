<template>
  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Gestión de Permisos</h1>
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
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">
                  <i class="fas fa-key mr-2"></i>
                  Permisos del Sistema
                </h3>
                <div class="card-tools">
                  <span v-if="selectedCount > 0" class="badge badge-info mr-2">
                    {{ selectedCount }} seleccionados
                  </span>
                </div>
              </div>
              
              <div class="card-body">
                <PermissionTable
                  :permissions="permissions"
                  :roles="roles"
                  :loading="loading"
                  :selected-ids="selectedIds"
                  :current-role="currentRole"
                  @update:selected-ids="selectedIds = $event"
                  @refresh="loadPermissions"
                  @assign-permissions="handleAssignPermissions"
                  @revoke-permissions="handleRevokePermissions"
                  @create-permission="handleCreatePermission"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
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
  selectedCount,
  fetchPermissions,
  fetchRolesWithPermissions,
  assignPermissions,
  revokePermissions,
  createPermission,
  reset,
} = usePermissions()

// Métodos
const loadPermissions = async () => {
  try {
    await fetchPermissions()
  } catch (error) {
    console.error('Error loading permissions:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'No se pudieron cargar los permisos',
    })
  }
}

const loadRoles = async () => {
  try {
    await fetchRolesWithPermissions()
  } catch (error) {
    console.error('Error loading roles:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'No se pudieron cargar los roles',
    })
  }
}

const handlePageChange = async (page: number) => {
  try {
    await fetchPermissions(15) // TODO: Usar el per_page actual
  } catch (error) {
    console.error('Error changing page:', error)
  }
}

const handleAssignPermissions = async (roleId: string, permissionIds: string[]) => {
  try {
    await assignPermissions(roleId, permissionIds)
    await loadRoles() // Recargar roles para actualizar permisos
    Swal.fire({
      icon: 'success',
      title: 'Éxito',
      text: 'Permisos asignados correctamente',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
    })
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
    await loadRoles() // Recargar roles para actualizar permisos
    Swal.fire({
      icon: 'success',
      title: 'Éxito',
      text: 'Permisos revocados correctamente',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
    })
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
    await loadPermissions() // Recargar permisos
    Swal.fire({
      icon: 'success',
      title: 'Éxito',
      text: 'Permiso creado correctamente',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
    })
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
  // Cargar datos iniciales
  await loadPermissions()
  await loadRoles()
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
