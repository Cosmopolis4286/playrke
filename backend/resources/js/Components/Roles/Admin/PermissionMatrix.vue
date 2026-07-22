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

    <!-- Estado vacío -->
    <div v-if="!permissionCategories.length" class="matrix-empty">
      <i class="fas fa-key"></i>
      <p class="mb-0">
        {{ search ? 'Ningún permiso coincide con tu búsqueda.' : 'No hay permisos registrados.' }}
      </p>
    </div>

    <!-- Grid de permisos (columnas por rol) -->
    <div v-else class="matrix-card">
      <div class="matrix-scroll">
        <div class="matrix-grid" :style="gridTemplateStyle">
          <!-- Header -->
          <div class="grid-head grid-head--permission">Permiso</div>
          <div class="grid-head grid-head--module">Módulo</div>
          <div v-for="role in displayRoles" :key="role.id" class="grid-head grid-head--role">
            <span class="badge role-badge" :class="getRoleBadgeClass(role.name)">
              {{ formatRoleName(role.name) }}
            </span>
          </div>
          <div class="grid-head grid-head--actions"></div>

          <!-- Grupos -->
          <template v-for="category in permissionCategories" :key="category.name">
            <button
              class="grid-category"
              :style="{ gridColumn: `1 / -1` }"
              @click="toggleCollapse(category.name)"
            >
              <i class="fas fa-chevron-right collapse-caret" :class="{ 'is-open': !isCollapsed(category.name) }"></i>
              <span class="category-dot"></span>
              <span class="category-title">
                {{ category.moduleName }}
                <template v-if="category.subModule">
                  <i class="fas fa-angle-right category-sep"></i>
                  <span class="category-sub">{{ category.subModule }}</span>
                </template>
              </span>
              <span class="category-count">({{ category.permissions.length }})</span>
            </button>

            <template v-if="!isCollapsed(category.name)">
              <template v-for="permission in category.permissions" :key="permission.id">
                <div class="grid-cell cell-permission">
                  <i class="fas fa-key permission-key-icon"></i>
                  <div class="permission-text">
                    <span class="permission-name">
                      {{ formatPermissionName(permission.name) }}
                      <i v-if="permission.is_system" class="fas fa-lock text-danger ml-1" title="Permiso del sistema"></i>
                    </span>
                    <code class="permission-slug">{{ permission.name }}</code>
                  </div>
                </div>

                <div class="grid-cell cell-module">
                  <span class="badge badge-light module-badge">{{ category.subModule || category.moduleName }}</span>
                </div>

                <div v-for="role in displayRoles" :key="role.id" class="grid-cell cell-role">
                  <div class="custom-checkbox-wrapper">
                    <input
                      type="checkbox"
                      class="custom-checkbox"
                      :checked="isRoleSuperadmin(role.name) || hasPermission(role.id, permission.id)"
                      :disabled="permission.is_system || saving || isRoleSuperadmin(role.name)"
                      :title="isRoleSuperadmin(role.name)
                        ? 'SuperAdmin tiene todos los permisos por defecto y no es editable'
                        : `${formatRoleName(role.name)} — ${permission.name}`"
                      @change="$emit('toggle-permission', role.id, permission.id)"
                    />
                  </div>
                </div>

                <div class="grid-cell cell-actions">
                  <button
                    v-if="!permission.is_system"
                    class="permission-delete"
                    title="Eliminar permiso"
                    @click="handleDelete(permission.id, permission.name)"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </template>
            </template>
          </template>
        </div>
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
const collapsedGroups = reactive<Record<string, boolean>>({})

// Orden jerárquico real del sistema (ver RoleSeeder.php): administración de
// plataforma → negocio → operativo → consumidor final → externo. Roles que
// no aparezcan aquí (futuros, o creados fuera del seeder) no se ocultan —
// se ordenan alfabéticamente al final — a diferencia del bug original donde
// un array hardcodeado los excluía en silencio de la matriz por completo.
const ROLE_HIERARCHY = [
  'superadmin', 'admin',
  'commerce', 'subsidiary', 'seller', 'treasurer',
  'customer', 'player',
  'provider',
]

const displayRoles = computed(() =>
  [...props.roles].sort((a, b) => {
    const ia = ROLE_HIERARCHY.indexOf(a.name.toLowerCase())
    const ib = ROLE_HIERARCHY.indexOf(b.name.toLowerCase())
    if (ia === -1 && ib === -1) return a.name.localeCompare(b.name)
    if (ia === -1) return 1
    if (ib === -1) return -1
    return ia - ib
  })
)

// Grid CSS en vez de <table>: columnas fijas para permiso/módulo/acciones y
// una columna igual por cada rol, calculadas dinámicamente según cuántos
// roles trae el backend (antes era un <table> con <th> por rol).
const gridTemplateStyle = computed(() => ({
  gridTemplateColumns: `minmax(220px, 2fr) minmax(110px, 0.7fr) repeat(${displayRoles.value.length}, minmax(90px, 0.8fr)) 48px`,
}))

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
    customer: 'Cliente', treasurer: 'Tesorero', player: 'Jugador',
  }
  return map[name.toLowerCase()] ?? name
}

