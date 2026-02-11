<script setup lang="ts" name="BingoGameRoomForm">
  import { computed } from 'vue'

  // Components
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'

  // Composables
  import { useBingoRoomForm } from '@/js/composables/games/bingo/room/useBingoRoomForm'

  // Types
  import type { TimeZone } from '@/js/types/time-zone'
  import type { BingoRoomFormData } from '@/js/types/games/bingo/bingo-room'

  /* ==========================================================================
   * Opción genérica para selects
   * ========================================================================== */

  interface SelectOption {
    value: string | number
    label: string
  }

  /* ==========================================================================
   * Props
   * ========================================================================== */

  const props = defineProps<{
    timeZone: TimeZone
    automaticRoom?: boolean
    creator: { type: string; id: string | number }
    roomTypeOptions: SelectOption[]
    gameModeOptions: SelectOption[]
    prizePoolTypeOptions: SelectOption[]
    allowedCurrenciesOptions: SelectOption[]
    modelValue: BingoRoomFormData
  }>()

  /* ==========================================================================
   * Emits
   * ========================================================================== */

  const emit = defineEmits<{
    (e: 'submit'): void
    (e: 'update:modelValue', value: BingoRoomFormData): void
    (e: 'createRoom'): void
  }>()

  /* ==========================================================================
   * Form logic (computed v-model)
   * ========================================================================== */

  const { form, errors, isValid } = useBingoRoomForm(() => props.modelValue, emit)

  /* ==========================================================================
   * Computed
   * ========================================================================== */

  /**
   * Fecha mínima: hoy (formato YYYY-MM-DD)
   */
  const minDate = computed(() => {
    const today = new Date()
    return today.toISOString().split('T')[0]
  })

  /* ==========================================================================
   * Métodos
   * ========================================================================== */

  function toggleRoundsConfig() {
    form.value = {
      ...form.value,
      roundsConfigActive: !form.value.roundsConfigActive
    }
  }

  function createRoom() {
    if (!isValid.value) return
    emit('createRoom')
  }

  /* ==========================================================================
   * Submit
   * ========================================================================== */

  const submit = (): boolean => {
    if (!isValid.value) return false
    emit('submit')
    return true
  }

  /* ==========================================================================
   * Expose
   * ========================================================================== */

  defineExpose({
    submit,
    isValid
  })
