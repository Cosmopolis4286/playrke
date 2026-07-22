<template>
  <div class="permission-matrix">
    <!-- Barra de acciones + búsqueda -->
    <div class="matrix-toolbar">
      <div class="toolbar-search">
        <i class="fas fa-search"></i>
        <input
          v-model="search"
          type="text"
          class="form-control"
          placeholder="Buscar permiso, módulo o rol..."
        />
        <button v-if="search" class="search-clear" title="Limpiar" @click="search = ''">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <div class="toolbar-actions">
        <button class="btn btn-outline-primary" :disabled="loading" @click="$emit('reload')">
          <i class="fas fa-sync-alt mr-2" :class="{ 'fa-spin': loading }"></i>Recargar
        </button>
        <button class="btn btn-primary" @click="showCreateModal = true">
          <i class="fas fa-plus mr-2"></i>Nuevo permiso
        </button>
        <button
          class="btn btn-success"
          :disabled="!hasChanges || saving"
          @click="handleSave"
        >
          <i class="fas fa-save mr-2"></i>{{ saving ? 'Guardando...' : `Guardar cambios${pendingChangeCount ? ` (${pendingChangeCount})` : ''}` }}
        </button>
      </div>
    </div>

    <!-- Chips de rol: leyenda + filtro rápido -->
    <div class="role-legend">
      <span class="role-legend-label">Roles:</span>
      <button
        v-for="role in displayRoles"
        :key="role.id"
        class="role-chip"
        :class="[getRoleBadgeClass(role.name), { 'is-muted': roleFilter && roleFilter !== role.id }]"
        @click="toggleRoleFilter(role.id)"
      >
        {{ formatRoleName(role.name) }}
      </button>
      <button v-if="roleFilter" class="role-chip-clear" @click="roleFilter = null">
        <i class="fas fa-times mr-1"></i>Quitar filtro
      </button>
    </div>

    <!-- Estado vacío -->
    <div v-if="!permissionCategories.length" class="matrix-empty">
      <i class="fas fa-key"></i>
      <p class="mb-0">
        {{ search ? 'Ningún permiso coincide con tu búsqueda.' : 'No hay permisos registrados.' }}
      </p>
    </div>

    <!-- Grupos por módulo -->
    <div v-else class="matrix-groups">
      <div v-for="category in permissionCategories" :key="category.name" class="module-card">
        <button class="module-header" @click="toggleCollapse(category.name)">
          <i class="fas fa-chevron-right collapse-caret" :class="{ 'is-open': !isCollapsed(category.name) }"></i>
          <i class="fas fa-folder module-icon"></i>
          <span class="module-title">
            {{ category.moduleName }}
            <template v-if="category.subModule">
              <i class="fas fa-angle-right module-sep"></i>
              <span class="module-sub">{{ category.subModule }}</span>
            </template>
          </span>
          <span class="module-count">{{ category.permissions.length }}</span>
        </button>

        <Transition name="collapse">
          <div v-if="!isCollapsed(category.name)" class="module-body">
            <div v-for="permission in category.permissions" :key="permission.id" class="permission-item">
              <div class="permission-info">
                <i class="fas fa-key permission-key-icon"></i>
                <div class="permission-text">
                  <span class="permission-name">
                    {{ formatPermissionName(permission.name) }}
                    <i v-if="permission.is_system" class="fas fa-lock text-danger ml-1" title="Permiso del sistema"></i>
                  </span>
                  <code class="permission-slug">{{ permission.name }}</code>
                </div>
              </div>

              <div class="permission-roles">
                <label
                  v-for="role in displayRoles"
                  :key="role.id"
                  v-show="!roleFilter || roleFilter === role.id"
                  class="role-toggle"
                  :class="{ 'is-checked': hasPermission(role.id, permission.id), 'is-disabled': permission.is_system || saving }"
                  :title="`${formatRoleName(role.name)} — ${permission.name}`"
                >
                  <input
                    type="checkbox"
                    :checked="hasPermission(role.id, permission.id)"
                    :disabled="permission.is_system || saving"
                    @change="$emit('toggle-permission', role.id, permission.id)"
                  />
                  <span class="role-toggle-dot"></span>
                  <span class="role-toggle-label">{{ formatRoleName(role.name) }}</span>
                </label>
              </div>

              <button
                v-if="!permission.is_system"
                class="permission-delete"
                title="Eliminar permiso"
                @click="handleDelete(permission.id, permission.name)"
              >
                <i class="fas fa-trash"></i>
              </button>
              <span v-else class="permission-delete-spacer"></span>
            </div>
          </div>
        </Transition>
      </div>
    </div>

    <!-- Modal crear permiso -->
    <Modal
      v-model="showCreateModal"
      title="Crear nuevo permiso"
      icon="fa-key"
      size="md"
      show-confirm
      confirm-text="Crear permiso"
      :confirm-disabled="!newPerm.name.trim()"
      :on-submit="handleCreate"
    >
      <div class="form-group">
        <label>Nombre del Permiso</label>
        <input
          v-model="newPerm.name"
          type="text"
          class="form-control"
          placeholder="ej: users.create"
          required
          @keydown.enter.prevent="handleCreate"
        />
        <small class="form-text text-muted">Formato: modulo.accion</small>
      </div>
      <div class="form-group">
        <label>Guard</label>
        <select v-model="newPerm.guard_name" class="form-control">
          <option value="web">Web</option>
          <option value="api">API</option>
        </select>
      </div>
      <div class="form-group mb-0">
        <label>Descripción</label>
        <textarea v-model="newPerm.description" class="form-control" rows="3"></textarea>
      </div>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import Modal from '@/js/Components/Common/Modal/Modal.vue'
