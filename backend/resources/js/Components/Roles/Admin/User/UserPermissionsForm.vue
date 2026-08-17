<script setup lang="ts" name="UserPermissionsForm">
  import { computed, ref } from 'vue'
  import type { CatalogPermission, SpecificPermissionGrant } from '@/js/composables/roles/admin/user/useAdminUserPermissions'

  /* ==========================================================================
   * Props
   * ========================================================================== */

  const props = defineProps<{
    modelValue: SpecificPermissionGrant[]
    catalog: CatalogPermission[]
    viaRoles: string[]
    reason: string
  }>()

  /* ==========================================================================
   * Emits
   * ========================================================================== */

  const emit = defineEmits<{
    (e: 'update:modelValue', value: SpecificPermissionGrant[]): void
    (e: 'update:reason', value: string): void
  }>()

  /* ==========================================================================
   * Estado local (selección editable de permisos específicos + su expiración)
   * ========================================================================== */

  const grantsByName = computed<Map<string, SpecificPermissionGrant>>(() => {
    const map = new Map<string, SpecificPermissionGrant>()
    for (const g of props.modelValue) map.set(g.name, g)
    return map
  })
  const searchQuery = ref('')

  const reasonValue = computed<string>({
    get: () => props.reason,
    set: value => emit('update:reason', value)
  })

  /* ==========================================================================
   * Opciones rápidas de expiración
   * ========================================================================== */

  type ExpiryPreset = 'never' | '7' | '30' | '90' | 'custom'

  const EXPIRY_PRESETS: { value: ExpiryPreset; label: string }[] = [
    { value: 'never', label: 'Permanente' },
    { value: '7', label: '7 días' },
    { value: '30', label: '30 días' },
    { value: '90', label: '90 días' },
    { value: 'custom', label: 'Fecha personalizada' },
  ]

  function presetFromExpiresAt(expiresAt: string | null): ExpiryPreset {
    if (!expiresAt) return 'never'
    const days = Math.round((new Date(expiresAt).getTime() - Date.now()) / 86_400_000)
    if (days === 7 || days === 30 || days === 90) return String(days) as ExpiryPreset
    return 'custom'
  }

  function applyPreset(name: string, preset: ExpiryPreset) {
    const next = new Map(grantsByName.value)
    const current = next.get(name)
    if (!current) return

    if (preset === 'never') {
      next.set(name, { ...current, expires_at: null })
    } else if (preset === 'custom') {
      // Deja la fecha actual (o vacía) para que el <input type="date"> la fije.
      next.set(name, { ...current, expires_at: current.expires_at ?? new Date().toISOString() })
    } else {
      const days = Number(preset)
      const date = new Date(Date.now() + days * 86_400_000)
      next.set(name, { ...current, expires_at: date.toISOString() })
    }
    emit('update:modelValue', [...next.values()])
  }

  function applyCustomDate(name: string, dateStr: string) {
    const next = new Map(grantsByName.value)
    const current = next.get(name)
    if (!current || !dateStr) return
    next.set(name, { ...current, expires_at: new Date(dateStr).toISOString() })
    emit('update:modelValue', [...next.values()])
  }

  function dateInputValue(expiresAt: string | null): string {
    return expiresAt ? expiresAt.slice(0, 10) : ''
  }

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
    return hasViaRole(name) || grantsByName.value.has(name)
  }

  function toggle(perm: CatalogPermission) {
    if (isLocked(perm)) return
    const next = new Map(grantsByName.value)
    if (next.has(perm.name)) {
      next.delete(perm.name)
    } else {
      next.set(perm.name, { name: perm.name, expires_at: null })
    }
    emit('update:modelValue', [...next.values()])
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
      solo se pueden otorgar/revocar permisos <strong>específicos</strong> adicionales. Cada uno puede
      tener su propia fecha de expiración; al vencer se revoca automáticamente y se notifica.
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

          <div v-if="!isLocked(perm) && grantsByName.has(perm.name)" class="upm-expiry">
            <i class="fas fa-clock upm-expiry__icon"></i>
            <select
              class="upm-expiry__select"
              :value="presetFromExpiresAt(grantsByName.get(perm.name)!.expires_at)"
              @change="applyPreset(perm.name, ($event.target as HTMLSelectElement).value as any)"
            >
              <option v-for="opt in EXPIRY_PRESETS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
            <input
              v-if="presetFromExpiresAt(grantsByName.get(perm.name)!.expires_at) === 'custom'"
              type="date"
              class="upm-expiry__date"
              :min="new Date(Date.now() + 86400000).toISOString().slice(0, 10)"
              :value="dateInputValue(grantsByName.get(perm.name)!.expires_at)"
              @change="applyCustomDate(perm.name, ($event.target as HTMLInputElement).value)"
            />
            <span v-if="grantsByName.get(perm.name)!.expires_at" class="upm-expiry__hint">
              Expira {{ new Date(grantsByName.get(perm.name)!.expires_at!).toLocaleDateString('es-VE') }}
            </span>
          </div>
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

.upm-expiry {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0.4rem 0 0 1.6rem;
  padding: 0.4rem 0.6rem;
  background: var(--ds-surface-2);
  border-radius: 6px;
}
.upm-expiry__icon { font-size: 0.72rem; color: var(--ds-text-muted); }
.upm-expiry__select,
.upm-expiry__date {
  font-size: 0.78rem;
  padding: 0.25rem 0.5rem;
  border: 1px solid var(--ds-border-2);
  border-radius: 5px;
  background: var(--ds-surface);
  color: var(--ds-text);
}
.upm-expiry__hint { font-size: 0.74rem; color: var(--ds-text-muted); margin-left: auto; }
</style>
