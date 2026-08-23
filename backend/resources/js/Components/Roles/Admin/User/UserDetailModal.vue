<script setup lang="ts">
import { ref } from 'vue'
import Swal from 'sweetalert2'
import Modal from '@/js/Components/Common/Modal/Modal.vue'
import RoleBadge from '@/js/Components/Roles/Admin/RoleBadge.vue'
import type { AdminUserDetail, AdminUserDetailFlag } from '@/js/types/roles/admin/admin-user'

const props = defineProps<{
  modelValue: boolean
  loading: boolean
  detail: AdminUserDetail | null
  error?: string | null
  loadingMoreAudits?: boolean
  loadingMoreWalletMovements?: boolean
  resolvingSuspension?: boolean
  suspendingManually?: boolean
  flags?: AdminUserDetailFlag[]
  loadingFlags?: boolean
  flagsLoaded?: boolean
  hasMoreFlags?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'view-full-history': [userId: string]
  'view-permissions': [userId: string]
  retry: []
  'load-more-audits': []
  'load-more-wallet-movements': []
  'resolve-suspension': [suspensionId: string, notes: string]
  'suspend-manually': [userId: string, reason: string]
  'load-flags': []
  'load-more-flags': []
}>()

async function handleResolveSuspension(suspensionId: string) {
  const result = await Swal.fire({
    title: 'Resolver suspensión',
    input: 'textarea',
    inputLabel: 'Notas de resolución',
    inputPlaceholder: 'Motivo por el que se reactiva al jugador...',
    inputValidator: (value) => (!value || !value.trim() ? 'Las notas son obligatorias' : undefined),
    showCancelButton: true,
    confirmButtonText: 'Reactivar jugador',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#16a34a',
    reverseButtons: true,
  })

  if (!result.isConfirmed || !result.value) return
  emit('resolve-suspension', suspensionId, result.value.trim())
}

async function handleSuspendManually() {
  if (!props.detail) return

  const result = await Swal.fire({
    title: 'Suspender jugador',
    input: 'textarea',
    inputLabel: 'Motivo de la suspensión',
    inputPlaceholder: 'Motivo por el que se suspende al jugador...',
    inputValidator: (value) => (!value || !value.trim() ? 'El motivo es obligatorio' : undefined),
    showCancelButton: true,
    confirmButtonText: 'Suspender',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#dc2626',
    reverseButtons: true,
  })

  if (!result.isConfirmed || !result.value) return
  emit('suspend-manually', props.detail.id, result.value.trim())
}

const showFlagsDetail = ref(false)

function toggleFlagsDetail() {
  showFlagsDetail.value = !showFlagsDetail.value
  if (showFlagsDetail.value && !props.flagsLoaded) {
    emit('load-flags')
  }
}

const walletMovementTypeLabel: Record<string, string> = {
  DEPOSIT: 'Depósito',
  WITHDRAWAL: 'Retiro',
  PURCHASE: 'Compra',
  PRIZE: 'Premio',
  BONUS_PRIZE: 'Premio bono',
  REFUND: 'Reembolso',
  TRIVIA_ENTRY: 'Entrada trivia',
  RKE_COMMISSION: 'Comisión RKE',
}

function formatWalletMovementType(type: string): string {
  return walletMovementTypeLabel[type] ?? type
}

