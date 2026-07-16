<template>
  <div class="permission-matrix">
    <!-- Botones de acción -->
    <div class="d-flex justify-content-start mb-3">
      <div class="d-flex gap-2">
        <button class="btn btn-primary" @click="showCreateModal = true">
          <i class="fas fa-plus mr-2"></i>Nuevo permiso
        </button>
        <button class="btn btn-outline-primary" :disabled="loading" @click="$emit('reload')">
          <i class="fas fa-sync-alt mr-2"></i>Recargar
        </button>
        <button class="btn btn-success" :disabled="!hasChanges || saving" @click="handleSave">
          <i class="fas fa-save mr-2"></i>{{ saving ? 'Guardando...' : 'Guardar cambios' }}
        </button>
      </div>
    </div>

    <!-- Tabla -->
    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover permission-table mb-0">
            <thead>
              <tr>
                <th class="permission-col">Permiso</th>
                <th class="module-col">Módulo</th>
                <th v-for="role in displayRoles" :key="role.id" class="role-col text-center">
                  <span class="badge" :class="getRoleBadgeClass(role.name)">
                    {{ formatRoleName(role.name) }}
                  </span>
                </th>
                <th class="actions-col"></th>
              </tr>
            </thead>
            <tbody>
              <template v-for="category in permissionCategories" :key="category.name">
                <tr class="category-header">
                  <td colspan="100%">
                    <div class="d-flex align-items-center">
                      <i class="fas fa-folder text-primary mr-2"></i>
                      <span v-if="category.subModule" class="font-weight-bold">
                        {{ category.moduleName }}
                        <i class="fas fa-angle-right text-muted mx-1"></i>
                        <span class="text-primary">{{ category.subModule }}</span>
                      </span>
                      <span v-else class="font-weight-bold">{{ category.moduleName }}</span>
                      <span class="text-muted ml-2">({{ category.permissions.length }})</span>
                    </div>
                  </td>
                </tr>
                <tr v-for="permission in category.permissions" :key="permission.id" class="permission-row">
                  <td class="permission-col">
                    <div class="d-flex align-items-center ml-4">
                      <i class="fas fa-key text-muted mr-2" style="font-size: 10px;"></i>
                      <span class="font-weight-medium">{{ formatPermissionName(permission.name) }}</span>
                      <i v-if="permission.isSystem" class="fas fa-lock text-danger ml-2" title="Permiso del sistema"></i>
                    </div>
                    <small class="text-muted d-block permission-key ml-4">{{ permission.name }}</small>
                  </td>
                  <td class="module-col">
                    <span class="badge badge-light text-muted">{{ category.subModule || category.moduleName }}</span>
                  </td>
                  <td v-for="role in displayRoles" :key="role.id" class="role-col text-center">
                    <div class="custom-checkbox-wrapper">
                      <input
                        type="checkbox"
                        class="custom-checkbox"
                        :checked="hasPermission(role.id, permission.id)"
                        :disabled="permission.isSystem || saving"
                        @change="$emit('toggle-permission', role.id, permission.id)"
                      />
                    </div>
                  </td>
                  <td class="actions-col text-center">
                    <button
                      v-if="!permission.isSystem"
                      class="btn btn-sm btn-link text-danger"
                      @click="handleDelete(permission.id, permission.name)"
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

    <!-- Modal crear permiso -->
    <Modal v-model="showCreateModal" title="Crear Nuevo Permiso">
      <form @submit.prevent="handleCreate">
        <div class="form-group">
          <label>Nombre del Permiso</label>
          <input v-model="newPerm.name" type="text" class="form-control" placeholder="ej: users.create" required />
          <small class="form-text text-muted">Formato: modulo.accion</small>
        </div>
        <div class="form-group">
          <label>Guard</label>
          <select v-model="newPerm.guard_name" class="form-control">
            <option value="web">Web</option>
            <option value="api">API</option>
          </select>
        </div>
        <div class="form-group">
          <label>Descripción</label>
          <textarea v-model="newPerm.description" class="form-control" rows="3"></textarea>
        </div>
      </form>
      <template #footer>
        <button type="button" class="btn btn-secondary" @click="showCreateModal = false">Cancelar</button>
        <button type="button" class="btn btn-primary" @click="handleCreate">
          <i class="fas fa-save mr-2"></i>Crear Permiso
        </button>
      </template>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import Modal from '@/js/Components/Common/Modal/Modal.vue'
