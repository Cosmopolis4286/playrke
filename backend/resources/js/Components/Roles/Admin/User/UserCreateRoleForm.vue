<script setup lang="ts" name="UserCreateRoleForm">
  import { computed, ref } from 'vue'
  import type { AdminRole } from '@/js/types/roles/admin/admin-user'

  /* ==========================================================================
   * Props
   * ========================================================================== */

  const props = defineProps<{
    roles: AdminRole[]
  }>()

  /* ==========================================================================
   * Emits
   * ========================================================================== */

  const emit = defineEmits<{
    (e: 'select', roleName: string): void
  }>()

  /** Mapa rol.name (lowercase) → disponibilidad de vista de creación propia. */
  const ROLE_ROUTES = ['commerce', 'customer', 'player', 'seller', 'treasurer']

  const ROLE_META: Record<string, { icon: string; description: string }> = {
    commerce: { icon: 'fas fa-store', description: 'Comercio afiliado que gestiona ventas y comisiones.' },
    customer: { icon: 'fas fa-user', description: 'Cliente final que participa en los juegos.' },
    player: { icon: 'fas fa-gamepad', description: 'Jugador registrado en la plataforma.' },
    seller: { icon: 'fas fa-cash-register', description: 'Vendedor de cartones/tickets en punto físico.' },
    treasurer: { icon: 'fas fa-hand-holding-usd', description: 'Gestiona movimientos y cierres de caja.' },
  }

  const availableRoles = computed(() =>
    props.roles
      .filter(r => ROLE_ROUTES.includes(r.name.toLowerCase()))
      .map(r => ({
        key: r.name.toLowerCase(),
        label: r.name.charAt(0).toUpperCase() + r.name.slice(1),
        icon: ROLE_META[r.name.toLowerCase()]?.icon ?? 'fas fa-user-plus',
        description: ROLE_META[r.name.toLowerCase()]?.description ?? '',
      }))
  )

  const selectedRole = ref<string | null>(null)

  function selectRole(key: string) {
    selectedRole.value = key
    emit('select', key)
  }

  /* ==========================================================================
   * Contrato ModalFormExpose (no requiere validación: cada tarjeta ya dispara
   * el submit al hacer click, así que submit() aquí solo confirma el estado).
   * ========================================================================== */

  const isValid = computed(() => !!selectedRole.value)

  function submit(): boolean {
    return isValid.value
  }

  defineExpose({ submit, isValid })
</script>

<template>
  <div>
    <p class="text-muted small mb-3">Selecciona el rol del usuario que deseas registrar:</p>

    <div v-if="availableRoles.length === 0" class="text-muted text-center py-4">
      No hay roles disponibles para registrar usuarios.
    </div>

    <div v-else class="ucr-grid">
      <button
        v-for="role in availableRoles"
        :key="role.key"
        type="button"
        class="ucr-card"
        :class="{ 'ucr-card--selected': selectedRole === role.key }"
        @click="selectRole(role.key)"
      >
        <span class="ucr-card__icon"><i :class="role.icon"></i></span>
        <span class="ucr-card__label">{{ role.label }}</span>
        <span class="ucr-card__desc">{{ role.description }}</span>
      </button>
    </div>
  </div>
</template>

<style scoped>
.ucr-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 0.85rem;
}

.ucr-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 0.5rem;
  padding: 1.1rem 0.85rem;
  border: 1px solid var(--ds-border-2);
  border-radius: 0.5rem;
  background: var(--ds-surface);
  color: var(--ds-text);
  cursor: pointer;
  transition: border-color 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
}

.ucr-card:hover,
.ucr-card:focus-visible {
  border-color: #764ba2;
  transform: translateY(-2px);
  box-shadow: 0 0.35rem 0.75rem rgba(118, 75, 162, 0.18);
  outline: none;
}

.ucr-card--selected {
  border-color: #764ba2;
  box-shadow: 0 0 0 2px rgba(118, 75, 162, 0.35);
}

.ucr-card__icon {
  width: 2.75rem;
  height: 2.75rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: linear-gradient(135deg, rgba(102,126,234,.15), rgba(118,75,162,.15));
  color: #764ba2;
  font-size: 1.15rem;
}

.ucr-card__label {
  font-weight: 600;
  font-size: 0.95rem;
}

.ucr-card__desc {
  font-size: 0.72rem;
  color: var(--ds-text-muted);
  line-height: 1.3;
}
</style>
