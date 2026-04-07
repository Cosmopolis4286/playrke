<template>
  <div class="security-tab">

    <!-- ── Left column ─────────────────────────────────────────────────────── -->
    <div class="security-main">

      <!-- Contraseña -->
      <section class="sec-section">
        <div class="sec-section-head">
          <div class="sec-icon-wrap sec-icon-wrap--warning">
            <i class="fas fa-key"></i>
          </div>
          <div>
            <h4 class="sec-section-title">Contraseña</h4>
            <p class="sec-section-desc">Cambia tu contraseña regularmente para mantener tu cuenta segura.</p>
          </div>
        </div>
        <button class="sec-btn sec-btn--warning" @click="$emit('open-password')">
          <i class="fas fa-lock me-2"></i>Cambiar Contraseña
        </button>
      </section>

      <hr class="sec-divider" />

      <!-- 2FA -->
      <section class="sec-section">
        <div class="sec-section-head">
          <div class="sec-icon-wrap sec-icon-wrap--info">
            <i class="fas fa-mobile-alt"></i>
          </div>
          <div>
            <h4 class="sec-section-title">Autenticación de Dos Factores</h4>
            <p class="sec-section-desc">Añade una capa adicional de seguridad a tu cuenta.</p>
          </div>
        </div>
        <label class="toggle-control">
          <div
            :class="['toggle-track', { 'toggle-track--on': twoFactorEnabled }]"
            @click="handleTwoFactorToggle"
          >
            <div class="toggle-thumb" />
          </div>
          <span class="toggle-text">
            {{ twoFactorEnabled ? '2FA activado' : '2FA desactivado' }}
          </span>
        </label>
      </section>

      <hr class="sec-divider" />

      <!-- Sesiones activas -->
      <section class="sec-section">
        <div class="sec-section-head">
          <div class="sec-icon-wrap sec-icon-wrap--primary">
            <i class="fas fa-desktop"></i>
          </div>
          <div>
            <h4 class="sec-section-title">Sesiones Activas</h4>
            <p class="sec-section-desc">Revisa y administra los dispositivos conectados a tu cuenta.</p>
          </div>
        </div>

        <div class="sessions-list">
          <!-- Current -->
          <div class="session-item session-item--current">
            <div class="session-device-icon">
              <i class="fas fa-desktop"></i>
            </div>
            <div class="session-info">
              <span class="session-device">Este dispositivo</span>
              <span class="session-meta">{{ currentDeviceInfo }}</span>
              <span class="session-active">
                <i class="fas fa-circle me-1" style="font-size:0.5rem;color:#28a745"></i>Activa ahora
              </span>
            </div>
            <span class="session-badge">Actual</span>
          </div>

          <!-- Other sessions -->
          <div v-for="session in activeSessions" :key="session.id" class="session-item">
            <div class="session-device-icon">
              <i :class="['fas', session.device.includes('iPhone') || session.device.includes('Android') ? 'fa-mobile-alt' : 'fa-laptop']"></i>
            </div>
            <div class="session-info">
              <span class="session-device">{{ session.device }}</span>
              <span class="session-meta">{{ session.location }}</span>
              <span class="session-time">{{ formatDate(session.last_activity?.toString(), true) }}</span>
            </div>
            <button class="session-revoke" @click="revokeSession(session.id)" title="Cerrar sesión">
              <i class="fas fa-sign-out-alt"></i>
            </button>
          </div>
        </div>

        <div v-if="activeSessions.length" class="mt-3">
          <button class="sec-btn sec-btn--danger-ghost" @click="revokeAllSessions">
            <i class="fas fa-sign-out-alt me-2"></i>Cerrar todas las sesiones
          </button>
        </div>
      </section>

    </div>

    <!-- ── Right column ────────────────────────────────────────────────────── -->
    <aside class="security-aside">

      <!-- Estado de cuenta -->
      <div class="sec-card">
        <h5 class="sec-card-title"><i class="fas fa-user-shield me-2"></i>Estado de la Cuenta</h5>
        <dl class="sec-info-list">
          <div class="sec-info-row">
            <dt>Estado</dt>
            <dd><span class="status-badge status-badge--success">Activa</span></dd>
          </div>
          <div class="sec-info-row">
            <dt>Email</dt>
            <dd>
              <span v-if="user.is_verified" class="status-badge status-badge--success">
                <i class="fas fa-check-circle me-1"></i>Verificado
              </span>
              <span v-else class="status-badge status-badge--warn">
                <i class="fas fa-exclamation-triangle me-1"></i>Sin verificar
              </span>
            </dd>
          </div>
          <div class="sec-info-row">
            <dt>Autenticación</dt>
            <dd>
              <span :class="['status-badge', twoFactorEnabled ? 'status-badge--info' : 'status-badge--muted']">
                <i :class="['fas me-1', twoFactorEnabled ? 'fa-shield-alt' : 'fa-unlock']"></i>
                {{ twoFactorEnabled ? 'Con 2FA' : 'Básica' }}
              </span>
            </dd>
          </div>
          <div class="sec-info-row">
            <dt>Último acceso</dt>
            <dd class="text-sm">{{ formatDate(user.last_active, true) }}</dd>
          </div>
        </dl>
      </div>

      <!-- Recomendaciones -->
      <div class="sec-card mt-3">
        <h5 class="sec-card-title"><i class="fas fa-lightbulb me-2 text-warning"></i>Recomendaciones</h5>
        <ul class="rec-list">
          <li v-for="rec in recommendations" :key="rec.text" class="rec-item">
            <i :class="['fas rec-icon', rec.done ? 'fa-check-circle text-success' : 'fa-circle text-muted']"></i>
            <span>{{ rec.text }}</span>
          </li>
        </ul>
      </div>

      <!-- Exportar datos -->
      <div class="sec-card mt-3">
        <h5 class="sec-card-title"><i class="fas fa-download me-2"></i>Mis Datos</h5>
        <p class="text-sm text-muted mb-2">Descarga una copia de tus datos de cuenta.</p>
        <button class="sec-btn sec-btn--ghost" @click="downloadUserData">
          <i class="fas fa-file-download me-2"></i>Exportar datos
        </button>
      </div>

    </aside>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { showToast } from '@js/utils/toastNotify'
