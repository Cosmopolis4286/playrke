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
                      <i class="fas fa-circle text-danger mr-2" style="font-size: 8px;"></i>
                      <span class="font-weight-bold text-uppercase">{{ category.name }}</span>
                      <span class="text-muted ml-2">({{ category.permissions.length }})</span>
                    </div>
                  </td>
                </tr>
                <tr v-for="permission in category.permissions" :key="permission.id" class="permission-row">
                  <td class="permission-col">
                    <div class="d-flex align-items-center">
                      <span class="font-weight-medium">{{ formatPermissionName(permission.name) }}</span>
                      <i v-if="permission.isSystem" class="fas fa-lock text-danger ml-2" title="Permiso del sistema"></i>
                    </div>
                    <small class="text-muted d-block permission-key">{{ permission.name }}</small>
                  </td>
                  <td class="module-col">
                    <span class="text-muted">{{ category.moduleName }}</span>
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
                      @click="handleDelete(permission.id)"
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
  const map = new Map<string, { name: string; moduleName: string; permissions: (Permission & { isSystem: boolean })[] }>()

  for (const p of props.permissions.rows ?? []) {
    const cat = categorize(p.name)
    if (!map.has(cat.key)) {
      map.set(cat.key, { name: cat.key, moduleName: cat.module, permissions: [] })
    }
    map.get(cat.key)!.permissions.push({ ...p, isSystem: isSystem(p.name) })
  }

  return [...map.values()].sort((a, b) => a.name.localeCompare(b.name))
})

function categorize(name: string): { key: string; module: string } {
  const module = name.split('.')[0]
  const mod = module.charAt(0).toUpperCase() + module.slice(1)

  if (/user|admin/.test(name))                            return { key: 'USUARIOS',      module: mod }
  if (/commerce|business|store/.test(name))               return { key: 'COMERCIOS',     module: mod }
  if (/game|bingo|play/.test(name))                       return { key: 'JUEGOS',        module: mod }
  if (/wallet|finance|payment|transaction/.test(name))    return { key: 'FINANZAS',      module: mod }
  if (/role|permission/.test(name))                       return { key: 'ROLES',         module: mod }
  if (/audit/.test(name))                                 return { key: 'AUDIT',         module: mod }
  if (/system|config|setting/.test(name))                 return { key: 'SISTEMA',       module: mod }
  if (/support|ticket|help/.test(name))                   return { key: 'SOPORTE',       module: mod }
  if (/analytics|report|stats/.test(name))                return { key: 'ANALYTICS',     module: mod }
  return { key: mod.toUpperCase(), module: mod }
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

async function handleDelete(permissionId: string) {
  const result = await Swal.fire({
    title: 'Confirmar eliminación',
    text: '¿Eliminar este permiso? Esta acción no se puede deshacer.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc3545',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Eliminar',
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
  background-color: #f8f9fa;
  border-bottom: 2px solid #dee2e6;
  font-weight: 600;
  color: #495057;
  white-space: nowrap;
  padding: 1rem 0.75rem;
}

.permission-col { min-width: 200px; padding: 0.75rem; }
.module-col    { min-width: 100px; color: #6c757d; padding: 0.75rem; }
.role-col      { min-width: 90px; padding: 0.75rem; }
.actions-col   { width: 50px; padding: 0.75rem; }

.permission-key { font-family: monospace; font-size: 0.75rem; }

.category-header { background-color: #f8f9fa; }
.category-header td {
  padding: 0.75rem;
  border-top: 1px solid #dee2e6;
  border-bottom: 1px solid #dee2e6;
}

.permission-row:hover { background-color: #f8f9fa; }
.permission-row td { border-bottom: 1px solid #f0f0f0; vertical-align: middle; }

.custom-checkbox-wrapper { display: flex; justify-content: center; align-items: center; }
.custom-checkbox { width: 18px; height: 18px; cursor: pointer; accent-color: #007bff; }
.custom-checkbox:disabled { cursor: not-allowed; opacity: 0.6; }

.badge-purple   { background-color: #e9d5ff; color: #7c3aed; }
.badge-blue     { background-color: #dbeafe; color: #2563eb; }
.badge-green    { background-color: #d1fae5; color: #059669; }
.badge-yellow   { background-color: #fef3c7; color: #d97706; }
.badge-info     { background-color: #cff4fc; color: #055160; }
.badge-secondary{ background-color: #e9ecef; color: #6c757d; }

.font-weight-medium { font-weight: 500; }
.gap-2 { gap: 0.5rem; }

@media (max-width: 1200px) {
  .permission-table { font-size: 0.85rem; min-width: 800px; }
  .permission-col   { min-width: 180px; }
  .role-col         { min-width: 75px; }
}
</style>
