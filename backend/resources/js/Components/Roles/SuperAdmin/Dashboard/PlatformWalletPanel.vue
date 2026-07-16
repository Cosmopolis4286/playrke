<script setup lang="ts">
import type { PlatformWallet, PlatformMovement, PlatformMovementsMeta } from '@js/composables/roles/superadmin/dashboard/usePlatformWallet'
import type { MovementTypeMeta } from '@js/composables/wallet/useMovementTypeCatalog'

defineProps<{
  wallets: PlatformWallet[]
  activeWallet: PlatformWallet | null
  movements: PlatformMovement[]
  movementsMeta: PlatformMovementsMeta | null
  loading: boolean
  movLoading: boolean
  error: string | null
  rkeTotal: number | null
  movMeta: (type: string) => MovementTypeMeta
  formatUsd: (v: number, decimals?: number) => string
  formatDateTime: (iso: string) => string
}>()

const emit = defineEmits<{
  refresh: []
  'refresh-movements': [page: number]
  'select-wallet': [wallet: PlatformWallet]
  'open-cash-modal': [mode: 'deposit' | 'withdraw']
}>()
</script>

<template>
  <div class="pw-section">

    <!-- Header -->
    <div class="pw-header">
      <div class="pw-header__title">
        <div class="pw-header__icon"><i class="fas fa-building-columns"></i></div>
        <div>
          <h2 class="pw-header__h2">Billetera de Plataforma</h2>
          <p class="pw-header__sub">Caja de Playrke — ingresos RKE, depósitos y retiros del sistema</p>
        </div>
      </div>
      <button class="pw-refresh" :class="{ 'pw-refresh--spin': loading }" :disabled="loading" @click="emit('refresh')">
        <i class="fas fa-sync-alt"></i>
      </button>
    </div>

    <!-- Error -->
    <div v-if="error" class="rke-state rke-state--error">
      <i class="fas fa-exclamation-triangle"></i> {{ error }}
    </div>

    <!-- Loading inicial -->
    <div v-else-if="loading && !wallets.length" class="rke-state rke-state--loading">
      <span class="rke-spinner"></span> Cargando billetera...
    </div>

    <template v-else-if="wallets.length">

      <!-- Selector de moneda -->
      <div class="pw-currency-tabs">
        <button
          v-for="w in wallets"
          :key="w.id"
          class="pw-currency-tab"
          :class="{ 'pw-currency-tab--active': activeWallet?.id === w.id }"
          @click="emit('select-wallet', w)"
        >
          {{ w.currency_code }}
        </button>
      </div>

      <!-- Saldo destacado -->
      <div v-if="activeWallet" class="pw-balance-card">
        <div class="pw-balance-card__left">
          <p class="pw-balance-card__label">Saldo disponible</p>
          <p class="pw-balance-card__amount">
            {{ activeWallet.currency_symbol }}&nbsp;{{ formatUsd(activeWallet.balance) }}
          </p>
          <span class="pw-balance-card__currency">{{ activeWallet.currency_code }}</span>
        </div>
        <div class="pw-balance-card__right">
          <div class="pw-balance-card__stat">
            <i class="fas fa-arrow-trend-up"></i>
            <span>RKE acumulado</span>
          </div>
          <p class="pw-balance-card__stat-val" v-if="rkeTotal !== null">
            $ {{ formatUsd(rkeTotal) }}
          </p>
          <div class="pw-cash-actions">
            <button class="pw-cash-btn pw-cash-btn--deposit" @click="emit('open-cash-modal', 'deposit')">
              <i class="fas fa-arrow-down"></i> Depositar
            </button>
            <button class="pw-cash-btn pw-cash-btn--withdraw" @click="emit('open-cash-modal', 'withdraw')">
              <i class="fas fa-arrow-up"></i> Retirar
            </button>
          </div>
        </div>
      </div>

      <!-- Movimientos -->
      <div class="pw-movements">
        <div class="pw-movements__header">
          <span class="pw-movements__title"><i class="fas fa-history"></i> Movimientos recientes</span>
          <button class="pw-refresh pw-refresh--sm" :class="{ 'pw-refresh--spin': movLoading }" :disabled="movLoading" @click="emit('refresh-movements', 1)">
            <i class="fas fa-sync-alt"></i>
          </button>
        </div>

        <div v-if="movLoading && !movements.length" class="rke-state rke-state--loading">
          <span class="rke-spinner"></span> Cargando movimientos...
        </div>

        <div v-else-if="!movements.length" class="rke-state rke-state--empty">
          <i class="fas fa-inbox"></i> Sin movimientos todavía.
        </div>

        <ul v-else class="pw-mov-list">
          <li v-for="mov in movements" :key="mov.id" class="pw-mov-item">
            <div class="pw-mov-item__icon" :style="{ background: movMeta(mov.type).color + '18', color: movMeta(mov.type).color }">
              <i :class="mov.type === 'RKE_COMMISSION' ? 'fas fa-percentage' : mov.type === 'DEPOSIT' ? 'fas fa-arrow-down' : 'fas fa-arrow-up'"></i>
            </div>
            <div class="pw-mov-item__info">
              <p class="pw-mov-item__label">{{ movMeta(mov.type).label }}</p>
              <p class="pw-mov-item__desc">{{ mov.description ?? '—' }}</p>
              <p class="pw-mov-item__date">{{ formatDateTime(mov.created_at) }}</p>
            </div>
            <div class="pw-mov-item__amount" :style="{ color: movMeta(mov.type).color }">
              {{ movMeta(mov.type).sign }}{{ activeWallet?.currency_symbol }}&nbsp;{{ formatUsd(mov.amount) }}
              <p class="pw-mov-item__balance">Saldo: {{ formatUsd(mov.balance_after) }}</p>
            </div>
          </li>
        </ul>

        <!-- Paginación -->
        <div v-if="movementsMeta && movementsMeta.last_page > 1" class="pw-pagination">
          <button
            v-for="p in movementsMeta.last_page"
            :key="p"
            class="pw-page-btn"
            :class="{ 'pw-page-btn--active': p === movementsMeta.current_page }"
            @click="emit('refresh-movements', p)"
          >{{ p }}</button>
        </div>
      </div>
    </template>

    <!-- Sin monedas activas -->
    <div v-else class="rke-state rke-state--empty">
      <i class="fas fa-exclamation-circle"></i>
      No hay monedas activas configuradas para la billetera de plataforma.
    </div>

  </div>
