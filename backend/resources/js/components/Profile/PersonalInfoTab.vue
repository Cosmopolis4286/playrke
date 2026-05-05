<template>
  <div class="personal-tab">

    <!-- ── Formulario ─────────────────────────────────────────────────────── -->
    <section class="form-section">
      <div class="section-header">
        <h3 class="section-title"><i class="fas fa-user-circle me-2 text-primary" />Datos Personales</h3>
        <span v-if="editMode" class="section-hint">
          <i class="fas fa-info-circle me-1" />Edita y guarda los cambios
        </span>
      </div>

      <!-- Error global -->
      <Transition name="msg-fade">
        <div v-if="errorMsg" class="form-error-bar">
          <i class="fas fa-exclamation-triangle" /> {{ errorMsg }}
        </div>
      </Transition>

      <form @submit.prevent="$emit('submit')">
        <div class="fields-grid">

          <!-- Nombre -->
          <div class="field-wrap">
            <label class="field-label">Nombre</label>
            <input v-model="profileForm.first_name" type="text" class="field-input"
              :class="{ 'field-input--disabled': !editMode, 'field-input--error': profileForm.errors.first_name }"
              :disabled="!editMode" placeholder="Tu nombre" maxlength="100" />
            <p v-if="profileForm.errors.first_name" class="field-error">{{ profileForm.errors.first_name }}</p>
          </div>

          <!-- Apellido -->
          <div class="field-wrap">
            <label class="field-label">Apellido</label>
            <input v-model="profileForm.last_name" type="text" class="field-input"
              :class="{ 'field-input--disabled': !editMode }"
              :disabled="!editMode" placeholder="Tu apellido" maxlength="100" />
          </div>

          <!-- Username -->
          <div class="field-wrap">
            <label class="field-label">Nombre de usuario</label>
            <input v-model="profileForm.username" type="text" class="field-input"
              :class="{ 'field-input--disabled': !editMode, 'field-input--error': profileForm.errors.username }"
              :disabled="!editMode" placeholder="username" maxlength="30" />
            <p v-if="profileForm.errors.username" class="field-error">{{ profileForm.errors.username }}</p>
          </div>

          <!-- Email -->
          <div class="field-wrap">
            <label class="field-label">
              Correo electrónico
              <span v-if="user.is_verified" class="field-badge field-badge--ok"><i class="fas fa-check-circle" /> Verificado</span>
              <span v-else class="field-badge field-badge--warn"><i class="fas fa-exclamation-circle" /> Sin verificar</span>
            </label>
            <input v-model="profileForm.email" type="email" class="field-input"
              :class="{ 'field-input--disabled': !editMode, 'field-input--error': profileForm.errors.email }"
              :disabled="!editMode" placeholder="correo@ejemplo.com" />
            <p v-if="profileForm.errors.email" class="field-error">{{ profileForm.errors.email }}</p>
          </div>

          <!-- Teléfono -->
          <div class="field-wrap">
            <label class="field-label">Teléfono</label>
            <input v-model="profileForm.phone" type="tel" class="field-input"
              :class="{ 'field-input--disabled': !editMode }"
              :disabled="!editMode" placeholder="+58 412 1234567" maxlength="25" />
          </div>

          <!-- Fecha de nacimiento -->
          <div class="field-wrap">
            <label class="field-label">
              Fecha de nacimiento
              <span v-if="!user.birth_date && !editMode" class="field-badge field-badge--warn">
                <i class="fas fa-exclamation-circle" /> Sin registrar
              </span>
            </label>
            <input v-model="profileForm.birth_date" type="date" class="field-input"
              :class="{ 'field-input--disabled': !isBirthDateEditable }"
              :disabled="!isBirthDateEditable" />
            <p v-if="profileForm.errors.birth_date" class="field-error">{{ profileForm.errors.birth_date }}</p>
          </div>

          <!-- País -->
          <div class="field-wrap">
            <label class="field-label">País</label>
            <input v-if="!editMode" type="text" class="field-input field-input--disabled"
              :value="selectedCountryName || '—'" disabled />
            <select v-else v-model="profileForm.country_id" class="field-input">
              <option value="">Selecciona tu país</option>
              <option v-for="c in countries" :key="c.value" :value="c.value">{{ c.label }}</option>
            </select>
          </div>

          <!-- Bio (ocupa columna completa) -->
          <div class="field-wrap field-wrap--full">
            <label class="field-label">Biografía <span class="field-optional">(opcional)</span></label>
            <textarea v-model="profileForm.bio" class="field-input field-textarea"
              :class="{ 'field-input--disabled': !editMode }"
              :disabled="!editMode"
              rows="3" maxlength="500"
              placeholder="Cuéntanos algo sobre ti..." />
            <p class="field-hint">{{ (profileForm.bio?.length ?? 0) }}/500</p>
          </div>

        </div>

        <!-- Acciones del form -->
        <div v-if="editMode || !user.birth_date" class="form-actions">
          <template v-if="editMode">
            <button type="submit" class="btn-action btn-action--primary" :disabled="profileForm.processing">
              <i class="fas fa-save me-2" />
              {{ profileForm.processing ? 'Guardando...' : 'Guardar cambios' }}
            </button>
            <button type="button" class="btn-action btn-action--ghost" @click="$emit('cancel')">
              <i class="fas fa-times me-2" />Cancelar
            </button>
          </template>
          <button v-else-if="!user.birth_date" type="submit" class="btn-action btn-action--primary"
            :disabled="profileForm.processing">
            <i class="fas fa-save me-2" />
            {{ profileForm.processing ? 'Guardando...' : 'Guardar fecha' }}
          </button>
        </div>
      </form>

      <!-- ── KYC integrado ─────────────────────────────────────────────── -->
      <div class="kyc-inline-section">
        <div class="kyc-inline-header">
          <div class="kyc-inline-icon" :class="`kyc-inline-icon--${kycColorKey}`">
            <i :class="kycIcon" />
          </div>
          <div>
            <h4 class="kyc-inline-title">Verificación de Identidad (KYC)</h4>
            <p class="kyc-inline-sub">
              {{ kycDescription }}
            </p>
          </div>
          <button
            v-if="user.kyc_status !== 'VERIFIED' && user.kyc_status !== 'PENDING'"
            type="button" class="kyc-inline-btn"
            @click="$emit('open-kyc')"
          >
            <i class="fas fa-id-card" /> Verificar ahora
          </button>
          <span v-else-if="user.kyc_status === 'PENDING'" class="kyc-inline-badge kyc-inline-badge--pending">
            En revisión
          </span>
          <span v-else class="kyc-inline-badge kyc-inline-badge--verified">
            <i class="fas fa-check-circle" /> Verificado
          </span>
        </div>

        <!-- Barra de nivel KYC -->
        <div class="kyc-levels">
          <div v-for="(lvl, i) in kycLevels" :key="i"
            class="kyc-level-step"
            :class="{ 'kyc-level-step--done': user.kyc_level > i, 'kyc-level-step--active': user.kyc_level === i }"
          >
            <div class="kyc-level-dot">
              <i v-if="user.kyc_level > i" class="fas fa-check" />
              <span v-else>{{ i + 1 }}</span>
            </div>
            <span class="kyc-level-label">{{ lvl }}</span>
          </div>
        </div>
      </div>

    </section>

    <!-- ── Sidebar info ────────────────────────────────────────────────────── -->
    <aside class="info-aside">

      <div class="info-card">
        <h4 class="info-card-title"><i class="fas fa-id-card me-2" />Información de Cuenta</h4>
        <dl class="info-list">
          <div class="info-row">
            <dt>ID</dt>
            <dd class="font-mono text-xs">{{ user.id }}</dd>
          </div>
          <div class="info-row">
            <dt>Miembro desde</dt>
            <dd>{{ formatDate(user.created_at) }}</dd>
          </div>
          <div class="info-row">
            <dt>Última actividad</dt>
            <dd>{{ formatDate(user.last_active) }}</dd>
          </div>
          <div class="info-row">
            <dt>Email</dt>
            <dd>
              <span v-if="user.is_verified" class="status-badge status-badge--success">
                <i class="fas fa-check-circle me-1" />Verificado
              </span>
              <span v-else class="status-badge status-badge--warn">
                <i class="fas fa-exclamation-triangle me-1" />Sin verificar
              </span>
            </dd>
          </div>
          <div class="info-row">
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
          <div class="info-row">
            <dt>TOS</dt>
            <dd>
              <span v-if="user.tos_accepted_at" class="status-badge status-badge--success">
                <i class="fas fa-check-circle me-1" />Aceptado
              </span>
              <span v-else class="status-badge status-badge--danger">
                <i class="fas fa-times-circle me-1" />Pendiente
              </span>
            </dd>
          </div>
          <div v-if="user.wallet" class="info-row">
            <dt>Saldo</dt>
            <dd class="fw-bold text-success">
              {{ user.wallet.symbol }} {{ formatCurrency(user.wallet.balance, user.wallet.currency) }}
            </dd>
          </div>
        </dl>
      </div>

      <div class="info-card mt-3">
        <h4 class="info-card-title"><i class="fas fa-user-tag me-2" />Roles</h4>
        <div class="roles-list">
          <div v-for="role in user.roles" :key="role.id" class="role-item">
            <span class="role-name">{{ role.name }}</span>
            <span class="role-desc">{{ role.description ?? 'Usuario del sistema' }}</span>
          </div>
        </div>
      </div>

    </aside>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { ProfileUser, CountryOption } from '@js/composables/useProfile'