import type { ProfileUser } from '@js/composables/useProfile'

const props = defineProps<{
    user: ProfileUser
    formatDate: (d: string | null | undefined, withTime?: boolean) => string
}>()

defineEmits<{ 'open-password': [] }>()

// ── State ──────────────────────────────────────────────────────────────────
const twoFactorEnabled = ref(false)

const activeSessions = ref([
    { id: 1, device: 'Chrome en Windows',  location: 'Caracas, Venezuela',   last_activity: new Date(Date.now() - 2 * 3600 * 1000) },
    { id: 2, device: 'Safari en iPhone',   location: 'Valencia, Venezuela',  last_activity: new Date(Date.now() - 86400 * 1000) },
])

// ── Computed ───────────────────────────────────────────────────────────────
const currentDeviceInfo = computed(() =>
    typeof navigator !== 'undefined' ? navigator.platform : 'Dispositivo desconocido'
)

const recommendations = computed(() => [
    { text: 'Usa una contraseña única y fuerte',         done: true },
    { text: 'Activa la autenticación de dos factores',   done: twoFactorEnabled.value },
    { text: 'Revisa tus sesiones activas regularmente',  done: false },
    { text: 'Mantén tu email verificado',                done: props.user.is_verified },
])

// ── Methods ────────────────────────────────────────────────────────────────
function handleTwoFactorToggle() {
    twoFactorEnabled.value = !twoFactorEnabled.value
    showToast(
        twoFactorEnabled.value ? '2FA activado' : '2FA desactivado',
        twoFactorEnabled.value ? 'success' : 'warning',
    )
}

function revokeSession(id: number) {
    activeSessions.value = activeSessions.value.filter(s => s.id !== id)
    showToast('Sesión cerrada', 'success')
}

function revokeAllSessions() {
    activeSessions.value = []
    showToast('Todas las sesiones han sido cerradas', 'success')
}

function downloadUserData() {
    const data = { id: props.user.id, name: props.user.name, email: props.user.email, created_at: props.user.created_at }
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
    const url  = URL.createObjectURL(blob)
    const link = Object.assign(document.createElement('a'), {
        href: url,
        download: `user_data_${props.user.id}.json`,
    })
    link.click()
    URL.revokeObjectURL(url)
    showToast('Datos exportados correctamente', 'success')
}
</script>

<style scoped>
/* ── Layout ── */
.security-tab {
    display: grid;
    grid-template-columns: 1fr 260px;
    gap: 2rem;
    align-items: start;
}

