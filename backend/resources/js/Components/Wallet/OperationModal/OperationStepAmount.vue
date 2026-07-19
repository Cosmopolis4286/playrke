<script setup lang="ts">
import type { Wallet, WalletOperation, PendingOperation } from '@js/types/wallet'
import { formatAmount, formatOperationDate } from '@js/composables/wallet/useWalletOperationFormatters'

const props = defineProps<{
  operation: WalletOperation
  activeWallet: Wallet | null
  formattedBalance: string
  amount: string
  selectedQuickAmount: number | null
  pendingOperation: PendingOperation | null
  pendingLoading: boolean
  errorMsg: string | null
}>()

const emit = defineEmits<{
  (e: 'update:amount', v: string): void
  (e: 'select-quick-amount', v: number): void
  (e: 'clear-quick-amount'): void
}>()

const quickAmounts = [100, 250, 500, 1000, 2500, 5000]

function onAmountInput(e: Event) {
  const input = e.target as HTMLInputElement
  const value = input.value

  const match = value.match(/^-?\d*(\.\d{0,2})?/)
  const truncated = match ? match[0] : value
  if (truncated !== value) {
    input.value = truncated
  }

  emit('update:amount', truncated)
  emit('clear-quick-amount')
}

function amountFmt(value: number): string {
  return formatAmount(value, props.activeWallet?.currency.decimal_places ?? 2)
}
</script>

<template>
  <!-- Verificando pendiente -->
  <div v-if="pendingLoading" class="op-pending-checking">
    <span class="op-spinner op-spinner--indigo"></span>
    Verificando solicitudes activas...
  </div>

  <!-- BLOQUEADO: ya tiene pendiente -->
  <template v-else-if="pendingOperation">
    <div class="op-pending-block">
      <div class="op-pending-block__icon">
        <i class="fas fa-clock"></i>
      </div>
      <div class="op-pending-block__body">
        <p class="op-pending-block__title">Solicitud en cola</p>
        <p class="op-pending-block__text">
          Tienes {{ pendingOperation.type === 'DEPOSIT' ? 'un depósito' : 'un retiro' }} de
          <strong>{{ activeWallet?.currency.symbol }} {{ amountFmt(pendingOperation.amount) }}</strong>
          pendiente de confirmación.
        </p>
        <p class="op-pending-block__hint">
          No puedes hacer otra solicitud hasta que un administrador la procese.
        </p>
      </div>
    </div>

    <!-- Detalle del movimiento pendiente -->
    <div class="op-pending-detail">
      <div class="op-pending-detail__row">
        <span>Tipo</span>
        <span :class="pendingOperation.type === 'DEPOSIT' ? 'text--deposit' : 'text--withdraw'">
          <i :class="pendingOperation.type === 'DEPOSIT' ? 'fas fa-arrow-down' : 'fas fa-arrow-up'"></i>
          {{ pendingOperation.type === 'DEPOSIT' ? 'Depósito' : 'Retiro' }}
        </span>
      </div>
      <div class="op-pending-detail__row">
        <span>Monto</span>
        <strong>{{ activeWallet?.currency.symbol }} {{ amountFmt(pendingOperation.amount) }}</strong>
      </div>
      <div class="op-pending-detail__row">
        <span>Estado</span>
        <span class="op-pending-detail__badge">
          <span class="op-pending-detail__dot"></span> En revisión
        </span>
      </div>
      <div class="op-pending-detail__row">
        <span>Solicitado</span>
        <span>{{ formatOperationDate(pendingOperation.created_at) }}</span>
      </div>
    </div>
  </template>

  <!-- LIBRE: puede solicitar -->
  <template v-else>
    <!-- Saldo actual -->
    <div class="op-balance">
      <div>
        <p class="op-balance__label">Saldo disponible</p>
        <p class="op-balance__amount">{{ activeWallet?.currency.symbol }} {{ formattedBalance }}</p>
      </div>
      <div class="op-balance__icon"><i class="fas fa-wallet"></i></div>
    </div>

    <!-- Input de monto -->
    <div class="op-field">
      <label class="op-field__label">
        Monto a {{ operation === 'deposit' ? 'depositar' : 'retirar' }}
      </label>
      <div class="op-field__input-wrap">
        <span class="op-field__symbol">{{ activeWallet?.currency.symbol }}</span>
        <input
          :value="amount"
          type="number"
          min="0.01"
          step="0.01"
          placeholder="0.00"
          class="op-field__input"
          :class="operation === 'deposit' ? 'op-input--deposit' : 'op-input--withdraw'"
          @input="onAmountInput"
        />
      </div>

      <!-- Montos rápidos -->
      <div class="op-quick">
        <p class="op-quick__label">Montos rápidos:</p>
        <div class="op-quick__grid">
          <button
            v-for="qa in quickAmounts"
            :key="qa"
            type="button"
            class="op-quick__btn"
            :class="{ 'op-quick__btn--active': selectedQuickAmount === qa }"
            @click="emit('select-quick-amount', qa)"
          >
            {{ activeWallet?.currency.symbol }} {{ qa }}
          </button>
        </div>
      </div>
    </div>

    <!-- Error paso 1 -->
    <Transition name="msg-fade">
      <div v-if="errorMsg" class="op-msg op-msg--error">
        <div class="op-msg__icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div><p class="op-msg__title">Atención</p><p>{{ errorMsg }}</p></div>
      </div>
    </Transition>
  </template>
