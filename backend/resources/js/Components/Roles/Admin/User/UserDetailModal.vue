<script setup lang="ts">
import Modal from '@/js/Components/Common/Modal/Modal.vue'
import RoleBadge from '@/js/Components/Roles/Admin/RoleBadge.vue'
import type { AdminUserDetail } from '@/js/types/roles/admin/admin-user'

const props = defineProps<{
  modelValue: boolean
  loading: boolean
  detail: AdminUserDetail | null
  error?: string | null
  loadingMoreAudits?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'view-full-history': [userId: string]
  'view-permissions': [userId: string]
  retry: []
  'load-more-audits': []
}>()

const statusLabel: Record<string, string> = {
  ACTIVE: 'Activo',
  INACTIVE: 'Inactivo',
  DELETED: 'Eliminado',
  SUSPENDED: 'Suspendido',
  PENDING_VERIFICATION: 'Pendiente',
}

const eventLabel: Record<string, string> = {
  created: 'Creado',
  updated: 'Actualizado',
  deleted: 'Eliminado',
  restored: 'Restaurado',
  force_deleted: 'Eliminado permanentemente',
  specific_permissions_synced: 'Permisos específicos modificados',
  password_reset_by_admin: 'Contraseña restablecida (admin)',
  wallet_loaded_by_admin: 'Saldo cargado (admin)',
  xp_granted_by_admin: 'XP otorgado (admin)',
}

const eventIcon: Record<string, string> = {
  created: 'fa-plus-circle',
  updated: 'fa-pen',
  deleted: 'fa-trash',
  restored: 'fa-rotate-left',
  force_deleted: 'fa-trash-alt',
  specific_permissions_synced: 'fa-user-shield',
  password_reset_by_admin: 'fa-key',
  wallet_loaded_by_admin: 'fa-wallet',
  xp_granted_by_admin: 'fa-star',
}

function formatEventLabel(event: string): string {
  if (eventLabel[event]) return eventLabel[event]
  return event.replace(/_/g, ' ').replace(/^\w/, (c) => c.toUpperCase())
}
</script>

