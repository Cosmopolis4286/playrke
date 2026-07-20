<template>
  <Modal
    v-model="open"
    title="Cambiar Contraseña"
    icon="fa-lock"
    size="md"
    :static="true"
    :show-confirm="false"
    @update:model-value="v => !v && $emit('close')"
  >
    <!-- Ícono de cabecera en slot implícito del título — usamos el body -->
    <form id="password-form" @submit.prevent="handleSubmit" autocomplete="off">

      <!-- ── Campo: contraseña actual ── -->
      <div class="field-group">
        <label class="field-label" for="current_password">Contraseña actual</label>
        <div class="password-input-wrap">
          <input
            id="current_password"
            :type="show.current ? 'text' : 'password'"
            v-model="form.current_password"
            class="field-input"
            :class="{ 'field-input--error': form.errors.current_password }"
            placeholder="Tu contraseña actual"
            autocomplete="current-password"
          />
          <button type="button" class="eye-btn" @click="show.current = !show.current" tabindex="-1">
            <i :class="['fas', show.current ? 'fa-eye-slash' : 'fa-eye']"></i>
          </button>
        </div>
        <p v-if="form.errors.current_password" class="field-error">{{ form.errors.current_password }}</p>
      </div>

      <!-- ── Campo: nueva contraseña + fuerza ── -->
      <div class="field-group">
        <label class="field-label" for="new_password">Nueva contraseña</label>
        <div class="password-input-wrap">
          <input
            id="new_password"
            :type="show.new ? 'text' : 'password'"
            v-model="form.new_password"
            class="field-input"
            :class="{ 'field-input--error': form.errors.new_password }"
            placeholder="Mínimo 8 caracteres"
            autocomplete="new-password"
          />
          <button type="button" class="eye-btn" @click="show.new = !show.new" tabindex="-1">
            <i :class="['fas', show.new ? 'fa-eye-slash' : 'fa-eye']"></i>
          </button>
        </div>
        <p v-if="form.errors.new_password" class="field-error">{{ form.errors.new_password }}</p>

        <!-- Barra de fuerza -->
        <div v-if="form.new_password" class="strength-wrap">
          <div class="strength-bar">
            <div
              class="strength-fill"
              :style="{ width: strength.pct + '%' }"
              :class="'strength-fill--' + strength.level"
            />
          </div>
          <span class="strength-label" :class="'strength-label--' + strength.level">
            {{ strength.text }}
          </span>
        </div>
      </div>

      <!-- ── Campo: confirmar contraseña ── -->
      <div class="field-group">
        <label class="field-label" for="confirm_password">Confirmar contraseña</label>
        <div class="password-input-wrap">
          <input
            id="confirm_password"
            :type="show.confirm ? 'text' : 'password'"
            v-model="form.new_password_confirmation"
            class="field-input"
            :class="{
              'field-input--error': form.errors.new_password_confirmation,
              'field-input--match': form.new_password && form.new_password_confirmation && form.new_password === form.new_password_confirmation,
            }"
            placeholder="Repite la nueva contraseña"
            autocomplete="new-password"
          />
          <button type="button" class="eye-btn" @click="show.confirm = !show.confirm" tabindex="-1">
            <i :class="['fas', show.confirm ? 'fa-eye-slash' : 'fa-eye']"></i>
          </button>
        </div>
        <p v-if="form.errors.new_password_confirmation" class="field-error">
          {{ form.errors.new_password_confirmation }}
        </p>
        <p
          v-else-if="form.new_password_confirmation && form.new_password !== form.new_password_confirmation"
          class="field-error"
        >
          Las contraseñas no coinciden
        </p>
      </div>

      <!-- ── Requisitos ── -->
      <div class="requirements">
        <p class="requirements-title"><i class="fas fa-shield-alt me-1"></i>Requisitos</p>
        <ul class="requirements-list">
          <li v-for="req in requirements" :key="req.label" :class="['req-item', req.met ? 'req-item--ok' : 'req-item--no']">
            <i :class="['fas req-icon', req.met ? 'fa-check-circle' : 'fa-circle']"></i>
            {{ req.label }}
          </li>
        </ul>
      </div>

    </form>

    <template #footer>
      <button type="button" class="action-btn action-btn--ghost" @click="$emit('close')">
        Cancelar
      </button>
      <button
        type="button"
        class="action-btn action-btn--primary"
        :disabled="!isValid || form.processing"
        @click="handleSubmit"
      >
        <i class="fas fa-lock me-1"></i>
        {{ form.processing ? 'Guardando...' : 'Cambiar contraseña' }}
      </button>
    </template>
  </Modal>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { showToast } from '@js/utils/toastNotify'
import Modal from '@js/Components/Common/Modal/Modal.vue'

const emit = defineEmits<{ close: []; 'password-changed': [success: boolean] }>()

const open = ref(true)

const show = ref({ current: false, new: false, confirm: false })

const form = useForm({
    current_password: '',
    new_password: '',
    new_password_confirmation: '',
})

