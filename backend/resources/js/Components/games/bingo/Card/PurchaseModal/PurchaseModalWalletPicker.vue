<script setup lang="ts">
/**
 * Con qué billetera (moneda) paga el jugador. Solo aparece si tiene más de
 * una billetera en monedas que la sala acepta; al cambiarla, el precio se
 * recalcula en esa moneda (useGameRoomPurchaseModal.selectWallet).
 */
import type { BingoWalletOption } from '@/js/types/games/bingo/card/purchase-modal/bingo-purchase-modal'

defineProps<{
  options: BingoWalletOption[]
  selectedCurrencyId: string | null
  loading: boolean
}>()

const emit = defineEmits<{ select: [currencyId: string] }>()

const label = (option: BingoWalletOption) =>
  `${option.iso_code ?? option.symbol ?? 'Billetera'} · saldo ${option.balance.toFixed(2)}`
</script>

<template>
  <div v-if="options.length > 1" class="pm-wallet">
    <label class="pm-wallet__label" for="pm-wallet-select">
      <i class="fas fa-wallet" /> Pagar con
    </label>
    <select
      id="pm-wallet-select"
      class="form-control form-control-sm"
      :value="selectedCurrencyId ?? ''"
      :disabled="loading"
      @change="emit('select', ($event.target as HTMLSelectElement).value)"
    >
      <option v-for="option in options" :key="option.currency_id" :value="option.currency_id">
        {{ label(option) }}
      </option>
    </select>
  </div>
</template>

<style scoped>
.pm-wallet {
  margin-bottom: 0.75rem;
}

.pm-wallet__label {
  display: block;
  margin-bottom: 0.25rem;
  font-size: 0.8rem;
  font-weight: 600;
}
</style>
