<template>
  <div class="permission-manager">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4>
        <i class="fas fa-key mr-2"></i>
        Gestión de Permisos por Rol
      </h4>
      <button
        class="btn btn-primary"
        @click="showCreateModal = true"
      >
        <i class="fas fa-plus mr-2"></i>
        Nuevo Permiso
      </button>
    </div>

    <!-- Roles Cards -->
    <div class="row">
      <div
        v-for="role in roles"
        :key="role.id"
        class="col-lg-4 col-md-6 mb-4"
      >
        <div class="card role-card">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
              <i class="fas fa-user-tag mr-2"></i>
              {{ role.name }}
              <small class="badge badge-light ml-2">{{ role.prefix }}</small>
            </h5>
          </div>
          <div class="card-body">
            <div class="role-info mb-3">
              <span class="text-muted">
                {{ role.permissions?.length || 0 }} permisos asignados
              </span>
            </div>
            
            <!-- Permission Categories -->
            <div class="permission-categories">
              <div
                v-for="category in getPermissionCategories(role.permissions)"
                :key="category.name"
                class="category-section mb-3"
              >
                <h6 class="category-title">
                  <i :class="category.icon" class="mr-2"></i>
                  {{ category.name }}
                  <small class="text-muted">({{ category.permissions.length }})</small>
                </h6>
                
                <div class="permission-list">
                  <div
                    v-for="permission in category.permissions"
                    :key="permission.id"
                    class="permission-item d-flex justify-content-between align-items-center"
                  >
                    <div class="permission-info">
                      <span class="permission-name">{{ permission.name }}</span>
                      <small class="text-muted d-block">{{ permission.description }}</small>
                    </div>
                    <div class="permission-actions">
                      <button
                        class="btn btn-sm btn-outline-danger"
                        @click="removePermission(role.id, permission.id)"
                        title="Quitar permiso"
                      >
                        <i class="fas fa-times"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="card-footer bg-transparent">
              <button
                class="btn btn-sm btn-outline-primary w-100"
                @click="openRolePermissions(role)"
              >
                <i class="fas fa-edit mr-2"></i>
                Gestionar Permisos
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Role Permission Management Modal -->
    <Modal
      v-model="showRoleModal"
      :title="`Gestionar Permisos - ${currentRole?.name || ''}`"
      size="xl"
    >
      <div v-if="currentRole" class="role-permission-manager">
        <!-- Permission Categories with Checkboxes -->
        <div class="row">
          <div
            v-for="category in allPermissionCategories"
            :key="category.name"
            class="col-md-6 col-lg-4 mb-4"
          >
            <div class="category-card">
              <div class="card-header">
                <h6 class="mb-0">
                  <i :class="category.icon" class="mr-2"></i>
                  {{ category.name }}
                </h6>
              </div>
              <div class="card-body p-2">
                <div class="permission-checkboxes">
                  <div
                    v-for="permission in category.permissions"
                    :key="permission.id"
                    class="form-check mb-2"
                  >
                    <input
                      :id="`perm-${permission.id}`"
                      v-model="permission.selected"
                      class="form-check-input"
                      type="checkbox"
                      @change="togglePermission(permission.id)"
                    >
                    <label
                      :for="`perm-${permission.id}`"
                      class="form-check-label"
                    >
                      <div class="permission-label">
                        <span class="permission-name">{{ permission.name }}</span>
                        <small class="text-muted d-block">{{ permission.description }}</small>
                      </div>
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>
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
        <button
          type="button"
          class="btn btn-primary"
          @click="saveRolePermissions"
        >
          <i class="fas fa-save mr-2"></i>
          Guardar Cambios
        </button>
      </template>
    </Modal>

    <!-- Create Permission Modal -->
    <Modal
      v-model="showCreateModal"
      title="Crear Nuevo Permiso"
    >
      <form @submit.prevent="createPermission">
        <div class="form-group">
          <label for="permission-name">Nombre del Permiso</label>
          <input
            id="permission-name"
            v-model="newPermission.name"
            type="text"
            class="form-control"
            placeholder="ej: users.manage"
            required
          >
        </div>
        
        <div class="form-group">
          <label for="permission-guard">Guard</label>
          <select
            id="permission-guard"
            v-model="newPermission.guard_name"
            class="form-control"
          >
            <option value="web">Web</option>
            <option value="api">API</option>
          </select>
        </div>
        
        <div class="form-group">
          <label for="permission-description">Descripción</label>
          <textarea
            id="permission-description"
            v-model="newPermission.description"
            class="form-control"
            rows="3"
            placeholder="Descripción del permiso..."
          ></textarea>
        </div>
      </form>

      <template #footer>
        <button
          type="button"
          class="btn btn-secondary"
          @click="showCreateModal = false"
        >
          Cancelar
        </button>
        <button
          type="button"
          class="btn btn-primary"
          @click="createPermission"
        >
          <i class="fas fa-save mr-2"></i>
          Crear Permiso
        </button>
      </template>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import Modal from '@/js/Components/Common/Modal/Modal.vue'
import type { Permission, RoleWithPermissions } from '@/js/types/roles/admin/permission'

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
  (e: 'assignPermissions', roleId: string, permissionIds: string[]): void
  (e: 'revokePermissions', roleId: string, permissionIds: string[]): void
  (e: 'createPermission', data: any): void
}

