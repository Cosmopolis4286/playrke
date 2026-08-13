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
        <button class="btn btn-outline-secondary" :disabled="hasChanges" @click="showCopyModal = true">
          <i class="fas fa-copy mr-2"></i>Copiar permisos
        </button>
        <button class="btn btn-outline-primary" :disabled="loading" @click="$emit('reload')">
          <i class="fas fa-sync-alt mr-2" :class="{ 'fa-spin': loading }"></i>Recargar
        </button>
        <button class="btn btn-primary" @click="openCreateModal">
          <i class="fas fa-plus mr-2"></i>Nuevo permiso
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

    <!-- Grid de permisos — vista desktop/tablet (>=769px) -->
    <div v-else class="matrix-card d-none d-md-block">
      <div class="matrix-grid" :style="gridTemplateStyle">
        <!-- Header -->
        <div class="grid-head grid-head--permission">Permiso</div>
        <div class="grid-head grid-head--module">Módulo</div>
        <div v-for="role in displayRoles" :key="role.id" class="grid-head grid-head--role">
          <label class="bulk-checkbox-wrapper" :title="`Seleccionar todos los permisos para ${formatRoleName(role.name)}`">
            <input
              type="checkbox"
              class="custom-checkbox bulk-checkbox"
              :checked="bulkSelection.isColumnFullyChecked(role.id)"
              :ref="el => setColumnCheckboxRef(el, role.id)"
              :disabled="isRoleSuperadmin(role.name) || saving"
              @change="bulkSelection.toggleColumn(role.id)"
            />
          </label>
          <RoleBadge :name="role.name" />
        </div>
        <div class="grid-head grid-head--actions"></div>

        <!-- Grupos -->
        <template v-for="category in permissionCategories" :key="category.name">
          <div class="grid-category" :style="{ gridColumn: `1 / -1` }">
            <label class="bulk-checkbox-wrapper" title="Seleccionar toda la categoría" @click.stop>
              <input
                type="checkbox"
                class="custom-checkbox bulk-checkbox"
                :checked="bulkSelection.isCategoryFullyChecked(category.permissions)"
                :ref="el => setCategoryCheckboxRef(el, category.name)"
                :disabled="saving"
                @change="bulkSelection.toggleCategory(category.permissions)"
              />
            </label>
            <button class="grid-category-toggle" @click="toggleCollapse(category.name)">
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
          </div>

          <template v-if="!isCollapsed(category.name)">
            <template v-for="permission in category.permissions" :key="permission.id">
              <div class="grid-cell cell-permission">
                <i class="fas fa-key permission-key-icon"></i>
                <div class="permission-text">
                  <span class="permission-name">
                    {{ formatPermissionName(permission.name) }}
                    <i v-if="permission.is_system" class="fas fa-lock text-danger ml-1" title="Permiso del sistema"></i>
                    <i
                      v-if="isPermissionOrphan(permission)"
                      class="fas fa-exclamation-triangle text-warning ml-1"
                      title="Ningún rol tiene este permiso asignado"
                    ></i>
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
                  class="row-action-btn"
                  title="Editar permiso"
                  @click="openEditModal(permission)"
                >
                  <i class="fas fa-pencil-alt"></i>
                </button>
                <button
                  v-if="!permission.is_system"
                  class="row-action-btn row-action-btn--danger"
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

    <!-- Vista mobile (<769px): tarjetas por permiso -->
    <div v-if="permissionCategories.length" class="mobile-cards d-md-none">
      <div v-for="category in permissionCategories" :key="category.name" class="mobile-category">
        <button class="mobile-category-header" @click="toggleCollapse(category.name)">
          <i class="fas fa-chevron-right collapse-caret" :class="{ 'is-open': !isCollapsed(category.name) }"></i>
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
          <div v-for="permission in category.permissions" :key="permission.id" class="mobile-perm-card">
            <div class="mobile-perm-card__header">
              <div class="permission-text">
                <span class="permission-name">
                  {{ formatPermissionName(permission.name) }}
                  <i v-if="permission.is_system" class="fas fa-lock text-danger ml-1" title="Permiso del sistema"></i>
                  <i
                    v-if="isPermissionOrphan(permission)"
                    class="fas fa-exclamation-triangle text-warning ml-1"
                    title="Ningún rol tiene este permiso asignado"
                  ></i>
                </span>
                <code class="permission-slug">{{ permission.name }}</code>
              </div>
              <div v-if="!permission.is_system" class="mobile-perm-card__actions">
                <button class="row-action-btn" title="Editar permiso" @click="openEditModal(permission)">
                  <i class="fas fa-pencil-alt"></i>
                </button>
                <button class="row-action-btn row-action-btn--danger" title="Eliminar permiso" @click="handleDelete(permission.id, permission.name)">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>
            <div class="mobile-perm-card__roles">
              <label
                v-for="role in displayRoles"
                :key="role.id"
                class="mobile-role-toggle"
                :class="{ 'is-disabled': permission.is_system || isRoleSuperadmin(role.name) }"
              >
                <input
                  type="checkbox"
                  :checked="isRoleSuperadmin(role.name) || hasPermission(role.id, permission.id)"
                  :disabled="permission.is_system || saving || isRoleSuperadmin(role.name)"
                  @change="$emit('toggle-permission', role.id, permission.id)"
                />
                <RoleBadge :name="role.name" />
              </label>
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- Barra sticky de guardado, visible solo con cambios pendientes -->
    <Transition name="save-bar">
      <div v-if="hasChanges" class="save-bar">
        <span class="save-bar__text">
          <i class="fas fa-circle-exclamation mr-2"></i>
          Tienes {{ pendingChangeCount }} cambio{{ pendingChangeCount === 1 ? '' : 's' }} sin guardar
        </span>
        <div class="save-bar__actions">
          <button class="btn btn-outline-light btn-sm" :disabled="saving" @click="$emit('reload')">Descartar</button>
          <button class="btn btn-success btn-sm" :disabled="saving" @click="handleSave">
            <i class="fas fa-save mr-2"></i>{{ saving ? 'Guardando...' : 'Guardar cambios' }}
          </button>
        </div>
      </div>
    </Transition>

    <!-- Modal crear/editar permiso -->
    <Modal
      v-model="showFormModal"
      :title="editingPermission ? 'Editar permiso' : 'Crear nuevo permiso'"
      icon="fa-key"
      size="md"
      show-confirm
      :confirm-text="editingPermission ? 'Guardar cambios' : 'Crear permiso'"
      :confirm-disabled="!formPerm.name.trim()"
      :on-submit="handleSubmitForm"
    >
      <div class="form-group">
        <label>Nombre del Permiso</label>
        <input
          v-model="formPerm.name"
          type="text"
          class="form-control"
          placeholder="ej: users.create"
          required
          @keydown.enter.prevent="handleSubmitForm"
        />
        <small class="form-text text-muted">Formato: modulo.accion</small>
      </div>
      <div class="form-group">
        <label>Guard</label>
        <select v-model="formPerm.guard_name" class="form-control">
          <option value="web">Web</option>
          <option value="api">API</option>
        </select>
      </div>
      <div class="form-group mb-0">
        <label>Descripción</label>
        <textarea v-model="formPerm.description" class="form-control" rows="3"></textarea>
      </div>
    </Modal>

    <!-- Modal copiar permisos entre roles -->
    <Modal
      v-model="showCopyModal"
      title="Copiar permisos entre roles"
      icon="fa-copy"
      size="md"
      show-confirm
      confirm-text="Copiar permisos"
      :confirm-disabled="!copyForm.sourceRoleId || !copyForm.targetRoleId || copyForm.sourceRoleId === copyForm.targetRoleId"
      :on-submit="handleCopy"
    >
      <p class="text-muted small">
        {{ copyForm.replace
          ? 'El rol destino quedará con EXACTAMENTE los permisos del origen — se revocará cualquier permiso no-sistema que tuviera y no esté en el origen.'
          : 'Los permisos del rol origen se añadirán al rol destino sin quitarle los que ya tenía.' }}
      </p>
      <div class="form-group">
        <label>Copiar de</label>
        <select v-model="copyForm.sourceRoleId" class="form-control">
          <option value="" disabled>Selecciona un rol origen</option>
          <option v-for="role in displayRoles" :key="role.id" :value="role.id">{{ formatRoleName(role.name) }}</option>
        </select>
      </div>
      <div class="form-group">
        <label>Hacia</label>
        <select v-model="copyForm.targetRoleId" class="form-control">
          <option value="" disabled>Selecciona un rol destino</option>
          <option v-for="role in copyTargetRoles" :key="role.id" :value="role.id">{{ formatRoleName(role.name) }}</option>
        </select>
      </div>
      <div class="form-group mb-0 form-check">
        <input id="copy-replace" v-model="copyForm.replace" type="checkbox" class="form-check-input" />
        <label for="copy-replace" class="form-check-label" :class="{ 'text-danger font-weight-bold': copyForm.replace }">
          Reemplazar en vez de fusionar
        </label>
      </div>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import Modal from '@/js/Components/Common/Modal/Modal.vue'
