<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import backendClient from '@/js/plugins/axios'
import { showToast } from '@/js/utils/toastNotify'

interface CatalogPermission {
  id: string
  name: string
  guard_name: string
  description?: string | null
  is_system: boolean
}

interface Props {
  show: boolean
  userId: string | number
  userName: string
}

interface Emits {
  close: []
  success: []
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const loading = ref(false)
const saving = ref(false)
const catalog = ref<CatalogPermission[]>([])
const viaRoles = ref<string[]>([])
const specific = ref<string[]>([])
const selected = ref<Set<string>>(new Set())
const searchQuery = ref('')

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

const filteredCatalog = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  const list = q
    ? catalog.value.filter(p => p.name.toLowerCase().includes(q) || (p.description ?? '').toLowerCase().includes(q))
    : catalog.value

  return [...list].sort((a, b) => a.name.localeCompare(b.name))
})

const groupedCatalog = computed(() => {
  const map = new Map<string, CatalogPermission[]>()
  for (const p of filteredCatalog.value) {
    const key = moduleLabel(p.name)
    if (!map.has(key)) map.set(key, [])
    map.get(key)!.push(p)
  }
  return [...map.entries()].sort(([a], [b]) => a.localeCompare(b))
})

function hasViaRole(name: string): boolean {
  return viaRoles.value.includes(name)
}

function isChecked(name: string): boolean {
  return hasViaRole(name) || selected.value.has(name)
}

function toggle(name: string) {
  if (hasViaRole(name)) return // ya lo tiene por rol, no editable aquí
  if (selected.value.has(name)) {
    selected.value.delete(name)
  } else {
    selected.value.add(name)
  }
}

async function fetchPermissions() {
  loading.value = true
  try {
    const { data } = await backendClient.get(`/admin/user/${props.userId}/permissions`)
    catalog.value = data.catalog ?? []
    viaRoles.value = data.via_roles ?? []
    specific.value = data.specific ?? []
    selected.value = new Set(specific.value)
  } catch (error) {
    console.error(error)
    showToast('Error al cargar los permisos del usuario', 'error')
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  try {
    await backendClient.put(`/admin/user/${props.userId}/permissions`, {
      permissions: [...selected.value],
    })
    showToast('Permisos actualizados correctamente', 'success')
    emit('success')
  } catch (error) {
    console.error(error)
    showToast('Error al actualizar los permisos', 'error')
  } finally {
    saving.value = false
  }
}

function closeModal() {
  emit('close')
  searchQuery.value = ''
}

watch(() => props.show, (visible) => {
  if (visible) fetchPermissions()
})
</script>

<template>
  <div v-if="show" class="modal fade show" tabindex="-1" style="display: block; background-color: rgba(0,0,0,.5);">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header upm-header">
          <h5 class="modal-title mb-0">
            <i class="fas fa-user-shield mr-2"></i>
            Permisos de {{ userName }}
          </h5>
          <button type="button" class="btn-close text-white" @click="closeModal">
            <span>&times;</span>
          </button>
        </div>

        <div class="modal-body">
          <div v-if="loading" class="text-center py-4">
            <div class="spinner-border spinner-border-sm text-primary mr-2"></div>
            Cargando permisos...
          </div>

          <template v-else>
            <p class="text-muted small mb-3">
              <i class="fas fa-info-circle mr-1"></i>
              Los permisos con candado ya los tiene por su rol y no pueden desmarcarse aquí —
              solo se pueden otorgar/revocar permisos <strong>específicos</strong> adicionales.
            </p>

            <input
              v-model="searchQuery"
              type="text"
              class="form-control mb-3"
              placeholder="Buscar permiso..."
            />

            <div class="upm-list">
              <div v-for="[group, perms] in groupedCatalog" :key="group" class="upm-group">
                <div class="upm-group__title">{{ group }}</div>
                <div v-for="perm in perms" :key="perm.id" class="upm-item">
                  <label class="upm-item__label">
                    <input
                      type="checkbox"
                      :checked="isChecked(perm.name)"
                      :disabled="hasViaRole(perm.name) || saving"
                      @change="toggle(perm.name)"
                    />
                    <span class="ml-2">{{ perm.name }}</span>
                    <i v-if="hasViaRole(perm.name)" class="fas fa-lock text-muted ml-2" title="Ya lo tiene por su rol"></i>
                  </label>
                  <small v-if="perm.description" class="text-muted d-block upm-item__desc">{{ perm.description }}</small>
                </div>
              </div>

              <p v-if="groupedCatalog.length === 0" class="text-muted text-center py-3">
                No hay permisos que coincidan con la búsqueda.
              </p>
            </div>
          </template>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary mr-2" @click="closeModal">
            <i class="fas fa-times mr-2"></i>
            Cerrar
          </button>
          <button type="button" class="btn btn-primary" :disabled="loading || saving" @click="save">
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

.upm-header {
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

.modal-body { padding: 1.5rem; max-height: 60vh; overflow-y: auto; }

.modal-footer {
  padding: 1rem 1.5rem;
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  border-top: 1px solid var(--ds-border);
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

.upm-list { display: flex; flex-direction: column; gap: 1rem; }

.upm-group__title {
  font-weight: 700;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--ds-text-muted);
  border-bottom: 1px solid var(--ds-border);
  padding-bottom: 0.35rem;
  margin-bottom: 0.5rem;
}

.upm-item { padding: 0.35rem 0; }

.upm-item__label {
  display: flex;
  align-items: center;
  cursor: pointer;
  font-family: monospace;
  font-size: 0.85rem;
  margin: 0;
}

.upm-item__desc { margin-left: 1.6rem; font-size: 0.75rem; }

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

.btn-secondary { background-color: #6c757d; color: white; }
.btn-secondary:hover { background-color: #5a6268; }

.btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
.btn-primary:hover { filter: brightness(1.05); }
.btn:disabled { opacity: 0.6; cursor: not-allowed; }
</style>
