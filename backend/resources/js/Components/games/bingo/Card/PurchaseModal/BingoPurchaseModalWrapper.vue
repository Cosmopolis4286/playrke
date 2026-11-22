<script setup lang="ts">
import type { Ref } from 'vue'
import BingoPurchaseModal from './BingoPurchaseModal.vue'
import { useGameRoomPurchaseModal } from '@js/composables/games/bingo/card/purchase-modal/useGameRoomPurchaseModal'

type PurchaseModal = ReturnType<typeof useGameRoomPurchaseModal>

/**
 * Wrapper sin estado que conecta useGameRoomPurchaseModal con BingoPurchaseModal.
 * Elimina la duplicación del bloque de 20+ props en cada página que use el modal.
 *
 * Uso:
 *   <BingoPurchaseModalWrapper :modal="modal" :exchange-rate="rate" :guard="canBuy"
 *     @success="onPurchaseSuccess" />
 */
defineProps<{
  modal: PurchaseModal
  exchangeRate: number
  guard?: boolean
}>()

const emit = defineEmits<{
  success: [roomId: string, result: { prizePool: number; cardCount: number }]
}>()

// Los refs viven en el composable (props.modal): se actualizan por esta vía
// en vez de asignar sobre la prop en el template.
function setRef<T>(target: Ref<T>, value: T) {
  target.value = value
}
</script>

<template>
  <BingoPurchaseModal
    v-if="modal.isOpen.value && guard !== false"
    :is-open="modal.isOpen.value"
    :step="modal.step.value"
    :selected-room="modal.selectedRoom.value"
    :countries="modal.countries.value"
    :loading-countries="modal.loadingCountries.value"
    :selected-country="modal.selectedCountry.value"
    :card-price="modal.cardPrice.value"
    :currency-symbol="modal.currencySymbol.value"
    :loading-price="modal.loadingPrice.value"
    :wallet-options="modal.walletOptions.value"
    :selected-currency-id="modal.selectedCurrencyId.value"
    :card-count="modal.cardCount.value"
    :total-amount="modal.totalAmount.value"
    :can-advance="modal.canAdvance.value"
    :purchasing="modal.purchasing.value"
    :exchange-rate="exchangeRate"
    :is-player="modal.isPlayer.value"
    :can-customize="modal.canCustomize.value"
    :customize-card="modal.customizeCard.value"
    :custom-card-numbers="modal.customCardNumbers.value"
    :active-card-index="modal.activeCardIndex.value"
    :config-loading="modal.configLoading.value"
    :manual-edits="modal.manualEditsPerCard.value"
    :custom-card-mode="modal.customCardState.value.mode"
    :custom-price-pct="modal.customPricePct.value"
    :max-custom-cards-allowed="modal.maxCustomCardsAllowed.value"
    :allow-move-free-space="modal.allowMoveFreeSpace.value"
    :move-free-space-price-pct="modal.moveFreeSpacePricePct.value"
    :exceeds-custom-card-limit="modal.exceedsCustomCardLimit.value"
    :base-price-per-card="modal.basePricePerCard.value"
    :custom-price-per-card="modal.customPricePerCard.value"
    :manual-card-count="modal.manualCardCount.value"
    :confirm-incomplete-custom-card="modal.confirmIncompleteCustomCard"
    @close="modal.close()"
    @select-country="modal.selectCountry($event)"
    @select-wallet="modal.selectWallet($event)"
    @next="modal.nextStep()"
    @prev="modal.prevStep()"
    @increment="modal.increment()"
    @decrement="modal.decrement()"
    @set-card-count="modal.setCardCount($event)"
    @toggle-customize="modal.toggleCustomize()"
    @update:custom-card-numbers="setRef(modal.customCardNumbers, $event)"
    @update:manual-edits="setRef(modal.manualEditsPerCard, $event)"
    @set-active-card-index="setRef(modal.activeCardIndex, $event)"
    @purchase="modal.purchase(result => { const id = modal.selectedRoom.value?.id; if (id) emit('success', id, result) })"
  />
</template>
