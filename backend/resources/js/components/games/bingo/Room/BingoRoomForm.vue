<script setup lang="ts" name="BingoGameRoomForm">
  import { computed, watch } from 'vue'

  // Components
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'

  // Composables
  import { useBingoRoomForm } from '@/js/composables/games/bingo/room/useBingoRoomForm'

  // Types
  import type { BingoRoomFormData, RoundConfig } from '@/js/types/games/bingo/bingo-room'

  /* ==========================================================================
   * Opción genérica para selects
   * ========================================================================== */

  interface SelectOption {
    value: string | number
    label: string
  }

  /* ==========================================================================
   * Opciones de tipo de premio por ronda
   * ========================================================================== */

  const roundPrizeTypeOptions: SelectOption[] = [
    { value: 'percentage', label: 'Porcentaje' },
    { value: 'fixed', label: 'Fijo' }
  ]

  /* ==========================================================================
   * Props
   * ========================================================================== */

  const props = defineProps<{
    automaticRoom?: boolean
    creator: { type: string; id: string | number }
    roomTypeOptions: SelectOption[]
    gameModeOptions: SelectOption[]
    prizePoolTypeOptions: SelectOption[]
    allowedCurrenciesOptions: SelectOption[]
    bingoPatternOptions: SelectOption[]
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

  /**
   * Mostrar %Rondas y config (más de 1 ronda)
   */
  const showRoundsPct = computed(() => (form.value.rounds ?? 1) > 1)

  /**
   * Mostrar container de valor fijo/extra (prizePoolType > 1)
   */
  const showAmountAddedPrizePool = computed(() => {
    const val = Number(form.value.prizePoolType)
    return val > 1
  })

  /* ==========================================================================
   * Watchers
   * ========================================================================== */

  /**
   * Cuando cambia el número de rondas, sincronizar el array roundsConfig
   */
  watch(
    () => form.value.rounds,
    (newRounds) => {
      const count = Number(newRounds) || 1
      if (count <= 1) {
        form.value = { ...form.value, roundsConfig: [], roundsConfigActive: false }
        return
      }

      const current = form.value.roundsConfig ?? []
      const updated: RoundConfig[] = []

      for (let i = 0; i < count; i++) {
        updated.push(
          current[i] ?? { prizeType: 'percentage', bingoPattern: null, prizeValue: null }
        )
      }

      form.value = { ...form.value, roundsConfig: updated }
    }
  )

  /* ==========================================================================
   * Métodos
   * ========================================================================== */

  function toggleRoundsConfig() {
    form.value = {
      ...form.value,
      roundsConfigActive: !form.value.roundsConfigActive
    }
  }

  function updateRoundConfig(index: number, field: keyof RoundConfig, value: RoundConfig[keyof RoundConfig]) {
    const configs = [...(form.value.roundsConfig ?? [])]
    configs[index] = { ...configs[index], [field]: value }
    form.value = { ...form.value, roundsConfig: configs }
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

      <!-- Valor extra al bote (solo si prizePoolType > 1) -->
      <FormInput
        v-show="showAmountAddedPrizePool"
        label="Valor Fijo/Extra"
        name="amount_added_prize_pool"
        id="amount-added-prize-pool"
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
              title="Ajustar valores de las rondas"
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
        v-show="form.roundsConfigActive && showRoundsPct"
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
          <template v-for="(round, index) in form.roundsConfig" :key="index">
            <div class="col-12 mb-2">
              <div class="d-flex align-items-center gap-2">
                <span class="badge badge-secondary mr-2">R{{ index + 1 }}</span>

                <!-- Tipo de premio -->
                <select
                  :name="`rounds_prize_type[${index}]`"
                  class="form-control form-control-sm mr-2"
                  :value="round.prizeType"
                  @change="updateRoundConfig(index, 'prizeType', ($event.target as HTMLSelectElement).value as any)"
                  style="max-width: 130px;"
                >
                  <option
                    v-for="opt in roundPrizeTypeOptions"
                    :key="opt.value"
                    :value="opt.value"
                  >
                    {{ opt.label }}
                  </option>
                </select>

                <!-- Patrón de bingo -->
                <select
                  :name="`rounds_bingo_patterns[${index}]`"
                  class="form-control form-control-sm mr-2"
                  :value="round.bingoPattern"
                  @change="updateRoundConfig(index, 'bingoPattern', ($event.target as HTMLSelectElement).value)"
                  style="max-width: 150px;"
                >
                  <option value="" disabled>Patrón</option>
                  <option
                    v-for="opt in bingoPatternOptions"
                    :key="opt.value"
                    :value="opt.value"
                  >
                    {{ opt.label }}
                  </option>
                </select>

                <!-- Valor del premio -->
                <input
                  :name="`rounds_prize_value[${index}]`"
                  type="number"
                  class="form-control form-control-sm"
                  placeholder="Valor"
                  :value="round.prizeValue"
                  @input="updateRoundConfig(index, 'prizeValue', ($event.target as HTMLInputElement).value)"
                  step="0.5"
                  min="0"
                  max="100"
                  style="max-width: 100px;"
                />
              </div>
            </div>
          </template>
        </div>
      </div>

      <!-- %Rondas (solo si rounds > 1) -->
      <FormInput
        v-show="showRoundsPct"
        label="%Rondas"
        name="rounds_pct"
        id="rounds-pct"
        type="number"
        :step="1"
        :min="3"
        :max="30"
        col="col-sm-6 col-md-4 col-lg-3"
        v-model="form.roundsPct"
      />

      <!-- %Bote Seriales -->
      <FormInput
        label="%Bote Seriales"
        name="serials_pct"
        id="serials-pct"
        type="number"
        required
        :step="0.25"
        :min="2"
        :max="35"
        placeholder="Ingrese valor"
        col="col-sm-6 col-md-4 col-lg-3"
        v-model="form.serialsPct"
      />

      <!-- #Max. Seriales -->
      <FormInput
        label="#Max. Seriales"
        name="num_serials"
        id="num-serials"
        type="number"
        required
        :step="1"
        :min="3"
        :max="30"
        placeholder="Ingrese la cantidad"
        col="col-sm-6 col-md-4 col-lg-3"
        v-model="form.numSerials"
      />

      <!-- Precio CTN -->
      <FormInput
        label="Precio CTN"
        name="card_price"
        id="ctn-price"
        type="number"
        required
        :step="0.05"
        :min="0.35"
        :max="50"
        placeholder="Precio Cartón"
        col="col-sm-6 col-md-4 col-lg-3"
        inputGroup
        v-model="form.ctnPrice"
      >
        <template #prepend>
          <span class="input-group-text">$</span>
        </template>
      </FormInput>
    </div>
  </form>
</template>