import RoleBadge from '@/js/Components/Roles/Admin/RoleBadge.vue'
import Swal from 'sweetalert2'
import { useToast } from 'vue-toastification'
import { sortByRoleHierarchy, formatRoleName, isRoleSuperadmin } from '@/js/utils/roles/roleDisplay'
import { useMatrixBulkSelection } from '@/js/composables/roles/admin/shared/useMatrixBulkSelection'

interface Permission {
  id: string
  name: string
  guard_name: string
  description?: string
  is_system: boolean
  /** true = no está asignado a ningún rol (calculado por el backend al cargar). */
  is_orphan?: boolean
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
  (e: 'update-permission', permissionId: string, data: { name: string; guard_name: string; description?: string }): void
  (e: 'delete-permission', permissionId: string): void
  (e: 'copy-permissions', sourceRoleId: string, targetRoleId: string, replace: boolean): void
}>()

const toast = useToast()

const saving = ref(false)
const showFormModal = ref(false)
const showCopyModal = ref(false)
const editingPermission = ref<Permission | null>(null)
const formPerm = ref({ name: '', guard_name: 'web', description: '' })
const copyForm = reactive({ sourceRoleId: '', targetRoleId: '', replace: false })
const search = ref('')
const collapsedGroups = reactive<Record<string, boolean>>({})

