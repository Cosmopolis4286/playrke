<template>
  <Modal
    v-model="open"
    :title="isChange ? 'Cambiar PIN de Retiro' : 'Configurar PIN de Retiro'"
    icon="fa-shield-alt"
    size="md"
    :static="true"
    :show-confirm="false"
    @update:model-value="v => !v && $emit('close')"
  >
    <form id="withdrawal-pin-form" @submit.prevent="handleSubmit" autocomplete="off">

      <p class="pin-intro">
        Este PIN de 6 dígitos se te pedirá cada vez que solicites un retiro,
        además de tu sesión iniciada — protege tu saldo aunque alguien más
        acceda a tu cuenta.
      </p>

      <!-- ── Campo: contraseña actual (reautenticación) ── -->
      <div class="field-group">
        <label class="field-label" for="pin_current_password">Contraseña actual</label>
        <div class="password-input-wrap">
          <input
            id="pin_current_password"
            :type="show.current ? 'text' : 'password'"
            v-model="form.current_password"
            class="field-input"
            :class="{ 'field-input--error': form.errors.current_password }"
            placeholder="Confirma tu identidad"
            autocomplete="current-password"
          />
          <button type="button" class="eye-btn" @click="show.current = !show.current" tabindex="-1">
            <i :class="['fas', show.current ? 'fa-eye-slash' : 'fa-eye']"></i>
          </button>
        </div>
        <p v-if="form.errors.current_password" class="field-error">{{ form.errors.current_password }}</p>
      </div>

      <!-- ── Campo: nuevo PIN ── -->
      <div class="field-group">
        <label class="field-label" for="pin_new">{{ isChange ? 'Nuevo PIN (6 dígitos)' : 'PIN (6 dígitos)' }}</label>
        <input
          id="pin_new"
          type="text"
          inputmode="numeric"
          pattern="[0-9]*"
          maxlength="6"
          v-model="form.pin"
          class="field-input pin-input"
          :class="{ 'field-input--error': form.errors.pin }"
          placeholder="••••••"
          autocomplete="off"
          @input="form.pin = form.pin.replace(/\D/g, '').slice(0, 6)"
        />
        <p v-if="form.errors.pin" class="field-error">{{ form.errors.pin }}</p>
      </div>

      <!-- ── Campo: confirmar PIN ── -->
      <div class="field-group">
        <label class="field-label" for="pin_confirm">Confirmar PIN</label>
        <input
          id="pin_confirm"
          type="text"
          inputmode="numeric"
          pattern="[0-9]*"
          maxlength="6"
          v-model="form.pin_confirmation"
          class="field-input pin-input"
          :class="{
            'field-input--error': form.errors.pin_confirmation,
            'field-input--match': form.pin && form.pin_confirmation && form.pin === form.pin_confirmation,
          }"
          placeholder="••••••"
          autocomplete="off"
          @input="form.pin_confirmation = form.pin_confirmation.replace(/\D/g, '').slice(0, 6)"
        />
        <p v-if="form.errors.pin_confirmation" class="field-error">{{ form.errors.pin_confirmation }}</p>
        <p
          v-else-if="form.pin_confirmation && form.pin !== form.pin_confirmation"
          class="field-error"
        >
          Los PIN no coinciden
        </p>
      </div>

      <div class="pin-warning">
        <i class="fas fa-exclamation-triangle me-1"></i>
        No compartas este PIN con nadie. Soporte nunca te lo pedirá.
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
        <i class="fas fa-shield-alt me-1"></i>
        {{ form.processing ? 'Guardando...' : (isChange ? 'Cambiar PIN' : 'Configurar PIN') }}
      </button>
    </template>
  </Modal>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { showToast } from '@js/utils/toastNotify'
import Modal from '@js/Components/Common/Modal/Modal.vue'

const props = defineProps<{ isChange: boolean }>()

const emit = defineEmits<{ close: []; 'withdrawal-pin-changed': [success: boolean] }>()

const open = ref(true)

const show = ref({ current: false })

const form = useForm({
    current_password: '',
    pin: '',
    pin_confirmation: '',
})

const isValid = computed(() =>
    form.current_password.length > 0 &&
    /^\d{6}$/.test(form.pin) &&
    form.pin === form.pin_confirmation
)

function handleSubmit() {
    if (!isValid.value) return

    form.patch('/profile/withdrawal-pin', {
        onSuccess: () => {
            form.reset()
            emit('withdrawal-pin-changed', true)
            emit('close')
        },
        onError: () => {
            form.reset('current_password', 'pin', 'pin_confirmation')
            showToast('Error al actualizar el PIN de retiro', 'error')
        },
    })
}
</script>

<style scoped>
.pin-intro {
    font-size: 0.8rem;
    color: var(--ds-text-2);
    line-height: 1.5;
    margin: 0 0 1.1rem;
}

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

.pin-input {
    padding-right: 0.85rem;
    letter-spacing: 0.5em;
    font-weight: 700;
    text-align: center;
}

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

.pin-warning {
    display: flex;
    align-items: center;
    background: rgba(245, 158, 11, .12);
    border: 1px solid rgba(245, 158, 11, .35);
    color: #b45309;
    border-radius: 10px;
    padding: 0.65rem 0.85rem;
    font-size: 0.75rem;
    margin-top: 0.25rem;
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
