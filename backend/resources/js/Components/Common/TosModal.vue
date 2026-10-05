<script setup lang="ts">
import { computed } from 'vue'
import Modal from '@js/Components/Common/Modal/Modal.vue'
import type { TosStatus } from '@js/types/tos'

const props = defineProps<{
  modelValue: boolean
  status: TosStatus | null
  accepting: boolean
  error: string | null
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', v: boolean): void
  (e: 'accept'): void
}>()

const summary = computed(() => props.status?.summary ?? 'Al operar con tu billetera aceptas nuestros términos.')
const version = computed(() => props.status?.version ?? '')
</script>

<template>
  <Modal
    :model-value="modelValue"
    title="Términos de Servicio"
    icon="fa-file-contract"
    size="md"
    :static="true"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <div class="tos-body">
      <div class="tos-icon-wrap">
        <i class="fas fa-file-contract tos-main-icon" />
      </div>

      <h3 class="tos-title">Acepta los Términos de Servicio</h3>
      <p v-if="version" class="tos-subtitle">Versión {{ version }}</p>
      <p class="tos-summary">{{ summary }}</p>

      <div class="tos-points">
        <div class="tos-point">
          <i class="fas fa-check-circle tos-point__icon" />
          <span>Competencia de habilidad — los resultados dependen de tu conocimiento</span>
        </div>
        <div class="tos-point">
          <i class="fas fa-check-circle tos-point__icon" />
          <span>Los premios se acreditan en tu billetera tras la verificación</span>
        </div>
        <div class="tos-point">
          <i class="fas fa-check-circle tos-point__icon" />
          <span>Los retiros están sujetos a límites según tu nivel de verificación</span>
        </div>
        <div class="tos-point">
          <i class="fas fa-check-circle tos-point__icon" />
          <span>Debes ser mayor de edad para participar en salas con entrada</span>
        </div>
      </div>

      <Transition name="msg-fade">
        <div v-if="error" class="tos-error">
          <i class="fas fa-exclamation-triangle" /> {{ error }}
        </div>
      </Transition>
    </div>

    <template #footer>
      <button
        type="button"
        class="tos-btn tos-btn--accept"
        :disabled="accepting"
        @click="emit('accept')"
      >
        <span v-if="accepting" class="tos-spinner" />
        <i v-else class="fas fa-check" />
        {{ accepting ? 'Procesando...' : 'Acepto los Términos' }}
      </button>
    </template>
  </Modal>
</template>

<style scoped>
.tos-body        { display: flex; flex-direction: column; align-items: center; gap: 1rem; text-align: center; padding: 0.5rem 0; }
.tos-icon-wrap   { width: 64px; height: 64px; border-radius: 20px; background: linear-gradient(135deg,#667eea22,#764ba222); display:flex;align-items:center;justify-content:center; }
.tos-main-icon   { font-size: 1.8rem; color: #667eea; }
.tos-title       { font-size: 1.1rem; font-weight: 700; color: var(--ds-text); margin: 0; }
.tos-subtitle    { font-size: 0.78rem; color: var(--ds-text-muted); margin: -0.5rem 0 0; }
.tos-summary     { font-size: 0.875rem; color: var(--ds-text-2); line-height: 1.6; max-width: 380px; margin: 0; }

.tos-points      { display: flex; flex-direction: column; gap: 0.6rem; width: 100%; text-align: left; background: var(--ds-surface-2); border-radius: 12px; padding: 1rem; }
.tos-point       { display: flex; align-items: flex-start; gap: 0.6rem; font-size: 0.82rem; color: var(--ds-text-2); line-height: 1.4; }
.tos-point__icon { color: #22c55e; font-size: 0.85rem; margin-top: 0.1rem; flex-shrink: 0; }

.tos-error       { width:100%; background:rgba(220,38,38,.14); border:1px solid rgba(220,38,38,.35); border-radius:8px; padding:0.6rem 0.8rem; font-size:0.82rem; color:#f87171; display:flex;gap:0.4rem;align-items:center; }

.tos-btn         { display:inline-flex;align-items:center;gap:0.5rem;padding:10px 28px;border-radius:10px;font-size:0.875rem;font-weight:600;cursor:pointer;border:none;transition:all 0.15s; }
.tos-btn--accept { background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;box-shadow:0 2px 8px rgba(102,126,234,0.35);width:100%; justify-content:center; }
.tos-btn--accept:hover:not(:disabled) { filter:brightness(1.08); transform:translateY(-1px); }
.tos-btn--accept:disabled { opacity:0.6; cursor:not-allowed; transform:none; }

.tos-spinner     { display:inline-block;width:14px;height:14px;border:2px solid rgba(255,255,255,0.4);border-top-color:#fff;border-radius:50%;animation:spin 0.6s linear infinite; }
@keyframes spin  { to { transform:rotate(360deg); } }

.msg-fade-enter-active, .msg-fade-leave-active { transition: opacity 0.2s; }
.msg-fade-enter-from,   .msg-fade-leave-to     { opacity: 0; }
</style>
