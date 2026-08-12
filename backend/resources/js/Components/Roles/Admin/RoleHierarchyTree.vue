<template>
  <div class="hierarchy-panel">
    <div class="hierarchy-header">
      <div>
        <h3 class="hierarchy-title"><i class="fas fa-sitemap mr-2"></i>Organigrama de Roles</h3>
        <p class="hierarchy-subtitle">
          Estructura real del sistema (quién depende de quién). Edita orden, función, ícono y color de cada rol.
        </p>
      </div>
      <button class="btn btn-sm btn-outline-secondary" :disabled="loading" @click="$emit('reload')">
        <i class="fas fa-sync-alt mr-1" :class="{ 'fa-spin': loading }"></i>Recargar
      </button>
    </div>

    <div v-if="loading && !tree.length" class="text-center py-5">
      <span class="spinner-border spinner-border-sm text-primary mr-2"></span>
      Cargando organigrama...
    </div>

    <ul v-else class="hierarchy-root">
      <RoleHierarchyNode
        v-for="root in tree"
        :key="root.id"
        :node="root"
        @edit="openEditor"
      />
    </ul>

    <Modal
      v-model="isEditing"
      title="Editar rol"
      icon="fa-user-shield"
      size="md"
      :show-confirm="true"
      confirm-text="Guardar cambios"
      :confirm-disabled="saving"
      :on-submit="handleSave"
    >
      <div v-if="editingNode" class="edit-form">
        <div class="form-group">
          <label>Rol</label>
          <div class="d-flex align-items-center gap-2">
            <RoleBadge :name="editingNode.name" />
            <span class="text-muted small">{{ editingNode.prefix }}</span>
          </div>
        </div>

        <div class="form-group">
          <label for="role-function">Función / descripción</label>
          <textarea
            id="role-function"
            v-model="form.description"
            class="form-control"
            rows="3"
            maxlength="255"
          ></textarea>
        </div>

        <div class="form-row">
          <div class="form-group col-6">
            <label for="role-order">Orden / nivel</label>
            <input id="role-order" v-model.number="form.sort_order" type="number" min="0" max="65535" class="form-control" />
            <small class="form-text text-muted">Menor número = más alto en la jerarquía.</small>
          </div>
          <div class="form-group col-6">
            <label for="role-color">Color</label>
            <input id="role-color" v-model="colorValue" type="color" class="form-control form-control-color" />
          </div>
        </div>

        <div class="form-group">
          <label for="role-icon">Ícono (clase Font Awesome)</label>
          <div class="icon-input">
            <span class="icon-preview"><i class="fas" :class="form.icon || 'fa-user'"></i></span>
            <input id="role-icon" v-model="form.icon" type="text" class="form-control" placeholder="fa-user-shield" />
          </div>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import Modal from '@/js/Components/Common/Modal/Modal.vue'
import RoleBadge from '@/js/Components/Roles/Admin/RoleBadge.vue'
import RoleHierarchyNode from '@/js/Components/Roles/Admin/RoleHierarchyNode.vue'
import { useToast } from 'vue-toastification'
import type { RoleHierarchyNode as RoleHierarchyNodeType, RoleMetaUpdate } from '@/js/types/roles/admin/role'

const props = defineProps<{
  tree: RoleHierarchyNodeType[]
  loading: boolean
  onSave: (roleId: string, attributes: RoleMetaUpdate) => Promise<void>
}>()

const emit = defineEmits<{
  reload: []
}>()

const toast = useToast()

const isEditing = ref(false)
const saving = ref(false)
const editingNode = ref<RoleHierarchyNodeType | null>(null)
const form = ref<{ description: string; sort_order: number; icon: string; color: string }>({
  description: '',
  sort_order: 0,
  icon: '',
  color: '#64748b',
})

const colorValue = computed({
  get: () => form.value.color || '#64748b',
  set: (value: string) => { form.value.color = value },
})

function openEditor(node: RoleHierarchyNodeType) {
  editingNode.value = node
  form.value = {
    description: node.description ?? '',
    sort_order: node.sort_order,
    icon: node.icon ?? '',
    color: node.color ?? '#64748b',
  }
  isEditing.value = true
}

async function handleSave(): Promise<boolean> {
  if (!editingNode.value) return false
  saving.value = true
  try {
    await props.onSave(editingNode.value.id, {
      description: form.value.description.trim(),
      sort_order: form.value.sort_order,
      icon: form.value.icon.trim() || null,
      color: form.value.color || null,
    })
    toast.success('Rol actualizado correctamente')
    return true
  } catch {
    toast.error('No se pudo actualizar el rol')
    return false
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.hierarchy-panel {
  background: var(--ds-surface);
  border: 1px solid var(--ds-border);
  border-radius: 12px;
  padding: 1.25rem;
}

.hierarchy-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.25rem;
  flex-wrap: wrap;
}
.hierarchy-title { font-size: 1rem; font-weight: 700; margin: 0; color: var(--ds-text); }
.hierarchy-subtitle { font-size: 0.82rem; color: var(--ds-text-muted); margin: 0.25rem 0 0; }

.hierarchy-root {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.edit-form .form-group { margin-bottom: 1rem; }
.icon-input { display: flex; align-items: center; gap: 0.5rem; }
.icon-preview {
  flex-shrink: 0;
  width: 2.2rem;
  height: 2.2rem;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border);
  color: var(--ds-text-2);
}
.form-control-color { height: calc(1.5em + 0.75rem + 2px); padding: 0.25rem; }
</style>
