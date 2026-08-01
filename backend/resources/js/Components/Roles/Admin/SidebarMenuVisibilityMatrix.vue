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
          placeholder="Buscar item o rol..."
        />
        <button v-if="search" class="search-clear" title="Limpiar" @click="search = ''">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <div class="toolbar-actions">
        <button class="btn btn-outline-primary" :disabled="loading" @click="$emit('reload')">
          <i class="fas fa-sync-alt mr-2" :class="{ 'fa-spin': loading }"></i>Recargar
        </button>
      </div>
    </div>

    <!-- Estado vacío -->
    <div v-if="!groups.length" class="matrix-empty">
      <i class="fas fa-bars"></i>
      <p class="mb-0">
        {{ search ? 'Ningún item coincide con tu búsqueda.' : 'No hay items de menú registrados.' }}
      </p>
    </div>

    <!-- Grid de items — vista desktop/tablet (>=769px) -->
    <div v-else class="matrix-card d-none d-md-block">
      <div class="matrix-grid" :style="gridTemplateStyle">
        <!-- Header -->
        <div class="grid-head grid-head--permission">Item del menú</div>
        <div class="grid-head grid-head--module">Ruta</div>
        <div v-for="role in displayRoles" :key="role.id" class="grid-head grid-head--role">
          <label class="bulk-checkbox-wrapper" :title="`Seleccionar todos los items para ${formatRoleName(role.name)}`">
            <input
              type="checkbox"
              class="custom-checkbox bulk-checkbox"
              :checked="bulkSelection.isColumnFullyChecked(role.id)"
              :ref="el => setColumnCheckboxRef(el, role.id)"
              :disabled="saving"
              @change="bulkSelection.toggleColumn(role.id)"
            />
          </label>
          <RoleBadge :name="role.name" />
        </div>

        <!-- Grupos -->
        <template v-for="group in groups" :key="group.key">
          <div class="grid-category" :style="{ gridColumn: `1 / -1` }">
            <label class="bulk-checkbox-wrapper" title="Seleccionar todo el grupo" @click.stop>
              <input
                type="checkbox"
                class="custom-checkbox bulk-checkbox"
                :checked="bulkSelection.isCategoryFullyChecked(group.items)"
                :ref="el => setCategoryCheckboxRef(el, group.key)"
                :disabled="saving"
                @change="bulkSelection.toggleCategory(group.items)"
              />
            </label>
            <button class="grid-category-toggle" @click="toggleCollapse(group.key)">
              <i class="fas fa-chevron-right collapse-caret" :class="{ 'is-open': !isCollapsed(group.key) }"></i>
              <span class="category-dot"></span>
              <span class="category-title">{{ group.title }}</span>
              <span class="category-count">({{ group.items.length }})</span>
            </button>
          </div>

          <template v-if="!isCollapsed(group.key)">
            <template v-for="item in group.items" :key="item.key">
              <div class="grid-cell cell-permission" :class="{ 'cell-permission--child': item.parent_key }">
                <i class="fas fa-bars permission-key-icon"></i>
                <div class="permission-text">
                  <span class="permission-name">{{ item.title }}</span>
                  <code class="permission-slug">{{ item.key }}</code>
                </div>
              </div>

              <div class="grid-cell cell-module">
                <span v-if="item.permission" class="badge badge-light module-badge">{{ item.permission }}</span>
                <span v-else class="text-muted small">—</span>
              </div>

              <!-- A diferencia de PermissionMatrix.vue, el checkbox de
                   superadmin NO se deshabilita aquí: hoy es posible ocultar
                   un item del sidebar incluso a superadmin (ver
                   'Mis Partidas' en SidebarMenuService::$menuStructure,
                   antes exclude_roles) y esta matriz preserva esa
                   capacidad para todos los roles por igual. -->
              <div v-for="role in displayRoles" :key="role.id" class="grid-cell cell-role">
                <div class="custom-checkbox-wrapper">
                  <input
                    type="checkbox"
                    class="custom-checkbox"
                    :checked="isVisible(item.key, role.id)"
                    :disabled="saving"
                    :title="`${formatRoleName(role.name)} — ${item.title}`"
                    @change="$emit('toggle-visibility', item.key, role.id)"
                  />
                </div>
              </div>
            </template>
          </template>
        </template>
      </div>
    </div>

    <!-- Vista mobile (<769px): tarjetas por item -->
    <div v-if="groups.length" class="mobile-cards d-md-none">
      <div v-for="group in groups" :key="group.key" class="mobile-category">
        <button class="mobile-category-header" @click="toggleCollapse(group.key)">
          <i class="fas fa-chevron-right collapse-caret" :class="{ 'is-open': !isCollapsed(group.key) }"></i>
          <span class="category-title">{{ group.title }}</span>
          <span class="category-count">({{ group.items.length }})</span>
        </button>

        <template v-if="!isCollapsed(group.key)">
          <div v-for="item in group.items" :key="item.key" class="mobile-perm-card">
            <div class="mobile-perm-card__header">
              <div class="permission-text">
                <span class="permission-name">{{ item.title }}</span>
                <code class="permission-slug">{{ item.key }}</code>
              </div>
            </div>
            <div class="mobile-perm-card__roles">
              <label v-for="role in displayRoles" :key="role.id" class="mobile-role-toggle">
                <input
                  type="checkbox"
                  :checked="isVisible(item.key, role.id)"
                  :disabled="saving"
                  @change="$emit('toggle-visibility', item.key, role.id)"
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
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import RoleBadge from '@/js/Components/Roles/Admin/RoleBadge.vue'
import { sortByRoleHierarchy, formatRoleName } from '@/js/utils/roles/roleDisplay'
import { useMatrixBulkSelection } from '@/js/composables/roles/admin/shared/useMatrixBulkSelection'
import type { SidebarMenuItem, SidebarMenuRole } from '@/js/types/roles/admin/sidebar-menu'

