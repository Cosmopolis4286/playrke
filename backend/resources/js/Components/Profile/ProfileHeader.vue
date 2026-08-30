<template>
  <header class="profile-header">
    <!-- Partículas decorativas space -->
    <div class="ph-stars" aria-hidden="true">
      <span class="ph-star" style="top:12%;left:8%;animation-delay:0s"/>
      <span class="ph-star" style="top:35%;left:22%;animation-delay:0.8s"/>
      <span class="ph-star" style="top:60%;left:5%;animation-delay:1.6s"/>
      <span class="ph-star" style="top:20%;left:75%;animation-delay:0.4s"/>
      <span class="ph-star" style="top:70%;left:88%;animation-delay:1.2s"/>
      <span class="ph-star" style="top:80%;left:45%;animation-delay:2s"/>
    </div>

    <div class="header-inner">

      <!-- Avatar + identidad -->
      <div class="header-identity">
        <div class="avatar-wrap">
          <img :src="user.avatar || '/img/fav.ico'" :alt="user.name" class="avatar-img" />
          <button class="avatar-btn" @click="$emit('open-avatar')" title="Cambiar foto">
            <i class="fas fa-camera" />
          </button>
          <!-- Indicador de nivel sobre avatar -->
          <span class="avatar-level" :title="`Nivel ${stats.level ?? 1}`">{{ stats.level ?? 1 }}</span>
        </div>

        <div class="identity-info">
          <div class="identity-name-row">
            <h2 class="identity-name">{{ user.name }}</h2>
            <!-- Indicador de rol principal -->
            <span class="primary-role-badge" :class="`primary-role-badge--${primaryRoleKey}`">
              <i :class="primaryRoleIcon" />
              {{ primaryRoleLabel }}
            </span>
          </div>
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

            <!-- Roles adicionales -->
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
        <!-- Wallet badge mejorado -->
        <div v-if="user.wallet" class="wallet-badge">
          <span class="wallet-badge__icon"><i class="fas fa-wallet" /></span>
          <div class="wallet-badge__info">
            <span class="wallet-badge__label">Saldo disponible</span>
            <span class="wallet-badge__amount">
              {{ formatCurrency(user.wallet.balance, user.wallet.currency) }}
            </span>
          </div>
        </div>

        <div class="actions-row">
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

        <!-- Alerta contextual según estado de cuenta -->
        <div v-if="accountAlert" class="account-alert" :class="`account-alert--${accountAlert.type}`">
          <i :class="accountAlert.icon" />
          <span>{{ accountAlert.message }}</span>
        </div>
      </div>

    </div>
  </header>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { ProfileUser } from '@js/composables/useProfile'
import { getRoleMeta } from '@js/composables/useRoleMeta'

const props = defineProps<{
    user: ProfileUser
    stats: Record<string, any>
    editMode: boolean
    formatCurrency: (amount: number | null, currency: unknown) => string
    missionsPending?: number
    missionsTotal?: number
}>()

defineEmits<{
    'toggle-edit': []
    'open-password': []
    'open-avatar': []
    'open-kyc': []
}>()

// Racha y misiones agregadas al resumen ya existente (Partidas/Victorias/
// Nivel/Logros) — antes había que entrar al tab "Estadísticas" para ver el
// nivel, y no había ningún vistazo de racha/misiones sin navegar.
const quickStats = computed(() => {
    const stats = [
        { label: 'Partidas',  value: props.stats.games_played       ?? 0 },
        { label: 'Victorias', value: props.stats.games_won          ?? 0 },
        { label: 'Nivel',     value: props.stats.level              ?? 1 },
        { label: 'Logros',    value: props.stats.achievements_count ?? 0 },
    ]

    const streak = props.stats.current_streak ?? 0
    stats.push({ label: 'Racha', value: streak > 0 ? `🔥 ${streak}` : '0' })

    if (props.missionsTotal) {
        const done = props.missionsTotal - (props.missionsPending ?? 0)
        stats.push({ label: 'Misiones', value: `${done}/${props.missionsTotal}` })
    }

    return stats
})

// ── Rol principal ──────────────────────────────────────────────
const primaryRole = computed(() => getRoleMeta(props.user.roles?.[0]?.name))

