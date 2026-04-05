<template>
  <div class="permission-matrix">
    <!-- Action Buttons -->
    <div class="d-flex justify-content-start mb-3">
      <div class="d-flex gap-2">
        <button
          class="btn btn-primary"
          @click="showCreateModal = true"
        >
          <i class="fas fa-plus mr-2"></i>
          Nuevo permiso
        </button>
        <button
          class="btn btn-outline-primary"
          @click="$emit('refresh')"
        >
          <i class="fas fa-sync-alt mr-2"></i>
          Recargar
        </button>
        <button
          class="btn btn-success"
          @click="saveChanges"
          :disabled="!hasChanges || saving"
        >
          <i class="fas fa-save mr-2"></i>
          {{ saving ? 'Guardando...' : 'Guardar cambios' }}
        </button>
      </div>
    </div>

    <!-- Permission Matrix Table -->
    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover permission-table mb-0">
            <thead>
              <tr>
                <th class="permission-col">Permiso</th>
                <th class="module-col">Módulo</th>
                <th
                  v-for="role in displayRoles"
                  :key="role.id"
                  class="role-col text-center"
                >
                  <span
                    class="badge"
                    :class="getRoleBadgeClass(role.name)"
                  >
                    {{ formatRoleName(role.name) }}
                  </span>
                </th>
                <th class="actions-col"></th>
              </tr>
            </thead>
            <tbody>
              <template v-for="category in permissionCategories" :key="category.name">
                <!-- Category Header Row -->
                <tr class="category-header">
                  <td colspan="100%">
                    <div class="d-flex align-items-center">
                      <i class="fas fa-circle text-danger mr-2" style="font-size: 8px;"></i>
                      <span class="font-weight-bold text-uppercase">{{ category.name }}</span>
                      <span class="text-muted ml-2">({{ category.permissions.length }})</span>
                      <span v-if="category.plan" class="text-muted ml-2 font-italic">{{ category.plan }}</span>
                    </div>
                  </td>
                </tr>

                <!-- Permission Rows -->
                <tr
                  v-for="permission in category.permissions"
                  :key="permission.id"
                  class="permission-row"
                >
                  <td class="permission-col">
                    <div class="d-flex align-items-center">
                      <span class="font-weight-medium">{{ permission.displayName || formatPermissionName(permission.name) }}</span>
                      <i
                        v-if="permission.isSystem"
                        class="fas fa-lock text-danger ml-2"
                        title="Permiso del sistema - No puede modificarse"
                      ></i>
                    </div>
                    <small class="text-muted d-block permission-key">{{ permission.name }}</small>
                  </td>
                  <td class="module-col">
                    <span class="text-muted">{{ category.moduleName }}</span>
                  </td>
                  <td
                    v-for="role in displayRoles"
                    :key="role.id"
                    class="role-col text-center"
                  >
                    <div class="custom-checkbox-wrapper">
                      <input
                        type="checkbox"
                        class="custom-checkbox"
                        :checked="hasPermission(role.id, permission.id)"
                        :disabled="permission.isSystem || saving"
                        @change="togglePermission(role.id, permission.id)"
                      />
                    </div>
                  </td>
                  <td class="actions-col text-center">
                    <button
                      v-if="!permission.isSystem"
                      class="btn btn-sm btn-link text-danger"
                      @click="deletePermission(permission.id)"
                      title="Eliminar permiso"
                    >
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>
    </div>

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
            placeholder="ej: users.create"
            required
          >
          <small class="form-text text-muted">Use el formato: modulo.accion (ej: users.create)</small>
        </div>

        <div class="form-group">
          <label for="permission-display">Nombre a Mostrar</label>
          <input
            id="permission-display"
            v-model="newPermission.displayName"
            type="text"
            class="form-control"
            placeholder="ej: Crear Usuarios"
          >
        </div>

        <div class="form-group">
          <label for="permission-module">Módulo</label>
          <select
            id="permission-module"
            v-model="newPermission.module"
            class="form-control"
          >
            <option value="">Seleccione un módulo</option>
            <option v-for="mod in availableModules" :key="mod" :value="mod">{{ mod }}</option>
          </select>
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
import { ref, computed, watch } from 'vue'
import Modal from '@/js/Components/Common/Modal/Modal.vue'
import type { Permission, RoleWithPermissions } from '@/js/types/roles/admin/permission'
import axios from 'axios'
import Swal from 'sweetalert2'

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