// Props con valores por defecto
const props = withDefaults(defineProps<Props>(), {
  permissions: () => ({
    rows: [],
    pagination: { current_page: 1, per_page: 15, total: 0, last_page: 1, from: 1, to: 0 }
  }),
  roles: () => [],
  loading: false,
  selectedIds: () => [],
  currentRole: null
})
const emit = defineEmits<Emits>()

// Estado local
const showRoleModal = ref(false)
const showCreateModal = ref(false)
const currentRole = ref<RoleWithPermissions | null>(null)
const allPermissions = ref<Permission[]>([])
const newPermission = ref({
  name: '',
  guard_name: 'web',
  description: ''
})

// Categorías de permisos
const allPermissionCategories = computed(() => {
  const categories = new Map()
  
  allPermissions.value.forEach(permission => {
    const category = categorizePermission(permission.name)
    if (!categories.has(category.name)) {
      categories.set(category.name, {
        name: category.name,
        icon: category.icon,
        permissions: []
      })
    }
    categories.get(category.name).permissions.push({
      ...permission,
      selected: currentRole.value?.permissions.includes(permission.id) || false
    })
  })
  
  return Array.from(categories.values())
})

// Métodos
const categorizePermission = (permissionName: string) => {
  if (permissionName.includes('user') || permissionName.includes('admin')) {
    return { name: 'Usuarios', icon: 'fas fa-users' }
  } else if (permissionName.includes('commerce') || permissionName.includes('business')) {
    return { name: 'Comercios', icon: 'fas fa-store' }
  } else if (permissionName.includes('game') || permissionName.includes('bingo')) {
    return { name: 'Juegos', icon: 'fas fa-gamepad' }
  } else if (permissionName.includes('report') || permissionName.includes('analytics')) {
    return { name: 'Reportes', icon: 'fas fa-chart-bar' }
  } else if (permissionName.includes('system') || permissionName.includes('config')) {
    return { name: 'Sistema', icon: 'fas fa-cog' }
  } else {
    return { name: 'General', icon: 'fas fa-shield-alt' }
  }
}

const getPermissionCategories = (rolePermissions: string[]) => {
  const rolePerms = allPermissions.value.filter(p => rolePermissions.includes(p.id))
  const categories = new Map()
  
  rolePerms.forEach(permission => {
    const category = categorizePermission(permission.name)
    if (!categories.has(category.name)) {
      categories.set(category.name, {
        name: category.name,
        icon: category.icon,
        permissions: []
      })
    }
    categories.get(category.name).permissions.push(permission)
  })
  
  return Array.from(categories.values())
}

const openRolePermissions = (role: RoleWithPermissions) => {
  currentRole.value = role
  showRoleModal.value = true
}

const closeRoleModal = () => {
  showRoleModal.value = false
  currentRole.value = null
}

const togglePermission = (permissionId: string) => {
  if (!currentRole.value) return
  
  const permission = allPermissions.value.find(p => p.id === permissionId)
  if (!permission) return
  
  const hasPermission = currentRole.value.permissions.includes(permissionId)
  
  if (hasPermission && !permission.selected) {
    // Quitar permiso
    emit('revokePermissions', currentRole.value.id, [permissionId])
  } else if (!hasPermission && permission.selected) {
    // Agregar permiso
    emit('assignPermissions', currentRole.value.id, [permissionId])
  }
}

const removePermission = (roleId: string, permissionId: string) => {
  emit('revokePermissions', roleId, [permissionId])
}

const saveRolePermissions = () => {
  if (!currentRole.value) return
  
  const selectedPermissions = allPermissions.value
    .filter(p => p.selected)
    .map(p => p.id)
  
  emit('assignPermissions', currentRole.value.id, selectedPermissions)
  closeRoleModal()
}

const createPermission = () => {
  emit('createPermission', { ...newPermission.value })
  newPermission.value = { name: '', guard_name: 'web', description: '' }
  showCreateModal.value = false
}

// Cargar todos los permisos
onMounted(() => {
  allPermissions.value = props.permissions.rows.map(p => ({
    id: p.id,
    name: p.name,
    guard_name: p.guard_name,
    description: p.description,
    status: p.status,
    created_at: p.created_at,
    selected: false
  }))
})
</script>

<style scoped>
.permission-manager {
  padding: 1rem 0;
}

.role-card {
  height: 100%;
  transition: transform 0.2s, box-shadow 0.2s;
}

.role-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.category-title {
  font-size: 0.9rem;
  font-weight: 600;
  color: #495057;
  margin-bottom: 0.75rem;
}

.permission-item {
  padding: 0.5rem 0;
  border-bottom: 1px solid #f0f0f0;
}

.permission-item:last-child {
  border-bottom: none;
}

.permission-name {
  font-weight: 500;
  font-size: 0.85rem;
}

.permission-actions .btn {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
}

.category-card {
  border: 1px solid #dee2e6;
  border-radius: 0.5rem;
  height: 100%;
}

.category-card .card-header {
  background-color: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
  padding: 0.75rem 1rem;
}

.permission-checkboxes {
  max-height: 300px;
  overflow-y: auto;
}

.permission-label {
  margin-left: 0.5rem;
  cursor: pointer;
}

.permission-label .permission-name {
  font-weight: 500;
  font-size: 0.85rem;
}

.role-permission-manager {
  max-height: 60vh;
  overflow-y: auto;
}

@media (max-width: 768px) {
  .role-card {
    margin-bottom: 1rem;
  }
  
  .category-card {
    margin-bottom: 1rem;
  }
}
</style>