// superadmin siempre tiene el 100% de los permisos y no es mutable desde
// la matriz — el backend ya lo garantiza (PermissionService::createPermission
// auto-asigna permisos nuevos a superadmin y revokePermissionsFromRole()
// ignora ese rol), esto solo evita que la UI insinúe que se puede editar.
function isRoleSuperadmin(name: string): boolean {
  return name.toLowerCase() === 'superadmin'
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

/* ── Card contenedora + scroll ── */
.matrix-card {
  border: 1px solid var(--ds-border);
  border-radius: 12px;
  background: var(--ds-surface);
  overflow: hidden;
}

.matrix-scroll {
  max-height: calc(100vh - 300px);
  overflow: auto;
}
.matrix-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
.matrix-scroll::-webkit-scrollbar-track { background: transparent; }
.matrix-scroll::-webkit-scrollbar-thumb { background: var(--ds-border-2); border-radius: 6px; }

/* ── Grid: reemplaza <table> — misma jerarquía visual (header + filas +
   separadores de grupo) sin depender de layout de tabla HTML. ── */
.matrix-grid {
  display: grid;
  min-width: 640px;
  font-size: 0.9rem;
}

.grid-head {
  position: sticky;
  top: 0;
  z-index: 1;
  background: var(--ds-surface-2);
  border-bottom: 2px solid var(--ds-border);
  font-weight: 600;
  color: var(--ds-text-2);
  white-space: nowrap;
  padding: 0.6rem 0.75rem;
}
.grid-head--role { text-align: center; }

.role-badge { font-size: 0.72rem; padding: 0.3rem 0.6rem; }

/* Fila de categoría: ocupa todas las columnas (gridColumn: 1 / -1 inline) */
.grid-category {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.5rem 0.75rem;
  background: var(--ds-surface-2);
  border: none;
  border-top: 1px solid var(--ds-border);
  border-bottom: 1px solid var(--ds-border);
  cursor: pointer;
  text-align: left;
}
.collapse-caret {
  font-size: 0.65rem;
  color: var(--ds-text-muted);
  transition: transform 0.18s;
  flex-shrink: 0;
}
.collapse-caret.is-open { transform: rotate(90deg); }
.category-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #ef4444;
  flex-shrink: 0;
}
.category-title {
  font-weight: 700;
  font-size: 0.82rem;
  letter-spacing: 0.02em;
  text-transform: uppercase;
  color: var(--ds-text);
}
.category-sep { font-size: 0.65rem; color: var(--ds-text-muted); margin: 0 0.3rem; }
.category-sub { color: #667eea; text-transform: none; }
.category-count {
  font-size: 0.78rem;
  font-weight: 500;
  color: var(--ds-text-muted);
  text-transform: none;
}

/* Celdas de fila */
.grid-cell {
  display: flex;
  align-items: center;
  padding: 0.55rem 0.75rem;
  border-bottom: 1px solid var(--ds-border);
  min-width: 0;
}

.cell-permission { gap: 0.5rem; padding-left: 1.75rem; }
.permission-key-icon { color: var(--ds-text-muted); font-size: 0.65rem; flex-shrink: 0; }
.permission-text { display: flex; flex-direction: column; gap: 0.1rem; min-width: 0; }
.permission-name { font-size: 0.87rem; font-weight: 500; color: var(--ds-text); }
.permission-slug { font-size: 0.72rem; color: var(--ds-text-muted); background: transparent; }

.cell-module { color: var(--ds-text-muted); }
.module-badge { font-weight: 500; }

.cell-role { justify-content: center; }

.custom-checkbox-wrapper { display: flex; justify-content: center; align-items: center; }
.custom-checkbox { width: 18px; height: 18px; cursor: pointer; accent-color: #34d399; }
.custom-checkbox:disabled { cursor: not-allowed; opacity: 0.6; }

.cell-actions { justify-content: center; }
.permission-delete {
  border: none;
  background: transparent;
  color: var(--ds-text-muted);
  cursor: pointer;
  padding: 0.35rem;
  border-radius: 6px;
}
.permission-delete:hover { color: #ef4444; background: rgba(239, 68, 68, 0.1); }

/* Filas de permiso (hover en todas sus celdas vía nth-selector no es viable
   con grid puro sin envolver en contenedor extra; el hover por celda basta
   para dar feedback visual sin reintroducir <table>). */
.grid-cell:hover { background: var(--ds-surface-2); }

/* ── Badges de rol ── */
.badge-purple    { background-color: rgba(124,58,237,.18); color: #a78bfa; }
.badge-blue      { background-color: rgba(37,99,235,.18);  color: #60a5fa; }
.badge-green     { background-color: rgba(5,150,105,.18);  color: #34d399; }
.badge-teal      { background-color: rgba(13,148,136,.18); color: #2dd4bf; }
.badge-yellow    { background-color: rgba(217,119,6,.18);  color: #fbbf24; }
.badge-orange    { background-color: rgba(234,88,12,.18);  color: #fb923c; }
.badge-pink      { background-color: rgba(219,39,119,.18); color: #f472b6; }
.badge-info      { background-color: rgba(5,81,96,.25);    color: #22d3ee; }
.badge-secondary { background-color: var(--ds-surface-2);  color: var(--ds-text-2); }

@media (max-width: 768px) {
  .matrix-toolbar { flex-direction: column; align-items: stretch; }
  .toolbar-search { max-width: none; }
  .toolbar-actions { justify-content: stretch; }
  .toolbar-actions .btn { flex: 1; }
}
</style>