const kycStatusLabel: Record<string, string> = {
  NONE: 'Sin verificar',
  PENDING: 'En revisión',
  VERIFIED: 'Verificado',
  REJECTED: 'Rechazado',
}

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
      <button type="button" class="ud-close-btn" @click="$emit('retry')">
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
            <template v-if="detail.has_specific_permissions">
              <button
                class="ud-permissions-link"
                @click="$emit('view-permissions', detail.id)"
              >
                {{ detail.specific_permissions_count }} asignado{{ detail.specific_permissions_count === 1 ? '' : 's' }}
                <i class="fas fa-arrow-up-right-from-square ml-1"></i>
              </button>
              <span v-if="detail.specific_permissions_expiring_soon_count > 0" class="ud-expiring-badge">
                <i class="fas fa-clock"></i>
                {{ detail.specific_permissions_expiring_soon_count }} expira{{ detail.specific_permissions_expiring_soon_count === 1 ? '' : 'n' }} pronto
              </span>
            </template>
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

        <div v-if="detail.wallet.recent_movements.length" class="ud-movements">
          <span class="ud-field-label mb-2"><i class="fas fa-receipt"></i>Movimientos recientes</span>
          <ul class="ud-movement-list">
            <li v-for="movement in detail.wallet.recent_movements" :key="movement.id" class="ud-movement-item">
              <span class="ud-movement-body">
                <span class="ud-movement-type">{{ formatWalletMovementType(movement.type) }}</span>
                <span class="ud-movement-meta">{{ movement.date }} · {{ movement.status }}</span>
              </span>
              <span class="ud-movement-amount" :class="{ 'ud-movement-amount--negative': movement.amount < 0 }">
                {{ movement.amount >= 0 ? '+' : '' }}{{ movement.amount.toFixed(2) }}
              </span>
            </li>
          </ul>
          <button
            v-if="detail.has_more_wallet_movements"
            class="btn btn-sm btn-link ud-audit-more"
            :disabled="loadingMoreWalletMovements"
            @click="$emit('load-more-wallet-movements')"
          >
            <span v-if="loadingMoreWalletMovements" class="spinner-border spinner-border-sm me-1"></span>
            <template v-else>Cargar más</template>
          </button>
        </div>
        <p v-else class="text-muted small mb-0 mt-2">Sin movimientos registrados.</p>
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

      <!-- Estadísticas de juego -->
      <div v-if="detail.game_stats" class="ud-section">
        <h4 class="ud-section-title"><i class="fas fa-gamepad mr-2"></i>Estadísticas de juego</h4>
        <div class="ud-grid ud-grid--stats">
          <div class="ud-field">
            <span class="ud-field-label"><i class="fas fa-dice"></i>Partidas jugadas</span>
            <span class="ud-field-value">{{ detail.game_stats.games_played.toLocaleString() }}</span>
          </div>
          <div class="ud-field">
            <span class="ud-field-label"><i class="fas fa-trophy"></i>Partidas ganadas</span>
            <span class="ud-field-value">{{ detail.game_stats.games_won.toLocaleString() }}</span>
          </div>
          <div class="ud-field">
            <span class="ud-field-label"><i class="fas fa-sack-dollar"></i>Ganancias totales</span>
            <span class="ud-field-value">{{ detail.wallet?.currency?.symbol ?? '' }}{{ detail.game_stats.total_winnings.toLocaleString() }}</span>
          </div>
        </div>

        <div v-if="detail.game_stats.achievements.length" class="ud-achievements">
          <span class="ud-field-label mb-2"><i class="fas fa-medal"></i>Logros desbloqueados ({{ detail.game_stats.achievements_count }})</span>
          <ul class="ud-achievement-list">
            <li v-for="achievement in detail.game_stats.achievements" :key="achievement.id" class="ud-achievement-item">
              <span class="ud-achievement-icon" :style="{ background: achievement.badge_color }">
                <i class="fas" :class="achievement.icon ?? 'fa-medal'"></i>
              </span>
              <span class="ud-achievement-body">
                <span class="ud-achievement-name">{{ achievement.name }}</span>
                <span class="ud-achievement-date">{{ achievement.unlocked_at }}</span>
              </span>
            </li>
          </ul>
        </div>
        <p v-else class="text-muted small mb-0">Sin logros desbloqueados.</p>
      </div>

      <!-- KYC -->
      <div v-if="detail.kyc" class="ud-section">
        <h4 class="ud-section-title"><i class="fas fa-id-card-clip mr-2"></i>Verificación de identidad (KYC)</h4>
        <div class="ud-grid">
          <div class="ud-field">
            <span class="ud-field-label"><i class="fas fa-layer-group"></i>Nivel</span>
            <span class="ud-field-value">{{ detail.kyc.level }}</span>
          </div>
          <div class="ud-field">
            <span class="ud-field-label"><i class="fas fa-check-double"></i>Estado</span>
            <span class="badge ud-kyc-badge" :class="`ud-kyc-badge--${detail.kyc.status}`">
              {{ kycStatusLabel[detail.kyc.status] ?? detail.kyc.status }}
            </span>
          </div>
          <div v-if="detail.kyc.verified_at" class="ud-field">
            <span class="ud-field-label"><i class="fas fa-calendar-check"></i>Verificado</span>
            <span class="ud-field-value">{{ detail.kyc.verified_at }}</span>
          </div>
        </div>
        <p v-if="detail.kyc.rejection_reason" class="ud-kyc-rejection">
          <i class="fas fa-triangle-exclamation mr-1"></i>{{ detail.kyc.rejection_reason }}
        </p>
      </div>

      <!-- Seguridad (anti-cheat / suspensiones) -->
      <div v-if="detail.security" class="ud-section">
        <h4 class="ud-section-title"><i class="fas fa-user-shield mr-2"></i>Seguridad</h4>

        <div v-if="detail.security.active_suspension" class="ud-suspension-alert">
          <div class="ud-suspension-header">
            <i class="fas fa-ban"></i>
            <span>Jugador suspendido</span>
          </div>
          <p class="ud-suspension-reason">{{ detail.security.active_suspension.reason }}</p>
          <p class="ud-suspension-meta">
            {{ detail.security.active_suspension.origin === 'anti_cheat' ? 'Automática (anti-cheat)' : 'Manual' }}
            · {{ detail.security.active_suspension.created_at }}
            <template v-if="detail.security.active_suspension.suspended_by"> · por {{ detail.security.active_suspension.suspended_by }}</template>
          </p>
          <button
            class="btn btn-sm btn-success mt-2"
            :disabled="resolvingSuspension"
            @click="handleResolveSuspension(detail.security.active_suspension.id)"
          >
            <span v-if="resolvingSuspension" class="spinner-border spinner-border-sm me-1"></span>
            <template v-else><i class="fas fa-unlock mr-1"></i>Resolver suspensión</template>
          </button>
        </div>
        <div v-else class="ud-suspend-manual">
          <p class="text-muted small mb-0">Sin suspensiones activas.</p>
          <button
            class="btn btn-sm btn-outline-danger"
            :disabled="suspendingManually"
            @click="handleSuspendManually"
          >
            <span v-if="suspendingManually" class="spinner-border spinner-border-sm me-1"></span>
            <template v-else><i class="fas fa-ban mr-1"></i>Suspender manualmente</template>
          </button>
        </div>

        <div v-if="detail.security.recent_flags_count > 0" class="ud-flags">
          <button class="ud-flags-toggle" @click="toggleFlagsDetail">
            <i class="fas fa-flag mr-1"></i>{{ detail.security.recent_flags_count }} señal{{ detail.security.recent_flags_count === 1 ? '' : 'es' }} anti-cheat reciente{{ detail.security.recent_flags_count === 1 ? '' : 's' }}
            <i class="fas ml-1" :class="showFlagsDetail ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
          </button>

          <div v-if="showFlagsDetail" class="ud-flags-detail">
            <div v-if="loadingFlags && !flags?.length" class="ud-flags-loading">
              <div class="spinner-border spinner-border-sm me-2"></div>Cargando señales...
            </div>
            <ul v-else-if="flags?.length" class="ud-movement-list">
              <li v-for="flag in flags" :key="flag.id" class="ud-movement-item">
                <span class="ud-movement-body">
                  <span class="ud-movement-type">
                    {{ flag.signal_type_label }}
                    <span v-if="flag.triggered_suspension" class="ud-flag-triggered">disparó suspensión</span>
                  </span>
                  <span class="ud-movement-meta">{{ flag.game_slug }} · {{ flag.created_at }}</span>
                </span>
                <span class="ud-flag-severity">Sev. {{ flag.severity }}</span>
              </li>
            </ul>
            <p v-else class="text-muted small mb-0">Sin señales registradas.</p>
            <button
              v-if="hasMoreFlags"
              class="btn btn-sm btn-link ud-audit-more"
              :disabled="loadingFlags"
              @click="$emit('load-more-flags')"
            >
              <span v-if="loadingFlags" class="spinner-border spinner-border-sm me-1"></span>
              <template v-else>Cargar más</template>
            </button>
          </div>
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
      <button type="button" class="ud-close-btn" @click="$emit('update:modelValue', false)">Cerrar</button>
    </template>
  </Modal>
