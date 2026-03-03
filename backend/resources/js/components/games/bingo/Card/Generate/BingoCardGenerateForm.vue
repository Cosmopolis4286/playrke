<script setup lang="ts" name="GameBingoCardGenerateForm">
  import BingoCardGenerateFormRoomSection from './BingoCardGenerateFormRoomSection.vue'
  import BingoCardGenerateFormPlayerSection from './BingoCardGenerateFormPlayerSection.vue'

  import type { Country } from '@/js/types/country'
  import type { Room, Player } from '@/js/types/games/bingo/card/bingo-card-generate'

  /* ========================================================================== */
  /* Props */
  /* ========================================================================== */

  const props = defineProps<{
    rooms: Room[]
    countries: Country[]
    loadingCountries: boolean
    roomCtnPrice: number
    totalAmount: number
    sellerCommission: number
    commerceAmount: number
    isFormValid: boolean
    loading: boolean
    selectedRoom: Room | null
    numCtns: number
    selectedCountry: number | null
    player: Player
  }>()

  /* ========================================================================== */
  /* Emits */
  /* ========================================================================== */

  const emit = defineEmits<{
    (e: 'update:selectedRoom', value: Room | null): void
    (e: 'update:numCtns', value: number): void
    (e: 'update:selectedCountry', value: number | null): void
    (e: 'update:player', value: Player): void
    (e: 'generate'): void
  }>()
</script>

<template>
  <form novalidate @submit.prevent="emit('generate')">
    <!-- ───────────────── Room Section ───────────────── -->
    <BingoCardGenerateFormRoomSection
      :rooms="rooms"
      :selected-room="selectedRoom"
      :num-ctns="numCtns"
      :room-ctn-price="roomCtnPrice"
      @update:selectedRoom="emit('update:selectedRoom', $event)"
      @update:numCtns="emit('update:numCtns', $event)"
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
      @update:selectedCountry="emit('update:selectedCountry', $event)"
      @update:player="emit('update:player', $event)"
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
