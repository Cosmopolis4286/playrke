<script setup lang="ts" name="UserPermissionsForm">
  import { computed, ref } from 'vue'
  import type { CatalogPermission } from '@/js/composables/roles/admin/user/useAdminUserPermissions'

  /* ==========================================================================
   * Props
   * ========================================================================== */

  const props = defineProps<{
    modelValue: string[]
    catalog: CatalogPermission[]
    viaRoles: string[]
    reason: string
  }>()

  /* ==========================================================================
   * Emits
   * ========================================================================== */

  const emit = defineEmits<{
    (e: 'update:modelValue', value: string[]): void
    (e: 'update:reason', value: string): void
  }>()

  /* ==========================================================================
   * Estado local (selección editable de permisos específicos)
   * ========================================================================== */

  const selected = computed<Set<string>>({
    get: () => new Set(props.modelValue),
    set: value => emit('update:modelValue', [...value])
  })
  const searchQuery = ref('')

  const reasonValue = computed<string>({
    get: () => props.reason,
    set: value => emit('update:reason', value)
  })

  /* ==========================================================================
   * Agrupación por módulo
   * ========================================================================== */

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
      ? props.catalog.filter(p => p.name.toLowerCase().includes(q) || (p.description ?? '').toLowerCase().includes(q))
      : props.catalog

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

  /* ==========================================================================
   * Estado de cada permiso: heredado por rol, del sistema (no editable aquí
   * en ningún caso — ver UserPermissionService::syncSpecificPermissions), o
   * específico editable.
   * ========================================================================== */

  function hasViaRole(name: string): boolean {
    return props.viaRoles.includes(name)
  }

  function isLocked(perm: CatalogPermission): boolean {
    return hasViaRole(perm.name) || perm.is_system
  }

  function lockedReason(perm: CatalogPermission): string {
    if (perm.is_system) return 'Permiso del sistema — no puede asignarse individualmente'
    if (hasViaRole(perm.name)) return 'Ya lo tiene por su rol'
    return ''
  }

  function isChecked(name: string): boolean {
    return hasViaRole(name) || selected.value.has(name)
  }

  function toggle(perm: CatalogPermission) {
    if (isLocked(perm)) return
    const next = new Set(selected.value)
    if (next.has(perm.name)) {
      next.delete(perm.name)
    } else {
      next.add(perm.name)
    }
    selected.value = next
  }

  /* ==========================================================================
   * Submit
   * ========================================================================== */

  const isValid = computed(() => true)

  function submit(): boolean {
    return true
  }

  defineExpose({ submit, isValid })
</script>

<template>
  <div>
    <p class="text-muted small mb-3">
      <i class="fas fa-info-circle mr-1"></i>
      Los permisos con candado ya los tiene por su rol o son del sistema y no pueden modificarse aquí —
      solo se pueden otorgar/revocar permisos <strong>específicos</strong> adicionales.
    </p>

    <div class="form-group mb-3">
      <label class="upm-reason-label" for="upm-reason">Motivo del cambio <span class="text-muted">(opcional, queda en el historial de auditoría)</span></label>
      <input
        id="upm-reason"
        v-model="reasonValue"
        type="text"
        class="form-control"
        placeholder="Ej. Cobertura temporal de vacaciones del tesorero"
        maxlength="255"
      />
    </div>

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
              :disabled="isLocked(perm)"
              @change="toggle(perm)"
            />
            <span class="ml-2">{{ perm.name }}</span>
            <i v-if="isLocked(perm)" class="fas fa-lock text-muted ml-2" :title="lockedReason(perm)"></i>
          </label>
          <small v-if="perm.description" class="text-muted d-block upm-item__desc">{{ perm.description }}</small>
        </div>
      </div>

      <p v-if="groupedCatalog.length === 0" class="text-muted text-center py-3">
        No hay permisos que coincidan con la búsqueda.
      </p>
    </div>
  </div>
</template>

<style scoped>
.form-control {
  width: 100%;
  padding: 0.6rem 0.9rem;
  border: 1px solid var(--ds-border-2);
  border-radius: 0.375rem;
  font-size: 0.9rem;
  background: var(--ds-surface);
  color: var(--ds-text);
}

.upm-list { display: flex; flex-direction: column; gap: 1rem; max-height: 50vh; overflow-y: auto; }

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

.upm-reason-label { font-size: 0.82rem; font-weight: 600; color: var(--ds-text-2); }
</style>