// Orden jerárquico + formato de roles: ver utils/roleDisplay.ts (compartido
// con SidebarMenuVisibilityMatrix.vue, antes duplicado en ambos componentes).
const displayRoles = computed(() => sortByRoleHierarchy(props.roles))
const copyTargetRoles = computed(() => displayRoles.value.filter(r => !isRoleSuperadmin(r.name)))

/**
 * Un permiso está "huérfano" si ningún rol lo tiene activo — recalculado en
 * vivo contra `hasPermission` (no solo el `is_orphan` inicial del backend)
 * para que se actualice al togglear checkboxes en la matriz, antes incluso
 * de guardar.
 */
function isPermissionOrphan(permission: Permission): boolean {
  return !displayRoles.value.some(role => props.hasPermission(role.id, permission.id))
}

// Grid CSS en vez de <table>: columnas fijas para permiso/módulo/acciones y
// una columna igual por cada rol, calculadas dinámicamente según cuántos
// roles trae el backend (antes era un <table> con <th> por rol).
const gridTemplateStyle = computed(() => ({
  gridTemplateColumns: `minmax(220px, 2fr) minmax(110px, 0.7fr) repeat(${displayRoles.value.length}, minmax(90px, 0.8fr)) 64px`,
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

// ── Selección masiva (columna = rol completo, categoría = grupo completo) ──
// rows()/cols() leen de permissionCategories/displayRoles (ya filtrados por
// búsqueda), para que "seleccionar todo" no afecte permisos ocultos por el
// filtro activo.
const bulkSelection = useMatrixBulkSelection<Permission, RoleWithPermissions>({
  rows: () => permissionCategories.value.flatMap(c => c.permissions),
  cols: () => displayRoles.value,
  getValue: (permId, roleId) => {
    const role = displayRoles.value.find(r => r.id === roleId)
    return !!role && (isRoleSuperadmin(role.name) || props.hasPermission(roleId, permId))
  },
  setValue: (permId, roleId) => emit('toggle-permission', roleId, permId),
  isCellDisabled: (permId, roleId) => {
    const permission = permissionCategories.value
      .flatMap(c => c.permissions)
      .find(p => p.id === permId)
    const role = displayRoles.value.find(r => r.id === roleId)
    return !!permission?.is_system || !!role && isRoleSuperadmin(role.name)
  },
})

// indeterminate no tiene binding declarativo en Vue — se setea a mano vía ref.
// Los refs de función reciben Element | ComponentPublicInstance | null; estos
// siempre apuntan a un <input>, de ahí el cast a HTMLInputElement.
const columnCheckboxRefs = new Map<string, HTMLInputElement>()
function setColumnCheckboxRef(el: unknown, roleId: string) {
  if (el) {
    const input = el as HTMLInputElement
    columnCheckboxRefs.set(roleId, input)
    input.indeterminate = bulkSelection.isColumnPartiallyChecked(roleId)
  } else {
    columnCheckboxRefs.delete(roleId)
  }
}
const categoryCheckboxRefs = new Map<string, HTMLInputElement>()
function setCategoryCheckboxRef(el: unknown, categoryName: string) {
  if (el) {
    const input = el as HTMLInputElement
    categoryCheckboxRefs.set(categoryName, input)
    const category = permissionCategories.value.find(c => c.name === categoryName)
    if (category) {
      input.indeterminate = bulkSelection.isCategoryPartiallyChecked(category.permissions)
    }
  } else {
    categoryCheckboxRefs.delete(categoryName)
  }
}

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

function openCreateModal() {
  editingPermission.value = null
  formPerm.value = { name: '', guard_name: 'web', description: '' }
  showFormModal.value = true
}

function openEditModal(permission: Permission) {
  editingPermission.value = permission
  formPerm.value = {
    name: permission.name,
    guard_name: permission.guard_name,
    description: permission.description ?? '',
  }
  showFormModal.value = true
}

async function handleSubmitForm(): Promise<boolean> {
  if (!formPerm.value.name.trim()) return false

  const data = {
    name: formPerm.value.name.trim(),
    guard_name: formPerm.value.guard_name,
    description: formPerm.value.description || undefined,
  }

  if (editingPermission.value) {
    emit('update-permission', editingPermission.value.id, data)
  } else {
    emit('create-permission', data)
  }

  editingPermission.value = null
  formPerm.value = { name: '', guard_name: 'web', description: '' }
  return true
}

async function handleCopy(): Promise<boolean> {
  if (!copyForm.sourceRoleId || !copyForm.targetRoleId || copyForm.sourceRoleId === copyForm.targetRoleId) return false
  emit('copy-permissions', copyForm.sourceRoleId, copyForm.targetRoleId, copyForm.replace)
  copyForm.sourceRoleId = ''
  copyForm.targetRoleId = ''
  copyForm.replace = false
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

/* ── Card contenedora: SIN scroll vertical propio — el único scroll
   vertical es el del layout (#content-scrollable, ver auth-layout.blade.php).
   overflow-x:auto conserva el scroll horizontal en pantallas angostas sin
   bloquear el eje vertical; overflow-y:visible + "clip" en vez de "hidden"
   evita que este contenedor se registre como scroll container y rompa el
   position:sticky del header de la tabla. ── */
.matrix-card {
  border: 1px solid var(--ds-border);
  border-radius: 12px;
  background: var(--ds-surface);
  overflow-x: auto;
  overflow-y: visible;
}

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
.grid-head--role {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
}

/* Fila de categoría: ocupa todas las columnas (gridColumn: 1 / -1 inline) */
.grid-category {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.4rem 0.75rem;
  background: var(--ds-surface-2);
  border-top: 1px solid var(--ds-border);
  border-bottom: 1px solid var(--ds-border);
}
.grid-category-toggle {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex: 1;
  border: none;
  background: transparent;
  cursor: pointer;
  text-align: left;
  padding: 0.15rem 0;
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

.bulk-checkbox-wrapper { display: flex; align-items: center; cursor: pointer; }
.bulk-checkbox { accent-color: #6366f1; }

.cell-actions { justify-content: center; gap: 0.25rem; }
.row-action-btn {
  border: none;
  background: transparent;
  color: var(--ds-text-muted);
  cursor: pointer;
  padding: 0.35rem;
  border-radius: 6px;
}
.row-action-btn:hover { color: #6366f1; background: rgba(99, 102, 241, 0.1); }
.row-action-btn--danger:hover { color: #ef4444; background: rgba(239, 68, 68, 0.1); }

.grid-cell:hover { background: var(--ds-surface-2); }

/* ── Vista mobile: tarjetas ── */
.mobile-cards { display: flex; flex-direction: column; gap: 0.75rem; }
.mobile-category {
  border: 1px solid var(--ds-border);
  border-radius: 12px;
  background: var(--ds-surface);
  overflow: clip;
}
.mobile-category-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.65rem 0.85rem;
  background: var(--ds-surface-2);
  border: none;
  border-bottom: 1px solid var(--ds-border);
  text-align: left;
}
.mobile-perm-card {
  padding: 0.75rem 0.85rem;
  border-bottom: 1px solid var(--ds-border);
}
.mobile-perm-card:last-child { border-bottom: none; }
.mobile-perm-card__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.5rem;
  margin-bottom: 0.6rem;
}
.mobile-perm-card__actions { display: flex; gap: 0.15rem; flex-shrink: 0; }
.mobile-perm-card__roles {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}
.mobile-role-toggle {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  min-height: 44px;
  padding: 0.3rem 0.5rem;
  border: 1px solid var(--ds-border);
  border-radius: 8px;
  cursor: pointer;
  user-select: none;
}
.mobile-role-toggle.is-disabled { opacity: 0.6; cursor: not-allowed; }
.mobile-role-toggle input { width: 18px; height: 18px; accent-color: #34d399; flex-shrink: 0; }

/* ── Barra sticky de guardado ── */
.save-bar {
  position: sticky;
  bottom: 1rem;
  margin-top: 1rem;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border-radius: 12px;
  background: #1f2937;
  color: #f9fafb;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
}
.save-bar__text { font-size: 0.85rem; font-weight: 500; }
.save-bar__actions { display: flex; gap: 0.5rem; }

.save-bar-enter-active,
.save-bar-leave-active { transition: opacity 0.2s, transform 0.2s; }
.save-bar-enter-from,
.save-bar-leave-to { opacity: 0; transform: translateY(8px); }

@media (max-width: 768px) {
  .matrix-toolbar { flex-direction: column; align-items: stretch; }
  .toolbar-search { max-width: none; }
  .toolbar-actions { justify-content: stretch; }
  .toolbar-actions .btn { flex: 1; }
  .save-bar { flex-direction: column; align-items: stretch; }
  .save-bar__actions .btn { flex: 1; }
}
</style>
