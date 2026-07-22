<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'
import type { AdminUser, AdminRole } from '@/js/types/roles/admin/admin-user'
import type { Country } from '@/js/types/country'

interface Props {
  show: boolean
  user: AdminUser | null
  roles: AdminRole[]
  countries: Country[]
  /** true si el actor autenticado es superadmin. */
  isActorSuperadmin: boolean
  /** true si el usuario editado es el propio actor autenticado. */
  isSelf: boolean
}

interface Emits {
  close: []
  success: []
  resetPassword: [userId: string | number]
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const saving = ref(false)
const errors = ref<Record<string, string[]>>({})

const form = ref({
  first_name: '',
  last_name: '',
  username: '',
  email: '',
  country_id: '',
  role_id: '',
})

const isTargetSuperadmin = computed(() => props.user?.role_name === 'superadmin')

/** El campo de rol se bloquea si el actor no es superadmin y el objetivo lo es, o si es su propio usuario. */
const isRoleLocked = computed(() =>
  props.isSelf || (isTargetSuperadmin.value && !props.isActorSuperadmin)
)

const roleLockedReason = computed(() => {
  if (props.isSelf) return 'No puedes cambiar tu propio rol'
  if (isTargetSuperadmin.value && !props.isActorSuperadmin) return 'Solo un Super Administrador puede reasignar este rol'
  return ''
})

function resetForm() {
  if (!props.user) return
  form.value = {
    first_name: props.user.first_name ?? '',
    last_name: props.user.last_name ?? '',
    username: props.user.username ?? '',
    email: props.user.email ?? '',
    country_id: props.user.country_id != null ? String(props.user.country_id) : '',
    role_id: props.user.role_id != null ? String(props.user.role_id) : '',
  }
  errors.value = {}
}

watch(() => props.show, (visible) => {
  if (visible) resetForm()
})

function fieldError(field: string): string {
  return errors.value[field]?.[0] ?? ''
}

async function save() {
  if (!props.user) return

  saving.value = true
  errors.value = {}

  try {
    const payload: Record<string, string> = {
      first_name: form.value.first_name,
      last_name: form.value.last_name,
      username: form.value.username,
      email: form.value.email,
      country_id: form.value.country_id,
    }

    if (!isRoleLocked.value && form.value.role_id) {
      payload.role_id = form.value.role_id
    }

    await backendClient.patch(`/admin/user/${props.user.id}`, payload)
    showToast('Usuario actualizado correctamente', 'success')
    emit('success')
  } catch (error: any) {
    if (error?.response?.status === 422) {
      errors.value = error.response.data?.errors ?? {}
    } else {
      showToast(error?.response?.data?.message ?? 'Error al actualizar el usuario', 'error')
    }
  } finally {
    saving.value = false
  }
}

function closeModal() {
  emit('close')
}
</script>

<template>
  <div v-if="show" class="modal fade show" tabindex="-1" style="display: block; background-color: rgba(0,0,0,.5);">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header uem-header">
          <h5 class="modal-title mb-0">
            <i class="fas fa-user-edit mr-2"></i>
            Editar {{ user?.full_name || user?.username }}
          </h5>
          <button type="button" class="btn-close text-white" @click="closeModal">
            <span>&times;</span>
          </button>
        </div>

        <div class="modal-body">
          <div v-if="isSelf" class="alert alert-info small mb-3">
            <i class="fas fa-info-circle mr-1"></i>
            Estás editando tu propia cuenta — el rol no puede modificarse desde aquí.
          </div>
          <div v-else-if="isTargetSuperadmin && !isActorSuperadmin" class="alert alert-warning small mb-3">
            <i class="fas fa-shield-alt mr-1"></i>
            Esta cuenta es Super Administrador — solo otro Super Administrador puede cambiar su rol.
          </div>

