<script setup lang="ts">
import type { Wallet } from '@js/types/wallet'

defineProps<{
  wallets: Wallet[]
  activeWallet: Wallet | null
  formattedBalance: string
  canWithdraw: boolean
}>()

const emit = defineEmits<{
  (e: 'select-wallet', wallet: Wallet): void
  (e: 'deposit'): void
  (e: 'withdraw'): void
}>()
</script>

<template>
  <header class="wallet-header">
    <div class="wallet-header__inner">
      <!-- Icono + título -->
      <div class="wallet-header__identity">
        <div class="wallet-header__icon">
          <i class="fas fa-wallet"></i>
        </div>
        <div>
          <h1 class="wallet-header__title">Mi Billetera</h1>
          <p class="wallet-header__subtitle">Gestiona tus fondos y revisa tu actividad</p>
        </div>
      </div>

      <!-- Saldo destacado -->
      <div v-if="activeWallet" class="wallet-header__balance">
        <span class="wallet-header__balance-label">Saldo disponible</span>
        <span class="wallet-header__balance-amount">
          {{ activeWallet.currency.symbol }}&nbsp;{{ formattedBalance }}
        </span>
        <span
          class="wallet-header__status-badge"
          :class="{
            'badge--active':   activeWallet.status === 'ACTIVE',
            'badge--inactive': activeWallet.status === 'INACTIVE',
            'badge--blocked':  activeWallet.status === 'BLOCKED',
          }"
        >
          <span class="badge-dot"></span>
          {{ activeWallet.status === 'ACTIVE' ? 'Activa' :
             activeWallet.status === 'INACTIVE' ? 'Inactiva' : 'Bloqueada' }}
        </span>
      </div>

      <!-- Acciones -->
      <div v-if="activeWallet" class="wallet-header__actions">
        <button class="action-btn action-btn--deposit" @click="emit('deposit')">
          <i class="fas fa-arrow-down"></i> Depositar
        </button>
        <button
          class="action-btn action-btn--withdraw"
          :class="{ 'action-btn--disabled': !canWithdraw }"
          :disabled="!canWithdraw"
          @click="emit('withdraw')"
        >
          <i class="fas fa-arrow-up"></i>
          {{ canWithdraw ? 'Retirar' : 'Sin saldo' }}
        </button>
      </div>
    </div>

    <!-- Selector de billetera (si hay varias) -->
    <div v-if="wallets.length > 1" class="wallet-selector">
      <button
        v-for="wallet in wallets"
        :key="wallet.id"
        type="button"
        class="wallet-selector__btn"
        :class="{ 'wallet-selector__btn--active': activeWallet?.id === wallet.id }"
        @click="emit('select-wallet', wallet)"
      >
        <i class="fas fa-coins"></i>
        {{ wallet.currency.symbol }} {{ wallet.currency.iso_code }}
      </button>
    </div>
  </header>
</template>

<style scoped>
/* ── Header ── */
.wallet-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 16px 16px 0 0;
  padding: 2rem 1.5rem 1.25rem;
  margin-bottom: 0;
}

.wallet-header__inner {
  display: flex;
  align-items: center;
  gap: 2rem;
  flex-wrap: wrap;
}

.wallet-header__identity {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex: 0 0 auto;
}

.wallet-header__icon {
  width: 56px;
  height: 56px;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: #fff;
  flex-shrink: 0;
}

.wallet-header__title {
  color: #fff;
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 2px;
  letter-spacing: -0.02em;
}

.wallet-header__subtitle {
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.875rem;
  margin: 0;
}

.wallet-header__balance {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.wallet-header__balance-label {
  color: rgba(255, 255, 255, 0.65);
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.wallet-header__balance-amount {
  color: #fff;
  font-size: 2rem;
  font-weight: 800;
  line-height: 1;
  text-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}

.wallet-header__status-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 0.72rem;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 20px;
}

.badge--active   { background: rgba(40, 167, 69, 0.85); color: #fff; }
.badge--inactive { background: rgba(255, 193, 7, 0.85); color: #fff; }
.badge--blocked  { background: rgba(220, 53, 69, 0.85); color: #fff; }

.badge-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
  opacity: 0.9;
}

.wallet-header__actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
  flex: 0 0 auto;
}

/* ── Selector de billetera ── */
.wallet-selector {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: 1.25rem;
  padding-top: 1.25rem;
  border-top: 1px solid rgba(255, 255, 255, 0.15);
}

.wallet-selector__btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 16px;
  border-radius: 20px;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  border: 1px solid rgba(255, 255, 255, 0.35);
  background: rgba(255, 255, 255, 0.15);
  color: rgba(255, 255, 255, 0.85);
  transition: all 0.15s ease;
}

.wallet-selector__btn:hover {
  background: rgba(255, 255, 255, 0.25);
}

.wallet-selector__btn--active {
  background: rgba(255, 255, 255, 0.95);
  color: #667eea;
  border-color: transparent;
}

/* ── Botones de acción ── */
.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 18px;
  border-radius: 10px;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: all 0.15s ease;
  white-space: nowrap;
}

.action-btn--deposit {
  background: rgba(255, 255, 255, 0.95);
  color: #28a745;
}
.action-btn--deposit:hover { background: #fff; transform: translateY(-1px); }

.action-btn--withdraw {
  background: rgba(255, 255, 255, 0.15);
  color: #fff;
  border: 1px solid rgba(255, 255, 255, 0.3);
}
.action-btn--withdraw:hover { background: rgba(255, 255, 255, 0.25); }

.action-btn--disabled {
  opacity: 0.45;
  cursor: not-allowed;
}
.action-btn--disabled:hover { transform: none; }

/* ── Responsive ── */
@media (max-width: 768px) {
  .wallet-header__inner { flex-direction: column; align-items: flex-start; gap: 1.25rem; }
  .wallet-header__balance { align-items: flex-start; }
  .wallet-header__actions { flex-direction: row; flex-wrap: wrap; }
}
</style>
