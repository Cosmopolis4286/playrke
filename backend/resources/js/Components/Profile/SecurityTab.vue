<template>
  <div class="security-tab">

    <!-- ── Columna principal ──────────────────────────────────────────────── -->
    <div class="security-main">

      <!-- Contraseña -->
      <section class="sec-section">
        <div class="sec-section-head">
          <div class="sec-icon-wrap sec-icon-wrap--warning"><i class="fas fa-key" /></div>
          <div>
            <h4 class="sec-section-title">Contraseña</h4>
            <p class="sec-section-desc">Cambia tu contraseña regularmente para mantener tu cuenta segura.</p>
          </div>
        </div>
        <button class="sec-btn sec-btn--warning" @click="$emit('open-password')">
          <i class="fas fa-lock me-2" />Cambiar contraseña
        </button>
      </section>

      <hr class="sec-divider" />

      <!-- TOS -->
      <section class="sec-section">
        <div class="sec-section-head">
          <div class="sec-icon-wrap" :class="user.tos_accepted_at ? 'sec-icon-wrap--success' : 'sec-icon-wrap--danger'">
            <i class="fas fa-file-contract" />
          </div>
          <div>
            <h4 class="sec-section-title">Términos de Servicio</h4>
            <p class="sec-section-desc">
              <template v-if="user.tos_accepted_at">
                Aceptaste los términos el {{ formatDate(user.tos_accepted_at, true) }}.
              </template>
              <template v-else>
                Debes aceptar los Términos de Servicio para operar con tu billetera.
              </template>
            </p>
          </div>
        </div>
        <span v-if="user.tos_accepted_at" class="status-badge status-badge--success">
          <i class="fas fa-check-circle me-1" />Aceptado
        </span>
        <button v-else class="sec-btn sec-btn--primary" @click="$emit('open-tos')">
          <i class="fas fa-file-signature me-2" />Aceptar términos
        </button>
      </section>

      <hr class="sec-divider" />

      <!-- KYC -->
      <section class="sec-section">
        <div class="sec-section-head">
          <div class="sec-icon-wrap"
            :class="{
              'sec-icon-wrap--success': user.kyc_status === 'VERIFIED',
              'sec-icon-wrap--warning': user.kyc_status === 'PENDING',
              'sec-icon-wrap--danger':  user.kyc_status === 'REJECTED',
              'sec-icon-wrap--muted':   user.kyc_status === 'NONE',
            }"
          ><i :class="kycIcon" /></div>
          <div>
            <h4 class="sec-section-title">Verificación de Identidad (KYC) — Nivel {{ user.kyc_level }}</h4>
            <p class="sec-section-desc">{{ kycDescription }}</p>
          </div>
        </div>
        <button
          v-if="user.kyc_status !== 'VERIFIED' && user.kyc_status !== 'PENDING'"
          class="sec-btn sec-btn--info"
          @click="$emit('open-kyc')"
        >
          <i class="fas fa-id-card me-2" />Verificar identidad
        </button>
        <span v-else-if="user.kyc_status === 'PENDING'" class="status-badge status-badge--warn">
          <i class="fas fa-clock me-1" />En revisión
        </span>
        <span v-else class="status-badge status-badge--success">
          <i class="fas fa-shield-check me-1" />Verificado
        </span>
      </section>

      <hr class="sec-divider" />

      <!-- 2FA (UI ready, backend pendiente) -->
      <section class="sec-section">
        <div class="sec-section-head">
          <div class="sec-icon-wrap sec-icon-wrap--info"><i class="fas fa-mobile-alt" /></div>
          <div>
            <h4 class="sec-section-title">Autenticación de Dos Factores</h4>
            <p class="sec-section-desc">Añade una capa adicional de seguridad a tu cuenta.</p>
          </div>
        </div>
        <label class="toggle-control">
          <div :class="['toggle-track', { 'toggle-track--on': twoFa }]" @click="handleTwoFaToggle">
            <div class="toggle-thumb" />
          </div>
          <span class="toggle-text">{{ twoFa ? '2FA activado' : '2FA desactivado' }}</span>
        </label>
      </section>

      <hr class="sec-divider" />

      <!-- Sesiones activas -->
      <section class="sec-section">
        <div class="sec-section-head">
          <div class="sec-icon-wrap sec-icon-wrap--primary"><i class="fas fa-desktop" /></div>
          <div>
            <h4 class="sec-section-title">Sesiones Activas</h4>
            <p class="sec-section-desc">Revisa y cierra sesiones en otros dispositivos.</p>
          </div>
          <button class="sec-btn-sm sec-btn-sm--ghost" :disabled="sessionsLoading" @click="$emit('load-sessions')">
            <i :class="['fas', sessionsLoading ? 'fa-spinner fa-spin' : 'fa-sync-alt']" />
          </button>
        </div>

        <div v-if="sessionsLoading" class="sessions-loading">
          <i class="fas fa-spinner fa-spin" /> Cargando sesiones...
        </div>

        <div v-else-if="sessions.length" class="sessions-list">
          <div v-for="session in sessions" :key="session.id"
            class="session-item"
            :class="{ 'session-item--current': session.is_current }"
          >
            <div class="session-device-icon">
              <i :class="['fas', session.device.includes('iOS') || session.device.includes('Android') ? 'fa-mobile-alt' : 'fa-desktop']" />
            </div>
            <div class="session-info">
              <span class="session-device">{{ session.device }}</span>
              <span class="session-meta">IP: {{ session.ip_address ?? '—' }}</span>
              <span class="session-time">{{ formatUnixDate(session.last_activity) }}</span>
            </div>
            <span v-if="session.is_current" class="session-badge">Actual</span>
            <button v-else class="session-revoke" @click="$emit('revoke-session', session.id)" title="Cerrar sesión">
              <i class="fas fa-sign-out-alt" />
            </button>
          </div>
        </div>

        <p v-else class="sessions-empty">No se encontraron otras sesiones.</p>

        <div v-if="otherSessions.length" class="mt-3">
          <button class="sec-btn sec-btn--danger-ghost" @click="$emit('revoke-all-sessions')">
            <i class="fas fa-sign-out-alt me-2" />Cerrar todas las otras sesiones
          </button>
        </div>
      </section>

    </div>

    <!-- ── Aside ──────────────────────────────────────────────────────────── -->
    <aside class="security-aside">

      <div class="sec-card">
        <h5 class="sec-card-title"><i class="fas fa-user-shield me-2" />Estado de la Cuenta</h5>
        <dl class="sec-info-list">
          <div class="sec-info-row">
            <dt>Estado</dt>
            <dd><span class="status-badge status-badge--success">Activa</span></dd>
          </div>
          <div class="sec-info-row">
            <dt>Email</dt>
            <dd>
              <span v-if="user.is_verified" class="status-badge status-badge--success"><i class="fas fa-check-circle me-1" />Verificado</span>
              <span v-else class="status-badge status-badge--warn"><i class="fas fa-exclamation-triangle me-1" />Sin verificar</span>
            </dd>
          </div>
          <div class="sec-info-row">
            <dt>KYC</dt>
            <dd>
              <span class="status-badge"
                :class="{
                  'status-badge--success': user.kyc_status === 'VERIFIED',
                  'status-badge--warn':    user.kyc_status === 'PENDING',
                  'status-badge--muted':   user.kyc_status === 'NONE',
                  'status-badge--danger':  user.kyc_status === 'REJECTED',
                }"
              >Nivel {{ user.kyc_level }}</span>
            </dd>
          </div>
          <div class="sec-info-row">
            <dt>TOS</dt>
            <dd>
              <span v-if="user.tos_accepted_at" class="status-badge status-badge--success"><i class="fas fa-check-circle me-1" />Aceptado</span>
              <span v-else class="status-badge status-badge--danger"><i class="fas fa-times-circle me-1" />Pendiente</span>
            </dd>
          </div>
          <div class="sec-info-row">
            <dt>2FA</dt>
            <dd>
              <span :class="['status-badge', twoFa ? 'status-badge--info' : 'status-badge--muted']">
                <i :class="['fas me-1', twoFa ? 'fa-shield-alt' : 'fa-unlock']" />
                {{ twoFa ? 'Activo' : 'Inactivo' }}
              </span>
            </dd>
          </div>
          <div class="sec-info-row">
            <dt>Último acceso</dt>
            <dd class="text-sm">{{ formatDate(user.last_active, true) }}</dd>
          </div>
        </dl>
      </div>

      <div class="sec-card mt-3">
        <h5 class="sec-card-title"><i class="fas fa-lightbulb me-2 text-warning" />Recomendaciones</h5>
        <ul class="rec-list">
          <li v-for="rec in recommendations" :key="rec.text" class="rec-item">
            <i :class="['fas rec-icon', rec.done ? 'fa-check-circle text-success' : 'fa-circle text-muted']" />
            <span>{{ rec.text }}</span>
          </li>
        </ul>
      </div>

      <div class="sec-card mt-3">
        <h5 class="sec-card-title"><i class="fas fa-download me-2" />Mis datos</h5>
        <p class="text-sm text-muted mb-2">Descarga una copia básica de tus datos.</p>
        <button class="sec-btn sec-btn--ghost" @click="downloadData">
          <i class="fas fa-file-download me-2" />Exportar datos
        </button>
      </div>

    </aside>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { showToast } from '@js/utils/toastNotify'
