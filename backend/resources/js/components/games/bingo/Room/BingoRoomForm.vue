<script setup lang="ts" name="BingoGameRoomForm">
  import { computed, watch, ref } from 'vue'

  // Components
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'

  // Composables
  import { useBingoRoomForm } from '@/js/composables/games/bingo/room/useBingoRoomForm'

  // Types
  import type { BingoRoomFormData, RoundConfig } from '@/js/types/games/bingo/room/bingo-room'

  /* ==========================================================================
   * Opción genérica para selects
   * ========================================================================== */

  interface SelectOption {
    value: string | number
    code?: string
    label: string
  }

  /* ==========================================================================
   * Opciones de tipo de premio por ronda
   * ========================================================================== */

  const roundPrizeTypeOptions: SelectOption[] = [
    { value: 'percentage', label: 'Porcentaje' },
    { value: 'fixed', label: 'Fijo' },
    { value: 'prize', label: 'Premio' }
  ]

  /* ==========================================================================
   * Props
   * ========================================================================== */

  const props = defineProps<{
    automaticRoom?: boolean
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
  }>()

  /* ==========================================================================
   * Form logic (computed v-model)
   * ========================================================================== */

  const { form, errors, isValid, roomCommn, jackpotPct, roundsPct, serialsPct, percentageLimits } =
    useBingoRoomForm(
      () => props.modelValue,
      emit,
      () => props.automaticRoom ?? false
    )

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
   * Suma total de los valores de los premios de las rondas
   */
  const totalRoundValues = computed(() => {
    return (form.value.roundsConfig ?? []).reduce((sum, round) => {
      const value = Number(round.prizeValue) || 0
      return sum + value
    }, 0)
  })

  /**
   * Valida que la suma de valores de rondas sea igual a %Rondas
   */
  const isRoundValuesValid = computed(() => {
    const roundsPct = Number(form.value.roundsPct) || 0
    if (roundsPct === 0) return true // No validar si no hay valor
    const total = totalRoundValues.value
    return Math.abs(total - roundsPct) < 0.01 // Permitir pequeñas diferencias por redondeo
  })

  /**
   * Mostrar container de valor fijo/extra (cuando NO es Acumulativo)
   */
  const showAmountAddedPrizePool = computed(() => {
    const selected = props.prizePoolTypeOptions.find(
      opt => String(opt.value) === String(form.value.prizePoolType)
    )
    return selected ? selected.code !== 'ACCUMULATIVE' : false
  })

  /**
   * Permite ajustar precio por moneda solo si hay más de una moneda seleccionada
   */
  const canAdjustCtnPriceByCurrency = computed(() => {
    const allowed = form.value.allowedCurrencies
    return Array.isArray(allowed) && allowed.length > 1
  })

  const cardPriceByCurrencyActive = ref(false)

  function toggleCtnPriceByCurrency() {
    if (!canAdjustCtnPriceByCurrency.value) return
    cardPriceByCurrencyActive.value = !cardPriceByCurrencyActive.value
    if (!form.value.cardPriceByCurrency) {
      form.value = { ...form.value, cardPriceByCurrency: {} }
    }
  }

  function updateCtnPriceForCurrency(currency: string | number, value: string | number | null) {
    const key = String(currency)
    const current = { ...(form.value.cardPriceByCurrency ?? {}) }
    const parsed = value === null || value === '' ? null : Number(value)
    current[key] = isNaN(Number(parsed)) ? null : (parsed as number)
    form.value = { ...form.value, cardPriceByCurrency: current }
  }

  function getCurrencyLabel(id: string | number) {
    const found = props.allowedCurrenciesOptions.find(opt => String(opt.value) === String(id))
    return found ? String(found.label) : String(id)
  }

  const hasCardPriceByCurrencyErrors = computed(() => {
    const err = errors.value.cardPriceByCurrency
    return err && Object.keys(err).length > 0
  })

  const cardPriceByCurrencyPopoverText = computed(() => {
    const err = errors.value.cardPriceByCurrency
    if (!err) return ''

    return 'Ajusta los precios de los cartones'
  })

  /* ==========================================================================
   * Watchers
   * ========================================================================== */

  /**
   * Cuando cambia el número de rondas, sincronizar el array roundsConfig
   */
  watch(
    () => form.value.rounds,
    newRounds => {
      const count = Math.max(1, Number(newRounds) || 1)

      const current = form.value.roundsConfig ?? []
      const updated: RoundConfig[] = []

      for (let i = 0; i < count; i++) {
        updated.push(
          current[i] ?? { prizeType: 'percentage', bingoPattern: null, prizeValue: null }
        )
      }

      form.value = { ...form.value, roundsConfig: updated }
    },
    { immediate: true }
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

  function updateRoundConfig(
    index: number,
    field: keyof RoundConfig,
    value: RoundConfig[keyof RoundConfig]
  ) {
    const configs = [...(form.value.roundsConfig ?? [])]
    configs[index] = { ...configs[index], [field]: value }
    form.value = { ...form.value, roundsConfig: configs }
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
        :error="errors.date"
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
        :error="errors.time"
        type="time"
        required
        col="col-sm-6 col-md-4 col-lg-3"
        v-model="form.time"
      />

      <!-- Tipo de sala -->
      <FormSelect
        label="Tipo de Sala"
        name="room_type"
        :error="errors.roomType"
        required
        placeholder="Selecciona tipo"
        :options="roomTypeOptions"
        optionValue="value"
        optionLabel="label"
        col="col-sm-6 col-md-4 col-lg-3"
        v-model="form.roomType"
      />

      <!-- Modo de juego -->
      <FormSelect
        label="Modo de Juego"
        name="game_mode"
        :error="errors.gameMode"
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
        :error="errors.prizePoolType"
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
        :error="errors.allowedCurrencies"
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

      <!-- Precio Carton (solo si hay  una moneda seleccionada) -->
      <FormInput
        v-show="(form.allowedCurrencies ?? []).length > 0"
        label="Precio Carton"
        name="card_price"
        :error="errors.cardPrice"
        type="number"
        required
        :step="0.05"
        :min="0.35"
        :max="50"
        placeholder="Precio Cartón"
        col="col-sm-6 col-md-4 col-lg-3"
        inputGroup
        v-model="form.cardPrice"
      >
        <template #prepend>
          <span class="input-group-text">$</span>
        </template>

        <template #append>
          <VDropdown
            :shown="hasCardPriceByCurrencyErrors"
            :triggers="[]"
            placement="top"
            :autoHide="false"
            container="body"
            popperClass="popover-error"
          >
            <button
              class="btn btn-outline-secondary"
              type="button"
              :data-active="cardPriceByCurrencyActive ? 1 : 0"
              @click="toggleCtnPriceByCurrency"
              :disabled="!canAdjustCtnPriceByCurrency"
            >
              <i class="fa-solid fa-sliders"></i>
            </button>

            <template #popper>
              <div
                style="
                  padding: 4px 7px;
                  max-width: 260px;
                  font-size: 0.82rem;
                  white-space: pre-line;
                "
              >
                {{ cardPriceByCurrencyPopoverText }}
              </div>
            </template>
          </VDropdown>
        </template>
      </FormInput>

      <!-- Inputs por moneda (cuando activo) -->
      <div
        class="col-12"
        v-show="cardPriceByCurrencyActive && canAdjustCtnPriceByCurrency"
        style="margin-top: 0.5rem; margin-bottom: 1rem"
      >
        <div
          style="
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            padding: 1rem;
          "
        >
          <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="mb-0" style="font-size: 0.9rem; font-weight: 600">
              Ajustar precio de los cartones por moneda
            </h6>

            <button
              type="button"
              class="close"
              @click="cardPriceByCurrencyActive = false"
              aria-label="Cerrar"
            >
              <span aria-hidden="true">&times;</span>
            </button>
          </div>

          <div class="row">
            <template v-for="cur in form.allowedCurrencies ?? []" :key="String(cur)">
              <FormInput
                :name="`ctn_price_${String(cur)}`"
                :error="
                  errors.cardPriceByCurrency ? errors.cardPriceByCurrency[String(cur)] : undefined
                "
                type="number"
                :step="0.05"
                :min="0"
                :max="1000"
                :placeholder="`Precio Cartón ${getCurrencyLabel(cur)}`"
                col="col-6 col-md-3 mb-2"
                inputGroup
                inputGroupClass="input-group-sm"
                :modelValue="form.cardPriceByCurrency?.[String(cur)] ?? ''"
                @update:modelValue="updateCtnPriceForCurrency(cur, $event)"
              >
                <template #prepend>
                  <span class="input-group-text">{{ getCurrencyLabel(cur) }}</span>
                </template>
              </FormInput>
            </template>
          </div>
        </div>
      </div>

      <!-- Valor extra al bote (solo si prizePoolType > 1) -->
      <FormInput
        v-show="showAmountAddedPrizePool"
        label="Valor Fijo/Extra"
        name="amount_added_prize_pool"
        :error="errors.amountAddedPrizePool"
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

      <!-- Comisiones, jackpot y tiempo ficha -->
      <FormInput
        label="%Sala"
        name="room_commission"
        id="room-commn"
        type="number"
        required
        :step="0.05"
        :min="percentageLimits.roomCommn.min"
        :max="percentageLimits.roomCommn.max"
        col="col-sm-6 col-md-4 col-lg-3"
        v-model="roomCommn"
      />

      <FormInput
        label="%Jackpot"
        name="jackpot_pct"
        id="jackpot-pct"
        type="number"
        :step="0.5"
        :min="percentageLimits.jackpotPct.min"
        :max="percentageLimits.jackpotPct.max"
        col="col-sm-6 col-md-4 col-lg-3"
        v-model="jackpotPct"
      />

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
          <button
            class="btn btn-outline-secondary"
            type="button"
            :data-active="form.roundsConfigActive ? 1 : 0"
            @click="toggleRoundsConfig"
            :disabled="(form.rounds ?? 1) <= 1"
            title="Ajustar valores de las rondas"
          >
            <i class="fa-solid fa-sliders"></i>
          </button>
        </template>
      </FormInput>

      <!-- %Rondas (solo si rounds > 1) -->
      <FormInput
        v-show="showRoundsPct"
        label="%Rondas"
        name="rounds_pct"
        :error="errors.roundsPct"
        type="number"
        :required="showRoundsPct"
        :step="1"
        :min="percentageLimits.roundsPct.min"
        :max="percentageLimits.roundsPct.max"
        col="col-sm-6 col-md-4 col-lg-3"
        v-model="roundsPct"
      />

      <!-- Configuración avanzada de rondas -->
      <div
        class="col-12"
        v-show="form.roundsConfigActive && (form.rounds ?? 1) > 1"
        style="margin-top: 0.5rem; margin-bottom: 1rem"
      >
        <div
          style="
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            padding: 1rem;
          "
        >
          <div class="d-flex align-items-center mb-3">
            <h6 class="mb-0" style="font-size: 0.9rem; font-weight: 600; margin-right: 0.5rem">
              Ajustar valores de las rondas
            </h6>
            <div
              v-if="form.loadingRoundsConfig"
              class="spinner-border spinner-border-sm text-primary"
              role="status"
            >
              <span class="sr-only">Cargando...</span>
            </div>
          </div>
          <div class="row">
            <!-- Validación de suma (solo si hay más de una ronda) -->
            <div class="col-12 mb-2" v-if="totalRoundValues > 0 && showRoundsPct">
              <small :class="isRoundValuesValid ? 'text-success' : 'text-danger'">
                {{ isRoundValuesValid ? '✓' : '✗' }} Suma: {{ totalRoundValues }} /
                {{ roundsPct }}
              </small>
            </div>
            <template v-for="(round, index) in form.roundsConfig" :key="`round_${index}`">
              <div class="col-lg-6 col-12 mb-2">
                <div class="d-flex align-items-center" style="gap: 0.5rem">
                  <span class="badge badge-secondary" style="white-space: nowrap; flex-shrink: 0">
                    R{{ index + 1 }}
                  </span>

                  <!-- Tipo de premio -->
                  <select
                    :name="`rounds_prize_type[${index}]`"
                    class="form-control form-control-sm"
                    :value="round.prizeType"
                    @change="
                      updateRoundConfig(
                        index,
                        'prizeType',
                        ($event.target as HTMLSelectElement).value as any
                      )
                    "
                    style="flex: 0 1 300px"
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
                    class="form-control form-control-sm"
                    :value="round.bingoPattern"
                    @change="
                      updateRoundConfig(
                        index,
                        'bingoPattern',
                        ($event.target as HTMLSelectElement).value
                      )
                    "
                    style="flex: 1 1 auto; min-width: 130px"
                  >
                    <option value="" disabled>Patrón</option>
                    <option v-for="opt in bingoPatternOptions" :key="opt.value" :value="opt.value">
                      {{ opt.label }}
                    </option>
                  </select>

                  <!-- Valor del premio -->
                  <input
                    :name="`rounds_prize_value[${index}]`"
                    type="number"
                    class="form-control form-control-sm"
                    :class="{ 'is-invalid': !isRoundValuesValid && totalRoundValues > 0 }"
                    placeholder="Valor"
                    :value="round.prizeValue"
                    @input="
                      updateRoundConfig(
                        index,
                        'prizeValue',
                        ($event.target as HTMLInputElement).value
                      )
                    "
                    step="0.5"
                    min="0"
                    :max="Number(roundsPct) || percentageLimits.roundsPct.max"
                    style="flex: 0 1 100px"
                  />
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>

      <!-- %Bote Seriales -->
      <FormInput
        label="%Bote Seriales"
        name="serials_pct"
        :error="errors.serialsPct"
        type="number"
        required
        :step="0.25"
        :min="percentageLimits.serialsPct.min"
        :max="percentageLimits.serialsPct.max"
        placeholder="Ingrese valor"
        col="col-sm-6 col-md-4 col-lg-3"
        v-model="serialsPct"
      />

      <!-- #Max. Seriales -->
      <FormInput
        label="#Max. Seriales"
        name="num_serials"
        :error="errors.numSerials"
        type="number"
        required
        :step="1"
        :min="0"
        :max="30"
        placeholder="Ingrese la cantidad"
        col="col-sm-6 col-md-4 col-lg-3"
        v-model="form.numSerials"
      />

      <FormInput
        label="Tiempo Ficha"
        name="time_chip"
        :error="errors.timeChip"
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
    </div>
  </form>
</template>