import Swal from 'sweetalert2'

interface Permission {
  id: string
  name: string
  guard_name: string
  description?: string
  is_system: boolean
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
  pendingChangeCount?: number
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
const search = ref('')
const roleFilter = ref<string | null>(null)
const collapsedGroups = reactive<Record<string, boolean>>({})

// Todos los roles activos recibidos del backend, orden alfabético — antes se
// filtraba contra un array roleOrder hardcodeado (['superadmin','admin',...])
// que ocultaba en silencio cualquier rol no listado ahí (ej. 'customer' nunca
// aparecía en la matriz aunque existiera y tuviera permisos asignados).
const displayRoles = computed(() =>
  [...props.roles].sort((a, b) => a.name.localeCompare(b.name))
)

function toggleRoleFilter(roleId: string) {
  roleFilter.value = roleFilter.value === roleId ? null : roleId
}

function toggleCollapse(categoryName: string) {
  collapsedGroups[categoryName] = !collapsedGroups[categoryName]
}

function isCollapsed(categoryName: string): boolean {
  return !!collapsedGroups[categoryName]
}

const permissionCategories = computed(() => {
  const term = search.value.trim().toLowerCase()

  const map = new Map<string, { name: string; moduleName: string; subModule: string | null; permissions: Permission[] }>()

  for (const p of props.permissions.rows ?? []) {
    if (term) {
      const roleNames = props.roles
        .filter(r => r.permissions.includes(p.id))
        .map(r => formatRoleName(r.name).toLowerCase())
        .join(' ')
      const haystack = `${p.name} ${p.description ?? ''} ${roleNames}`.toLowerCase()
      if (!haystack.includes(term)) continue
    }

    const cat = categorize(p.name)
    const key = cat.subModule ? `${cat.module} › ${cat.subModule}` : cat.module
    if (!map.has(key)) {
      map.set(key, { name: key, moduleName: cat.module, subModule: cat.subModule, permissions: [] })
    }
    map.get(key)!.permissions.push(p)
  }

  const sorted = [...map.values()].sort((a, b) => {
    if (a.moduleName !== b.moduleName) {
      return a.moduleName.localeCompare(b.moduleName)
    }
    if (a.subModule && b.subModule) {
      return a.subModule.localeCompare(b.subModule)
    }
    return a.subModule ? 1 : -1
  })

  for (const cat of sorted) {
    cat.permissions.sort((a, b) => a.name.localeCompare(b.name))
  }

  return sorted
})

function categorize(name: string): { module: string; subModule: string | null } {
  const parts = name.split('.')
  const mainModule = parts[0]

  const module = mainModule.charAt(0).toUpperCase() + mainModule.slice(1)

  const actions = ['view', 'create', 'edit', 'delete', 'manage', 'list', 'show', 'update', 'store', 'destroy', 'index', 'config', 'assign', 'revoke', 'toggle', 'export', 'import', 'approve', 'reject', 'play', 'access']

  let subModule: string | null = null
  if (parts.length >= 2 && !actions.includes(parts[1])) {
    subModule = parts[1].charAt(0).toUpperCase() + parts[1].slice(1)
  }

  return { module, subModule }
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
    subsidiary: 'Sucursal', seller: 'Freelancer', provider: 'Proveedor',
    customer: 'Cliente', treasurer: 'Tesorero', player: 'Normal',
  }
  return map[name.toLowerCase()] ?? name
}

