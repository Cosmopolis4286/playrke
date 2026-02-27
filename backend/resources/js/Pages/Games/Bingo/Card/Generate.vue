<script setup lang="ts">
  import CardHeader from '@js/Components/Common/Card/CardHeader.vue'
  import BingoCardGenerateForm from '@/js/Components/Games/Bingo/Card/BingoCardGenerateForm.vue'
  import { useBingogenerateCard } from '@/js/composables/games/bingo/card/useBingoCardGenerate'
  import type { Room } from '@/js/composables/games/bingo/card/useBingoCardGenerate'

  const props = defineProps<{
    rooms: Room[]
    commission: number
  }>()

  const {
    selectedRoom,
    numCtns,
    selectedCountry,
    countries,
    loadingCountries,
    player,
    loading,
    roomCtnPrice,
    totalAmount,
    sellerCommission,
    commerceAmount,
    isFormValid,
    generate
  } = useBingogenerateCard(props.commission)
</script>

<template>
  <div class="card">
    <CardHeader title="Generar Cartones" />
    <div class="card-body">
      <BingoCardGenerateForm
        v-model:selected-room="selectedRoom"
        v-model:num-ctns="numCtns"
        v-model:selected-country="selectedCountry"
        v-model:player="player"
        :rooms="rooms"
        :countries="countries"
        :loading-countries="loadingCountries"
        :room-ctn-price="roomCtnPrice"
        :total-amount="totalAmount"
        :seller-commission="sellerCommission"
        :commerce-amount="commerceAmount"
        :is-form-valid="isFormValid"
        :loading="loading"
        @generate="generate"
      />
    </div>
  </div>
</template>
