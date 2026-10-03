<script setup lang="ts">
import RpCard from './RpCard.vue'
import type { RoleProfileReceivable, RoleProfileWallet } from '@/js/types/roles/shared/role-profile-common'
import { formatAmount } from '@/js/composables/roles/shared/profile/useRoleProfile'

defineProps<{
  wallets: RoleProfileWallet[]
  /** Solo Commerce: deuda pendiente con la plataforma por ventas fuera del sistema (PlatformReceivable). */
  receivables?: RoleProfileReceivable[]
}>()
</script>

<template>
  <RpCard title="Billetera" icon="fa-wallet">
    <template #actions>
      <a href="/wallet" class="rp-link">Movimientos <i class="fas fa-arrow-right"></i></a>
    </template>

    <div v-if="wallets.length" class="rp-wallets">
      <div v-for="wallet in wallets" :key="wallet.currency" class="rp-wallet">
        <span class="rp-wallet__currency">{{ wallet.currency }}</span>
        <span class="rp-wallet__balance"><small>{{ wallet.symbol }}</small> {{ formatAmount(wallet.balance) }}</span>
        <span v-if="wallet.bonus_balance > 0" class="rp-wallet__bonus">
          <i class="fas fa-gift"></i>+{{ formatAmount(wallet.bonus_balance) }} de bono
        </span>
      </div>
    </div>
    <p v-else class="rp-empty">Sin billetera asociada.</p>

    <div v-if="receivables?.length" class="rp-debt">
      <div class="rp-debt__head">
        <i class="fas fa-file-invoice-dollar"></i>
        <span>Por liquidar con la plataforma</span>
      </div>
      <div v-for="r in receivables" :key="r.currency" class="rp-debt__row">
        <span class="rp-debt__amount">{{ r.symbol }} {{ formatAmount(r.amount) }} <small>{{ r.currency }}</small></span>
        <span class="rp-debt__items">{{ r.items }} venta(s)</span>
      </div>
      <p class="rp-debt__hint">
        Comisión RKE, aporte al jackpot y bote de las ventas que cobraste en mano. Se marca como liquidado cuando la plataforma confirma el pago.
      </p>
    </div>
  </RpCard>
</template>

<style scoped>
.rp-link { display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.78rem; font-weight: 700; color: #7c8cf0; text-decoration: none; }
.rp-link:hover { text-decoration: underline; color: #7c8cf0; }

.rp-wallets { display: flex; flex-direction: column; gap: 0.6rem; }
.rp-wallet {
  display: flex; flex-direction: column; gap: 0.1rem;
  padding: 0.85rem 1rem;
  border-radius: 12px;
  background:
    radial-gradient(circle at 100% 0%, rgba(102, 126, 234, 0.18), transparent 55%),
    var(--ds-surface-2);
}
.rp-wallet__currency { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ds-text-muted); }
.rp-wallet__balance { font-size: 1.45rem; font-weight: 800; color: var(--ds-text); font-variant-numeric: tabular-nums; line-height: 1.2; }
.rp-wallet__balance small { font-size: 0.85rem; font-weight: 700; color: var(--ds-text-2); }
.rp-wallet__bonus { display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.74rem; color: #f59e0b; }

.rp-empty { margin: 0; font-size: 0.8rem; color: var(--ds-text-muted); }

.rp-debt {
  margin-top: 0.85rem; padding: 0.8rem 0.9rem;
  border-radius: 12px;
  border: 1px solid rgba(217, 119, 6, 0.3);
  background: rgba(217, 119, 6, 0.08);
}
.rp-debt__head { display: flex; align-items: center; gap: 0.45rem; font-size: 0.74rem; font-weight: 700; color: #f59e0b; text-transform: uppercase; letter-spacing: 0.04em; }
.rp-debt__row { display: flex; align-items: baseline; justify-content: space-between; gap: 0.5rem; margin-top: 0.4rem; }
.rp-debt__amount { font-size: 1.1rem; font-weight: 800; color: var(--ds-text); font-variant-numeric: tabular-nums; }
.rp-debt__amount small { font-size: 0.72rem; color: var(--ds-text-muted); font-weight: 600; }
.rp-debt__items { font-size: 0.72rem; color: var(--ds-text-2); }
.rp-debt__hint { margin: 0.45rem 0 0; font-size: 0.72rem; line-height: 1.4; color: var(--ds-text-2); }
</style>
