<script setup lang="ts" name="GameBingoCardGenerateForm">
  import BingoCardGenerateFormRoomSection from './BingoCardGenerateFormRoomSection.vue'
  import BingoCardGenerateFormPlayerSection from './BingoCardGenerateFormPlayerSection.vue'

  import { useBingoGenerateCard } from '@/js/composables/games/bingo/card/useBingoCardGenerate'
  import type { SelectOption } from '@/js/types/common/form/form'

  /**
   * Opciones configuradas desde backend
   */
  defineProps<{
    roomTypeOptions: SelectOption[]
    gameModeOptions: SelectOption[]
  }>()

  /* ───────────────── Composable ───────────────── */

  const {
    filters,
    rooms,

    selectedRoom,
    numCtns,
    player,
    selectedCountry,
    countries,

    loading,
    loadingRooms,
    loadingCountries,

    roomStepStatus,
    roomAlert,

    canSelectRoomType,
    canSelectGameMode,
    canSelectRoom,

    roomCtnPrice,
    totalAmount,
    sellerCommission,
    commerceAmount,
    isFormValid,

    generate
  } = useBingoGenerateCard()

  /**
   * Submit del formulario
   */
  async function handleSubmit() {
    await generate()
  }
</script>

<template>
  <form novalidate @submit.prevent="handleSubmit">
    <!-- ───────────── Room Section ───────────── -->

    <BingoCardGenerateFormRoomSection
      v-model:date="filters.date"
      v-model:roomType="filters.roomType"
      v-model:gameMode="filters.gameMode"
      v-model:selectedRoom="selectedRoom"
      :room-step="roomStepStatus"
         roomAlert,

    canSelectRoomType,
    canSelectGameMode,
    canSelectRoom,

    roomCtnPrice,
      :rooms="rooms"
      :room-type-options="roomTypeOptions"
      :game-mode-options="gameModeOptions"
      :loading-rooms="loadingRooms"
    />

    <!-- ───────────── Resumen ───────────── -->

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

    <!-- ───────────── Player Section ───────────── -->

    <BingoCardGenerateFormPlayerSection
      v-if="selectedRoom"
      class="mt-4"
      :selected-room="selectedRoom"
      :countries="countries"
      :loading-countries="loadingCountries"
      v-model:selectedCountry="selectedCountry"
      v-model:numCtns="numCtns"
      v-model:player="player"
    />

    <!-- ───────────── Botón ───────────── -->

    <div v-if="selectedRoom" class="mt-3 text-center">
      <button type="submit" class="btn btn-primary btn-lg px-5" :disabled="!isFormValid || loading">
        <span v-if="loading" class="spinner-border spinner-border-sm mr-2" role="status" />

        {{ loading ? 'Generando…' : 'Generar Cartones' }}
      </button>
    </div>
  </form>
</template>