import Swal from 'sweetalert2'

interface Permission {
  id: string
  name: string
  guard_name: string
  description?: string
}

interface RoleWithPermissions {
  id: string
  name: string
  prefix: string
  permissions: string[]
}

const props = defineProps<{
  permissions: { rows: Permission[]; pagination: any }
  roles: RoleWithPermissions[]
  loading: boolean
  hasChanges: boolean
  hasPermission: (roleId: string, permissionId: string) => boolean
}>()

const emit = defineEmits<{
  (e: 'toggle-permission', roleId: string, permissionId: string): void
  (e: 'reload'): void
  (e: 'save'): void
  (e: 'create-permission', data: { name: string; guard_name: string; description?: string }): void
  (e: 'delete-permission', permissionId: string): void
}>()

const saving = ref(false)
const showCreateModal = ref(false)
const newPerm = ref({ name: '', guard_name: 'web', description: '' })

const roleOrder = ['superadmin', 'admin', 'commerce', 'seller', 'treasurer', 'player']

const displayRoles = computed(() =>
  [...props.roles]
    .filter(r => roleOrder.includes(r.name.toLowerCase()))
    .sort((a, b) => roleOrder.indexOf(a.name.toLowerCase()) - roleOrder.indexOf(b.name.toLowerCase()))
)

const permissionCategories = computed(() => {
  const map = new Map<string, { name: string; moduleName: string; subModule: string | null; permissions: (Permission & { isSystem: boolean })[] }>()

  for (const p of props.permissions.rows ?? []) {
    const cat = categorize(p.name)
    const key = cat.subModule ? `${cat.module} › ${cat.subModule}` : cat.module
    if (!map.has(key)) {
      map.set(key, { name: key, moduleName: cat.module, subModule: cat.subModule, permissions: [] })
    }
    map.get(key)!.permissions.push({ ...p, isSystem: isSystem(p.name) })
  }

  // Sort by module name, then by submodule name
  const sorted = [...map.values()].sort((a, b) => {
    if (a.moduleName !== b.moduleName) {
      return a.moduleName.localeCompare(b.moduleName)
    }
    if (a.subModule && b.subModule) {
      return a.subModule.localeCompare(b.subModule)
    }
    return a.subModule ? 1 : -1
  })
  
  // Sort permissions within each category alphabetically by permission name
  for (const cat of sorted) {
    cat.permissions.sort((a, b) => a.name.localeCompare(b.name))
  }
  
  return sorted
})

function categorize(name: string): { module: string; subModule: string | null } {
  const parts = name.split('.')
  const mainModule = parts[0]
  
  // Capitalize first letter
  const module = mainModule.charAt(0).toUpperCase() + mainModule.slice(1)
  
  // If there's a second part and it's not an action, treat it as submodule
  const actions = ['view', 'create', 'edit', 'delete', 'manage', 'list', 'show', 'update', 'store', 'destroy', 'index', 'config', 'assign', 'revoke', 'toggle', 'export', 'import', 'approve', 'reject', 'play', 'access']
  
  let subModule: string | null = null
  if (parts.length >= 2 && !actions.includes(parts[1])) {
    subModule = parts[1].charAt(0).toUpperCase() + parts[1].slice(1)
  }
  
  return { module, subModule }
}

function isSystem(name: string): boolean {
  return /superadmin|roles\.manage|permissions\.manage|system\.config/.test(name)
}

function formatPermissionName(name: string): string {
  const parts = name.split('.')
  const action = parts[parts.length - 1]
  const actionMap: Record<string, string> = {
    view: 'Ver', create: 'Crear', edit: 'Editar', delete: 'Eliminar',
    manage: 'Gestionar', list: 'Listar', show: 'Ver detalles', update: 'Actualizar',
    store: 'Guardar', destroy: 'Eliminar', index: 'Listar', config: 'Configurar',
    assign: 'Asignar', revoke: 'Revocar', toggle: 'Cambiar estado',
    export: 'Exportar', import: 'Importar', approve: 'Aprobar', reject: 'Rechazar',
  }
  const mod = parts[0].charAt(0).toUpperCase() + parts[0].slice(1)
  const act = actionMap[action] ?? (action.charAt(0).toUpperCase() + action.slice(1))
  return parts.length === 2 ? `${act} ${mod}` : act
}