const props = defineProps<{
  items: SidebarMenuItem[]
  roles: SidebarMenuRole[]
  loading: boolean
  hasChanges: boolean
  pendingChangeCount?: number
  isVisible: (itemKey: string, roleId: string) => boolean
}>()

const emit = defineEmits<{
  (e: 'toggle-visibility', itemKey: string, roleId: string): void
  (e: 'reload'): void
  (e: 'save'): void
}>()

const saving = ref(false)
const search = ref('')
const collapsedGroups = reactive<Record<string, boolean>>({})

// Orden jerárquico + formato de roles: ver utils/roles/roleDisplay.ts
// (compartido con PermissionMatrix.vue).
const displayRoles = computed(() => sortByRoleHierarchy(props.roles))

// Grid CSS: columnas fijas para item/ruta + una columna igual por cada rol.
const gridTemplateStyle = computed(() => ({
  gridTemplateColumns: `minmax(220px, 2fr) minmax(140px, 1fr) repeat(${displayRoles.value.length}, minmax(90px, 0.8fr))`,
}))

function toggleCollapse(groupKey: string) {
  collapsedGroups[groupKey] = !collapsedGroups[groupKey]
}

function isCollapsed(groupKey: string): boolean {
  return !!collapsedGroups[groupKey]
}

// Agrupación por parent_key: el backend ya manda la jerarquía explícita
// (SidebarMenuService::getFlatMenuItemsForMatrix), no hace falta inferirla
// parseando el nombre como en PermissionMatrix::categorize().
const groups = computed(() => {
  const term = search.value.trim().toLowerCase()

  const matches = (item: SidebarMenuItem) => {
    if (!term) return true
    const roleNames = props.roles
      .filter(r => !isVisibleDefault(item.key, r.id))
      .map(r => formatRoleName(r.name).toLowerCase())
      .join(' ')
    return `${item.title} ${item.key} ${roleNames}`.toLowerCase().includes(term)
  }

  function isVisibleDefault(itemKey: string, roleId: string) {
    return props.isVisible(itemKey, roleId)
  }

  const parents = props.items.filter(i => i.parent_key === null)

  return parents
    .map(parent => {
      const children = props.items.filter(i => i.parent_key === parent.key)
      const items = [parent, ...children].filter(matches)
      return { key: parent.key, title: parent.title, items }
    })
    .filter(group => group.items.length > 0)
})

// ── Selección masiva (columna = rol completo, categoría = grupo completo) ──
// Sin isCellDisabled: a diferencia de PermissionMatrix, ninguna celda está
// deshabilitada aquí (comportamiento intencional, ver comentario arriba).
const bulkSelection = useMatrixBulkSelection<SidebarMenuItem, SidebarMenuRole>({
  rows: () => groups.value.flatMap(g => g.items),
  cols: () => displayRoles.value,
  getRowId: item => item.key,
  getValue: (itemKey, roleId) => props.isVisible(itemKey, roleId),
  setValue: (itemKey, roleId) => emit('toggle-visibility', itemKey, roleId),
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
function setCategoryCheckboxRef(el: unknown, groupKey: string) {
  if (el) {
    const input = el as HTMLInputElement
    categoryCheckboxRefs.set(groupKey, input)
    const group = groups.value.find(g => g.key === groupKey)
    if (group) {
      input.indeterminate = bulkSelection.isCategoryPartiallyChecked(group.items)
    }
  } else {
    categoryCheckboxRefs.delete(groupKey)
  }
}

async function handleSave() {
  saving.value = true
  try {
    emit('save')
  } finally {
    saving.value = false
  }
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
   bloquear el eje vertical ni el position:sticky del header. ── */
.matrix-card {
  border: 1px solid var(--ds-border);
  border-radius: 12px;
  background: var(--ds-surface);
  overflow-x: auto;
  overflow-y: visible;
}

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
  background: #667eea;
  flex-shrink: 0;
}
.category-title {
  font-weight: 700;
  font-size: 0.82rem;
  letter-spacing: 0.02em;
  text-transform: uppercase;
  color: var(--ds-text);
}
.category-count {
  font-size: 0.78rem;
  font-weight: 500;
  color: var(--ds-text-muted);
  text-transform: none;
}

.grid-cell {
  display: flex;
  align-items: center;
  padding: 0.55rem 0.75rem;
  border-bottom: 1px solid var(--ds-border);
  min-width: 0;
}

.cell-permission { gap: 0.5rem; padding-left: 1.75rem; }
.cell-permission--child { padding-left: 2.75rem; }
.permission-key-icon { color: var(--ds-text-muted); font-size: 0.65rem; flex-shrink: 0; }
.permission-text { display: flex; flex-direction: column; gap: 0.1rem; min-width: 0; }
.permission-name { font-size: 0.87rem; font-weight: 500; color: var(--ds-text); }
.permission-slug { font-size: 0.72rem; color: var(--ds-text-muted); background: transparent; }

.cell-module { color: var(--ds-text-muted); }
.module-badge { font-weight: 500; font-family: monospace; font-size: 0.72rem; }

.cell-role { justify-content: center; }

.custom-checkbox-wrapper { display: flex; justify-content: center; align-items: center; }
.custom-checkbox { width: 18px; height: 18px; cursor: pointer; accent-color: #34d399; }
.custom-checkbox:disabled { cursor: not-allowed; opacity: 0.6; }

.bulk-checkbox-wrapper { display: flex; align-items: center; cursor: pointer; }
.bulk-checkbox { accent-color: #6366f1; }

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
.mobile-perm-card__header { margin-bottom: 0.6rem; }
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