import type { ProfileUser, ProfileSession } from '@js/composables/useProfile'

const props = defineProps<{
    user: ProfileUser
    sessions: ProfileSession[]
    sessionsLoading: boolean
    formatDate: (d: string | null | undefined, withTime?: boolean) => string
    formatUnixDate: (ts: number) => string
}>()

defineEmits<{
    'open-password': []
    'open-tos': []
    'open-kyc': []
    'load-sessions': []
    'revoke-session': [id: string]
    'revoke-all-sessions': []
}>()

const twoFa = ref(props.user.kyc_level > 0) // placeholder

function handleTwoFaToggle() {
    twoFa.value = !twoFa.value
    showToast(twoFa.value ? '2FA activado' : '2FA desactivado', twoFa.value ? 'success' : 'warning')
}

const otherSessions = computed(() => props.sessions.filter(s => !s.is_current))

const kycIcon = computed(() => ({
    VERIFIED: 'fas fa-shield-check',
    PENDING:  'fas fa-clock',
    REJECTED: 'fas fa-times-circle',
    NONE:     'fas fa-shield-alt',
}[props.user.kyc_status] ?? 'fas fa-shield-alt'))

const kycDescription = computed(() => ({
    VERIFIED: 'Tu identidad ha sido verificada — tienes acceso completo a retiros.',
    PENDING:  'Tus documentos están en revisión. Recibirás una notificación en 24-48 horas.',
    REJECTED: 'Tu verificación fue rechazada. Sube tus documentos de nuevo.',
    NONE:     'Verifica tu identidad para desbloquear mayores límites de retiro.',
}[props.user.kyc_status] ?? ''))

