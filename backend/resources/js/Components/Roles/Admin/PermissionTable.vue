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

    <TableFilter
      search-placeholder="Buscar por nombre o descripción..."
      :filters="tableFilters"
      :debounce-delay="300"
      @filter="onFilter"
    />

    <div class="card">
      <div class="card-body p-0">
        <BaseTable
          table-id="permissions-matrix"
          :columns="columns"
          :rows="filteredRows"
          :has-active-filters="hasActiveFilters"
          empty-message="No hay permisos registrados"
          @clear-filters="clearFilters"
        >
          <template #cell-permission="{ row }">
            <div class="d-flex align-items-center">
              <i class="fas fa-key text-muted mr-2" style="font-size: 10px;"></i>
              <span class="font-weight-medium">{{ formatPermissionName(row.name) }}</span>
              <i v-if="row.is_system" class="fas fa-lock text-danger ml-2" title="Permiso del sistema"></i>
            </div>
            <small class="text-muted d-block permission-key">{{ row.name }}</small>
          </template>

          <template #cell-module="{ row }">
            <span class="badge badge-light text-muted">{{ row.moduleLabel }}</span>
          </template>

          <template v-for="role in displayRoles" :key="`h-${role.id}`" #[`header-role-${role.id}`]>
            <span class="badge" :class="getRoleBadgeClass(role.name)">
              {{ formatRoleName(role.name) }}
            </span>
          </template>

          <template v-for="role in displayRoles" :key="role.id" #[`cell-role-${role.id}`]="{ row }">
            <div class="custom-checkbox-wrapper">
              <input
                type="checkbox"
                class="custom-checkbox"
                :checked="hasPermission(role.id, String(row.id))"
                :disabled="row.is_system || saving"
                @change="$emit('toggle-permission', role.id, String(row.id))"
              />
            </div>
          </template>

          <template #cell-actions="{ row }">
            <button
              v-if="!row.is_system"
              class="btn btn-sm btn-link text-danger"
              @click="handleDelete(String(row.id), row.name)"
              title="Eliminar permiso"
            >
              <i class="fas fa-trash"></i>
            </button>
          </template>
        </BaseTable>
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
import BaseTable from '@/js/Components/Common/Table/BaseTable.vue'
import TableFilter from '@/js/Components/Common/Table/TableFilter.vue'
import Modal from '@/js/Components/Common/Modal/Modal.vue'
import Swal from 'sweetalert2'
import type { Column, Row } from '@/js/types/common/table/column-row'
import type { TableFilterInterface } from '@/js/types/common/table/table-filter'

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

// Todos los roles activos recibidos del backend, orden alfabético — antes
// se filtraba contra un array `roleOrder` hardcodeado que ocultaba en
// silencio cualquier rol no listado ahí (ej. un rol nuevo creado después).
const displayRoles = computed(() =>
  [...props.roles].sort((a, b) => a.name.localeCompare(b.name))
)

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

function moduleLabel(name: string): string {
  const { module, subModule } = categorize(name)
  return subModule ? `${module} › ${subModule}` : module
}

// Columnas: fijas (permiso, módulo) + una dinámica por rol + acciones.
// BaseTable ya soporta columnas y slots cell-{key} arbitrarios — no hace
// falta modificarlo para columnas generadas desde datos (roles).
const columns = computed<Column[]>(() => [
  { key: 'permission', label: 'Permiso', sortable: true },
  { key: 'module', label: 'Módulo', sortable: true },
  ...displayRoles.value.map(role => ({
    key: `role-${role.id}`,
    label: formatRoleName(role.name),
    sortable: false,
    searchable: false,
  })),
  { key: 'actions', label: '', sortable: false, searchable: false },
])

// Filas planas: se pierde el header de sección con colspan que agrupaba
// visualmente por módulo (decisión consciente, ver PermissionTable — antes
// esto no permitía búsqueda/filtro real). El módulo queda como columna
// normal, ordenable y filtrable.
const allRows = computed<(Row & Permission & { moduleLabel: string })[]>(() =>
  (props.permissions.rows ?? []).map(p => ({
    ...p,
    id: p.id,
    moduleLabel: moduleLabel(p.name),
  }))
)

const moduleOptions = computed(() => {
  const modules = new Set(allRows.value.map(r => categorize(r.name).module))
  return [...modules].sort().map(m => ({ value: m, label: m }))
})

const tableFilters = computed<TableFilterInterface[]>(() => [
  {
    key: 'module',
    label: 'Módulo',
    placeholder: 'Todos los módulos',
    options: moduleOptions.value,
  },
])

const searchQuery = ref('')
const activeModule = ref('')

const hasActiveFilters = computed(() => searchQuery.value.trim() !== '' || activeModule.value !== '')

function onFilter(payload: { search: string; filters: Record<string, string> }) {
  searchQuery.value = payload.search
  activeModule.value = payload.filters.module ?? ''
}

function clearFilters() {
  searchQuery.value = ''
  activeModule.value = ''
}

const filteredRows = computed(() => {
  let rows = allRows.value

  if (activeModule.value) {
    rows = rows.filter(r => categorize(r.name).module === activeModule.value)
  }

  const q = searchQuery.value.trim().toLowerCase()
  if (q) {
    rows = rows.filter(r =>
      r.name.toLowerCase().includes(q) || (r.description ?? '').toLowerCase().includes(q)
    )
  }

  return [...rows].sort((a, b) => a.name.localeCompare(b.name))
})

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

.permission-key { font-family: monospace; font-size: 0.75rem; }

.custom-checkbox-wrapper { display: flex; justify-content: center; align-items: center; }
.custom-checkbox { width: 18px; height: 18px; cursor: pointer; accent-color: #007bff; }
.custom-checkbox:disabled { cursor: not-allowed; opacity: 0.6; }

.font-weight-medium { font-weight: 500; }
.gap-2 { gap: 0.5rem; }

.badge-purple    { background-color: rgba(124,58,237,.18); color: #a78bfa; }
.badge-blue      { background-color: rgba(37,99,235,.18);  color: #60a5fa; }
.badge-green     { background-color: rgba(5,150,105,.18);  color: #34d399; }
.badge-teal      { background-color: rgba(13,148,136,.18); color: #2dd4bf; }
.badge-yellow    { background-color: rgba(217,119,6,.18);  color: #fbbf24; }
.badge-orange    { background-color: rgba(234,88,12,.18);  color: #fb923c; }
.badge-pink      { background-color: rgba(219,39,119,.18); color: #f472b6; }
.badge-info      { background-color: rgba(5,81,96,.25);    color: #22d3ee; }
.badge-secondary { background-color: var(--ds-surface-2);  color: var(--ds-text-2); }
</style>