// ── Rules ──────────────────────────────────────────────────────────────────
const rules = computed(() => ({
    length:   form.new_password.length >= 8,
    upper:    /[A-Z]/.test(form.new_password),
    lower:    /[a-z]/.test(form.new_password),
    number:   /\d/.test(form.new_password),
    special:  /[!@#$%^&*(),.?":{}|<>]/.test(form.new_password),
}))

const requirements = computed(() => [
    { label: 'Mínimo 8 caracteres',        met: rules.value.length  },
    { label: 'Una letra mayúscula',         met: rules.value.upper   },
    { label: 'Una letra minúscula',         met: rules.value.lower   },
    { label: 'Un número',                   met: rules.value.number  },
    { label: 'Un carácter especial',        met: rules.value.special },
])

const metCount = computed(() => Object.values(rules.value).filter(Boolean).length)

const strength = computed(() => {
    const n = metCount.value
    const map = [
        { pct: 0,   level: 'none',   text: '' },
        { pct: 20,  level: 'weak',   text: 'Muy débil'  },
        { pct: 40,  level: 'weak',   text: 'Débil'      },
        { pct: 60,  level: 'fair',   text: 'Regular'    },
        { pct: 80,  level: 'strong', text: 'Fuerte'     },
        { pct: 100, level: 'great',  text: 'Excelente'  },
    ]
    return map[n]
})

const isValid = computed(() =>
    metCount.value === 5 &&
    form.new_password === form.new_password_confirmation &&
    form.current_password.length > 0
)

// ── Submit ─────────────────────────────────────────────────────────────────
function handleSubmit() {
    form.patch('/profile/password', {
        onSuccess: () => {
            form.reset()
            showToast('Contraseña actualizada', 'success')
            emit('password-changed', true)
            emit('close')
        },
        onError: () => {
            form.reset('current_password')
            showToast('Error al cambiar la contraseña', 'error')
        },
    })
}
</script>

<style scoped>
/* ── Fields ── */
.field-group { margin-bottom: 1.1rem; }

.field-label {
    display: block;
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--ds-text-2);
    margin-bottom: 5px;
}

.password-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.field-input {
    width: 100%;
    padding: 0.5rem 2.5rem 0.5rem 0.85rem;
    border: 1.5px solid var(--ds-border-2);
    border-radius: 10px;
    font-size: 0.875rem;
    color: var(--ds-text);
    background: var(--ds-surface-2);
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
}
.field-input:focus {
    border-color: #667eea;
    background: var(--ds-surface);
    box-shadow: 0 0 0 3px rgba(102,126,234,0.12);
}
.field-input--error { border-color: #ef4444; }
.field-input--error:focus { box-shadow: 0 0 0 3px rgba(239,68,68,0.12); }
.field-input--match { border-color: #10b981; }
.field-input--match:focus { box-shadow: 0 0 0 3px rgba(16,185,129,0.12); }

.eye-btn {
    position: absolute;
    right: 10px;
    background: none;
    border: none;
    color: var(--ds-text-muted);
    cursor: pointer;
    padding: 0;
    font-size: 0.8rem;
    line-height: 1;
    transition: color 0.15s;
}
.eye-btn:hover { color: #667eea; }

.field-error {
    font-size: 0.72rem;
    color: #ef4444;
    margin: 4px 0 0;
}

/* ── Strength bar ── */
.strength-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 6px;
}
.strength-bar {
    flex: 1;
    height: 4px;
    background: var(--ds-border);
    border-radius: 4px;
    overflow: hidden;
}
.strength-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.3s ease, background 0.3s ease;
}
.strength-fill--weak   { background: #ef4444; }
.strength-fill--fair   { background: #f59e0b; }
.strength-fill--strong { background: #3b82f6; }
.strength-fill--great  { background: #10b981; }

.strength-label {
    font-size: 0.7rem;
    font-weight: 600;
    white-space: nowrap;
}
.strength-label--weak   { color: #ef4444; }
.strength-label--fair   { color: #f59e0b; }
.strength-label--strong { color: #3b82f6; }
.strength-label--great  { color: #10b981; }

/* ── Requirements ── */
.requirements {
    background: var(--ds-surface-2);
    border: 1px solid var(--ds-border);
    border-radius: 10px;
    padding: 0.75rem 0.9rem;
    margin-bottom: 1.25rem;
}
.requirements-title {
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--ds-text-2);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
}
.requirements-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4px 12px;
}
.req-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.75rem;
    transition: color 0.2s;
}
.req-item--ok  { color: #10b981; }
.req-item--no  { color: var(--ds-text-muted); }
.req-icon { font-size: 0.65rem; }

/* ── Actions ── */
.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding-top: 0.25rem;
}
.action-btn {
    display: inline-flex;
    align-items: center;
    padding: 9px 22px;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.15s;
    line-height: 1.4;
}
.action-btn--ghost {
    background: transparent;
    color: var(--ds-text-2);
    border: 1.5px solid var(--ds-border-2);
}
.action-btn--ghost:hover { background: var(--ds-surface-2); }
.action-btn--primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff;
    box-shadow: 0 2px 8px rgba(102,126,234,0.3);
}
.action-btn--primary:hover:not(:disabled) { filter: brightness(1.08); transform: translateY(-1px); }
.action-btn--primary:disabled { opacity: 0.55; cursor: not-allowed; transform: none; }
</style>
