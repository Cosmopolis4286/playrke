<template>
  <div class="permission-table">
    <!-- Tabla de Permisos -->
    <TableWithFilterBridge
      :columns="tableColumns"
      :data="permissions.rows"
      :pagination="permissions.pagination || { current_page: 1, per_page: 15, total: permissions.rows?.length || 0, last_page: 1, from: 1, to: permissions.rows?.length || 0 }"
      :loading="loading"
      :extra-buttons="extraButtons"
      :show-select="true"
      :selected-ids="selectedIds"
      @update:selected-ids="$emit('update:selectedIds', $event)"
      @refresh="refreshData"
      @page-changed="handlePageChange"
      @extra-button-click="handleExtraButtonClick"
    />

    <!-- Modal de Asignación de Permisos por Rol -->
    <Modal
      v-if="showRoleModal"
      :show="showRoleModal"
      @close="closeRoleModal"
      title="Gestionar Permisos por Rol"
      size="lg"
    >
      <div class="role-permission-manager">
        <!-- Selector de Rol -->
        <div class="mb-4">
          <label class="form-label">Seleccionar Rol:</label>
          <select
            v-model="selectedRoleId"
            class="form-select"
            @change="loadRolePermissions"
          >
            <option value="">-- Seleccione un rol --</option>
            <option
              v-for="role in roles"
              :key="role.id"
              :value="role.id"
            >
              {{ role.name }} ({{ role.prefix }})
            </option>
          </select>
        </div>

        <!-- Lista de Permisos del Rol -->
        <div v-if="currentRole" class="permission-list">
          <h6>Permisos asignados a {{ currentRole.roleName }}:</h6>
          <div class="row">
            <div
              v-for="permission in allPermissions"
              :key="permission.id"
              class="col-md-6 mb-2"
            >
              <div class="form-check">
                <input
                  :id="`permission-${permission.id}`"
                  v-model="permission.selected"
                  class="form-check-input"
                  type="checkbox"
                  @change="togglePermission(permission.id)"
                >
                <label
                  :for="`permission-${permission.id}`"
                  class="form-check-label"
                >
                  {{ permission.name }}
                  <small class="text-muted d-block">{{ permission.description }}</small>
                </label>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="text-center text-muted">
          <p>Seleccione un rol para ver y gestionar sus permisos</p>
        </div>
      </div>

      <template #footer>
        <button
          type="button"
          class="btn btn-secondary"
          @click="closeRoleModal"
        >
          Cerrar
        </button>
      </template>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import TableWithFilterBridge from '@/js/Components/Common/Table/TableWithFilterBridge.vue'
import Modal from '@/js/Components/Common/Modal/Modal.vue'
import type { ExtraButton } from '@js/types/common/table/extra-buttons'
import type { Permission, RoleWithPermissions } from '@js/types/roles/admin/permission'

interface Props {
  permissions: {
    rows: any[]
    pagination: any
  }
  roles: RoleWithPermissions[]
  loading: boolean
  selectedIds: string[]
  currentRole?: any
}

interface Emits {
  (e: 'update:selectedIds', ids: string[]): void
  (e: 'refresh'): void
  (e: 'pageChanged', page: number): void
  (e: 'assignPermissions', roleId: string, permissionIds: string[]): void
  (e: 'revokePermissions', roleId: string, permissionIds: string[]): void
  (e: 'loadRolePermissions', roleId: string): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Estado local
const showRoleModal = ref(false)
const selectedRoleId = ref('')
const allPermissions = ref<Permission[]>([])

// Configuración de la tabla
const tableColumns = [
  { key: 'name', label: 'Nombre', sortable: true },
  { key: 'guard_name', label: 'Guard', sortable: true },
  { key: 'description', label: 'Descripción', sortable: false },
  { key: 'roles_badge', label: 'Roles', sortable: false },
  { key: 'status_badge', label: 'Estado', sortable: false },
  { key: 'created_at', label: 'Creado', sortable: true },
]

// Botones extra
const extraButtons = computed<ExtraButton[]>(() => [
  {
    key: 'manage-role-permissions',
    label: 'Gestionar por Rol',
    icon: 'fas fa-user-shield',
    class: 'btn-primary',
    action: 'openRoleModal',
  },
  {
    key: 'create-permission',
    label: 'Nuevo Permiso',
    icon: 'fas fa-plus',
    class: 'btn-success',
    action: 'createPermission',
  },
])

// Métodos
const refreshData = () => {
  emit('refresh')
}

const handlePageChange = (page: number) => {
  emit('pageChanged', page)
}

const handleExtraButtonClick = (action: string) => {
  switch (action) {
    case 'openRoleModal':
      openRoleModal()
      break
    case 'createPermission':
      // TODO: Implementar creación de permisos
      console.log('Crear permiso')
      break
  }
}

const openRoleModal = () => {
  showRoleModal.value = true
  loadAllPermissions()
}

const closeRoleModal = () => {
  showRoleModal.value = false
  selectedRoleId.value = ''
}

const loadAllPermissions = async () => {
  try {
    // Extraer permisos únicos de las filas de la tabla
    const uniquePermissions = new Map<string, Permission>()
    
    props.permissions.rows.forEach((row: any) => {
      if (row.original_data) {
        const permission = row.original_data
        uniquePermissions.set(permission.id, {
          id: permission.id,
          name: permission.name,
          guard_name: permission.guard_name,
          description: permission.description,
          status: permission.status,
          created_at: permission.created_at,
          updated_at: permission.updated_at,
          selected: false,
        })
      }
    })
    
    allPermissions.value = Array.from(uniquePermissions.values())
  } catch (error) {
    console.error('Error loading all permissions:', error)
  }
}

const loadRolePermissions = async () => {
  if (!selectedRoleId.value) return
  
  emit('loadRolePermissions', selectedRoleId.value)
}

const togglePermission = (permissionId: string) => {
  if (!selectedRoleId.value || !props.currentRole) return
  
  const permission = allPermissions.value.find(p => p.id === permissionId)
  if (!permission) return
  
  const hasPermission = props.currentRole.permissions.some((p: any) => p.id === permissionId)
  
  if (hasPermission && !permission.selected) {
    // Quitar permiso
    emit('revokePermissions', selectedRoleId.value, [permissionId])
  } else if (!hasPermission && permission.selected) {
    // Agregar permiso
    emit('assignPermissions', selectedRoleId.value, [permissionId])
  }
}

// Watch para actualizar el estado de los checkboxes cuando cambia el rol actual
watch(() => props.currentRole, (newRole) => {
  if (!newRole) return
  
  allPermissions.value.forEach(permission => {
    permission.selected = newRole.permissions.some((p: any) => p.id === permission.id)
  })
}, { deep: true })
</script>

<style scoped>
.permission-table {
  /* Estilos específicos para la tabla de permisos */
}

.role-permission-manager {
  max-height: 500px;
  overflow-y: auto;
}

.permission-list {
  max-height: 400px;
  overflow-y: auto;
}

.permission-list .form-check {
  padding: 0.5rem;
  border-radius: 0.25rem;
  transition: background-color 0.2s;
}

.permission-list .form-check:hover {
  background-color: #f8f9fa;
}

.permission-list .form-check-label {
  cursor: pointer;
}

.permission-list .form-check-label small {
  font-size: 0.875rem;
  margin-top: 0.25rem;
}
</style>
