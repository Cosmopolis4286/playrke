<template>
  <header class="profile-header">
    <div class="header-inner">

      <!-- Avatar + identidad -->
      <div class="header-identity">
        <div class="avatar-wrap">
          <img :src="user.avatar || '/img/fav.ico'" :alt="user.name" class="avatar-img" />
          <button class="avatar-btn" @click="$emit('open-avatar')" title="Cambiar foto">
            <i class="fas fa-camera" />
          </button>
        </div>

        <div class="identity-info">
          <h2 class="identity-name">{{ user.name }}</h2>
          <p class="identity-username">@{{ user.username }}</p>

          <div class="identity-badges">
            <!-- Email verificado -->
            <span v-if="user.is_verified" class="badge-pill badge-verified">
              <i class="fas fa-check-circle" /> Verificado
            </span>
            <span v-else class="badge-pill badge-warn">
              <i class="fas fa-exclamation-circle" /> Email sin verificar
            </span>

            <!-- KYC -->
            <span
              class="badge-pill"
              :class="{
                'badge-kyc-full':    user.kyc_status === 'VERIFIED',
                'badge-kyc-pending': user.kyc_status === 'PENDING',
                'badge-kyc-none':    user.kyc_status === 'NONE' || user.kyc_status === 'REJECTED',
              }"
              :title="kycTitle"
            >
              <i :class="kycIcon" />
              {{ kycLabel }}
            </span>

            <!-- TOS -->
            <span
              v-if="!user.tos_accepted_at"
              class="badge-pill badge-tos-missing"
              title="Debes aceptar los Términos de Servicio"
            >
              <i class="fas fa-file-contract" /> TOS pendiente
            </span>

            <!-- Roles -->
            <span v-for="role in user.roles" :key="role.id" class="badge-pill badge-role">
              <i class="fas fa-user-tag" /> {{ role.name }}
            </span>
          </div>

          <!-- Bio -->
          <p v-if="user.bio" class="identity-bio">{{ user.bio }}</p>
        </div>
      </div>

      <!-- Stats rápidas -->
      <div class="header-stats">
        <div v-for="stat in quickStats" :key="stat.label" class="stat-item">
          <span class="stat-value">{{ stat.value }}</span>
          <span class="stat-label">{{ stat.label }}</span>
        </div>
      </div>

      <!-- Acciones + wallet -->
      <div class="header-actions">
        <div v-if="user.wallet" class="wallet-badge">
          <i class="fas fa-wallet" />
          <span class="wallet-amount">
            {{ formatCurrency(user.wallet.balance, user.wallet.currency) }}
          </span>
        </div>

        <button
          :class="['action-btn', editMode ? 'action-btn--save' : 'action-btn--edit']"
          @click="$emit('toggle-edit')"
        >
          <i :class="['fas', editMode ? 'fa-save' : 'fa-pen']" />
          {{ editMode ? 'Guardar' : 'Editar perfil' }}
        </button>

        <button class="action-btn action-btn--ghost" @click="$emit('open-password')">
          <i class="fas fa-lock" /> Contraseña
        </button>

        <button
          v-if="user.kyc_status !== 'VERIFIED' && user.kyc_status !== 'PENDING'"
          class="action-btn action-btn--kyc"
          @click="$emit('open-kyc')"
        >
          <i class="fas fa-id-card" /> Verificar identidad
        </button>
      </div>

    </div>
  </header>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { ProfileUser } from '@js/composables/useProfile'

const props = defineProps<{
    user: ProfileUser
    stats: Record<string, any>
    editMode: boolean
    formatCurrency: (amount: number | null, currency: unknown) => string
}>()

defineEmits<{
    'toggle-edit': []
    'open-password': []
    'open-avatar': []
    'open-kyc': []
}>()

const quickStats = computed(() => [
    { label: 'Partidas',  value: props.stats.games_played      ?? 0 },
    { label: 'Victorias', value: props.stats.games_won         ?? 0 },
    { label: 'Nivel',     value: props.stats.level             ?? 1 },
    { label: 'Logros',    value: props.stats.achievements_count ?? 0 },
])

const kycLabel = computed(() => ({
    VERIFIED: 'KYC Completo',
    PENDING:  'KYC en revisión',
    REJECTED: 'KYC rechazado',
    NONE:     'Sin KYC',
}[props.user.kyc_status] ?? 'Sin KYC'))

const kycTitle = computed(() => ({
    VERIFIED: 'Identidad verificada — límites máximos disponibles',
    PENDING:  'Verificación en proceso (24-48 h)',
    REJECTED: 'Verificación rechazada — sube tus documentos de nuevo',
    NONE:     'Verifica tu identidad para ampliar límites de retiro',
}[props.user.kyc_status] ?? ''))