function formatRoleName(name: string): string {
  const map: Record<string, string> = {
    superadmin: 'SuperAdmin', admin: 'Admin', commerce: 'Comercio',
    seller: 'Freelancer', treasurer: 'Tesorero', player: 'Normal',
  }
  return map[name.toLowerCase()] ?? name
}

function getRoleBadgeClass(name: string): string {
  const map: Record<string, string> = {
    superadmin: 'badge-purple', admin: 'badge-blue', commerce: 'badge-green',
    seller: 'badge-yellow', treasurer: 'badge-info', player: 'badge-secondary',
  }
  return map[name.toLowerCase()] ?? 'badge-secondary'
}

async function handleSave() {
  saving.value = true
  try {
    emit('save')
  } finally {
    saving.value = false
  }
}

async function handleDelete(permissionId: string, permissionName: string) {
  const result = await Swal.fire({
    title: 'Confirmar eliminación',
    html: `
      <p class="mb-2">Estás a punto de eliminar el permiso:</p>
      <code class="d-block px-3 py-2 bg-light rounded text-danger fw-bold">${permissionName}</code>
      <p class="mt-2 mb-0 text-muted small">Esta acción eliminará el permiso de todos los roles asignados y no se puede deshacer.</p>
    `,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc3545',
    cancelButtonColor: '#6c757d',
    confirmButtonText: '<i class="fas fa-trash mr-1"></i> Sí, eliminar',
    cancelButtonText: 'Cancelar',
    reverseButtons: true,
  })
  if (result.isConfirmed) emit('delete-permission', permissionId)
}

function handleCreate() {
  if (!newPerm.value.name.trim()) return
  emit('create-permission', {
    name: newPerm.value.name.trim(),
    guard_name: newPerm.value.guard_name,
    description: newPerm.value.description || undefined,
  })
  newPerm.value = { name: '', guard_name: 'web', description: '' }
  showCreateModal.value = false
}
</script>

<style scoped>
.permission-matrix { width: 100%; }

.permission-table {
  font-size: 0.9rem;
  width: 100%;
  min-width: 900px;
}

.permission-table thead th {
  background-color: var(--ds-surface-2);
  border-bottom: 2px solid var(--ds-border);
  font-weight: 600;
  color: var(--ds-text-2);
  white-space: nowrap;
  padding: 1rem 0.75rem;
}

.permission-col { min-width: 200px; padding: 0.75rem; }
.module-col    { min-width: 100px; color: var(--ds-text-muted); padding: 0.75rem; }
.role-col      { min-width: 90px; padding: 0.75rem; }
.actions-col   { width: 50px; padding: 0.75rem; }

.permission-key { font-family: monospace; font-size: 0.75rem; }

.category-header { background-color: var(--ds-surface-2); }
.category-header td {
  padding: 0.75rem;
  border-top: 1px solid var(--ds-border);
  border-bottom: 1px solid var(--ds-border);
}

.permission-row:hover { background-color: var(--ds-surface-2); }
.permission-row td { border-bottom: 1px solid var(--ds-border); vertical-align: middle; }

.custom-checkbox-wrapper { display: flex; justify-content: center; align-items: center; }
.custom-checkbox { width: 18px; height: 18px; cursor: pointer; accent-color: #007bff; }
.custom-checkbox:disabled { cursor: not-allowed; opacity: 0.6; }

.badge-purple   { background-color: rgba(124,58,237,.18); color: #a78bfa; }
.badge-blue     { background-color: rgba(37,99,235,.18);  color: #60a5fa; }
.badge-green    { background-color: rgba(5,150,105,.18);  color: #34d399; }
.badge-yellow   { background-color: rgba(217,119,6,.18);  color: #fbbf24; }
.badge-info     { background-color: rgba(5,81,96,.25);    color: #22d3ee; }
.badge-secondary{ background-color: var(--ds-surface-2);  color: var(--ds-text-2); }

.font-weight-medium { font-weight: 500; }
.gap-2 { gap: 0.5rem; }

@media (max-width: 1200px) {
  .permission-table { font-size: 0.85rem; min-width: 800px; }
  .permission-col   { min-width: 180px; }
  .role-col         { min-width: 75px; }
}
</style>
