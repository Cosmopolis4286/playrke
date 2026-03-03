<script setup lang="ts" name="GameBingoCardGenerateForm">
  import BingoCardGenerateFormRoomSection from './BingoCardGenerateFormRoomSection.vue'
  import BingoCardGenerateFormPlayerSection from './BingoCardGenerateFormPlayerSection.vue'

  import { useBingoGenerateCard } from '@/js/composables/games/bingo/card/useBingoCardGenerate'

  /* ==========================================================================
   * Composable
   * ========================================================================== */

  const {
    /* setters */
    setRooms,

    /* filtros */
    filters,
    roomTypeOptions,
    gameModeOptions,
    filteredRooms,

    /* venta */
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
  } = useBingoGenerateCard()

  /* ==========================================================================
   * Submit
   * ========================================================================== */

  async function handleSubmit() {
    await generate()
  }
</script>

<template>
  <form novalidate @submit.prevent="handleSubmit">
    <!-- ───────────────── Room Section ───────────────── -->

    <BingoCardGenerateFormRoomSection
      :rooms="filteredRooms"
      :selected-room="selectedRoom"
      :room-ctn-price="roomCtnPrice"
      :date="filters.date"
      :room-type="filters.roomType"
      :game-mode="filters.gameMode"
      :room-type-options="roomTypeOptions"
      :game-mode-options="gameModeOptions"
      @update:date="filters.date = $event"
      @update:roomType="filters.roomType = $event"
      @update:gameMode="filters.gameMode = $event"
      @update:selectedRoom="selectedRoom = $event"
    />

    <!-- ───────────────── Resumen ───────────────── -->

    <div v-if="selectedRoom" class="row bg-light mt-2 rounded py-2">
      <div class="col-4 border-right text-center">
        <div class="text-muted small">Monto Total</div>
        <div class="h5 font-weight-bold mb-0">$ {{ totalAmount.toFixed(2) }}</div>
        <small class="text-muted">Cobrar al jugador</small>
      </div>

      <div class="col-4 border-right text-center">
        <div class="text-muted small">Mi Comisión</div>
        <div class="h5 text-success font-weight-bold mb-0">$ {{ sellerCommission.toFixed(2) }}</div>
        <small class="text-muted">Tu ganancia</small>
      </div>

      <div class="col-4 text-center">
        <div class="text-muted small">Total Banca</div>
        <div class="h5 font-weight-bold mb-0">$ {{ commerceAmount.toFixed(2) }}</div>
        <small class="text-muted">Enviar al comercio</small>
      </div>
    </div>

    <!-- ───────────────── Player Section ───────────────── -->

    <BingoCardGenerateFormPlayerSection
      v-if="selectedRoom"
      class="mt-4"
      :countries="countries"
      :loading-countries="loadingCountries"
      :selected-country="selectedCountry"
      :player="player"
      @update:selectedCountry="selectedCountry = $event"
      @update:player="Object.assign(player, $event)"
    />

    <!-- ───────────────── Botón ───────────────── -->

    <div v-if="selectedRoom" class="mt-3 text-center">
      <button type="submit" class="btn btn-primary btn-lg px-5" :disabled="!isFormValid || loading">
        <span v-if="loading" class="spinner-border spinner-border-sm mr-2" role="status" />
        {{ loading ? 'Generando…' : 'Generar Cartones' }}
      </button>
    </div>
  </form>
</template>