<template>
  <Modal
    :model-value="modelValue"
    title="Ficha de usuario"
    icon="fa-id-card"
    size="lg"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <div v-if="loading" class="ud-loading">
      <div class="spinner-border spinner-border-sm text-primary me-2"></div>
      Cargando ficha del usuario...
    </div>

    <div v-else-if="error" class="ud-error">
      <i class="fas fa-triangle-exclamation ud-error-icon"></i>
      <p class="ud-error-text">{{ error }}</p>
      <button class="modal-btn modal-btn--ghost" @click="$emit('retry')">
        <i class="fas fa-rotate-right mr-1"></i>Reintentar
      </button>
    </div>

    <div v-else-if="detail" class="ud-detail">
      <!-- Identidad -->
      <div class="ud-header">
        <div class="ud-avatar" :class="`ud-avatar--${detail.status}`">
          {{ (detail.full_name || detail.username).charAt(0).toUpperCase() }}
        </div>
        <div class="ud-header-info">
          <h3 class="ud-name">{{ detail.full_name || detail.username }}</h3>
          <p class="ud-username">@{{ detail.username }}</p>
        </div>
        <div class="ud-header-badges">
          <RoleBadge :name="detail.role_name" />
          <span class="badge ud-status" :class="`ud-status--${detail.status}`">
            <span class="ud-status-dot"></span>{{ statusLabel[detail.status] ?? detail.status }}
          </span>
        </div>
      </div>

      <!-- Contacto -->
      <div class="ud-section">
        <h4 class="ud-section-title"><i class="fas fa-address-card mr-2"></i>Contacto</h4>
        <div class="ud-grid">
          <div class="ud-field">
            <span class="ud-field-label"><i class="fas fa-envelope"></i>Email</span>
            <span class="ud-field-value ud-field-value--truncate" :title="detail.email">{{ detail.email }}</span>
          </div>
          <div class="ud-field">
            <span class="ud-field-label"><i class="fas fa-phone"></i>Teléfono</span>
            <span class="ud-field-value">{{ detail.phone_number ?? '—' }}</span>
          </div>
          <div class="ud-field">
            <span class="ud-field-label"><i class="fas fa-globe"></i>País</span>
            <span class="ud-field-value">{{ detail.country ?? '—' }}</span>
          </div>
        </div>
      </div>

      <!-- Cuenta -->
      <div class="ud-section">
        <h4 class="ud-section-title"><i class="fas fa-shield-halved mr-2"></i>Cuenta</h4>
        <div class="ud-grid">
          <div class="ud-field">
            <span class="ud-field-label"><i class="fas fa-calendar-plus"></i>Registrado</span>
            <span class="ud-field-value">{{ detail.created_at ?? '—' }}</span>
          </div>
          <div class="ud-field">
            <span class="ud-field-label"><i class="fas fa-right-to-bracket"></i>Último acceso</span>
            <span class="ud-field-value">{{ detail.last_login_at ?? 'Nunca' }}</span>
          </div>
          <div class="ud-field">
            <span class="ud-field-label"><i class="fas fa-user-shield"></i>Permisos individuales</span>
            <button
              v-if="detail.has_specific_permissions"
              class="ud-permissions-link"
              @click="$emit('view-permissions', detail.id)"
            >
              {{ detail.specific_permissions_count }} asignado{{ detail.specific_permissions_count === 1 ? '' : 's' }}
              <i class="fas fa-arrow-up-right-from-square ml-1"></i>
            </button>
            <span v-else class="ud-field-value">Ninguno</span>
          </div>
        </div>
      </div>

      <!-- Billetera -->
      <div v-if="detail.wallet" class="ud-section">
        <h4 class="ud-section-title"><i class="fas fa-wallet mr-2"></i>Billetera</h4>
        <p class="ud-wallet-balance">
          {{ detail.wallet.currency?.symbol ?? '' }}{{ detail.wallet.balance.toFixed(2) }}
          <span class="text-muted small">{{ detail.wallet.currency?.iso_code }}</span>
        </p>
      </div>

      <!-- Nivel / XP -->
      <div v-if="detail.level" class="ud-section">
        <h4 class="ud-section-title"><i class="fas fa-star mr-2"></i>Nivel / XP</h4>
        <div class="ud-level">
          <div class="ud-level-top">
            <span class="ud-level-badge">Nivel {{ detail.level.level }}</span>
            <span class="ud-level-xp">{{ detail.level.current_xp.toLocaleString() }} / {{ (detail.level.current_xp + detail.level.xp_to_next_level).toLocaleString() }} XP</span>
          </div>
          <div class="ud-level-bar">
            <div class="ud-level-bar-fill" :style="{ width: `${Math.min(100, Math.max(0, detail.level.progress_percentage))}%` }"></div>
          </div>
          <p class="ud-level-meta">
            {{ detail.level.total_xp.toLocaleString() }} XP total
            <template v-if="detail.level.leveled_up_at"> · Último ascenso {{ new Date(detail.level.leveled_up_at).toLocaleDateString('es-VE') }}</template>
          </p>
        </div>
      </div>

      <!-- Actividad reciente -->
      <div class="ud-section">
        <div class="ud-section-header">
          <h4 class="ud-section-title mb-0"><i class="fas fa-history mr-2"></i>Actividad reciente</h4>
          <button class="btn btn-sm btn-link p-0" @click="$emit('view-full-history', detail.id)">
            Ver historial completo <i class="fas fa-arrow-right ml-1"></i>
          </button>
        </div>
        <ul v-if="detail.recent_audits.length" class="ud-audit-list">
          <li v-for="audit in detail.recent_audits" :key="audit.id" class="ud-audit-item">
            <span class="ud-audit-icon"><i class="fas" :class="eventIcon[audit.event] ?? 'fa-circle-dot'"></i></span>
            <span class="ud-audit-body">
              <span class="ud-audit-event">{{ formatEventLabel(audit.event) }}</span>
              <span class="ud-audit-meta">{{ audit.actor }} · {{ audit.date }}</span>
            </span>
          </li>
        </ul>
        <p v-else class="text-muted small mb-0">Sin actividad registrada.</p>
        <button
          v-if="detail.has_more_audits"
          class="btn btn-sm btn-link ud-audit-more"
          :disabled="loadingMoreAudits"
          @click="$emit('load-more-audits')"
        >
          <span v-if="loadingMoreAudits" class="spinner-border spinner-border-sm me-1"></span>
          <template v-else>Cargar más</template>
        </button>
      </div>
    </div>

    <template #footer>
      <button class="modal-btn modal-btn--ghost" @click="$emit('update:modelValue', false)">Cerrar</button>
    </template>
  </Modal>
</template>

<style scoped>
.ud-loading { display: flex; align-items: center; justify-content: center; padding: 2.5rem 0; color: var(--ds-text-muted); font-size: 0.88rem; }

