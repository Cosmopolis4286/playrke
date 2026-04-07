<template>
  <div class="personal-tab">
    <!-- ── Left: form ─────────────────────────────────────────────────────── -->
    <section class="form-section">
      <div class="section-header">
        <h3 class="section-title">
          <i class="fas fa-user-circle me-2 text-primary"></i>Datos Personales
        </h3>
        <span v-if="editMode" class="section-hint">
          <i class="fas fa-info-circle me-1"></i>Edita tus datos y guarda los cambios
        </span>
      </div>

      <form @submit.prevent="$emit('submit')">
        <div class="fields-grid">
          <!-- Nombre -->
          <FormInput
            id="name" name="name" label="Nombre Completo"
            v-model="profileForm.name"
            :error="profileForm.errors.name"
            :disabled="!editMode"
            placeholder="Tu nombre completo"
            col="col-span-1"
          />
          <!-- Username -->
          <FormInput
            id="username" name="username" label="Nombre de Usuario"
            v-model="profileForm.username"
            :error="profileForm.errors.username"
            :disabled="!editMode"
            placeholder="username"
            col="col-span-1"
          />
          <!-- Email -->
          <FormInput
            id="email" name="email" label="Correo Electrónico" type="email"
            v-model="profileForm.email"
            :error="profileForm.errors.email"
            :disabled="!editMode"
            placeholder="correo@ejemplo.com"
            col="col-span-1"
          />
          <!-- Teléfono -->
          <FormInput
            id="phone" name="phone" label="Teléfono" type="tel"
            v-model="profileForm.phone"
            :error="profileForm.errors.phone"
            :disabled="!editMode"
            placeholder="+58 412 1234567"
            col="col-span-1"
          />

          <!-- Fecha de Nacimiento -->
          <div class="field-wrap">
            <label class="field-label">
              Fecha de Nacimiento
              <span v-if="!user.birth_date && !editMode" class="field-badge field-badge--warn">
                <i class="fas fa-exclamation-circle me-1"></i>Sin registrar
              </span>
            </label>
            <input
              id="birth_date" name="birth_date" type="date"
              v-model="profileForm.birth_date"
              :disabled="!isBirthDateEditable"
              :class="['field-input', { 'field-input--disabled': !isBirthDateEditable }]"
            />
            <p v-if="profileForm.errors.birth_date" class="field-error">
              {{ profileForm.errors.birth_date }}
            </p>
            <p v-else-if="!user.birth_date && !editMode" class="field-hint">
              Puedes agregar tu fecha de nacimiento directamente aquí.
            </p>
          </div>

          <!-- País -->
          <div class="field-wrap">
            <label class="field-label">País</label>
            <input
              v-if="!editMode"
              type="text"
              class="field-input field-input--disabled"
              :value="selectedCountryName || '—'"
              disabled
            />
            <FormSelect
              v-else
              id="country_id" name="country_id" label="País"
              :hide-label="true"
              v-model="profileForm.country_id"
              :options="countries"
              option-value="value"
              option-label="label"
              :error="profileForm.errors.country_id"
              placeholder="Selecciona tu país"
              col="col-12 p-0"
            />
          </div>
        </div>

        <!-- Acciones -->
        <div v-if="editMode || !user.birth_date" class="form-actions">
          <button
            v-if="!user.birth_date && !editMode"
            type="submit"
            class="btn-action btn-action--primary"
            :disabled="profileForm.processing"
          >
            <i class="fas fa-save me-2"></i>
            {{ profileForm.processing ? 'Guardando...' : 'Guardar fecha' }}
          </button>
          <template v-if="editMode">
            <button type="submit" class="btn-action btn-action--primary" :disabled="profileForm.processing">
              <i class="fas fa-save me-2"></i>
              {{ profileForm.processing ? 'Guardando...' : 'Guardar cambios' }}
            </button>
            <button type="button" class="btn-action btn-action--ghost" @click="$emit('cancel')">
              <i class="fas fa-times me-2"></i>Cancelar
            </button>
          </template>
        </div>
      </form>
    </section>

    <!-- ── Right: account info ──────────────────────────────────────────────── -->
    <aside class="info-aside">
      <!-- Account card -->
      <div class="info-card">
        <h4 class="info-card-title">
          <i class="fas fa-id-card me-2"></i>Información de Cuenta
        </h4>

        <dl class="info-list">
          <div class="info-row">
            <dt>ID de Usuario</dt>
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
            <dt>Verificación</dt>
            <dd>
              <span v-if="user.is_verified" class="status-badge status-badge--success">
                <i class="fas fa-check-circle me-1"></i>Verificado
              </span>
              <span v-else class="status-badge status-badge--warn">
                <i class="fas fa-exclamation-triangle me-1"></i>Sin verificar
              </span>
            </dd>
          </div>
          <div v-if="user.wallet" class="info-row">
            <dt>Saldo</dt>
            <dd class="fw-bold text-success">
              {{ formatCurrency(user.wallet.balance, user.wallet.currency) }}
            </dd>
          </div>
        </dl>
      </div>

      <!-- Roles card -->
      <div class="info-card mt-3">
        <h4 class="info-card-title">
          <i class="fas fa-user-tag me-2"></i>Roles
        </h4>
        <div class="roles-list">
          <div v-for="role in user.roles" :key="role.id" class="role-item">
            <span class="role-name">{{ role.name }}</span>
            <span class="role-desc">{{ role.description || 'Usuario del sistema' }}</span>
          </div>
        </div>
      </div>
    </aside>
  </div>