</template>

<style scoped>
/**
 * Botón local (no .modal-btn de Modal.vue): los estilos scoped de Modal.vue
 * no alcanzan el contenido de sus slots (#footer, y el body vía <slot />
 * por defecto) porque ese markup se renderiza y se marca con el atributo
 * data-v-xxxx de ESTE componente, no el de Modal.vue — scoped no atraviesa
 * la frontera padre/slot. Mismo patrón que MovementDetailModal.vue (.op-btn).
 */
.ud-close-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 9px 22px;
  border-radius: 10px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  border: 1.5px solid var(--ds-border-2);
  background: transparent;
  color: var(--ds-text-2);
  transition: all 0.15s;
  line-height: 1.4;
}
.ud-close-btn:hover { background: var(--ds-surface-2); }

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

.ud-expiring-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  align-self: flex-start;
  margin-top: 0.3rem;
  font-size: 0.72rem;
  font-weight: 600;
  color: #fbbf24;
  background: rgba(217,119,6,.15);
  padding: 0.15rem 0.5rem;
  border-radius: 20px;
}

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

.ud-grid--stats { margin-bottom: 1rem; }

.ud-achievements { margin-top: 0.25rem; }
.ud-achievement-list { list-style: none; margin: 0.5rem 0 0; padding: 0; display: flex; flex-direction: column; gap: 0.4rem; }
.ud-achievement-item {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.5rem 0.6rem;
  background: var(--ds-surface-2);
  border-radius: 8px;
  font-size: 0.82rem;
}
.ud-achievement-icon {
  width: 26px;
  height: 26px;
  flex-shrink: 0;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 0.7rem;
}
.ud-achievement-body { display: flex; flex-direction: column; gap: 0.1rem; min-width: 0; }
.ud-achievement-name { font-weight: 600; color: var(--ds-text); }
.ud-achievement-date { color: var(--ds-text-muted); font-size: 0.74rem; }

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