const emit = defineEmits<{
  (e: 'refresh'): void
  (e: 'update:selectedIds', ids: string[]): void
  (e: 'assignPermissions', roleId: string, permissionIds: string[]): void
  (e: 'revokePermissions', roleId: string, permissionIds: string[]): void
  (e: 'createPermission', data: any): void
}>()

// State
const showCreateModal = ref(false)
const saving = ref(false)
const pendingChanges = ref<Map<string, Set<string>>>(new Map()) // roleId -> Set of permissionIds to toggle

const newPermission = ref({
  name: '',
  displayName: '',
  module: '',
  guard_name: 'web',
  description: ''
})

// Filter to show only relevant roles in the matrix
const displayRoles = computed(() => {
  const roleOrder = ['superadmin', 'admin', 'commerce', 'seller', 'treasurer', 'player']
  return props.roles
    .filter(role => roleOrder.includes(role.name.toLowerCase()))
    .sort((a, b) => {
      const indexA = roleOrder.indexOf(a.name.toLowerCase())
      const indexB = roleOrder.indexOf(b.name.toLowerCase())
      return indexA - indexB
    })
})

// Available modules for new permission
const availableModules = computed(() => {
  const modules = new Set<string>()
  props.permissions.rows?.forEach(p => {
    const module = getModuleFromPermission(p.name)
    modules.add(module)
  })
  return Array.from(modules).sort()
})

// Group permissions by category
const permissionCategories = computed(() => {
  const categories = new Map<string, {
    name: string
    moduleName: string
    plan?: string
    permissions: (Permission & { displayName: string; isSystem: boolean })[]
  }>()

  props.permissions.rows?.forEach(permission => {
    const category = categorizePermission(permission.name)
    const categoryKey = category.name

    if (!categories.has(categoryKey)) {
      categories.set(categoryKey, {
        name: category.name,
        moduleName: category.module,
        plan: category.plan,
        permissions: []
      })
    }

    categories.get(categoryKey)!.permissions.push({
      ...permission,
      displayName: formatPermissionName(permission.name),
      isSystem: isSystemPermission(permission.name)
    })
  })

  // Sort categories by name
  return Array.from(categories.values()).sort((a, b) => a.name.localeCompare(b.name))
})

const hasChanges = computed(() => {
  return pendingChanges.value.size > 0
})

// Methods
function getModuleFromPermission(name: string): string {
  const parts = name.split('.')
  return parts[0]?.charAt(0).toUpperCase() + parts[0]?.slice(1) || 'General'
}

function categorizePermission(name: string): { name: string; module: string; plan?: string } {
  const module = getModuleFromPermission(name)

  if (name.includes('analytics') || name.includes('report') || name.includes('stats')) {
    return { name: 'ANALYTICS', module, plan: 'Sin plan' }
  } else if (name.includes('schedule') || name.includes('booking') || name.includes('agend')) {
    return { name: 'AGENDAMIENTOS', module, plan: 'Sin plan' }
  } else if (name.includes('user') || name.includes('admin')) {
    return { name: 'USUARIOS', module }
  } else if (name.includes('commerce') || name.includes('business') || name.includes('store')) {
    return { name: 'COMERCIOS', module }
  } else if (name.includes('game') || name.includes('bingo') || name.includes('play')) {
    return { name: 'JUEGOS', module }
  } else if (name.includes('finance') || name.includes('payment') || name.includes('transaction')) {
    return { name: 'FINANZAS', module }
  } else if (name.includes('system') || name.includes('config') || name.includes('setting')) {
    return { name: 'SISTEMA', module }
  } else if (name.includes('support') || name.includes('ticket') || name.includes('help')) {
    return { name: 'SOPORTE', module }
  }

  return { name: module.toUpperCase(), module }
}

