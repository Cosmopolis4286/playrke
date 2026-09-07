<script setup lang="ts">
import type { Wallet, UserDirectory } from '@js/types/wallet'
import { formatAmount } from '@js/composables/wallet/useWalletOperationFormatters'

defineProps<{
  activeWallet: Wallet | null
  parsedAmount: number
  directories: UserDirectory[]
  directoriesLoading: boolean
  selectedDirectory: UserDirectory | null
  notes: string
  errorMsg: string | null
  hasWithdrawalPin: boolean
  withdrawalPin: string
}>()

const emit = defineEmits<{
  (e: 'update:selectedDirectory', v: UserDirectory | null): void
  (e: 'update:notes', v: string): void
  (e: 'update:withdrawalPin', v: string): void
}>()

function onPinInput(e: Event) {
  const input = e.target as HTMLInputElement
  emit('update:withdrawalPin', input.value.replace(/\D/g, '').slice(0, 6))
}
</script>

<template>
  <!-- Resumen del monto -->
  <div class="op-summary">
    <i class="fas fa-arrow-up op-summary__icon op-summary__icon--withdraw"></i>
    <div>
      <p class="op-summary__label">Retirando</p>
      <p class="op-summary__amount">{{ activeWallet?.currency.symbol }} {{ formatAmount(parsedAmount, activeWallet?.currency.decimal_places ?? 2) }}</p>
    </div>
  </div>

  <!-- Lista de directorios -->
  <div class="op-field">
    <label class="op-field__label">¿A dónde enviamos el dinero?</label>

    <div v-if="directoriesLoading" class="op-dirs-loading">
      <span class="op-spinner op-spinner--indigo"></span>
      Cargando tus cuentas...
    </div>

    <div v-else-if="directories.length === 0" class="op-dirs-empty">
      <i class="fas fa-exclamation-circle op-dirs-empty__icon"></i>
      <div>
        <p class="op-dirs-empty__text">No tienes cuentas registradas para esta moneda.</p>
        <a href="/directory" class="op-dirs-empty__link">
          <i class="fas fa-plus-circle"></i> Ir a Directorio y agregar una
        </a>
      </div>
    </div>

    <div v-else class="op-dirs-list">
      <label
        v-for="dir in directories"
        :key="dir.id"
        class="op-dir-card"
        :class="{ 'op-dir-card--selected': selectedDirectory?.id === dir.id }"
      >
        <input
          type="radio"
          :value="dir"
          :checked="selectedDirectory?.id === dir.id"
          class="op-dir-card__radio"
          @change="emit('update:selectedDirectory', dir)"
        />
        <div class="op-dir-card__icon">
          <i :class="dir.type === 'pago_movil' ? 'fas fa-mobile-alt' : dir.type === 'pix' ? 'fas fa-barcode' : 'fab fa-bitcoin'"></i>
        </div>
        <div class="op-dir-card__info">
          <p class="op-dir-card__label">{{ dir.label }}</p>
          <p class="op-dir-card__detail">{{ dir.detail }}</p>
          <p v-if="dir.holder" class="op-dir-card__holder">{{ dir.holder }}</p>
        </div>
        <i v-if="selectedDirectory?.id === dir.id" class="fas fa-check-circle op-dir-card__check"></i>
      </label>
    </div>
  </div>

  <!-- PIN de retiro -->
  <div v-if="hasWithdrawalPin" class="op-field">
    <label class="op-field__label" for="withdraw-pin">PIN de retiro</label>
    <input
      id="withdraw-pin"
      type="text"
      inputmode="numeric"
      pattern="[0-9]*"
      maxlength="6"
      :value="withdrawalPin"
      class="op-pin-input"
      placeholder="••••••"
      autocomplete="off"
      @input="onPinInput"
    />
  </div>
  <div v-else class="op-pin-missing">
    <i class="fas fa-exclamation-triangle op-pin-missing__icon"></i>
    <div>
      <p class="op-pin-missing__text">Debes configurar un PIN de retiro antes de continuar.</p>
      <a href="/profile#security" class="op-pin-missing__link">
        <i class="fas fa-shield-alt"></i> Ir a Seguridad y configurarlo
      </a>
    </div>
  </div>

  <div class="op-field">
    <label class="op-field__label">Notas <span class="op-optional">(opcional)</span></label>
    <textarea
      :value="notes"
      class="op-field__textarea"
      rows="2"
      placeholder="Información adicional..."
      maxlength="500"
      @input="emit('update:notes', ($event.target as HTMLTextAreaElement).value)"
    ></textarea>
  </div>

  <Transition name="msg-fade">
    <div v-if="errorMsg" class="op-msg op-msg--error">
      <div class="op-msg__icon"><i class="fas fa-exclamation-triangle"></i></div>
      <div><p class="op-msg__title">Error</p><p>{{ errorMsg }}</p></div>
    </div>
  </Transition>
</template>

<style scoped>
.op-summary {
  display: flex;
  align-items: center;
  gap: 12px;
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border);
  border-radius: 12px;
  padding: 0.875rem 1rem;
  margin-bottom: 1.25rem;
}