const recommendations = computed(() => [
    { text: 'Usa una contraseña única y fuerte',        done: true },
    { text: 'Activa la autenticación de dos factores',  done: twoFa.value },
    { text: 'Acepta los Términos de Servicio',          done: !!props.user.tos_accepted_at },
    { text: 'Verifica tu identidad (KYC)',              done: props.user.kyc_status === 'VERIFIED' },
    { text: 'Mantén tu email verificado',               done: props.user.is_verified },
])

function downloadData() {
    const data = { id: props.user.id, name: props.user.name, email: props.user.email, created_at: props.user.created_at }
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
    const url  = URL.createObjectURL(blob)
    const link = Object.assign(document.createElement('a'), { href: url, download: `user_${props.user.id}.json` })
    link.click()
    URL.revokeObjectURL(url)
    showToast('Datos exportados', 'success')
}
</script>

<style scoped>
.security-tab { display: grid; grid-template-columns: 1fr 260px; gap: 2rem; align-items: start; }

.sec-section { margin-bottom: 0.25rem; }
.sec-section-head { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 1rem; }
.sec-section-title { font-size: 0.9rem; font-weight: 700; color: var(--ds-text); margin: 0 0 2px; }
.sec-section-desc  { font-size: 0.78rem; color: var(--ds-text-muted); margin: 0; flex: 1; }

