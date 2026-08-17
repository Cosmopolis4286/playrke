<script setup lang="ts" name="GameBingoCardGenerateForm">
  import { ref, computed } from 'vue'
  import BingoCardGenerateFormRoomSection from './BingoCardGenerateFormRoomSection.vue'
  import BingoCardGenerateFormPlayerSection from './BingoCardGenerateFormPlayerSection.vue'

  import { useBingoCardGenerate } from '@/js/composables/games/bingo/card/useBingoCardGenerate'
  import { showToast } from '@/js/utils/toastNotify'
  import type { SelectOption } from '@/js/types/common/form/form'
  import type { Player } from '@/js/types/games/bingo/card/bingo-card-generate'

  defineProps<{
    roomTypeOptions: SelectOption[]
    connectionTypeOptions: SelectOption[]
    prizePoolTypeOptions: SelectOption[]
  }>()

  /* ───────── Player Section ref ───────── */

  const playerSectionRef = ref<InstanceType<typeof BingoCardGenerateFormPlayerSection> | null>(null)

  const roomSectionRef = ref<InstanceType<typeof BingoCardGenerateFormRoomSection> | null>(null)

  const selectedRoom = computed(() => roomSectionRef.value?.selectedRoom ?? null)

  /* ───────── Composable ───────── */

  const {
    countries,
    selectedCountry,
    cardCount,
    player,
    loading,
    loadingCountries,
    loadingCardPrice,
    cardPriceData,
    currencySymbol,
    totalAmount,
    sellerCommission,
    commerceAmount,
    isFormValid,
    generatedPlayerLink,
    generate
  } = useBingoCardGenerate(selectedRoom)

  /**
   * Actualiza los campos del jugador usando Object.assign para preservar
   * la reactividad del objeto reactive<Player> del composable.
   *
   * @param updated - Objeto Player completo emitido por BingoCardGenerateFormPlayerSection.
   */
  function onUpdatePlayer(updated: Player) {
    Object.assign(player, updated)
  }

  /**
   * Actualiza los datos del comprobante en el composable.
   *
   * @param receiptData - Datos del comprobante emitidos por BingoCardGenerateFormPlayerSection.
   */
  function onUpdateReceiptData(receiptData: { receiptNumber: string; receiptImage: File | null }) {
    player.receiptNumber = receiptData.receiptNumber
    player.receiptImage = receiptData.receiptImage
  }

  async function handleSubmit() {
    await generate()
  }

  async function copyPlayerLink() {
    if (!generatedPlayerLink.value) return
    try {
      await navigator.clipboard.writeText(generatedPlayerLink.value)
      showToast('Link copiado al portapapeles', 'success')
    } catch {
      showToast('No se pudo copiar el link', 'error')
    }
  }
</script>

<template>
  <form novalidate @submit.prevent="handleSubmit">
    <!-- ───────────── Room Section ───────────── -->

    <BingoCardGenerateFormRoomSection
      ref="roomSectionRef"
      :room-type-options="roomTypeOptions"
      :connection-type-options="connectionTypeOptions"
      :prize-pool-type-options="prizePoolTypeOptions"
      :card-count="cardCount"
      :selected-country="selectedCountry"
      @update:cardCount="cardCount = $event"
    />

    <!-- ───────────── Resumen ───────────── -->

    <div v-if="roomSectionRef?.selectedRoom && selectedCountry" class="row bg-light mt-2 rounded py-2">

      <!-- Cargando precio -->
      <div v-if="loadingCardPrice" class="col-12 text-center text-muted small py-1">
        <span class="spinner-border spinner-border-sm mr-1" role="status" />
        Calculando precio del cartón…
      </div>

      <template v-else-if="cardPriceData">
        <div class="col-4 border-right text-center">
          <div class="text-muted small">Monto Total</div>
          <div class="h5 font-weight-bold mb-0">
            {{ currencySymbol }} {{ totalAmount.toFixed(2) }}
          </div>
          <small class="text-muted">Cobrar al jugador</small>
        </div>

        <div class="col-4 border-right text-center">
          <div class="text-muted small">Mi Comisión</div>
          <div class="h5 text-success font-weight-bold mb-0">
            {{ currencySymbol }} {{ sellerCommission.toFixed(2) }}
          </div>
          <small class="text-muted">Tu ganancia</small>
        </div>

        <div class="col-4 text-center">
          <div class="text-muted small">Total Banca</div>
          <div class="h5 font-weight-bold mb-0">
            {{ currencySymbol }} {{ commerceAmount.toFixed(2) }}
          </div>
          <small class="text-muted">Enviar al comercio</small>
        </div>
      </template>
    </div>

    <!-- ───────────── Player Section ───────────── -->

    <BingoCardGenerateFormPlayerSection
      v-if="roomSectionRef?.selectedRoom"
      ref="playerSectionRef"
      class="mt-4"
      :selected-room="roomSectionRef.selectedRoom"
      :countries="countries"
      :loading-countries="loadingCountries"
      v-model:selectedCountry="selectedCountry"
      :player="player"
      @update:player="onUpdatePlayer"
      @update:receiptData="onUpdateReceiptData"
    />

    <!-- ───────────── Link de acceso del jugador ───────────── -->

    <div v-if="generatedPlayerLink" class="alert alert-success mt-3">
      <div class="d-flex align-items-center mb-2">
        <i class="fas fa-check-circle me-2"></i>
        <strong>Cartones generados.</strong>&nbsp;Comparte este link con el jugador para que vea sus cartones y el sorteo:
      </div>
      <div class="input-group">
        <input type="text" class="form-control" readonly :value="generatedPlayerLink" @click="($event.target as HTMLInputElement)?.select()" />
        <button type="button" class="btn btn-outline-success" @click="copyPlayerLink">
          <i class="fas fa-copy me-1"></i>Copiar
        </button>
      </div>
    </div>

    <!-- ───────────── Botón ───────────── -->

    <div v-if="roomSectionRef?.selectedRoom" class="mt-3 text-center">
      <button type="submit" class="btn btn-primary btn-lg px-5" :disabled="!isFormValid || loading">
        <span v-if="loading" class="spinner-border spinner-border-sm mr-2" role="status" />
        <template v-if="loading">Generando…</template>
        <template v-else>
          {{ cardCount === 1 ? 'Generar Cartón' : 'Generar Cartones' }}
        </template>
      </button>
    </div>
  </form>
</template>
