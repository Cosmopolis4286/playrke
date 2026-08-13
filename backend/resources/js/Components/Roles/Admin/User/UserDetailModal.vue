<script setup lang="ts">
import Modal from '@/js/Components/Common/Modal/Modal.vue'
import RoleBadge from '@/js/Components/Roles/Admin/RoleBadge.vue'
import type { AdminUserDetail } from '@/js/types/roles/admin/admin-user'

defineProps<{
  modelValue: boolean
  loading: boolean
  detail: AdminUserDetail | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'view-full-history': [userId: string]
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
      Cargando...
    </div>

    <div v-else-if="detail" class="ud-detail">
      <div class="ud-header">
        <div>
          <h3 class="ud-name">{{ detail.full_name || detail.username }}</h3>
          <p class="ud-username">@{{ detail.username }} · {{ detail.email }}</p>
        </div>
        <div class="ud-header-badges">
          <RoleBadge :name="detail.role_name" />
          <span class="badge ud-status" :class="`ud-status--${detail.status}`">{{ statusLabel[detail.status] ?? detail.status }}</span>
        </div>
      </div>

      <div class="ud-grid">
        <div class="ud-field">
          <span class="ud-field-label">País</span>
          <span class="ud-field-value">{{ detail.country ?? '—' }}</span>
        </div>
        <div class="ud-field">
          <span class="ud-field-label">Registrado</span>
          <span class="ud-field-value">{{ detail.created_at ?? '—' }}</span>
        </div>
        <div class="ud-field">
          <span class="ud-field-label">Permisos individuales</span>
          <span class="ud-field-value">{{ detail.has_specific_permissions ? 'Sí' : 'No' }}</span>
        </div>
      </div>

      <div v-if="detail.wallet" class="ud-section">
        <h4 class="ud-section-title"><i class="fas fa-wallet mr-2"></i>Billetera</h4>
        <p class="ud-wallet-balance">
          {{ detail.wallet.currency?.symbol ?? '' }}{{ detail.wallet.balance.toFixed(2) }}
          <span class="text-muted small">{{ detail.wallet.currency?.iso_code }}</span>
        </p>
      </div>

      <div v-if="detail.level" class="ud-section">
        <h4 class="ud-section-title"><i class="fas fa-star mr-2"></i>Nivel / XP</h4>
        <pre class="ud-level-raw">{{ JSON.stringify(detail.level, null, 2) }}</pre>
      </div>

      <div class="ud-section">
        <div class="ud-section-header">
          <h4 class="ud-section-title mb-0"><i class="fas fa-history mr-2"></i>Actividad reciente</h4>
          <button class="btn btn-sm btn-link p-0" @click="$emit('view-full-history', detail.id)">
            Ver historial completo <i class="fas fa-arrow-right ml-1"></i>
          </button>
        </div>
        <ul v-if="detail.recent_audits.length" class="ud-audit-list">
          <li v-for="audit in detail.recent_audits" :key="audit.id" class="ud-audit-item">
            <span class="ud-audit-event">{{ eventLabel[audit.event] ?? audit.event }}</span>
            <span class="ud-audit-meta">{{ audit.actor }} · {{ audit.date }}</span>
          </li>
        </ul>
        <p v-else class="text-muted small mb-0">Sin actividad registrada.</p>
      </div>
    </div>

    <template #footer>
      <button class="modal-btn modal-btn--ghost" @click="$emit('update:modelValue', false)">Cerrar</button>
    </template>
  </Modal>
</template>

<style scoped>
.ud-loading { display: flex; align-items: center; justify-content: center; padding: 2rem 0; }

.ud-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--ds-border);
}
.ud-name { font-size: 1.1rem; font-weight: 700; margin: 0; color: var(--ds-text); }
.ud-username { font-size: 0.85rem; color: var(--ds-text-muted); margin: 0.2rem 0 0; }
.ud-header-badges { display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; }

.ud-status { font-size: 0.7rem; }
.ud-status--ACTIVE { background: rgba(5,150,105,.18); color: #34d399; }
.ud-status--INACTIVE { background: var(--ds-surface-2); color: var(--ds-text-2); }
.ud-status--DELETED { background: rgba(75,85,99,.25); color: #9ca3af; }
.ud-status--SUSPENDED { background: rgba(220,38,38,.18); color: #f87171; }
.ud-status--PENDING_VERIFICATION { background: rgba(217,119,6,.18); color: #fbbf24; }

.ud-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 0.75rem;
  margin-bottom: 1.25rem;
}
.ud-field { display: flex; flex-direction: column; gap: 0.15rem; }
.ud-field-label { font-size: 0.72rem; color: var(--ds-text-muted); text-transform: uppercase; letter-spacing: 0.03em; }
.ud-field-value { font-size: 0.9rem; color: var(--ds-text); font-weight: 600; }

.ud-section { margin-bottom: 1.25rem; }
.ud-section:last-child { margin-bottom: 0; }
.ud-section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem; }
.ud-section-title { font-size: 0.88rem; font-weight: 700; color: var(--ds-text); margin: 0 0 0.5rem; }

.ud-wallet-balance { font-size: 1.4rem; font-weight: 700; color: var(--ds-text); margin: 0; }

.ud-level-raw {
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border);
  border-radius: 6px;
  padding: 0.6rem;
  font-size: 0.78rem;
  max-height: 160px;
  overflow: auto;
  margin: 0;
}

.ud-audit-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.4rem; }
.ud-audit-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.4rem 0.6rem;
  background: var(--ds-surface-2);
  border-radius: 6px;
  font-size: 0.82rem;
}
.ud-audit-event { font-weight: 600; color: var(--ds-text); }
.ud-audit-meta { color: var(--ds-text-muted); font-size: 0.76rem; white-space: nowrap; }
</style>
