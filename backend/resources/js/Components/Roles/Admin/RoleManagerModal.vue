<template>
  <Modal
    v-model="isOpen"
    title="Gestionar roles"
    icon="fa-user-shield"
    size="lg"
    @update:model-value="onVisibilityChange"
  >
    <div v-if="loading" class="text-center py-4">
      <span class="spinner-border spinner-border-sm text-primary mr-2"></span>
      Cargando roles...
    </div>

    <div v-else class="role-list">
      <div v-for="role in roles" :key="role.id" class="role-row" :class="{ 'is-inactive': !role.status }">
        <div class="role-row-main">
          <RoleBadge :name="role.name" />

          <template v-if="editingId === role.id">
            <input
              v-model="editValue"
              type="text"
              class="form-control form-control-sm role-desc-input"
              maxlength="255"
              @keydown.enter="saveDescription(role.id)"
              @keydown.escape="cancelEdit"
            />
            <button class="btn btn-sm btn-success" :disabled="!editValue.trim() || savingId === role.id" @click="saveDescription(role.id)">
              <i class="fas fa-check"></i>
            </button>
            <button class="btn btn-sm btn-outline-secondary" @click="cancelEdit">
              <i class="fas fa-times"></i>
            </button>
          </template>
          <template v-else>
            <span class="role-desc">{{ role.description || 'Sin descripción' }}</span>
            <button
              class="btn btn-sm btn-link"
              title="Editar descripción"
              :disabled="role.name === 'superadmin'"
              @click="startEdit(role)"
            >
              <i class="fas fa-pencil-alt"></i>
            </button>
          </template>
        </div>

        <div class="role-row-status">
          <span v-if="role.name === 'superadmin'" class="badge badge-locked" title="El rol superadmin no puede desactivarse">
            <i class="fas fa-lock mr-1"></i>Protegido
          </span>
          <label v-else class="role-status-switch" :class="{ 'is-busy': savingId === role.id }">
            <input
              type="checkbox"
              :checked="role.status"
              :disabled="savingId === role.id"
              @change="handleToggle(role)"
            />
            <span class="role-status-track"><span class="role-status-thumb"></span></span>
            <span class="role-status-label">{{ role.status ? 'Activo' : 'Inactivo' }}</span>
          </label>
        </div>
      </div>
    </div>

    <template #footer>
      <button class="modal-btn modal-btn--ghost" @click="isOpen = false">Cerrar</button>
    </template>
  </Modal>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import Modal from '@/js/Components/Common/Modal/Modal.vue'
import RoleBadge from '@/js/Components/Roles/Admin/RoleBadge.vue'
import Swal from 'sweetalert2'
import { useToast } from 'vue-toastification'
import { formatRoleName } from '@/js/utils/roles/roleDisplay'
import type { AdminRole } from '@/js/types/roles/admin/role'
import { useAdminRoles } from '@/js/composables/roles/admin/role/useAdminRoles'

const toast = useToast()

const isOpen = defineModel<boolean>({ required: true })

const { adminRoles: roles, loading, fetchAdminRoles, updateRoleDescription, toggleRoleStatus } = useAdminRoles()

const editingId = ref<string | null>(null)
const editValue = ref('')
const savingId = ref<string | null>(null)

watch(isOpen, open => {
  if (open) fetchAdminRoles()
})

function onVisibilityChange(open: boolean) {
  if (!open) {
    editingId.value = null
  }
}

const displayName = formatRoleName

function startEdit(role: AdminRole) {
  editingId.value = role.id
  editValue.value = role.description ?? ''
}

function cancelEdit() {
  editingId.value = null
}

async function saveDescription(roleId: string) {
  const value = editValue.value.trim()
  if (!value) return
  savingId.value = roleId
  try {
    await updateRoleDescription(roleId, value)
    editingId.value = null
  } catch {
    toast.error('No se pudo actualizar el rol')
  } finally {
    savingId.value = null
  }
}

async function handleToggle(role: AdminRole) {
  if (savingId.value) return

  if (role.status) {
    const result = await Swal.fire({
      title: 'Desactivar rol',
      html: `<p class="mb-0">Los usuarios con el rol "${displayName(role.name)}" perderán acceso al sistema hasta que lo reactives.</p>`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Sí, desactivar',
      cancelButtonText: 'Cancelar',
      reverseButtons: true,
    })
    if (!result.isConfirmed) return
  }

  savingId.value = role.id
  try {
    await toggleRoleStatus(role.id)
  } catch {
    toast.error('No se pudo actualizar el rol')
  } finally {
    savingId.value = null
  }
}
</script>

<style scoped>
.role-list { display: flex; flex-direction: column; gap: 0.5rem; }

.role-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.6rem 0.75rem;
  border: 1px solid var(--ds-border);
  border-radius: 10px;
  background: var(--ds-surface-2);
  flex-wrap: wrap;
}
.role-row.is-inactive { opacity: 0.6; }

.role-row-main {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  flex: 1 1 320px;
  min-width: 0;
}

.role-desc {
  font-size: 0.85rem;
  color: var(--ds-text-2);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.role-desc-input { max-width: 260px; }

.role-row-status { flex-shrink: 0; }

.badge-locked {
  background: var(--ds-surface);
  color: var(--ds-text-muted);
  border: 1px solid var(--ds-border);
  font-size: 0.72rem;
  padding: 0.3rem 0.6rem;
}

.role-status-switch {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  user-select: none;
}
.role-status-switch.is-busy { opacity: 0.6; cursor: not-allowed; }
.role-status-switch input { position: absolute; opacity: 0; width: 0; height: 0; }

.role-status-track {
  position: relative;
  width: 34px;
  height: 20px;
  border-radius: 999px;
  background: var(--ds-border-2);
  flex-shrink: 0;
  transition: background 0.15s;
}
.role-status-thumb {
  position: absolute;
  top: 2px;
  left: 2px;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: #fff;
  box-shadow: 0 1px 2px rgba(0,0,0,0.25);
  transition: transform 0.15s;
}
.role-status-switch input:checked + .role-status-track { background: #34d399; }
.role-status-switch input:checked + .role-status-track .role-status-thumb { transform: translateX(14px); }

.role-status-label { font-size: 0.8rem; font-weight: 600; color: var(--ds-text-2); min-width: 3.2rem; }
</style>
