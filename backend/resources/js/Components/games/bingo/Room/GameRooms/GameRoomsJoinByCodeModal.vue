<script setup lang="ts">
import { ref } from 'vue'

defineProps<{
  show: boolean
  loading: boolean
  error: string | null
  redirecting: boolean
}>()

const emit = defineEmits<{ submit: [accessCode: string, password: string]; close: [] }>()

const accessCode = ref('')
const password = ref('')

function submit() {
  if (!accessCode.value || !password.value) return
  emit('submit', accessCode.value, password.value)
}

function handleClose() {
  accessCode.value = ''
  password.value = ''
  emit('close')
}
</script>

<template>
  <Teleport to="body">
    <Transition name="grjc-modal">
      <div v-if="show" class="grjc-overlay" @click.self="handleClose">
        <form class="grjc-modal" @submit.prevent="submit">
          <button type="button" class="grjc-modal__close" :disabled="loading" @click="handleClose">
            <i class="fas fa-times" />
          </button>

          <div class="grjc-modal__header">
            <i class="fas fa-key grjc-modal__icon" />
            <div>
              <h3 class="grjc-modal__title">Unirse con código</h3>
              <p class="grjc-modal__sub">Ingresa el código y la contraseña de la sala privada a la que fuiste invitado</p>
            </div>
          </div>

          <div class="grjc-modal__body">
            <label class="grjc-modal__label" for="grjc-access-code">Código de acceso</label>
            <input
              id="grjc-access-code"
              v-model="accessCode"
              type="text"
              class="grjc-modal__input"
              placeholder="Código de acceso"
              autocomplete="off"
              :disabled="loading || redirecting"
            />

            <label class="grjc-modal__label" for="grjc-access-password">Contraseña</label>
            <input
              id="grjc-access-password"
              v-model="password"
              type="password"
              class="grjc-modal__input"
              placeholder="Contraseña"
              autocomplete="off"
              :disabled="loading || redirecting"
            />

            <p v-if="error" class="grjc-modal__error">
              <i class="fas fa-exclamation-circle" /> {{ error }}
            </p>
          </div>

          <div class="grjc-modal__actions">
            <button type="button" class="grjc-modal__cancel" :disabled="loading || redirecting" @click="handleClose">
              Cancelar
            </button>
            <button type="submit" class="grjc-modal__submit" :disabled="loading || redirecting || !accessCode || !password">
              <i v-if="loading || redirecting" class="fas fa-spinner fa-spin" />
              <span>{{ redirecting ? 'Ingresando...' : loading ? 'Verificando...' : 'Entrar' }}</span>
            </button>
          </div>
        </form>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.grjc-overlay {
  position: fixed; inset: 0; z-index: 9500;
  background: rgba(2,5,16,.86); backdrop-filter: blur(6px);
  display: flex; align-items: center; justify-content: center; padding: 20px;
}
.grjc-modal {
  position: relative; width: 100%; max-width: 380px;
  background: linear-gradient(160deg, #0d1530 0%, #0a1020 100%);
  border: 1px solid rgba(255,255,255,.1); border-radius: 18px;
  box-shadow: 0 24px 60px rgba(0,0,0,.7), 0 0 0 1px rgba(255,255,255,.04) inset;
  display: flex; flex-direction: column; overflow: hidden;
}
.grjc-modal__close {
  position: absolute; top: 12px; right: 12px;
  width: 28px; height: 28px; border-radius: 50%;
  border: 1px solid rgba(255,255,255,.1);
  background: rgba(255,255,255,.05); color: rgba(255,255,255,.5);
  font-size: 11px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: background .2s, color .2s;
}
.grjc-modal__close:hover:not(:disabled) { background: rgba(255,77,109,.15); color: #ff6e8a; border-color: rgba(255,77,109,.25); }
.grjc-modal__close:disabled { opacity: .4; cursor: not-allowed; }

.grjc-modal__header {
  display: flex; align-items: center; gap: 12px; padding: 18px 34px 14px 18px;
  border-bottom: 1px solid rgba(255,255,255,.06);
}
.grjc-modal__icon {
  font-size: 18px; color: #ffc200; flex-shrink: 0;
  width: 40px; height: 40px; border-radius: 10px;
  background: rgba(255,194,0,.08); border: 1px solid rgba(255,194,0,.18);
  display: flex; align-items: center; justify-content: center;
  filter: drop-shadow(0 0 6px rgba(255,194,0,.2));
}
.grjc-modal__title { font-size: 15px; font-weight: 700; color: #e0eaff; margin: 0 0 2px; }
.grjc-modal__sub   { font-size: 10px; color: #7a96c8; margin: 0; line-height: 1.4; }

.grjc-modal__body { padding: 16px 18px 4px; display: flex; flex-direction: column; gap: 4px; }
.grjc-modal__label {
  font-size: 10px; font-weight: 700; letter-spacing: .4px; color: #7a96c8;
  margin: 8px 0 4px;
}
.grjc-modal__input {
  width: 100%; padding: 9px 12px; border-radius: 8px;
  background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.12);
  color: #e0eaff; font-size: 13px;
  transition: border-color .15s, background .15s;
}
.grjc-modal__input:focus {
  outline: none; border-color: rgba(42,245,152,.5); background: rgba(255,255,255,.06);
}
.grjc-modal__input:disabled { opacity: .6; }

.grjc-modal__error {
  margin: 10px 0 0; font-size: 11px; color: #ff6e8a;
  display: flex; align-items: center; gap: 6px;
}

.grjc-modal__actions {
  display: flex; gap: 10px; padding: 16px 18px 18px;
}
.grjc-modal__cancel {
  flex: 1; padding: 10px; border-radius: 10px; font-size: 12px; font-weight: 700;
  background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1); color: #7a96c8;
  cursor: pointer; transition: background .15s;
}
.grjc-modal__cancel:hover:not(:disabled) { background: rgba(255,255,255,.09); }
.grjc-modal__cancel:disabled { opacity: .5; cursor: not-allowed; }
.grjc-modal__submit {
  flex: 2; padding: 10px; border-radius: 10px; font-size: 12px; font-weight: 700;
  letter-spacing: .3px; cursor: pointer;
  background: linear-gradient(135deg, #2af598 0%, #009efd 100%); border: none; color: #06111f;
  display: flex; align-items: center; justify-content: center; gap: 8px;
  transition: filter .15s, opacity .15s;
}
.grjc-modal__submit:hover:not(:disabled) { filter: brightness(1.08); }
.grjc-modal__submit:disabled { opacity: .55; cursor: not-allowed; }

.grjc-modal-enter-active, .grjc-modal-leave-active { transition: opacity .2s, transform .25s cubic-bezier(.22,1,.36,1); }
.grjc-modal-enter-from, .grjc-modal-leave-to { opacity: 0; }
.grjc-modal-enter-from .grjc-modal, .grjc-modal-leave-to .grjc-modal { transform: scale(.95) translateY(8px); }
</style>