const primaryRoleKey   = computed(() => primaryRole.value.key)
const primaryRoleLabel = computed(() => primaryRole.value.label)
const primaryRoleIcon  = computed(() => primaryRole.value.icon)

// ── Alerta contextual según estado ────────────────────────────
const accountAlert = computed(() => {
    if (!props.user.tos_accepted_at)
        return { type: 'warn', icon: 'fas fa-file-contract', message: 'Acepta los Términos de Servicio para continuar usando la plataforma.' }
    if (!props.user.is_verified)
        return { type: 'warn', icon: 'fas fa-envelope', message: 'Verifica tu correo electrónico para asegurar tu cuenta.' }
    if (props.user.kyc_status === 'REJECTED')
        return { type: 'danger', icon: 'fas fa-times-circle', message: 'Tu verificación KYC fue rechazada. Sube tus documentos de nuevo.' }
    return null
})

// ── KYC ───────────────────────────────────────────────────────
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
    background: linear-gradient(135deg, #1a1060 0%, #2d1b69 30%, #667eea 70%, #764ba2 100%);
    padding: 1.4rem 1.6rem 1.2rem;
    border-radius: 16px 16px 0 0;
    position: relative;
    overflow: hidden;
}

/* ── Partículas space ── */
.ph-stars { position: absolute; inset: 0; pointer-events: none; z-index: 0; }
.ph-star {
    position: absolute;
    width: 3px; height: 3px;
    border-radius: 50%;
    background: rgba(255,255,255,0.6);
    animation: phStarPulse 3s ease-in-out infinite;
}
@keyframes phStarPulse {
    0%, 100% { opacity: 0.3; transform: scale(1); }
    50%       { opacity: 1;   transform: scale(1.4); }
}

.header-inner {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
    position: relative;
    z-index: 1;
}

/* ── Identity ── */
.header-identity { display: flex; align-items: center; gap: 1rem; flex: 0 0 auto; }

.avatar-wrap { position: relative; flex-shrink: 0; }
.avatar-img  {
    width: 72px; height: 72px; border-radius: 50%;
    border: 2.5px solid rgba(255,255,255,0.55);
    object-fit: cover;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3), 0 0 0 4px rgba(255,255,255,0.08);
    transition: transform 0.2s, box-shadow 0.2s;
}
.avatar-img:hover { transform: scale(1.04); box-shadow: 0 6px 24px rgba(0,0,0,0.35), 0 0 0 4px rgba(255,255,255,0.15); }