function getRoleBadgeClass(name: string): string {
  const map: Record<string, string> = {
    superadmin: 'badge-purple', admin: 'badge-blue', commerce: 'badge-green',
    subsidiary: 'badge-teal', seller: 'badge-yellow', provider: 'badge-orange',
    customer: 'badge-pink', treasurer: 'badge-info', player: 'badge-secondary',
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

async function handleCreate(): Promise<boolean> {
  if (!newPerm.value.name.trim()) return false
  emit('create-permission', {
    name: newPerm.value.name.trim(),
    guard_name: newPerm.value.guard_name,
    description: newPerm.value.description || undefined,
  })
  newPerm.value = { name: '', guard_name: 'web', description: '' }
  return true
}
</script>

<style scoped>
.permission-matrix { width: 100%; }

/* ── Toolbar ── */
.matrix-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.9rem;
}

.toolbar-search {
  position: relative;
  flex: 1 1 260px;
  max-width: 360px;
  display: flex;
  align-items: center;
}
.toolbar-search i.fa-search {
  position: absolute;
  left: 0.75rem;
  color: var(--ds-text-muted);
  font-size: 0.8rem;
  pointer-events: none;
}
.toolbar-search .form-control {
  padding-left: 2.1rem;
  padding-right: 2rem;
}
.search-clear {
  position: absolute;
  right: 0.5rem;
  border: none;
  background: transparent;
  color: var(--ds-text-muted);
  font-size: 0.75rem;
  cursor: pointer;
  padding: 0.25rem;
}
.search-clear:hover { color: var(--ds-text); }

.toolbar-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

/* ── Leyenda de roles / filtro rápido ── */
.role-legend {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.4rem;
  margin-bottom: 1rem;
  padding-bottom: 0.9rem;
  border-bottom: 1px solid var(--ds-border);
}
.role-legend-label {
  font-size: 0.8rem;
  color: var(--ds-text-muted);
  font-weight: 600;
  margin-right: 0.25rem;
}
.role-chip {
  border: none;
  border-radius: 999px;
  padding: 0.3rem 0.75rem;
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.15s, transform 0.1s;
}
.role-chip:hover { transform: translateY(-1px); }
.role-chip.is-muted { opacity: 0.35; }
.role-chip-clear {
  border: 1.5px dashed var(--ds-border-2);
  background: transparent;
  color: var(--ds-text-muted);
  border-radius: 999px;
  padding: 0.3rem 0.75rem;
  font-size: 0.78rem;
  cursor: pointer;
}
.role-chip-clear:hover { color: var(--ds-text); border-color: var(--ds-text-muted); }

/* ── Estado vacío ── */
.matrix-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  padding: 3.5rem 1rem;
  color: var(--ds-text-muted);
  text-align: center;
}
.matrix-empty i { font-size: 2.2rem; opacity: 0.5; }

/* ── Grupos por módulo (cards) ── */
.matrix-groups {
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
  max-height: calc(100vh - 320px);
  overflow-y: auto;
  padding-right: 2px;
}
.matrix-groups::-webkit-scrollbar { width: 6px; }
.matrix-groups::-webkit-scrollbar-track { background: transparent; }
.matrix-groups::-webkit-scrollbar-thumb { background: var(--ds-border-2); border-radius: 6px; }

.module-card {
  border: 1px solid var(--ds-border);
  border-radius: 12px;
  background: var(--ds-surface);
  overflow: hidden;
}

