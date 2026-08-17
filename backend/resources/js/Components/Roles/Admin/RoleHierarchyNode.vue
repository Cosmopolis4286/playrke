<template>
  <li class="hierarchy-node">
    <div class="node-card" :class="{ 'is-inactive': !node.status }" :style="{ '--node-color': node.color || '#64748b' }">
      <button
        v-if="node.children.length"
        class="node-toggle"
        type="button"
        :aria-label="collapsed ? 'Expandir' : 'Colapsar'"
        @click="collapsed = !collapsed"
      >
        <i class="fas" :class="collapsed ? 'fa-chevron-right' : 'fa-chevron-down'"></i>
      </button>
      <span v-else class="node-toggle-spacer"></span>

      <span class="node-icon"><i class="fas" :class="node.icon || 'fa-user'"></i></span>

      <div class="node-body">
        <div class="node-title-row">
          <span class="node-name">{{ formatRoleName(node.name) }}</span>
          <span v-if="!node.status" class="badge badge-secondary node-status">Inactivo</span>
          <span class="badge node-order" title="Orden/nivel">#{{ node.sort_order }}</span>
        </div>
        <p class="node-function">{{ node.description || 'Sin descripción' }}</p>
        <div class="node-stats">
          <span title="Usuarios con este rol"><i class="fas fa-users"></i> {{ node.users_count }}</span>
          <span title="Permisos asignados"><i class="fas fa-key"></i> {{ node.permissions_count }}</span>
          <span class="node-guard" :title="`Guard: ${node.guard_name}`">{{ node.guard_name }}</span>
          <span v-if="node.status_changed_at" title="Último cambio de estado">
            <i class="fas fa-history"></i> <RelativeTime :value="node.status_changed_at" :show-icon="false" />
          </span>
        </div>
      </div>

      <div class="node-actions">
        <span v-if="locked" class="badge badge-locked" :title="lockedReason">
          <i class="fas fa-lock"></i>
        </span>
        <label v-else class="node-status-switch" :class="{ 'is-busy': togglingStatus }" title="Activar/desactivar rol">
          <input
            type="checkbox"
            :checked="node.status"
            :disabled="togglingStatus"
            @change="$emit('toggle-status', node)"
          />
          <span class="node-status-track"><span class="node-status-thumb"></span></span>
        </label>

        <button v-if="!locked" class="btn btn-sm btn-link node-edit" title="Editar rol" @click="$emit('edit', node)">
          <i class="fas fa-pencil-alt"></i>
        </button>
      </div>
    </div>

    <ul v-if="node.children.length && !collapsed" class="node-children">
      <RoleHierarchyNode
        v-for="child in node.children"
        :key="child.id"
        :node="child"
        :toggling-status-id="togglingStatusId"
        :is-superadmin="isSuperadmin"
        @edit="$emit('edit', $event)"
        @toggle-status="$emit('toggle-status', $event)"
      />
    </ul>
  </li>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import RelativeTime from '@/js/Components/Common/RelativeTime/RelativeTime.vue'
import { formatRoleName } from '@/js/utils/roles/roleDisplay'
import type { RoleHierarchyNode as RoleHierarchyNodeType } from '@/js/types/roles/admin/role'

const props = defineProps<{
  node: RoleHierarchyNodeType
  togglingStatusId?: string | null
  /** Actor actual — un admin (no superadmin) no puede gestionar 'superadmin' ni 'admin'. */
  isSuperadmin: boolean
}>()

defineEmits<{
  edit: [node: RoleHierarchyNodeType]
  'toggle-status': [node: RoleHierarchyNodeType]
}>()

const collapsed = ref(false)
const togglingStatus = computed(() => props.togglingStatusId === props.node.id)

const locked = computed(() =>
  props.node.name === 'superadmin' || (props.node.name === 'admin' && !props.isSuperadmin)
)
const lockedReason = computed(() =>
  props.node.name === 'superadmin'
    ? 'El rol superadmin no puede desactivarse'
    : 'Solo un superadmin puede gestionar el rol admin'
)
</script>

<style scoped>
.hierarchy-node { list-style: none; }

.node-card {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
  padding: 0.65rem 0.75rem;
  border: 1px solid var(--ds-border);
  border-left: 3px solid var(--node-color);
  border-radius: 10px;
  background: var(--ds-surface-2);
  position: relative;
}
.node-card.is-inactive { opacity: 0.55; }

.node-toggle, .node-toggle-spacer {
  width: 1.5rem;
  flex-shrink: 0;
}
.node-toggle {
  background: none;
  border: none;
  color: var(--ds-text-2);
  cursor: pointer;
  padding: 0.15rem;
  margin-top: 0.15rem;
}
.node-toggle:hover { color: var(--ds-text); }

.node-icon {
  flex-shrink: 0;
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: color-mix(in srgb, var(--node-color) 18%, transparent);
  color: var(--node-color);
  font-size: 0.9rem;
}

.node-body { flex: 1 1 auto; min-width: 0; }

.node-title-row { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
.node-name { font-weight: 600; color: var(--ds-text); font-size: 0.92rem; }
.node-status { font-size: 0.65rem; }
.node-order {
  font-size: 0.65rem;
  background: var(--ds-surface);
  color: var(--ds-text-muted);
  border: 1px solid var(--ds-border);
}

.node-function {
  margin: 0.2rem 0 0.35rem;
  font-size: 0.8rem;
  color: var(--ds-text-2);
  line-height: 1.35;
}

.node-stats {
  display: flex;
  align-items: center;
  gap: 0.9rem;
  font-size: 0.75rem;
  color: var(--ds-text-muted);
}
.node-stats i { margin-right: 0.25rem; }
.node-guard {
  text-transform: uppercase;
  letter-spacing: 0.03em;
  font-size: 0.65rem;
  padding: 0.1rem 0.4rem;
  border-radius: 4px;
  background: var(--ds-surface);
  border: 1px solid var(--ds-border);
}

.node-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-shrink: 0;
}

.node-edit {
  flex-shrink: 0;
  color: var(--ds-text-muted);
}
.node-edit:hover { color: var(--ds-text); }

.badge-locked {
  background: var(--ds-surface);
  color: var(--ds-text-muted);
  border: 1px solid var(--ds-border);
  font-size: 0.65rem;
  padding: 0.3rem 0.5rem;
}

.node-status-switch {
  display: inline-flex;
  align-items: center;
  cursor: pointer;
  user-select: none;
}
.node-status-switch.is-busy { opacity: 0.6; cursor: not-allowed; }
.node-status-switch input { position: absolute; opacity: 0; width: 0; height: 0; }

.node-status-track {
  position: relative;
  width: 30px;
  height: 18px;
  border-radius: 999px;
  background: var(--ds-border-2);
  flex-shrink: 0;
  transition: background 0.15s;
}
.node-status-thumb {
  position: absolute;
  top: 2px;
  left: 2px;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: #fff;
  box-shadow: 0 1px 2px rgba(0,0,0,0.25);
  transition: transform 0.15s;
}
.node-status-switch input:checked + .node-status-track { background: #34d399; }
.node-status-switch input:checked + .node-status-track .node-status-thumb { transform: translateX(12px); }

.node-children {
  list-style: none;
  margin: 0.5rem 0 0 2.4rem;
  padding-left: 0.9rem;
  border-left: 2px dashed var(--ds-border-2);
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
</style>