.ud-error { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.75rem; padding: 2.5rem 1rem; text-align: center; }
.ud-error-icon { font-size: 1.6rem; color: #f87171; }
.ud-error-text { color: var(--ds-text-muted); font-size: 0.88rem; margin: 0; }

.ud-header {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  flex-wrap: wrap;
  margin-bottom: 1.25rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--ds-border);
}
.ud-avatar {
  width: 44px;
  height: 44px;
  flex-shrink: 0;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  font-weight: 700;
  color: #fff;
  background: linear-gradient(135deg, #667eea, #764ba2);
}
.ud-avatar--INACTIVE, .ud-avatar--DELETED { background: linear-gradient(135deg, #6b7280, #4b5563); }
.ud-avatar--SUSPENDED { background: linear-gradient(135deg, #ef4444, #b91c1c); }
.ud-avatar--PENDING_VERIFICATION { background: linear-gradient(135deg, #f59e0b, #d97706); }

.ud-header-info { flex: 1; min-width: 0; }
.ud-name { font-size: 1.1rem; font-weight: 700; margin: 0; color: var(--ds-text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ud-username { font-size: 0.82rem; color: var(--ds-text-muted); margin: 0.15rem 0 0; }
.ud-header-badges { display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; }

.ud-status { display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.7rem; }
.ud-status-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.ud-status--ACTIVE { background: rgba(5,150,105,.18); color: #34d399; }
.ud-status--INACTIVE { background: var(--ds-surface-2); color: var(--ds-text-2); }
.ud-status--DELETED { background: rgba(75,85,99,.25); color: #9ca3af; }
.ud-status--SUSPENDED { background: rgba(220,38,38,.18); color: #f87171; }
.ud-status--PENDING_VERIFICATION { background: rgba(217,119,6,.18); color: #fbbf24; }

.ud-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 0.75rem;
}
.ud-field { display: flex; flex-direction: column; gap: 0.25rem; min-width: 0; }
.ud-field-label {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.7rem;
  color: var(--ds-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.03em;
}
.ud-field-label i { font-size: 0.68rem; opacity: 0.75; }
.ud-field-value { font-size: 0.9rem; color: var(--ds-text); font-weight: 600; }
.ud-field-value--truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.ud-permissions-link {
  align-self: flex-start;
  background: none;
  border: none;
  padding: 0;
  font-size: 0.9rem;
  font-weight: 600;
  color: #667eea;
  cursor: pointer;
}
.ud-permissions-link:hover { text-decoration: underline; }

.ud-section { margin-bottom: 1.25rem; }
.ud-section:last-child { margin-bottom: 0; }
.ud-section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem; }
.ud-section-title { display: flex; align-items: center; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; color: var(--ds-text-muted); margin: 0 0 0.6rem; }
.ud-section-title i { opacity: 0.7; }

.ud-wallet-balance { font-size: 1.4rem; font-weight: 700; color: var(--ds-text); margin: 0; }

.ud-level { background: var(--ds-surface-2); border: 1px solid var(--ds-border); border-radius: 10px; padding: 0.85rem 1rem; }
.ud-level-top { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.5rem; }
.ud-level-badge {
  font-size: 0.8rem;
  font-weight: 700;
  color: #fff;
  background: linear-gradient(135deg, #667eea, #764ba2);
  padding: 0.2rem 0.65rem;
  border-radius: 20px;
}
.ud-level-xp { font-size: 0.8rem; color: var(--ds-text-muted); font-weight: 600; white-space: nowrap; }
.ud-level-bar { height: 8px; border-radius: 4px; background: var(--ds-border); overflow: hidden; }
.ud-level-bar-fill { height: 100%; border-radius: 4px; background: linear-gradient(90deg, #667eea, #764ba2); transition: width 0.3s ease; }
.ud-level-meta { font-size: 0.76rem; color: var(--ds-text-muted); margin: 0.5rem 0 0; }

.ud-audit-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.4rem; }
.ud-audit-item {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.5rem 0.6rem;
  background: var(--ds-surface-2);
  border-radius: 8px;
  font-size: 0.82rem;
}
.ud-audit-icon {
  width: 26px;
  height: 26px;
  flex-shrink: 0;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--ds-surface);
  color: var(--ds-text-muted);
  font-size: 0.7rem;
}
.ud-audit-body { display: flex; flex-direction: column; gap: 0.1rem; min-width: 0; }
.ud-audit-event { font-weight: 600; color: var(--ds-text); }
.ud-audit-meta { color: var(--ds-text-muted); font-size: 0.74rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ud-audit-more { display: block; margin: 0.5rem auto 0; font-size: 0.8rem; }
</style>