.module-header {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  width: 100%;
  padding: 0.75rem 1rem;
  background: var(--ds-surface-2);
  border: none;
  cursor: pointer;
  text-align: left;
}
.collapse-caret {
  font-size: 0.7rem;
  color: var(--ds-text-muted);
  transition: transform 0.18s;
}
.collapse-caret.is-open { transform: rotate(90deg); }
.module-icon { color: #667eea; font-size: 0.85rem; }
.module-title {
  font-weight: 700;
  font-size: 0.9rem;
  color: var(--ds-text);
  flex: 1;
}
.module-sep { font-size: 0.7rem; color: var(--ds-text-muted); margin: 0 0.35rem; }
.module-sub { color: #667eea; }
.module-count {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--ds-text-muted);
  background: var(--ds-surface);
  border: 1px solid var(--ds-border);
  border-radius: 999px;
  padding: 0.1rem 0.55rem;
}

.module-body {
  display: flex;
  flex-direction: column;
}

/* ── Filas de permiso ── */
.permission-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.65rem 1rem;
  border-top: 1px solid var(--ds-border);
  flex-wrap: wrap;
}
.permission-item:hover { background: var(--ds-surface-2); }

.permission-info {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  flex: 1 1 220px;
  min-width: 200px;
}
.permission-key-icon { color: var(--ds-text-muted); font-size: 0.7rem; margin-top: 0.3rem; }
.permission-text { display: flex; flex-direction: column; gap: 0.1rem; }
.permission-name { font-size: 0.87rem; font-weight: 500; color: var(--ds-text); }
.permission-slug { font-size: 0.72rem; color: var(--ds-text-muted); background: transparent; }

.permission-roles {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  flex: 2 1 320px;
}

.role-toggle {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.28rem 0.6rem 0.28rem 0.4rem;
  border-radius: 999px;
  border: 1.5px solid var(--ds-border);
  background: var(--ds-surface);
  cursor: pointer;
  font-size: 0.74rem;
  font-weight: 600;
  color: var(--ds-text-muted);
  transition: border-color 0.15s, color 0.15s, background 0.15s;
  user-select: none;
}
.role-toggle input { position: absolute; opacity: 0; width: 0; height: 0; }
.role-toggle-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--ds-border-2);
  flex-shrink: 0;
  transition: background 0.15s;
}
.role-toggle.is-checked {
  border-color: #34d399;
  color: var(--ds-text);
  background: rgba(52, 211, 153, 0.08);
}
.role-toggle.is-checked .role-toggle-dot { background: #34d399; }
.role-toggle.is-disabled { cursor: not-allowed; opacity: 0.55; }
.role-toggle:not(.is-disabled):hover { border-color: #34d399; }

.permission-delete {
  border: none;
  background: transparent;
  color: var(--ds-text-muted);
  cursor: pointer;
  padding: 0.35rem;
  border-radius: 6px;
  flex-shrink: 0;
  margin-left: auto;
}
.permission-delete:hover { color: #ef4444; background: rgba(239, 68, 68, 0.1); }
.permission-delete-spacer { width: 26px; flex-shrink: 0; margin-left: auto; }

/* ── Badges de rol (leyenda) ── */
.badge-purple    { background-color: rgba(124,58,237,.18); color: #a78bfa; }
.badge-blue      { background-color: rgba(37,99,235,.18);  color: #60a5fa; }
.badge-green     { background-color: rgba(5,150,105,.18);  color: #34d399; }
.badge-teal      { background-color: rgba(13,148,136,.18); color: #2dd4bf; }
.badge-yellow    { background-color: rgba(217,119,6,.18);  color: #fbbf24; }
.badge-orange    { background-color: rgba(234,88,12,.18);  color: #fb923c; }
.badge-pink      { background-color: rgba(219,39,119,.18); color: #f472b6; }
.badge-info      { background-color: rgba(5,81,96,.25);    color: #22d3ee; }
.badge-secondary { background-color: var(--ds-surface-2);  color: var(--ds-text-2); }

/* ── Collapse transition ── */
.collapse-enter-active, .collapse-leave-active {
  transition: max-height 0.2s ease, opacity 0.2s ease;
  overflow: hidden;
}
.collapse-enter-from, .collapse-leave-to { max-height: 0; opacity: 0; }
.collapse-enter-to, .collapse-leave-from { max-height: 2000px; opacity: 1; }

@media (max-width: 768px) {
  .matrix-toolbar { flex-direction: column; align-items: stretch; }
  .toolbar-search { max-width: none; }
  .toolbar-actions { justify-content: stretch; }
  .toolbar-actions .btn { flex: 1; }
  .permission-item { flex-direction: column; align-items: stretch; }
  .permission-delete, .permission-delete-spacer { margin-left: 0; align-self: flex-end; }
}
</style>