</script>
<template>
  <form id="bingo-room-form" novalidate @submit.prevent="submit">
    <div class="row">
      <!-- Zona horaria (solo lectura) -->
      <FormInput
        label="Zona Horaria"
        name="time-zone"
        :modelValue="timeZone.utc"
        disabled
        col="col-sm-6 col-md-4 col-lg-3"
      />

      <!-- Fecha (solo si no es sala automática) -->
      <FormInput
        v-if="!automaticRoom"
        label="Fecha"
        name="date"
        type="date"
        required
        :min="minDate"
        col="col-sm-6 col-md-4 col-lg-3"
        v-model="form.date"
      />

      <!-- Hora -->
      <FormInput
        label="Hora"
        name="time"
        type="time"
        required
        col="col-sm-6 col-md-4 col-lg-3"
        v-model="form.time"
      />

      <!-- Tipo de sala -->
      <FormSelect
        v-if="automaticRoom && creator.type !== 'customer'"
        label="Tipo de Sala"
        name="room_type"
        id="room-type"
        required
        placeholder="Selecciona tipo"
        :options="roomTypeOptions"
        optionValue="value"
        optionLabel="label"
        col="col-sm-6 col-md-4 col-lg-3"
        v-model="form.roomType"
      />
      <input v-else type="hidden" name="room_type" :value="form.roomType" />

      <!-- Modo de juego -->
      <FormSelect
        label="Modo de Juego"
        name="game_mode"
        id="game-mode"
        required
        placeholder="Selecciona modo"
        :options="gameModeOptions"
        optionValue="value"
        optionLabel="label"
        col="col-sm-6 col-md-4 col-lg-3"
        v-model="form.gameMode"
      />

      <!-- Tipo Bote -->
      <FormSelect
        label="Tipo Bote"
        name="prize_pool_type"
        id="prize-pool-type"
        required
        placeholder="Selecciona tipo bote"
        :options="prizePoolTypeOptions"
        optionValue="value"
        optionLabel="label"
        col="col-sm-6 col-md-4 col-lg-3"
        v-model="form.prizePoolType"
      />

      <!-- Monedas permitidas -->
      <FormSelect
        label="Monedas Permitidas"
        name="allowed_currencies"
        id="allowed-currencies"
        container
        required
        multiple
        placeholder="Seleccionar Monedas"
        :options="allowedCurrenciesOptions"
        optionValue="value"
        optionLabel="label"
        col="col-sm-6"
        v-model="form.allowedCurrencies"
      />

      <!-- Valor extra al bote -->
      <FormInput
        label="Valor Fijo/Extra"
        name="amount_added_prize_pool"
        id="amount-added-prize-pool"
        container
        type="number"
        required
        :step="0.05"
        :min="20"
        :max="10000"
        col="col-sm-6 col-md-4 col-lg-3"
        inputGroup
        v-model="form.amountAddedPrizePool"
      >
        <template #prepend>
          <span class="input-group-text">$</span>
        </template>
      </FormInput>

      <input type="hidden" name="creator" :value="JSON.stringify(creator)" />

      <!-- Comisiones, jackpot y tiempo ficha -->
      <FormInput
        label="%Sala"
        name="room_commn"
        id="room-commn"
        type="number"
        required
        :step="0.05"
        :min="10"
        :max="50"
        col="col-sm-6 col-md-4 col-lg-3"
        v-model="form.roomCommn"
      />

      <FormInput
        label="%Jackpot"
        name="jackpot_pct"
        id="jackpot-pct"
        type="number"
        :step="0.5"
        :min="1"
        :max="15"
        col="col-sm-6 col-md-4 col-lg-3"
        v-model="form.jackpotPct"
      />

      <FormInput
        label="Tiempo Ficha"
        name="time_chip"
        id="time-chip"
        type="number"
        required
        :step="1"
        :min="3"
        :max="10"
        col="col-sm-6 col-md-4 col-lg-3"
        inputGroup
        v-model="form.timeChip"
      >
        <template #append>
          <span class="input-group-text">SEG</span>
        </template>
      </FormInput>

      <!-- Rondas -->
      <FormInput
        label="Rondas"
        name="rounds"
        type="number"
        required
        :step="1"
        :min="1"
        :max="15"
        col="col-sm-6 col-md-4 col-lg-3"
        inputGroup
        v-model="form.rounds"
      >
        <template #append>
          <div class="content-rounds-pct">
            <button
              id="btn-rounds-config"
              class="btn btn-outline-secondary"
              type="button"
              :data-active="form.roundsConfigActive ? 1 : 0"
              @click="toggleRoundsConfig"
              title="Ajustar precio por monedas"
            >
              <i class="fa-solid fa-sliders"></i>
            </button>
          </div>
        </template>
      </FormInput>

      <!-- Configuración avanzada de rondas -->
      <div
        id="container-rounds-config"
        class="form-group col-12"
        v-show="form.roundsConfigActive"
        style="
          max-height: 155px;
          overflow-y: auto;
          border: 1px solid #ddd;
          padding: 1rem;
          border-radius: 0.25rem;
        "
      >
        <div class="d-flex">
          <h6 class="mb-3 mr-1">Ajustar valores de las rondas</h6>
          <div
            v-if="form.loadingRoundsConfig"
            class="spinner-border spinner-border-sm text-primary"
            role="status"
          >
            <span class="sr-only">Cargando...</span>
          </div>
        </div>
        <div id="content-rounds-config" class="row">
          <!-- Contenido dinámico de configuración de rondas -->
        </div>
      </div>

      <!-- Botón para crear la sala -->
      <div class="form-group col-12 text-center">
        <button type="button" @click="createRoom" class="btn btn-primary btn-lg">
          <i class="fa-solid fa-plus mr-2"></i>
          Crear Sala de Bingo
        </button>
      </div>
    </div>
  </form>
</template>