</template>

<style scoped>
/* ── Verificando pendiente ── */
.op-pending-checking {
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--ds-text-muted);
  font-size: 0.85rem;
  padding: 1.5rem 0;
  justify-content: center;
}

/* ── Bloqueo por pendiente ── */
.op-pending-block {
  display: flex;
  gap: 14px;
  background: rgba(245, 158, 11, .12);
  border: 1.5px solid rgba(245, 158, 11, .4);
  border-radius: 14px;
  padding: 1.1rem 1.25rem;
  margin-bottom: 1rem;
}

.op-pending-block__icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: rgba(245, 158, 11, .2);
  color: #d97706;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}

.op-pending-block__title {
  font-size: 0.9rem;
  font-weight: 700;
  color: #d97706;
  margin: 0 0 4px;
}

.op-pending-block__text {
  font-size: 0.82rem;
  color: var(--ds-text-2);
  margin: 0 0 4px;
  line-height: 1.5;
}

.op-pending-block__hint {
  font-size: 0.75rem;
  color: #d97706;
  margin: 0;
}

/* ── Detalle del movimiento pendiente ── */
.op-pending-detail {
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border);
  border-radius: 12px;
  overflow: hidden;
  margin-bottom: 0.25rem;
}

.op-pending-detail__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 9px 14px;
  font-size: 0.82rem;
  color: var(--ds-text-2);
  border-bottom: 1px solid var(--ds-border);
}
.op-pending-detail__row:last-child { border-bottom: none; }
.op-pending-detail__row span:first-child { color: var(--ds-text-muted); }
.op-pending-detail__row strong { color: var(--ds-text); }

.op-pending-detail__badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: rgba(245, 158, 11, .2);
  color: #d97706;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 2px 9px;
  border-radius: 20px;
}

.op-pending-detail__dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #f59e0b;
  animation: pulse-dot 1.5s ease-in-out infinite;
}

@keyframes pulse-dot {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.3; }
}

.text--deposit  { color: #16a34a; font-weight: 600; }
.text--withdraw { color: #d97706; font-weight: 600; }

/* ── Balance ── */
.op-balance {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: var(--ds-surface-2);
  border: 1px solid var(--ds-border);
  border-radius: 12px;
  padding: 1rem 1.25rem;
  margin-bottom: 1.25rem;
}

.op-balance__label  { font-size: 0.78rem; color: var(--ds-text-muted); margin: 0 0 2px; }
.op-balance__amount { font-size: 1.35rem; font-weight: 800; color: var(--ds-text); margin: 0; }

.op-balance__icon {
  width: 36px;
  height: 36px;
  background: rgba(102, 126, 234, .14);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #667eea;
  font-size: 0.9rem;
}

.op-field { margin-bottom: 1.25rem; }

.op-field__label {
  display: block;
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--ds-text-2);
  margin-bottom: 8px;
}

.op-field__input-wrap { position: relative; }

.op-field__symbol {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  font-weight: 700;
  font-size: 1rem;
  color: var(--ds-text-muted);
  pointer-events: none;
}

.op-field__input {
  width: 100%;
  border: 2px solid var(--ds-border);
  border-radius: 12px;
  padding: 12px 14px 12px 44px;
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--ds-text);
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
  box-sizing: border-box;
}

.op-input--deposit:focus  { border-color: #22c55e; box-shadow: 0 0 0 3px rgba(34,197,94,0.15); }
.op-input--withdraw:focus { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,0.15); }

/* ── Quick amounts ── */
.op-quick { margin-top: 10px; }

.op-quick__label {
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--ds-text-muted);
  margin: 0 0 8px;
}

.op-quick__grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
}

.op-quick__btn {
  padding: 8px;
  border: 1px solid var(--ds-border);
  border-radius: 10px;
  background: var(--ds-surface);
  color: var(--ds-text-2);
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
}
.op-quick__btn:hover          { border-color: #667eea; color: #667eea; background: rgba(102, 126, 234, .1); }
.op-quick__btn--active        { background: #667eea; color: #fff; border-color: #667eea; }

/* ── Spinner variants ── */
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

.op-msg--error   { background: rgba(220, 38, 38, .14); border: 1px solid rgba(220, 38, 38, .35); color: #f87171; }

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
