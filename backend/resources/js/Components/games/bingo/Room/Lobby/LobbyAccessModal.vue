<script setup lang="ts">
import { ref } from 'vue'

defineProps<{
  show: boolean
  loading: boolean
  error: string | null
}>()

const emit = defineEmits<{ verify: [accessCode: string, password: string]; leave: [] }>()

const accessCode = ref('')
const password = ref('')

function submit() {
  if (!accessCode.value || !password.value) return
  emit('verify', accessCode.value, password.value)
}
</script>

<template>
  <Teleport to="body">
    <Transition name="lby-modal">
      <!-- Sin @click.self de cierre ni botón de cerrar: sala privada, no se
           puede ver el lobby sin verificar código/contraseña. -->
      <div v-if="show" class="lby-overlay">
        <form class="lby-modal lby-access-modal" @submit.prevent="submit">
          <div class="lby-modal__header">
            <i class="fas fa-lock lby-modal__icon" />
            <div>
              <h3 class="lby-modal__title">Sala Privada</h3>
              <p class="lby-modal__sub">Ingresa el código y la contraseña para continuar</p>
            </div>
          </div>

          <div class="lby-access-modal__body">
            <label class="lby-access-modal__label" for="lby-access-code">Código de acceso</label>
            <input
              id="lby-access-code"
              v-model="accessCode"
              type="text"
              class="lby-access-modal__input"
              placeholder="Código de acceso"
              autocomplete="off"
              :disabled="loading"
            />

            <label class="lby-access-modal__label" for="lby-access-password">Contraseña</label>
            <input
              id="lby-access-password"
              v-model="password"
              type="password"
              class="lby-access-modal__input"
              placeholder="Contraseña"
              autocomplete="off"
              :disabled="loading"
            />

            <p v-if="error" class="lby-access-modal__error">
              <i class="fas fa-exclamation-circle" /> {{ error }}
            </p>
          </div>

          <div class="lby-access-modal__actions">
            <button type="button" class="lby-access-modal__leave" :disabled="loading" @click="emit('leave')">
              Salir
            </button>
            <button type="submit" class="lby-access-modal__submit" :disabled="loading || !accessCode || !password">
              <i v-if="loading" class="fas fa-spinner fa-spin" />
              <span>{{ loading ? 'Verificando...' : 'Entrar' }}</span>
            </button>
          </div>
        </form>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.lby-overlay {
  position: fixed; inset: 0; z-index: 9500;
  background: rgba(2,5,16,.86); backdrop-filter: blur(6px);
  display: flex; align-items: center; justify-content: center; padding: 20px;
}
.lby-modal {
  position: relative; width: 100%; max-width: 380px;
  background: linear-gradient(160deg, #0d1530 0%, #0a1020 100%);
  border: 1px solid rgba(255,255,255,.1); border-radius: 18px;
  box-shadow: 0 24px 60px rgba(0,0,0,.7), 0 0 0 1px rgba(255,255,255,.04) inset;
  display: flex; flex-direction: column; overflow: hidden;
}
.lby-modal__header {
  display: flex; align-items: center; gap: 12px; padding: 18px 18px 14px;
  border-bottom: 1px solid rgba(255,255,255,.06);
}
.lby-modal__icon {
  font-size: 18px; color: #ffc200; flex-shrink: 0;
  width: 40px; height: 40px; border-radius: 10px;
  background: rgba(255,194,0,.08); border: 1px solid rgba(255,194,0,.18);
  display: flex; align-items: center; justify-content: center;
  filter: drop-shadow(0 0 6px rgba(255,194,0,.2));
}
.lby-modal__title { font-size: 15px; font-weight: 700; color: #e0eaff; margin: 0 0 2px; }
.lby-modal__sub   { font-size: 10px; color: #7a96c8; margin: 0; }

.lby-access-modal__body { padding: 16px 18px 4px; display: flex; flex-direction: column; gap: 4px; }
.lby-access-modal__label {
  font-size: 10px; font-weight: 700; letter-spacing: .4px; color: #7a96c8;
  margin: 8px 0 4px;
}
.lby-access-modal__input {
  width: 100%; padding: 9px 12px; border-radius: 8px;
  background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.12);
  color: #e0eaff; font-size: 13px;
  transition: border-color .15s, background .15s;
}
.lby-access-modal__input:focus {
  outline: none; border-color: rgba(42,245,152,.5); background: rgba(255,255,255,.06);
}
.lby-access-modal__input:disabled { opacity: .6; }

.lby-access-modal__error {
  margin: 10px 0 0; font-size: 11px; color: #ff6e8a;
  display: flex; align-items: center; gap: 6px;
}

.lby-access-modal__actions {
  display: flex; gap: 10px; padding: 16px 18px 18px;
}
.lby-access-modal__leave {
  flex: 1; padding: 10px; border-radius: 10px; font-size: 12px; font-weight: 700;
  background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1); color: #7a96c8;
  cursor: pointer; transition: background .15s;
}
.lby-access-modal__leave:hover:not(:disabled) { background: rgba(255,255,255,.09); }
.lby-access-modal__submit {
  flex: 2; padding: 10px; border-radius: 10px; font-size: 12px; font-weight: 700;
  letter-spacing: .3px; cursor: pointer;
  background: linear-gradient(135deg, #2af598 0%, #009efd 100%); border: none; color: #06111f;
  display: flex; align-items: center; justify-content: center; gap: 8px;
  transition: filter .15s, opacity .15s;
}
.lby-access-modal__submit:hover:not(:disabled) { filter: brightness(1.08); }
.lby-access-modal__submit:disabled { opacity: .55; cursor: not-allowed; }

.lby-modal-enter-active, .lby-modal-leave-active { transition: opacity .2s, transform .25s cubic-bezier(.22,1,.36,1); }
.lby-modal-enter-from, .lby-modal-leave-to { opacity: 0; }
.lby-modal-enter-from .lby-modal, .lby-modal-leave-to .lby-modal { transform: scale(.95) translateY(8px); }
</style>