.ud-movements { margin-top: 0.85rem; }
.ud-movement-list { list-style: none; margin: 0.5rem 0 0; padding: 0; display: flex; flex-direction: column; gap: 0.4rem; }
.ud-movement-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.65rem;
  padding: 0.5rem 0.6rem;
  background: var(--ds-surface-2);
  border-radius: 8px;
  font-size: 0.82rem;
}
.ud-movement-body { display: flex; flex-direction: column; gap: 0.1rem; min-width: 0; }
.ud-movement-type { font-weight: 600; color: var(--ds-text); }
.ud-movement-meta { color: var(--ds-text-muted); font-size: 0.74rem; }
.ud-movement-amount { font-weight: 700; color: #34d399; white-space: nowrap; }
.ud-movement-amount--negative { color: #f87171; }

.ud-kyc-badge { font-size: 0.72rem; padding: 0.3rem 0.6rem; }
.ud-kyc-badge--NONE { background: var(--ds-surface-2); color: var(--ds-text-2); }
.ud-kyc-badge--PENDING { background: rgba(217,119,6,.18); color: #fbbf24; }
.ud-kyc-badge--VERIFIED { background: rgba(5,150,105,.18); color: #34d399; }
.ud-kyc-badge--REJECTED { background: rgba(220,38,38,.18); color: #f87171; }
.ud-kyc-rejection {
  margin: 0.75rem 0 0;
  font-size: 0.82rem;
  color: #f87171;
  background: rgba(220,38,38,.1);
  padding: 0.5rem 0.65rem;
  border-radius: 8px;
}

.ud-suspension-alert {
  background: rgba(220,38,38,.08);
  border: 1px solid rgba(220,38,38,.3);
  border-radius: 10px;
  padding: 0.85rem 1rem;
}
.ud-suspension-header {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-weight: 700;
  color: #f87171;
  font-size: 0.9rem;
}
.ud-suspension-reason { margin: 0.5rem 0 0; font-size: 0.85rem; color: var(--ds-text); }
.ud-suspension-meta { margin: 0.25rem 0 0; font-size: 0.76rem; color: var(--ds-text-muted); }

.ud-suspend-manual { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap; }

.ud-flags { margin-top: 0.85rem; }
.ud-flags-toggle {
  display: inline-flex;
  align-items: center;
  background: none;
  border: none;
  padding: 0;
  font-size: 0.8rem;
  font-weight: 600;
  color: #fbbf24;
  cursor: pointer;
}
.ud-flags-toggle:hover { text-decoration: underline; }
.ud-flags-detail { margin-top: 0.5rem; }
.ud-flags-loading { display: flex; align-items: center; padding: 0.75rem 0; color: var(--ds-text-muted); font-size: 0.82rem; }
.ud-flag-triggered {
  margin-left: 0.4rem;
  font-size: 0.68rem;
  font-weight: 700;
  color: #f87171;
  background: rgba(220,38,38,.15);
  padding: 0.1rem 0.4rem;
  border-radius: 10px;
  text-transform: uppercase;
}
.ud-flag-severity { font-weight: 700; color: var(--ds-text-muted); font-size: 0.78rem; white-space: nowrap; }
</style>