const kycIcon = computed(() => ({
    VERIFIED: 'fas fa-shield-check',
    PENDING:  'fas fa-clock',
    REJECTED: 'fas fa-times-circle',
    NONE:     'fas fa-shield-alt',
}[props.user.kyc_status] ?? 'fas fa-shield-alt'))
</script>

<style scoped>
.profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 1.25rem 1.5rem;
    border-radius: 16px 16px 0 0;
}

.header-inner {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
}

/* ── Identity ── */
.header-identity { display: flex; align-items: center; gap: 1rem; flex: 0 0 auto; }

.avatar-wrap { position: relative; flex-shrink: 0; }
.avatar-img  { width: 64px; height: 64px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.6); object-fit: cover; box-shadow: 0 4px 16px rgba(0,0,0,0.25); transition: transform 0.2s; }
.avatar-img:hover { transform: scale(1.04); }

.avatar-btn {
    position: absolute; bottom: 0; right: 0;
    width: 22px; height: 22px; border-radius: 50%; border: none;
    background: rgba(255,255,255,0.95); color: #667eea; font-size: 0.62rem;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2); transition: background 0.15s;
}
.avatar-btn:hover { background: #fff; }

.identity-name     { color: #fff; font-size: 1.1rem; font-weight: 700; margin: 0; letter-spacing: -0.02em; line-height: 1.2; }
.identity-username { color: rgba(255,255,255,0.7); font-size: 0.78rem; margin: 0 0 5px; }
.identity-bio      { color: rgba(255,255,255,0.75); font-size: 0.78rem; margin: 4px 0 0; max-width: 320px; line-height: 1.35; }
.identity-badges   { display: flex; gap: 4px; flex-wrap: wrap; }

.badge-pill {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 0.64rem; font-weight: 600; padding: 2px 8px;
    border-radius: 20px; letter-spacing: 0.01em; white-space: nowrap;
}
.badge-verified     { background: rgba(40,167,69,0.9);   color: #fff; }
.badge-warn         { background: rgba(255,193,7,0.85);  color: #212529; }
.badge-role         { background: rgba(255,255,255,0.2); color: #fff; }
.badge-tos-missing  { background: rgba(220,53,69,0.85);  color: #fff; }
.badge-kyc-full     { background: rgba(40,167,69,0.9);   color: #fff; }
.badge-kyc-pending  { background: rgba(255,193,7,0.85);  color: #212529; }
.badge-kyc-none     { background: rgba(255,255,255,0.18); color: #fff; }

/* ── Stats ── */
.header-stats { display: flex; gap: 1.25rem; flex: 1; justify-content: center; flex-wrap: wrap; }
.stat-item    { display: flex; flex-direction: column; align-items: center; gap: 1px; }
.stat-value   { color: #fff; font-size: 1.25rem; font-weight: 800; line-height: 1; text-shadow: 0 2px 6px rgba(0,0,0,0.2); }
.stat-label   { color: rgba(255,255,255,0.65); font-size: 0.66rem; text-transform: uppercase; letter-spacing: 0.06em; }

/* ── Actions ── */
.header-actions { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; flex: 0 0 auto; }

.wallet-badge {
    display: flex; align-items: center; gap: 6px;
    color: rgba(255,255,255,0.9); font-size: 0.8rem; font-weight: 600;
    background: rgba(255,255,255,0.15); padding: 3px 10px; border-radius: 20px;
}

.action-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 14px; border-radius: 8px; font-size: 0.76rem;
    font-weight: 600; cursor: pointer; border: none;
    transition: all 0.15s ease; white-space: nowrap;
}
.action-btn--edit  { background: rgba(255,255,255,0.95); color: #667eea; }
.action-btn--edit:hover  { background: #fff; transform: translateY(-1px); }
.action-btn--save  { background: #28a745; color: #fff; box-shadow: 0 2px 8px rgba(40,167,69,0.4); }
.action-btn--save:hover  { background: #218838; transform: translateY(-1px); }
.action-btn--ghost { background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.3); }
.action-btn--ghost:hover { background: rgba(255,255,255,0.25); }
.action-btn--kyc   { background: rgba(255,193,7,0.85); color: #212529; }
.action-btn--kyc:hover   { background: rgba(255,193,7,1); transform: translateY(-1px); }

@media (max-width: 768px) {
    .header-inner   { flex-direction: column; align-items: flex-start; gap: 1rem; }
    .header-stats   { justify-content: flex-start; width: 100%; }
    .header-actions { align-items: flex-start; flex-direction: row; flex-wrap: wrap; }
}
</style>
