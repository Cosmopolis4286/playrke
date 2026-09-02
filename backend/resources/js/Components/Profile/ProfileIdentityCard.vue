<template>
  <div class="identity-card">
    <div class="avatar-wrap">
      <img :src="user.avatar || '/img/fav.ico'" :alt="user.name" class="avatar-img" />
      <button class="avatar-btn" title="Cambiar foto" @click="$emit('open-avatar')">
        <i class="fas fa-camera" />
      </button>
      <span v-if="showLevel" class="avatar-level" :title="`Nivel ${level}`">{{ level }}</span>
    </div>

    <div class="identity-info">
      <div class="identity-name-row">
        <h2 class="identity-name">{{ user.name }}</h2>
        <span class="primary-role-badge" :class="`primary-role-badge--${primaryRoleKey}`">
          <i :class="primaryRoleIcon" />
          {{ primaryRoleLabel }}
        </span>
      </div>

      <button class="identity-username" :title="copiedUsername ? 'Copiado' : 'Copiar @username'" @click="copyUsername">
        @{{ user.username }}
        <i class="fas" :class="copiedUsername ? 'fa-check' : 'fa-copy'" />
      </button>

      <div class="identity-badges">
        <span v-if="user.is_verified" class="badge-pill badge-verified">
          <i class="fas fa-check-circle" /> Verificado
        </span>
        <span v-else class="badge-pill badge-warn">
          <i class="fas fa-exclamation-circle" /> Email sin verificar
        </span>

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

        <span
          v-if="!user.tos_accepted_at"
          class="badge-pill badge-tos-missing"
          title="Debes aceptar los Términos de Servicio"
        >
          <i class="fas fa-file-contract" /> TOS pendiente
        </span>

        <span v-for="role in user.roles" :key="role.id" class="badge-pill badge-role">
          <i class="fas fa-user-tag" /> {{ role.name }}
        </span>
      </div>

      <p v-if="user.bio" class="identity-bio">{{ user.bio }}</p>

      <button class="identity-id" :title="copiedId ? 'Copiado' : 'Copiar ID de cuenta'" @click="copyId">
        <i class="fas fa-hashtag" /> ID: {{ shortId }}
        <i class="fas fa-xs" :class="copiedId ? 'fa-check' : 'fa-copy'" />
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import type { ProfileUser } from '@js/composables/useProfile'
import { getRoleMeta } from '@js/composables/useRoleMeta'

const props = defineProps<{
    user: ProfileUser
    level: number
    /** Gamificación exclusiva de player — ver ProfileTabs.vue para el detalle de por qué. */
    showLevel?: boolean
}>()

defineEmits<{
    'open-avatar': []
}>()

// ── Copiar username / ID ──────────────────────────────────────
async function copyToClipboard(text: string): Promise<boolean> {
    try {
        await navigator.clipboard.writeText(text)
        return true
    } catch {
        const el = document.createElement('textarea')
        el.value = text
        el.style.position = 'fixed'
        el.style.opacity = '0'
        document.body.appendChild(el)
        el.select()
        document.execCommand('copy')
        document.body.removeChild(el)
        return true
    }
}

const copiedUsername = ref(false)
async function copyUsername() {
    if (await copyToClipboard(`@${props.user.username}`)) {
        copiedUsername.value = true
        setTimeout(() => (copiedUsername.value = false), 2000)
    }
}

const copiedId = ref(false)
async function copyId() {
    if (await copyToClipboard(props.user.id)) {
        copiedId.value = true
        setTimeout(() => (copiedId.value = false), 2000)
    }
}

// El ID es un snowflake de 18-19 dígitos — se muestra truncado (últimos 8)
// con el valor completo copiado al portapapeles, igual que un short-hash.
const shortId = computed(() => `...${props.user.id.slice(-8)}`)

// ── Rol principal ──────────────────────────────────────────────
const primaryRole = computed(() => getRoleMeta(props.user.roles?.[0]?.name))
const primaryRoleKey   = computed(() => primaryRole.value.key)
const primaryRoleLabel = computed(() => primaryRole.value.label)
const primaryRoleIcon  = computed(() => primaryRole.value.icon)

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
.identity-card { display: flex; align-items: flex-start; gap: 1rem; min-width: 0; }

.avatar-wrap { position: relative; flex-shrink: 0; }
.avatar-img  {
    width: 68px; height: 68px; border-radius: 50%;
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

.identity-info { min-width: 0; }
.identity-name-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 2px; }
.identity-name     { color: #fff; font-size: 1.1rem; font-weight: 700; margin: 0; letter-spacing: -0.02em; line-height: 1.2; }

.identity-username {
    display: inline-flex; align-items: center; gap: 5px;
    background: none; border: none; padding: 0; margin: 0 0 6px;
    color: rgba(255,255,255,0.65); font-size: 0.78rem;
    cursor: pointer; transition: color 0.15s;
}
.identity-username:hover { color: rgba(255,255,255,0.9); }
.identity-username i { font-size: 0.65rem; opacity: 0.7; }

.identity-bio { color: rgba(255,255,255,0.72); font-size: 0.78rem; margin: 6px 0 0; max-width: 340px; line-height: 1.35; }
.identity-badges { display: flex; gap: 4px; flex-wrap: wrap; }

.identity-id {
    display: inline-flex; align-items: center; gap: 5px;
    background: rgba(255,255,255,0.08); border: none;
    padding: 3px 8px; margin-top: 6px; border-radius: 20px;
    color: rgba(255,255,255,0.5); font-size: 0.66rem; font-family: monospace;
    cursor: pointer; transition: background 0.15s, color 0.15s;
}
.identity-id:hover { background: rgba(255,255,255,0.16); color: rgba(255,255,255,0.8); }

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

@media (max-width: 480px) {
    .identity-card { gap: 0.8rem; }
    .avatar-img { width: 56px; height: 56px; }
}
</style>
