<script setup lang="ts" name="UserCreateRoleForm">
  import { computed } from 'vue'
  import type { AdminRole } from '@/js/types/roles/admin/admin-user'
  import { ROLE_ROUTE_MAP } from '@/js/types/roles/admin/admin-user'
  import { formatRoleName, sortByRoleHierarchy } from '@/js/utils/roles/roleDisplay'

  /* ==========================================================================
   * Props
   * ========================================================================== */

  const props = defineProps<{
    roles: AdminRole[]
    /** 'admin' solo se ofrece a un superadmin — AdministratorController (/admins) está protegido con role:superadmin. */
    isSuperadmin?: boolean
  }>()

  /* ==========================================================================
   * Emits
   * ========================================================================== */

  const emit = defineEmits<{
    (e: 'select', roleName: string): void
  }>()

  /**
   * Roles con vista de creación/gestión propia — derivado de ROLE_ROUTE_MAP
   * (única fuente real: son las rutas que de verdad existen en admin.php),
   * más 'admin' explícito (caso especial: AdministratorController vive en
   * /admins, protegido con role:superadmin, fuera del flujo genérico de
   * ROLE_ROUTE_MAP). Antes esta lista se duplicaba a mano aquí — un rol
   * nuevo con vista propia solo necesita agregarse a ROLE_ROUTE_MAP.
   */
  const ROLE_ROUTES = [...Object.keys(ROLE_ROUTE_MAP), 'admin']

  /**
   * Mismo nombre, descripción, ícono, color y orden que "Gestionar roles" y
   * el Organigrama de Roles (RoleHierarchyService::DOMAIN_TREE,
   * Role.description/icon/color/sort_order en BD) — antes esta tarjeta
   * tenía su propio texto hardcodeado (ROLE_META) que no coincidía con esas
   * dos pantallas, y el ícono se renderizaba sin el prefijo FontAwesome
   * 'fas' (Role.icon en BD es solo 'fa-store', no 'fas fa-store'), así que
   * nunca pintaba — mismo patrón de armado que RoleHierarchyNode.vue.
   */
  const availableRoles = computed(() =>
    sortByRoleHierarchy(
      props.roles
        .filter(r => ROLE_ROUTES.includes(r.name.toLowerCase()))
        .filter(r => r.name.toLowerCase() !== 'admin' || props.isSuperadmin)
    ).map(r => ({
      key: r.name.toLowerCase(),
      label: formatRoleName(r.name),
      icon: r.icon || 'fa-user-plus',
      color: r.color || '#764ba2',
      description: r.description ?? '',
    }))
  )

  /** Cada tarjeta redirige de inmediato — no hay paso de confirmación aparte. */
  function selectRole(key: string) {
    emit('select', key)
  }
</script>

<template>
  <div>
    <p class="text-muted small mb-2">Selecciona el rol del usuario que deseas registrar:</p>

    <div v-if="availableRoles.length === 0" class="text-muted text-center py-4">
      No hay roles disponibles para registrar usuarios.
    </div>

    <div v-else class="ucr-grid">
      <button
        v-for="role in availableRoles"
        :key="role.key"
        type="button"
        class="ucr-card"
        :style="{ '--role-color': role.color }"
        :title="role.description"
        @click="selectRole(role.key)"
      >
        <span class="ucr-card__icon"><i class="fas" :class="role.icon"></i></span>
        <span class="ucr-card__label">{{ role.label }}</span>
      </button>
    </div>
  </div>
</template>

<style scoped>
.ucr-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(96px, 1fr));
  gap: 0.5rem;
}

.ucr-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 0.35rem;
  padding: 0.65rem 0.4rem;
  border: 1px solid var(--ds-border-2);
  border-radius: 0.5rem;
  background: var(--ds-surface);
  color: var(--ds-text);
  cursor: pointer;
  transition: border-color 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
}

.ucr-card:hover,
.ucr-card:focus-visible {
  border-color: var(--role-color, #764ba2);
  transform: translateY(-2px);
  box-shadow: 0 0.3rem 0.6rem color-mix(in srgb, var(--role-color, #764ba2) 25%, transparent);
  outline: none;
}

.ucr-card__icon {
  width: 1.9rem;
  height: 1.9rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: color-mix(in srgb, var(--role-color, #764ba2) 15%, transparent);
  color: var(--role-color, #764ba2);
  font-size: 0.85rem;
}

.ucr-card__label {
  font-weight: 600;
  font-size: 0.78rem;
}
</style>