const props = defineProps<{
    user: ProfileUser
    countries: CountryOption[]
    editMode: boolean
    profileForm: any
    selectedCountryName: string
    isBirthDateEditable: boolean
    errorMsg: string | null
    formatDate: (d: string | null | undefined, withTime?: boolean) => string
    formatCurrency: (amount: number | null, currency: unknown) => string
}>()

defineEmits<{ submit: []; cancel: []; 'open-kyc': [] }>()

const kycLevels = ['Sin verificar', 'Básico', 'Completo']

const kycColorKey = computed(() => ({
    VERIFIED: 'verified',
    PENDING:  'pending',
    REJECTED: 'rejected',
    NONE:     'none',
}[props.user.kyc_status] ?? 'none'))

const kycIcon = computed(() => ({
    VERIFIED: 'fas fa-shield-check',
    PENDING:  'fas fa-clock',
    REJECTED: 'fas fa-times-circle',
    NONE:     'fas fa-shield-alt',
}[props.user.kyc_status] ?? 'fas fa-shield-alt'))

const kycDescription = computed(() => ({
    VERIFIED: 'Tu identidad ha sido verificada. Tienes acceso a los límites máximos de retiro.',
    PENDING:  'Tus documentos están en revisión. Recibirás una notificación en 24-48 horas.',
    REJECTED: 'Tu verificación fue rechazada. Por favor, sube tus documentos de nuevo.',
    NONE:     'Verifica tu identidad para desbloquear mayores límites de retiro.',
}[props.user.kyc_status] ?? 'Verifica tu identidad para desbloquear mayores límites de retiro.'))
</script>