function formatPermissionName(name: string): string {
  const parts = name.split('.')
  const action = parts[parts.length - 1]
  const actionMap: Record<string, string> = {
    'view': 'Ver',
    'create': 'Crear',
    'edit': 'Editar',
    'delete': 'Eliminar',
    'manage': 'Gestionar',
    'list': 'Listar',
    'show': 'Ver detalles',
    'update': 'Actualizar',
    'store': 'Guardar',
    'destroy': 'Eliminar',
    'index': 'Listar',
    'config': 'Configurar',
    'assign': 'Asignar',
    'revoke': 'Revocar',
    'toggle': 'Cambiar estado',
    'reset': 'Restablecer',
    'generate': 'Generar',
    'export': 'Exportar',
    'import': 'Importar'
  }

  const actionText = actionMap[action] || action.charAt(0).toUpperCase() + action.slice(1)
  const moduleText = parts[0]?.charAt(0).toUpperCase() + parts[0]?.slice(1) || ''

  if (parts.length === 2) {
    return `${actionText} ${moduleText}`
  }

  return actionText
}

function isSystemPermission(name: string): boolean {
  const systemPatterns = [
    'superadmin',
    'roles.manage',
    'permissions.manage',
    'system.config',
    'admin.users'
  ]
  return systemPatterns.some(pattern => name.includes(pattern))
}

function formatRoleName(name: string): string {
  const roleMap: Record<string, string> = {
    'superadmin': 'SuperAdmin',
    'admin': 'Admin',
    'commerce': 'Comercio',
    'seller': 'Freelancer',
    'treasurer': 'Tesorero',
    'player': 'Normal'
  }
  return roleMap[name.toLowerCase()] || name
}

function getRoleBadgeClass(name: string): string {
  const classMap: Record<string, string> = {
    'superadmin': 'badge-purple',
    'admin': 'badge-blue',
    'commerce': 'badge-green',
    'seller': 'badge-yellow',
    'treasurer': 'badge-info',
    'player': 'badge-secondary'
  }
  return classMap[name.toLowerCase()] || 'badge-secondary'
}

function hasPermission(roleId: string, permissionId: string): boolean {
  // Check pending changes first
  const roleChanges = pendingChanges.value.get(roleId)
  if (roleChanges) {
    if (roleChanges.has(permissionId)) {
      // Toggle the current state
      const role = props.roles.find(r => r.id === roleId)
      return !role?.permissions.includes(permissionId)
    }
  }

  const role = props.roles.find(r => r.id === roleId)
  return role?.permissions.includes(permissionId) || false
}

function togglePermission(roleId: string, permissionId: string) {
  if (!pendingChanges.value.has(roleId)) {
    pendingChanges.value.set(roleId, new Set())
  }

  const roleChanges = pendingChanges.value.get(roleId)!

  if (roleChanges.has(permissionId)) {
    roleChanges.delete(permissionId)
    if (roleChanges.size === 0) {
      pendingChanges.value.delete(roleId)
    }
  } else {
    roleChanges.add(permissionId)
  }
}

async function saveChanges() {
  saving.value = true
  try {
    const promises: Promise<any>[] = []

    pendingChanges.value.forEach((permissionIds, roleId) => {
      const role = props.roles.find(r => r.id === roleId)
      if (!role) return

      const toAssign: string[] = []
      const toRevoke: string[] = []

      permissionIds.forEach(permId => {
        const currentlyHas = role.permissions.includes(permId)
        if (currentlyHas) {
          toRevoke.push(String(permId))
        } else {
          toAssign.push(String(permId))
        }
      })

      if (toAssign.length > 0) {
        promises.push(axios.post(`/permissions/roles/${roleId}/assign`, {
          permission_ids: toAssign
        }))
      }
      if (toRevoke.length > 0) {
        promises.push(axios.post(`/permissions/roles/${roleId}/revoke`, {
          permission_ids: toRevoke
        }))
      }
    })

    await Promise.all(promises)

    pendingChanges.value.clear()
    emit('refresh')

    Swal.fire({
      icon: 'success',
      title: 'Éxito',
      text: 'Cambios guardados correctamente',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000
    })
  } catch (error: any) {
    console.error('Error saving changes:', error)
    const errorMsg = error.response?.data?.message || error.response?.data?.error || 'No se pudieron guardar los cambios'
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: errorMsg
    })
  } finally {
    saving.value = false
  }
}