          <form @submit.prevent="save">
            <div class="row">
              <div class="col-md-6 form-group">
                <label>Nombre</label>
                <input v-model="form.first_name" type="text" class="form-control" :class="{ 'is-invalid': fieldError('first_name') }" />
                <small v-if="fieldError('first_name')" class="text-danger">{{ fieldError('first_name') }}</small>
              </div>
              <div class="col-md-6 form-group">
                <label>Apellido</label>
                <input v-model="form.last_name" type="text" class="form-control" :class="{ 'is-invalid': fieldError('last_name') }" />
                <small v-if="fieldError('last_name')" class="text-danger">{{ fieldError('last_name') }}</small>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 form-group">
                <label>Username</label>
                <input v-model="form.username" type="text" class="form-control" :class="{ 'is-invalid': fieldError('username') }" />
                <small v-if="fieldError('username')" class="text-danger">{{ fieldError('username') }}</small>
              </div>
              <div class="col-md-6 form-group">
                <label>Email</label>
                <input v-model="form.email" type="email" class="form-control" :class="{ 'is-invalid': fieldError('email') }" />
                <small v-if="fieldError('email')" class="text-danger">{{ fieldError('email') }}</small>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 form-group">
                <label>País</label>
                <select v-model="form.country_id" class="form-control" :class="{ 'is-invalid': fieldError('country_id') }">
                  <option value="" disabled>Seleccionar país...</option>
                  <option v-for="c in countries" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                </select>
                <small v-if="fieldError('country_id')" class="text-danger">{{ fieldError('country_id') }}</small>
              </div>
              <div class="col-md-6 form-group">
                <label>
                  Rol
                  <i v-if="isRoleLocked" class="fas fa-lock text-muted ml-1" :title="roleLockedReason"></i>
                </label>
                <select
                  v-model="form.role_id"
                  class="form-control"
                  :class="{ 'is-invalid': fieldError('role_id') }"
                  :disabled="isRoleLocked"
                  :title="isRoleLocked ? roleLockedReason : undefined"
                >
                  <option v-for="r in roles" :key="r.id" :value="String(r.id)">{{ r.name }}</option>
                </select>
                <small v-if="fieldError('role_id')" class="text-danger">{{ fieldError('role_id') }}</small>
              </div>
            </div>
          </form>

          <button
            type="button"
            class="btn btn-outline-dark btn-sm mt-2"
            @click="user && emit('resetPassword', user.id)"
          >
            <i class="fas fa-key mr-2"></i>
            Resetear contraseña
          </button>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary mr-2" @click="closeModal">
            <i class="fas fa-times mr-2"></i>
            Cancelar
          </button>
          <button type="button" class="btn btn-primary" :disabled="saving" @click="save">
            <i class="fas fa-save mr-2"></i>
            {{ saving ? 'Guardando...' : 'Guardar cambios' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1050;
}

.modal-dialog {
  position: relative;
  width: auto;
  margin: 1.75rem auto;
}

.modal-lg { max-width: 700px; }

.modal-content {
  position: relative;
  background-color: var(--ds-surface);
  border: none;
  border-radius: 0.5rem;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
  overflow: hidden;
}

.modal-header {
  padding: 1rem 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.uem-header {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: #fff;
}

.modal-title { margin: 0; font-size: 1.25rem; font-weight: 500; }

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: white;
  opacity: 0.8;
  transition: opacity 0.2s;
}
.btn-close:hover { opacity: 1; }

.modal-body { padding: 1.5rem; max-height: 65vh; overflow-y: auto; }

.modal-footer {
  padding: 1rem 1.5rem;
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  border-top: 1px solid var(--ds-border);
}

.form-group { margin-bottom: 1rem; }

label {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--ds-text);
  margin-bottom: 0.35rem;
  display: block;
}

.form-control {
  width: 100%;
  padding: 0.6rem 0.9rem;
  border: 1px solid var(--ds-border-2);
  border-radius: 0.375rem;
  font-size: 0.9rem;
  background: var(--ds-surface);
  color: var(--ds-text);
}

.form-control.is-invalid { border-color: #dc3545; }
.form-control:disabled { opacity: 0.6; cursor: not-allowed; }

.text-danger { font-size: 0.78rem; }

.btn {
  padding: 0.6rem 1.25rem;
  border: none;
  border-radius: 0.375rem;
  cursor: pointer;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}

.btn-sm { padding: 0.4rem 0.9rem; font-size: 0.85rem; }

.btn-secondary { background-color: #6c757d; color: white; }
.btn-secondary:hover { background-color: #5a6268; }

.btn-outline-dark {
  background: transparent;
  border: 1px solid var(--ds-border-2);
  color: var(--ds-text);
}
.btn-outline-dark:hover { background: var(--ds-surface-2); }

.btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
.btn-primary:hover { filter: brightness(1.05); }
.btn:disabled { opacity: 0.6; cursor: not-allowed; }
</style>