</template>

<style scoped>
.pw-section {
  background: var(--ds-surface);
  border: 1px solid var(--ds-border);
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(102, 126, 234, 0.08);
  padding: 1.5rem;
  margin-top: 0.25rem;
}

.pw-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.pw-header__title {
  display: flex;
  align-items: center;
  gap: 0.875rem;
}

.pw-header__icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, #0ea5e9, #0284c7);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}

.pw-header__h2 {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--ds-text);
  margin: 0 0 2px;
}

.pw-header__sub {
  font-size: 0.78rem;
  color: var(--ds-text-2);
  margin: 0;
}

.pw-refresh {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 8px;
  border: 1px solid var(--ds-border-2);
  background: var(--ds-surface-2);
  color: #0ea5e9;
  cursor: pointer;
  transition: all 0.15s;
  flex-shrink: 0;
}
.pw-refresh:hover:not(:disabled) { background: rgba(14,165,233,.15); border-color: #0ea5e9; }
.pw-refresh:disabled { opacity: 0.5; cursor: not-allowed; }
.pw-refresh--spin i { animation: spin 0.7s linear infinite; }
.pw-refresh--sm { width: 28px; height: 28px; font-size: 0.75rem; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Selector de moneda */
.pw-currency-tabs {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  margin-bottom: 1rem;
}

.pw-currency-tab {
  padding: 5px 16px;
  border-radius: 20px;
  border: 1px solid var(--ds-border-2);
  background: var(--ds-surface-2);
  color: var(--ds-text-2);
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
}
.pw-currency-tab:hover { background: rgba(14,165,233,.15); border-color: #0ea5e9; color: #0ea5e9; }
.pw-currency-tab--active { background: #0ea5e9; border-color: #0ea5e9; color: #fff; }

/* Saldo */
.pw-balance-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
  border-radius: 14px;
  padding: 1.25rem 1.5rem;
  margin-bottom: 1.25rem;
  flex-wrap: wrap;
}

.pw-balance-card__label {
  font-size: 0.73rem;
  color: rgba(255,255,255,0.75);
  margin: 0 0 4px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.pw-balance-card__amount {
  font-size: 2rem;
  font-weight: 800;
  color: #fff;
  margin: 0;
  line-height: 1;
}

.pw-balance-card__currency {
  display: inline-block;
  background: rgba(255,255,255,0.2);
  color: #fff;
  font-size: 0.72rem;
  font-weight: 700;
  padding: 2px 10px;
  border-radius: 20px;
  margin-top: 6px;
}

.pw-balance-card__right {
  text-align: right;
}

.pw-balance-card__stat {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.78rem;
  color: rgba(255,255,255,0.8);
  justify-content: flex-end;
  margin-bottom: 4px;
}

.pw-balance-card__stat-val {
  font-size: 1.1rem;
  font-weight: 700;
  color: #fff;
  margin: 0;
}

/* Botones de caja en la balance card */
.pw-cash-actions {
  display: flex;
  gap: 6px;
  margin-top: 10px;
  justify-content: flex-end;
}

.pw-cash-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 14px;
  border-radius: 8px;
  border: none;
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.15s, transform 0.1s;
}
.pw-cash-btn:active { transform: scale(0.97); }
.pw-cash-btn--deposit  { background: rgba(255,255,255,0.25); color: #fff; }
.pw-cash-btn--deposit:hover  { background: rgba(255,255,255,0.38); }
.pw-cash-btn--withdraw { background: rgba(220,38,38,0.75); color: #fff; }
.pw-cash-btn--withdraw:hover { background: rgba(220,38,38,0.9); }

/* Movimientos */
.pw-movements__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.875rem;
}

.pw-movements__title {
  font-size: 0.875rem;
  font-weight: 700;
  color: var(--ds-text);
  display: flex;
  align-items: center;
  gap: 6px;
}

.pw-movements__title i { color: #0ea5e9; }

.pw-mov-list {
  list-style: none;
  margin: 0;
  padding: 0;
  border: 1px solid var(--ds-border);
  border-radius: 10px;
  overflow: hidden;
}

.pw-mov-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--ds-border);
  transition: background 0.12s;
}
.pw-mov-item:last-child { border-bottom: none; }
.pw-mov-item:hover { background: var(--ds-surface-2); }

.pw-mov-item__icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  flex-shrink: 0;
}

.pw-mov-item__info { flex: 1; min-width: 0; }

.pw-mov-item__label {
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--ds-text);
  margin: 0 0 1px;
}

.pw-mov-item__desc {
  font-size: 0.75rem;
  color: var(--ds-text-2);
  margin: 0 0 1px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.pw-mov-item__date {
  font-size: 0.7rem;
  color: var(--ds-text-muted);
  margin: 0;
}

.pw-mov-item__amount {
  text-align: right;
  flex-shrink: 0;
  font-size: 0.9rem;
  font-weight: 700;
}

.pw-mov-item__balance {
  font-size: 0.7rem;
  color: var(--ds-text-muted);
  font-weight: 400;
  margin: 0;
}

/* Paginación */
.pw-pagination {
  display: flex;
  gap: 4px;
  justify-content: center;
  margin-top: 0.875rem;
  flex-wrap: wrap;
}

.pw-page-btn {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: 1px solid var(--ds-border-2);
  background: var(--ds-surface-2);
  color: var(--ds-text-2);
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.12s;
}
.pw-page-btn:hover { background: rgba(14,165,233,.15); border-color: #0ea5e9; }
.pw-page-btn--active { background: #0ea5e9; border-color: #0ea5e9; color: #fff; }

/* Estado vacío / carga / error (compartido con RkeCommissionsSection) */
.rke-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 2.5rem;
  color: var(--ds-text-2);
  font-size: 0.875rem;
}

.rke-state--error { color: #ef4444; }
.rke-state--empty i { font-size: 1.5rem; color: var(--ds-text-muted); }

.rke-spinner {
  width: 20px;
  height: 20px;
  border: 3px solid var(--ds-border-2);
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  flex-shrink: 0;
}
</style>