</template>

<script setup lang="ts">
import FormInput  from '@js/Components/Common/Form/FormInput.vue'
import FormSelect from '@js/Components/Common/Form/FormSelect.vue'
import type { ProfileUser, CountryOption } from '@js/composables/useProfile'

defineProps<{
    user: ProfileUser
    countries: CountryOption[]
    editMode: boolean
    profileForm: any
    selectedCountryName: string
    isBirthDateEditable: boolean
    formatDate: (d: string | null | undefined, withTime?: boolean) => string
    formatCurrency: (amount: number | null, currency: unknown) => string
}>()

defineEmits<{ submit: []; cancel: [] }>()
</script>

<style scoped>
/* ── Layout ── */
.personal-tab {
    display: grid;
    grid-template-columns: 1fr 280px;
    gap: 2rem;
    align-items: start;
}

/* ── Section header ── */
.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 8px;
}
.section-title { font-size: 1rem; font-weight: 700; color: #2d3a52; margin: 0; }
.section-hint  { font-size: 0.78rem; color: #8892a4; }

/* ── Fields grid ── */
.fields-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.1rem;
    margin-bottom: 1.5rem;
}

/* FormInput injects its own col class; override wrapper for grid */
.fields-grid :deep(.form-group) {
    margin: 0 !important;
    padding: 0 !important;
    width: 100% !important;
    min-width: 0;
}

.field-wrap { display: flex; flex-direction: column; gap: 4px; }

.field-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #4a5568;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 2px;
}

.field-badge {
    font-size: 0.68rem;
    padding: 2px 8px;
    border-radius: 20px;
    font-weight: 600;
}
.field-badge--warn { background: #fff3cd; color: #856404; }

.field-input {
    width: 100%;
    padding: 0.45rem 0.75rem;
    border: 1px solid #d1d9e6;
    border-radius: 8px;
    font-size: 0.875rem;
    color: #2d3a52;
    background: #fff;
    transition: border-color 0.15s, box-shadow 0.15s;
    outline: none;
}
.field-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102,126,234,0.12);
}
.field-input--disabled {
    background: #f8f9fc;
    color: #6c757d;
    cursor: default;
}

.field-error { font-size: 0.75rem; color: #dc3545; margin: 0; }
.field-hint  { font-size: 0.75rem; color: #8892a4; margin: 0; }

/* ── Form actions ── */
.form-actions { display: flex; gap: 10px; flex-wrap: wrap; }

.btn-action {
    display: inline-flex;
    align-items: center;
    padding: 8px 20px;
    border-radius: 10px;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.15s;
}
.btn-action--primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff;
    box-shadow: 0 2px 8px rgba(102,126,234,0.3);
}
.btn-action--primary:hover:not(:disabled) { filter: brightness(1.08); transform: translateY(-1px); }
.btn-action--primary:disabled { opacity: 0.65; cursor: not-allowed; }
.btn-action--ghost {
    background: transparent;
    color: #6c757d;
    border: 1px solid #d1d9e6;
}
.btn-action--ghost:hover { background: #f8f9fc; }

/* ── Aside cards ── */
.info-card {
    background: #f8f9fc;
    border-radius: 12px;
    padding: 1.1rem 1.2rem;
    border: 1px solid #eaecf0;
}
.info-card-title {
    font-size: 0.82rem;
    font-weight: 700;
    color: #4a5568;
    margin-bottom: 1rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.info-list { margin: 0; }
.info-row {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 4px 12px;
    padding: 6px 0;
    border-bottom: 1px solid #eaecf0;
    align-items: start;
}
.info-row:last-child { border-bottom: none; }
.info-row dt { font-size: 0.72rem; color: #8892a4; font-weight: 600; white-space: nowrap; }
.info-row dd { font-size: 0.82rem; color: #2d3a52; margin: 0; word-break: break-all; }

.font-mono { font-family: monospace; }
.text-xs   { font-size: 0.72rem; }

.status-badge {
    display: inline-flex;
    align-items: center;
    font-size: 0.72rem;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 20px;
}
.status-badge--success { background: #d4edda; color: #155724; }
.status-badge--warn    { background: #fff3cd; color: #856404; }

.roles-list { display: flex; flex-direction: column; gap: 6px; }
.role-item  { display: flex; flex-direction: column; gap: 1px; }
.role-name  { font-size: 0.78rem; font-weight: 700; color: #667eea; }
.role-desc  { font-size: 0.72rem; color: #8892a4; }

/* ── Responsive ── */
@media (max-width: 860px) {
    .personal-tab  { grid-template-columns: 1fr; }
}
@media (max-width: 540px) {
    .fields-grid { grid-template-columns: 1fr; }
}
</style>