.sec-icon-wrap { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; flex-shrink: 0; }
.sec-icon-wrap--warning { background: #fff3cd; color: #856404; }
.sec-icon-wrap--info    { background: #d1ecf1; color: #0c5460; }
.sec-icon-wrap--primary { background: #e8eaff; color: #667eea; }
.sec-icon-wrap--success { background: #d4edda; color: #155724; }
.sec-icon-wrap--danger  { background: #f8d7da; color: #721c24; }
.sec-icon-wrap--muted   { background: #e9ecef; color: #6c757d; }

.sec-divider { border: none; border-top: 1px solid var(--ds-border); margin: 1.5rem 0; }

.sec-btn { display: inline-flex; align-items: center; padding: 7px 18px; border-radius: 9px; font-size: 0.8rem; font-weight: 600; cursor: pointer; border: none; transition: all 0.15s; }
.sec-btn--warning      { background: #fff3cd; color: #856404; border: 1px solid #ffc107; }
.sec-btn--warning:hover { background: #ffc107; color: #212529; }
.sec-btn--primary      { background: linear-gradient(135deg,#667eea,#764ba2); color: #fff; }
.sec-btn--primary:hover { filter: brightness(1.08); }
.sec-btn--info         { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
.sec-btn--info:hover   { background: #bee5eb; }
.sec-btn--danger-ghost { background: transparent; color: #dc3545; border: 1px solid #dc3545; }
.sec-btn--danger-ghost:hover { background: #dc3545; color: #fff; }
.sec-btn--ghost        { background: transparent; color: var(--ds-text-2); border: 1px solid var(--ds-border-2); }
.sec-btn--ghost:hover  { background: var(--ds-surface-2); }

.sec-btn-sm { padding: 4px 10px; border-radius: 7px; font-size: 0.75rem; font-weight: 600; cursor: pointer; border: none; transition: all 0.15s; }
.sec-btn-sm--ghost { background: transparent; color: var(--ds-text-2); border: 1px solid var(--ds-border); }
.sec-btn-sm--ghost:hover { background: var(--ds-surface-2); }

.toggle-control { display: flex; align-items: center; gap: 10px; cursor: pointer; user-select: none; }
.toggle-track   { width: 40px; height: 22px; border-radius: 22px; background: var(--ds-border-2); position: relative; transition: background 0.2s; cursor: pointer; }
.toggle-track--on { background: #667eea; }
.toggle-thumb   { position: absolute; top: 3px; left: 3px; width: 16px; height: 16px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.2); transition: transform 0.2s; }
.toggle-track--on .toggle-thumb { transform: translateX(18px); }
.toggle-text    { font-size: 0.82rem; font-weight: 600; color: var(--ds-text-2); }

.sessions-loading { font-size: 0.82rem; color: var(--ds-text-2); padding: 0.5rem 0; }
.sessions-empty   { font-size: 0.82rem; color: var(--ds-text-muted); margin: 0; }
.sessions-list    { display: flex; flex-direction: column; gap: 8px; }

.session-item { display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 10px; background: var(--ds-surface-2); border: 1px solid var(--ds-border); transition: border-color 0.15s; }
.session-item--current { border-color: #667eea; background: rgba(102,126,234,0.08); }
.session-device-icon { width: 34px; height: 34px; border-radius: 8px; background: rgba(102,126,234,0.1); color: #667eea; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; flex-shrink: 0; }
.session-info   { display: flex; flex-direction: column; gap: 1px; flex: 1; min-width: 0; }
.session-device { font-size: 0.82rem; font-weight: 600; color: var(--ds-text); }
.session-meta   { font-size: 0.72rem; color: var(--ds-text-muted); }
.session-time   { font-size: 0.7rem; color: var(--ds-text-muted); }
.session-badge  { font-size: 0.68rem; font-weight: 700; padding: 2px 8px; border-radius: 20px; background: #e8eaff; color: #667eea; }
.session-revoke { width: 28px; height: 28px; border-radius: 8px; border: 1px solid #f5c6cb; background: #fff5f5; color: #dc3545; font-size: 0.72rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.15s; }
.session-revoke:hover { background: #dc3545; color: #fff; }

.sec-card       { background: var(--ds-surface-2); border: 1px solid var(--ds-border); border-radius: 12px; padding: 1rem 1.1rem; }
.sec-card-title { font-size: 0.78rem; font-weight: 700; color: var(--ds-text-2); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.85rem; }

.sec-info-list { margin: 0; }
.sec-info-row  { display: grid; grid-template-columns: auto 1fr; gap: 4px 10px; padding: 6px 0; border-bottom: 1px solid var(--ds-border); align-items: center; }
.sec-info-row:last-child { border-bottom: none; }
.sec-info-row dt { font-size: 0.7rem; color: var(--ds-text-muted); font-weight: 600; }
.sec-info-row dd { margin: 0; font-size: 0.78rem; color: var(--ds-text); }
.text-sm { font-size: 0.75rem; }

.status-badge  { display: inline-flex; align-items: center; font-size: 0.68rem; font-weight: 600; padding: 2px 8px; border-radius: 20px; }
.status-badge--success { background: #d4edda; color: #155724; }
.status-badge--warn    { background: #fff3cd; color: #856404; }
.status-badge--info    { background: #d1ecf1; color: #0c5460; }
.status-badge--muted   { background: #e9ecef; color: #495057; }
.status-badge--danger  { background: #f8d7da; color: #721c24; }

.rec-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 8px; }
.rec-item { display: flex; align-items: flex-start; gap: 8px; font-size: 0.78rem; color: var(--ds-text-2); }
.rec-icon { font-size: 0.72rem; margin-top: 2px; flex-shrink: 0; }

.mt-3 { margin-top: 0.75rem; }
.mb-2 { margin-bottom: 0.5rem; }
.text-muted  { color: var(--ds-text-muted); }
.text-warning{ color: #856404; }
.text-success{ color: #155724; }

@media (max-width: 860px) { .security-tab { grid-template-columns: 1fr; } }
</style>
