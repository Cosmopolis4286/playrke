<script setup lang="ts">
import type { CommerceWallet } from '@/js/types/roles/commerce/dashboard/dashboard-commerce'
import DashboardEmptyState from '@/js/Components/Common/Dashboard/DashboardEmptyState.vue'

defineProps<{
  wallets: CommerceWallet[]
}>()
</script>

<template>
  <div v-if="wallets.length" class="wb-list">
    <div v-for="wallet in wallets" :key="wallet.currency_code" class="wb-item">
      <span class="wb-item__code">{{ wallet.currency_code }}</span>
      <div class="wb-item__amounts">
        <span class="wb-item__balance">{{ wallet.symbol }} {{ wallet.balance.toFixed(2) }}</span>
        <span v-if="wallet.bonus_balance > 0" class="wb-item__bonus">
          + {{ wallet.symbol }} {{ wallet.bonus_balance.toFixed(2) }} bono
        </span>
      </div>
    </div>
  </div>
  <DashboardEmptyState
    v-else
    icon="fas fa-wallet"
    title="Sin billeteras activas"
    description="Configura una billetera para ver tu saldo aquí."
  />
</template>

<style scoped>
.wb-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.wb-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.7rem 0.9rem;
  background: var(--ds-surface-2);
  border-radius: 8px;
}

.wb-item__code {
  font-weight: 700;
  color: var(--ds-text);
}

.wb-item__amounts {
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
  font-variant-numeric: tabular-nums;
}

.wb-item__balance {
  font-weight: 600;
  color: var(--ds-text);
}

.wb-item__bonus {
  font-size: 0.78rem;
  color: #34d399;
}
</style>