<style scoped>
.personal-tab { display: grid; grid-template-columns: 1fr 280px; gap: 2rem; align-items: start; }

/* Header */
.section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 8px; }
.section-title  { font-size: 1rem; font-weight: 700; color: #2d3a52; margin: 0; }
.section-hint   { font-size: 0.78rem; color: #8892a4; }

/* Error bar */
.form-error-bar { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 0.6rem 0.8rem; font-size: 0.82rem; color: #dc2626; display: flex; gap: 0.4rem; align-items: center; margin-bottom: 1rem; }

/* Fields */
.fields-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.1rem; margin-bottom: 1.5rem; }
.field-wrap  { display: flex; flex-direction: column; gap: 4px; }
.field-wrap--full { grid-column: 1 / -1; }

.field-label { font-size: 0.8rem; font-weight: 600; color: #4a5568; display: flex; align-items: center; gap: 6px; margin-bottom: 2px; }
.field-optional { font-size: 0.72rem; color: #94a3b8; font-weight: 400; }

.field-badge       { font-size: 0.68rem; padding: 2px 8px; border-radius: 20px; font-weight: 600; display: inline-flex; align-items: center; gap: 3px; }
.field-badge--ok   { background: #d4edda; color: #155724; }
.field-badge--warn { background: #fff3cd; color: #856404; }

.field-input {
    width: 100%; padding: 0.45rem 0.75rem; border: 1.5px solid #d1d9e6;
    border-radius: 8px; font-size: 0.875rem; color: #2d3a52; background: #fff;
    transition: border-color 0.15s, box-shadow 0.15s; outline: none; box-sizing: border-box;
}
.field-input:focus        { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.12); }
.field-input--disabled    { background: #f8f9fc; color: #6c757d; cursor: default; }
.field-input--error       { border-color: #dc3545; }
.field-textarea           { resize: vertical; min-height: 80px; font-family: inherit; }

.field-error { font-size: 0.75rem; color: #dc3545; margin: 0; }
.field-hint  { font-size: 0.72rem; color: #94a3b8; margin: 0; text-align: right; }

/* Actions */
.form-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 2rem; }
.btn-action { display: inline-flex; align-items: center; padding: 8px 20px; border-radius: 10px; font-size: 0.82rem; font-weight: 600; cursor: pointer; border: none; transition: all 0.15s; }
.btn-action--primary { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; box-shadow: 0 2px 8px rgba(102,126,234,0.3); }
.btn-action--primary:hover:not(:disabled) { filter: brightness(1.08); transform: translateY(-1px); }
.btn-action--primary:disabled { opacity: 0.65; cursor: not-allowed; }
.btn-action--ghost { background: transparent; color: #6c757d; border: 1px solid #d1d9e6; }
.btn-action--ghost:hover { background: #f8f9fc; }

/* KYC inline */
.kyc-inline-section { background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 1.1rem; }
.kyc-inline-header  { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 1rem; }
.kyc-inline-icon    { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
.kyc-inline-icon--none     { background: #f1f5f9; color: #64748b; }
.kyc-inline-icon--pending  { background: #fffbeb; color: #d97706; }
.kyc-inline-icon--verified { background: #f0fdf4; color: #16a34a; }
.kyc-inline-icon--rejected { background: #fef2f2; color: #dc2626; }
.kyc-inline-title  { font-size: 0.875rem; font-weight: 700; color: #1e293b; margin: 0 0 3px; }
.kyc-inline-sub    { font-size: 0.78rem; color: #64748b; margin: 0; flex: 1; line-height: 1.4; }
.kyc-inline-btn    { display: inline-flex; align-items: center; gap: 5px; padding: 6px 14px; background: linear-gradient(135deg,#667eea,#764ba2); color: #fff; border: none; border-radius: 8px; font-size: 0.78rem; font-weight: 600; cursor: pointer; white-space: nowrap; flex-shrink: 0; transition: all 0.15s; }
.kyc-inline-btn:hover { filter: brightness(1.08); }
.kyc-inline-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 0.72rem; font-weight: 600; padding: 3px 10px; border-radius: 20px; flex-shrink: 0; }
.kyc-inline-badge--pending  { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
.kyc-inline-badge--verified { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }

.kyc-levels { display: flex; align-items: center; gap: 0; }
.kyc-level-step {
    display: flex; align-items: center; gap: 8px; flex: 1;
    position: relative; padding-right: 1rem;
}
.kyc-level-step:not(:last-child)::after {
    content: ''; position: absolute; right: 0; top: 12px;
    width: calc(100% - 28px); left: 28px; height: 2px; background: #e2e8f0; z-index: 0;
}
.kyc-level-step--done::after  { background: #667eea; }
.kyc-level-dot {
    width: 24px; height: 24px; border-radius: 50%; border: 2px solid #e2e8f0;
    background: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 0.7rem; font-weight: 700; color: #94a3b8; flex-shrink: 0; z-index: 1;
}
.kyc-level-step--done   .kyc-level-dot { background: #667eea; border-color: #667eea; color: #fff; }
.kyc-level-step--active .kyc-level-dot { border-color: #667eea; color: #667eea; }
.kyc-level-label { font-size: 0.7rem; color: #64748b; white-space: nowrap; }
.kyc-level-step--done   .kyc-level-label { color: #667eea; font-weight: 600; }
.kyc-level-step--active .kyc-level-label { color: #1e293b; font-weight: 600; }

/* Aside */
.info-card       { background: #f8f9fc; border-radius: 12px; padding: 1.1rem 1.2rem; border: 1px solid #eaecf0; }
.info-card-title { font-size: 0.82rem; font-weight: 700; color: #4a5568; margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 0.05em; }
.info-list       { margin: 0; }
.info-row { display: grid; grid-template-columns: auto 1fr; gap: 4px 12px; padding: 6px 0; border-bottom: 1px solid #eaecf0; align-items: center; }
.info-row:last-child { border-bottom: none; }
.info-row dt { font-size: 0.72rem; color: #8892a4; font-weight: 600; white-space: nowrap; }
.info-row dd { font-size: 0.82rem; color: #2d3a52; margin: 0; word-break: break-all; }
.font-mono { font-family: monospace; }
.text-xs   { font-size: 0.72rem; }

.status-badge { display: inline-flex; align-items: center; font-size: 0.72rem; font-weight: 600; padding: 2px 8px; border-radius: 20px; }
.status-badge--success { background: #d4edda; color: #155724; }
.status-badge--warn    { background: #fff3cd; color: #856404; }
.status-badge--muted   { background: #e9ecef; color: #495057; }
.status-badge--danger  { background: #f8d7da; color: #721c24; }

.roles-list { display: flex; flex-direction: column; gap: 6px; }
.role-item  { display: flex; flex-direction: column; gap: 1px; }
.role-name  { font-size: 0.78rem; font-weight: 700; color: #667eea; }
.role-desc  { font-size: 0.72rem; color: #8892a4; }

.mt-3 { margin-top: 0.75rem; }
.fw-bold { font-weight: 700; }
.text-success { color: #28a745; }

.msg-fade-enter-active, .msg-fade-leave-active { transition: opacity 0.2s; }
.msg-fade-enter-from, .msg-fade-leave-to       { opacity: 0; }

@media (max-width: 860px) { .personal-tab { grid-template-columns: 1fr; } }
@media (max-width: 540px) { .fields-grid  { grid-template-columns: 1fr; } }
</style>