/* ── Sections ── */
.sec-section { margin-bottom: 0.25rem; }
.sec-section-head {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 1rem;
}
.sec-section-title { font-size: 0.9rem; font-weight: 700; color: #2d3a52; margin: 0 0 2px; }
.sec-section-desc  { font-size: 0.78rem; color: #8892a4; margin: 0; }

.sec-icon-wrap {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    flex-shrink: 0;
}
.sec-icon-wrap--warning { background: #fff3cd; color: #856404; }
.sec-icon-wrap--info    { background: #d1ecf1; color: #0c5460; }
.sec-icon-wrap--primary { background: #e8eaff; color: #667eea; }

.sec-divider { border: none; border-top: 1px solid #eaecf0; margin: 1.5rem 0; }

/* ── Buttons ── */
.sec-btn {
    display: inline-flex;
    align-items: center;
    padding: 7px 18px;
    border-radius: 9px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.15s;
}
.sec-btn--warning    { background: #fff3cd; color: #856404; border: 1px solid #ffc107; }
.sec-btn--warning:hover { background: #ffc107; color: #212529; }
.sec-btn--danger-ghost { background: transparent; color: #dc3545; border: 1px solid #dc3545; }
.sec-btn--danger-ghost:hover { background: #dc3545; color: #fff; }
.sec-btn--ghost { background: transparent; color: #4a5568; border: 1px solid #d1d9e6; }
.sec-btn--ghost:hover { background: #f8f9fc; }

/* ── 2FA toggle ── */
.toggle-control {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    user-select: none;
}
.toggle-track {
    width: 40px;
    height: 22px;
    border-radius: 22px;
    background: #d1d9e6;
    position: relative;
    transition: background 0.2s;
    cursor: pointer;
}
.toggle-track--on { background: #667eea; }
.toggle-thumb {
    position: absolute;
    top: 3px; left: 3px;
    width: 16px; height: 16px;
    border-radius: 50%;
    background: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    transition: transform 0.2s;
}
.toggle-track--on .toggle-thumb { transform: translateX(18px); }
.toggle-text { font-size: 0.82rem; font-weight: 600; color: #4a5568; }

/* ── Sessions ── */
.sessions-list { display: flex; flex-direction: column; gap: 8px; }

.session-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    border-radius: 10px;
    background: #f8f9fc;
    border: 1px solid #eaecf0;
    transition: border-color 0.15s;
}
.session-item--current { border-color: #667eea; background: #f0f2ff; }

.session-device-icon {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: rgba(102,126,234,0.1);
    color: #667eea;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    flex-shrink: 0;
}

.session-info {
    display: flex;
    flex-direction: column;
    gap: 1px;
    flex: 1;
    min-width: 0;
}
.session-device { font-size: 0.82rem; font-weight: 600; color: #2d3a52; }
.session-meta   { font-size: 0.72rem; color: #8892a4; }
.session-active { font-size: 0.7rem; color: #28a745; }
.session-time   { font-size: 0.7rem; color: #8892a4; }

.session-badge {
    font-size: 0.68rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 20px;
    background: #e8eaff;
    color: #667eea;
}

.session-revoke {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    border: 1px solid #f5c6cb;
    background: #fff5f5;
    color: #dc3545;
    font-size: 0.72rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s;
}
.session-revoke:hover { background: #dc3545; color: #fff; }

/* ── Aside cards ── */
.sec-card {
    background: #f8f9fc;
    border: 1px solid #eaecf0;
    border-radius: 12px;
    padding: 1rem 1.1rem;
}
.sec-card-title {
    font-size: 0.78rem;
    font-weight: 700;
    color: #4a5568;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.85rem;
}

.sec-info-list { margin: 0; }
.sec-info-row {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 4px 10px;
    padding: 6px 0;
    border-bottom: 1px solid #eaecf0;
    align-items: center;
}
.sec-info-row:last-child { border-bottom: none; }
.sec-info-row dt { font-size: 0.7rem; color: #8892a4; font-weight: 600; }
.sec-info-row dd { margin: 0; font-size: 0.78rem; color: #2d3a52; }
.text-sm { font-size: 0.75rem; }

/* Status badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    font-size: 0.68rem;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 20px;
}
.status-badge--success { background: #d4edda; color: #155724; }
.status-badge--warn    { background: #fff3cd; color: #856404; }
.status-badge--info    { background: #d1ecf1; color: #0c5460; }
.status-badge--muted   { background: #e9ecef; color: #495057; }

/* Recommendations */
.rec-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 8px; }
.rec-item {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 0.78rem;
    color: #4a5568;
}
.rec-icon { font-size: 0.72rem; margin-top: 2px; flex-shrink: 0; }

/* ── Responsive ── */
@media (max-width: 860px) {
    .security-tab { grid-template-columns: 1fr; }
}
</style>