.op-summary__icon {
  font-size: 1.25rem;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.op-summary__icon--withdraw { background: rgba(245, 158, 11, .14); color: #d97706; }

.op-summary__label  { font-size: 0.72rem; color: var(--ds-text-muted); margin: 0; }
.op-summary__amount { font-size: 1.2rem; font-weight: 800; color: var(--ds-text); margin: 0; }

/* ── Field / textarea ── */
.op-field { margin-bottom: 1.25rem; }

.op-field__label {
  display: block;
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--ds-text-2);
  margin-bottom: 8px;
}

.op-field__textarea {
  width: 100%;
  border: 2px solid var(--ds-border);
  border-radius: 12px;
  padding: 10px 14px;
  font-size: 0.875rem;
  color: var(--ds-text);
  outline: none;
  transition: border-color 0.15s;
  resize: vertical;
  box-sizing: border-box;
  font-family: inherit;
}
.op-field__textarea:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.12); }

.op-optional { color: var(--ds-text-muted); font-weight: 400; font-size: 0.75em; }

/* ── PIN de retiro ── */
.op-pin-input {
  width: 100%;
  border: 2px solid var(--ds-border);
  border-radius: 12px;
  padding: 12px 14px;
  font-size: 1.1rem;
  font-weight: 700;
  letter-spacing: 0.5em;
  text-align: center;
  color: var(--ds-text);
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
  box-sizing: border-box;
}
.op-pin-input:focus { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,0.15); }

.op-pin-missing {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  background: rgba(220, 38, 38, .12);
  border: 1px solid rgba(220, 38, 38, .35);
  border-radius: 12px;
  padding: 1rem 1.25rem;
  margin-bottom: 1.25rem;
  color: #dc2626;
  font-size: 0.82rem;
}
.op-pin-missing__icon { margin-top: 2px; flex-shrink: 0; font-size: 1rem; }
.op-pin-missing__text { margin: 0 0 6px; line-height: 1.4; }
.op-pin-missing__link {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 0.82rem;
  font-weight: 600;
  color: #dc2626;
  text-decoration: none;
  background: rgba(220, 38, 38, .14);
  padding: 4px 10px;
  border-radius: 8px;
  transition: background 0.15s;
}
.op-pin-missing__link:hover { background: rgba(220, 38, 38, .22); }

/* ── Directorios ── */
.op-dirs-loading {
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--ds-text-muted);
  font-size: 0.85rem;
  padding: 1rem 0;
}

.op-dirs-empty {
  background: rgba(245, 158, 11, .12);
  border: 1px solid rgba(245, 158, 11, .4);
  border-radius: 12px;
  padding: 1rem 1.25rem;
  color: #d97706;
  font-size: 0.82rem;
  display: flex;
  align-items: flex-start;
  gap: 10px;
}

.op-dirs-empty__icon { margin-top: 2px; flex-shrink: 0; font-size: 1rem; }

.op-dirs-empty__text { margin: 0 0 6px; line-height: 1.4; }

.op-dirs-empty__link {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 0.82rem;
  font-weight: 600;
  color: #667eea;
  text-decoration: none;
  background: rgba(102, 126, 234, .14);
  padding: 4px 10px;
  border-radius: 8px;
  transition: background 0.15s;
}
.op-dirs-empty__link:hover { background: rgba(102, 126, 234, .22); }

.op-dirs-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-height: 220px;
  overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: #cbd5e1 transparent;
}

.op-dir-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border: 2px solid var(--ds-border);
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.15s;
  background: var(--ds-surface);
}

.op-dir-card:hover            { border-color: #a5b4fc; background: var(--ds-surface-2); }
.op-dir-card--selected        { border-color: #667eea; background: rgba(102, 126, 234, .1); }

.op-dir-card__radio { display: none; }

.op-dir-card__icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: rgba(102, 126, 234, .14);
  color: #667eea;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  flex-shrink: 0;
}

.op-dir-card--selected .op-dir-card__icon { background: #667eea; color: #fff; }

.op-dir-card__info  { flex: 1; min-width: 0; }
.op-dir-card__label { font-size: 0.85rem; font-weight: 700; color: var(--ds-text-2); margin: 0; }
.op-dir-card__detail { font-size: 0.75rem; color: var(--ds-text-muted); margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.op-dir-card__holder { font-size: 0.72rem; color: var(--ds-text-muted); margin: 0; }

.op-dir-card__check { color: #667eea; font-size: 1.1rem; flex-shrink: 0; }

/* ── Spinner ── */
.op-spinner--indigo {
  border-color: rgba(102,126,234,0.25);
  border-top-color: #667eea;
}
.op-spinner {
  display: inline-block;
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255,255,255,0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Mensajes ── */
.op-msg {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px;
  border-radius: 12px;
  font-size: 0.82rem;
  margin-bottom: 0.75rem;
}
.op-msg--error { background: rgba(220, 38, 38, .14); border: 1px solid rgba(220, 38, 38, .35); color: #f87171; }
.op-msg__icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.op-msg--error .op-msg__icon { background: rgba(220, 38, 38, .2); }
.op-msg__title { font-weight: 700; margin: 0 0 2px; }

/* ── Transición mensajes ── */
.msg-fade-enter-active, .msg-fade-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.msg-fade-enter-from,   .msg-fade-leave-to     { opacity: 0; transform: translateY(-4px); }
</style>