.avatar-btn {
    position: absolute; bottom: 0; right: 0;
    width: 24px; height: 24px; border-radius: 50%; border: none;
    background: rgba(255,255,255,0.95); color: #667eea; font-size: 0.62rem;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2); transition: background 0.15s, transform 0.15s;
}
.avatar-btn:hover { background: #fff; transform: scale(1.1); }

.avatar-level {
    position: absolute; top: -4px; left: -4px;
    min-width: 20px; height: 20px; border-radius: 99px;
    background: linear-gradient(135deg, #f59e0b, #f97316);
    color: #fff; font-size: 9px; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    padding: 0 4px; border: 1.5px solid rgba(255,255,255,0.6);
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}

.identity-name-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 1px; }
.identity-name     { color: #fff; font-size: 1.15rem; font-weight: 700; margin: 0; letter-spacing: -0.02em; line-height: 1.2; }
.identity-username { color: rgba(255,255,255,0.65); font-size: 0.78rem; margin: 0 0 6px; }
.identity-bio      { color: rgba(255,255,255,0.72); font-size: 0.78rem; margin: 4px 0 0; max-width: 320px; line-height: 1.35; }
.identity-badges   { display: flex; gap: 4px; flex-wrap: wrap; }

/* Badge de rol principal */
.primary-role-badge {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 0.65rem; font-weight: 700; padding: 3px 9px;
    border-radius: 99px; letter-spacing: 0.02em; white-space: nowrap;
    text-transform: uppercase; border: 1px solid transparent;
}
.primary-role-badge--superadmin { background: rgba(245,158,11,0.9); color: #fff; border-color: rgba(245,158,11,0.4); }
.primary-role-badge--admin      { background: rgba(102,126,234,0.85); color: #fff; }
.primary-role-badge--treasurer  { background: rgba(16,185,129,0.85); color: #fff; }
.primary-role-badge--seller     { background: rgba(236,72,153,0.85); color: #fff; }
.primary-role-badge--customer   { background: rgba(59,130,246,0.85); color: #fff; }
.primary-role-badge--player     { background: rgba(255,255,255,0.18); color: #fff; border-color: rgba(255,255,255,0.3); }
.primary-role-badge--vip        { background: linear-gradient(135deg,rgba(245,158,11,0.9),rgba(249,115,22,0.9)); color: #fff; }

.badge-pill {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 0.64rem; font-weight: 600; padding: 2px 8px;
    border-radius: 20px; letter-spacing: 0.01em; white-space: nowrap;
}
.badge-verified     { background: rgba(40,167,69,0.9);    color: #fff; }
.badge-warn         { background: rgba(255,193,7,0.85);   color: #212529; }
.badge-role         { background: rgba(255,255,255,0.18); color: #fff; }
.badge-tos-missing  { background: rgba(220,53,69,0.85);   color: #fff; }
.badge-kyc-full     { background: rgba(40,167,69,0.9);    color: #fff; }
.badge-kyc-pending  { background: rgba(255,193,7,0.85);   color: #212529; }
.badge-kyc-none     { background: rgba(255,255,255,0.15); color: #fff; }

/* ── Stats ── */
.header-stats { display: flex; gap: 1.5rem; flex: 1; justify-content: center; flex-wrap: wrap; }
.stat-item    { display: flex; flex-direction: column; align-items: center; gap: 2px; }
.stat-value   {
    color: #fff; font-size: 1.4rem; font-weight: 800; line-height: 1;
    text-shadow: 0 2px 8px rgba(0,0,0,0.25);
}
.stat-label   { color: rgba(255,255,255,0.6); font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.07em; }

/* ── Actions ── */
.header-actions { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; flex: 0 0 auto; }

.wallet-badge {
    display: flex; align-items: center; gap: 8px;
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 10px; padding: 6px 12px;
}
.wallet-badge__icon {
    width: 30px; height: 30px; border-radius: 8px;
    background: rgba(52,211,153,0.2);
    color: #34d399; font-size: 13px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.wallet-badge__info { display: flex; flex-direction: column; }
.wallet-badge__label  { font-size: 0.65rem; color: rgba(255,255,255,0.55); line-height: 1; }
.wallet-badge__amount { font-size: 0.85rem; font-weight: 700; color: #fff; line-height: 1.3; }

.actions-row { display: flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }

.action-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border-radius: 8px; font-size: 0.76rem;
    font-weight: 600; cursor: pointer; border: none;
    transition: all 0.15s ease; white-space: nowrap;
}
.action-btn--edit  { background: rgba(255,255,255,0.95); color: #667eea; }
.action-btn--edit:hover  { background: #fff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.action-btn--save  { background: #28a745; color: #fff; box-shadow: 0 2px 8px rgba(40,167,69,0.4); }
.action-btn--save:hover  { background: #218838; transform: translateY(-1px); }
.action-btn--ghost { background: rgba(255,255,255,0.12); color: #fff; border: 1px solid rgba(255,255,255,0.25); }
.action-btn--ghost:hover { background: rgba(255,255,255,0.22); }
.action-btn--kyc   { background: rgba(255,193,7,0.85); color: #212529; }
.action-btn--kyc:hover   { background: rgba(255,193,7,1); transform: translateY(-1px); }

/* ── Alerta contextual ── */
.account-alert {
    display: flex; align-items: center; gap: 7px;
    padding: 6px 12px; border-radius: 8px;
    font-size: 0.75rem; font-weight: 500;
    max-width: 260px; line-height: 1.35;
}
.account-alert--warn   { background: rgba(255,193,7,0.18);  color: #fde68a; border: 1px solid rgba(255,193,7,0.3); }
.account-alert--danger { background: rgba(220,53,69,0.18);  color: #fca5a5; border: 1px solid rgba(220,53,69,0.3); }

@media (max-width: 820px) {
    .header-inner   { flex-direction: column; align-items: flex-start; gap: 1rem; }
    .header-stats   { justify-content: flex-start; width: 100%; }
    .header-actions { align-items: flex-start; width: 100%; }
    .actions-row    { justify-content: flex-start; }
    .account-alert  { max-width: 100%; }
}
</style>
