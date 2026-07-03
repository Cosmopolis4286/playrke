<script setup lang="ts">
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
const props = defineProps<{
  modal: PurchaseModal
  exchangeRate: number
  guard?: boolean
}>()

const emit = defineEmits<{
  success: [roomId: string, result: { prizePool: number; ctns: number }]
}>()
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
    :num-ctns="modal.numCtns.value"
    :total-amount="modal.totalAmount.value"
    :can-advance="modal.canAdvance.value"
    :purchasing="modal.purchasing.value"
    :exchange-rate="exchangeRate"
    :is-player="modal.isPlayer.value"
    :can-customize="modal.canCustomize.value"
    :customize-card="modal.customizeCard.value"
    :custom-card-numbers="modal.customCardNumbers.value"
    :confirm-incomplete-custom-card="modal.confirmIncompleteCustomCard"
    @close="modal.close()"
    @select-country="modal.selectCountry($event)"
    @next="modal.nextStep()"
    @prev="modal.prevStep()"
    @increment="modal.increment()"
    @decrement="modal.decrement()"
    @set-ctns="modal.setNumCtns($event)"
    @toggle-customize="modal.toggleCustomize()"
    @update:custom-card-numbers="modal.customCardNumbers.value = $event"
    @purchase="modal.purchase(result => emit('success', modal.selectedRoom.value!.id, result))"
  />
</template>
