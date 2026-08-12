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
        <p class="node-function">{{ node.function || node.description || 'Sin descripción' }}</p>
        <div class="node-stats">
          <span title="Usuarios con este rol"><i class="fas fa-users"></i> {{ node.users_count }}</span>
          <span title="Permisos asignados"><i class="fas fa-key"></i> {{ node.permissions_count }}</span>
          <span class="node-guard" :title="`Guard: ${node.guard_name}`">{{ node.guard_name }}</span>
        </div>
      </div>

      <button class="btn btn-sm btn-link node-edit" title="Editar rol" @click="$emit('edit', node)">
        <i class="fas fa-pencil-alt"></i>
      </button>
    </div>

    <ul v-if="node.children.length && !collapsed" class="node-children">
      <RoleHierarchyNode
        v-for="child in node.children"
        :key="child.id"
        :node="child"
        @edit="$emit('edit', $event)"
      />
    </ul>
  </li>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { formatRoleName } from '@/js/utils/roles/roleDisplay'
import type { RoleHierarchyNode as RoleHierarchyNodeType } from '@/js/types/roles/admin/role'

defineProps<{
  node: RoleHierarchyNodeType
}>()

defineEmits<{
  edit: [node: RoleHierarchyNodeType]
}>()

const collapsed = ref(false)
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

.node-edit {
  flex-shrink: 0;
  color: var(--ds-text-muted);
}
.node-edit:hover { color: var(--ds-text); }

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