async function deletePermission(permissionId: string) {
  const result = await Swal.fire({
    title: 'Confirmar Eliminación',
    text: '¿Está seguro de que desea eliminar este permiso? Esta acción no se puede deshacer.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc3545',
    cancelButtonColor: '#6c757d',
    confirmButtonText: '<i class="fas fa-trash mr-2"></i>Eliminar',
    cancelButtonText: 'Cancelar',
    reverseButtons: true
  })

  if (!result.isConfirmed) return

  try {
    await axios.delete(`/permissions/${permissionId}`)
    emit('refresh')

    Swal.fire({
      icon: 'success',
      title: 'Éxito',
      text: 'Permiso eliminado correctamente',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000
    })
  } catch (error) {
    console.error('Error deleting permission:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'No se pudo eliminar el permiso'
    })
  }
}

function createPermission() {
  if (!newPermission.value.name) return

  emit('createPermission', {
    name: newPermission.value.name,
    guard_name: newPermission.value.guard_name,
    description: newPermission.value.description
  })

  newPermission.value = {
    name: '',
    displayName: '',
    module: '',
    guard_name: 'web',
    description: ''
  }
  showCreateModal.value = false
}

// Watch for permission changes and reset pending changes
watch(() => props.permissions.rows, () => {
  pendingChanges.value.clear()
}, { deep: true })
</script>

<style scoped>
.permission-matrix {
  width: 100%;
}

.permission-table {
  font-size: 0.9rem;
  width: 100%;
  min-width: 900px;
}

.permission-table thead th {
  background-color: #f8f9fa;
  border-bottom: 2px solid #dee2e6;
  font-weight: 600;
  color: #495057;
  white-space: nowrap;
  padding: 1rem 0.75rem;
}

.permission-col {
  min-width: 200px;
  padding: 0.75rem;
}

.module-col {
  min-width: 100px;
  color: #6c757d;
  padding: 0.75rem;
}

.role-col {
  min-width: 90px;
  padding: 0.75rem;
}

.actions-col {
  width: 50px;
  padding: 0.75rem;
}

.permission-key {
  font-family: monospace;
  font-size: 0.75rem;
}

.category-header {
  background-color: #f8f9fa;
}

.category-header td {
  padding: 0.75rem;
  border-top: 1px solid #dee2e6;
  border-bottom: 1px solid #dee2e6;
}

.permission-row:hover {
  background-color: #f8f9fa;
}

.permission-row td {
  border-bottom: 1px solid #f0f0f0;
  vertical-align: middle;
}

/* Custom Checkbox */
.custom-checkbox-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
}

.custom-checkbox {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: #007bff;
}

.custom-checkbox:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

/* Role Badges */
.badge-purple {
  background-color: #e9d5ff;
  color: #7c3aed;
}

.badge-blue {
  background-color: #dbeafe;
  color: #2563eb;
}

.badge-green {
  background-color: #d1fae5;
  color: #059669;
}

.badge-yellow {
  background-color: #fef3c7;
  color: #d97706;
}

.badge-info {
  background-color: #cff4fc;
  color: #055160;
}

.badge-secondary {
  background-color: #e9ecef;
  color: #6c757d;
}

.font-weight-medium {
  font-weight: 500;
}

.gap-2 {
  gap: 0.5rem;
}

@media (max-width: 1200px) {
  .permission-table {
    font-size: 0.85rem;
    min-width: 800px;
  }

  .permission-col {
    min-width: 180px;
  }

  .role-col {
    min-width: 75px;
  }
}
</style>
