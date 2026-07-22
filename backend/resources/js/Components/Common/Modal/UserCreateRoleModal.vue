<script setup lang="ts">
import { computed } from 'vue'
import type { AdminRole } from '@/js/types/roles/admin/admin-user'

interface Props {
  show: boolean
  roles: AdminRole[]
}

interface Emits {
  close: []
  select: [roleName: string]
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

/** Mapa rol.name (lowercase) → ruta Inertia de creación (mismo alcance que ROLE_ROUTES en useAdminUser.ts). */
const ROLE_ROUTES: Record<string, string> = {
  commerce: '/admin/commerces',
  customer: '/admin/customers',
  player: '/admin/players',
  seller: '/admin/sellers',
  treasurer: '/admin/treasurers',
}

const ROLE_META: Record<string, { icon: string; description: string }> = {
  commerce: { icon: 'fas fa-store', description: 'Comercio afiliado que gestiona ventas y comisiones.' },
  customer: { icon: 'fas fa-user', description: 'Cliente final que participa en los juegos.' },
  player: { icon: 'fas fa-gamepad', description: 'Jugador registrado en la plataforma.' },
  seller: { icon: 'fas fa-cash-register', description: 'Vendedor de cartones/tickets en punto físico.' },
  treasurer: { icon: 'fas fa-hand-holding-usd', description: 'Gestiona movimientos y cierres de caja.' },
}

const availableRoles = computed(() =>
  props.roles
    .filter(r => r.name.toLowerCase() in ROLE_ROUTES)
    .map(r => ({
      name: r.name,
      key: r.name.toLowerCase(),
      label: r.name.charAt(0).toUpperCase() + r.name.slice(1),
      icon: ROLE_META[r.name.toLowerCase()]?.icon ?? 'fas fa-user-plus',
      description: ROLE_META[r.name.toLowerCase()]?.description ?? '',
    }))
)

function selectRole(key: string) {
  emit('select', key)
}

function closeModal() {
  emit('close')
}
</script>

<template>
  <div v-if="show" class="modal fade show" tabindex="-1" style="display: block; background-color: rgba(0,0,0,.5);" @click.self="closeModal">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header ucr-header">
          <h5 class="modal-title mb-0">
            <i class="fas fa-user-plus mr-2"></i>
            Registrar nuevo usuario
          </h5>
          <button type="button" class="btn-close text-white" @click="closeModal">
            <span>&times;</span>
          </button>
        </div>

        <div class="modal-body">
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
              @click="selectRole(role.key)"
            >
              <span class="ucr-card__icon"><i :class="role.icon"></i></span>
              <span class="ucr-card__label">{{ role.label }}</span>
              <span class="ucr-card__desc">{{ role.description }}</span>
            </button>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="closeModal">
            <i class="fas fa-times mr-2"></i>
            Cancelar
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

.ucr-header {
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
</style>
