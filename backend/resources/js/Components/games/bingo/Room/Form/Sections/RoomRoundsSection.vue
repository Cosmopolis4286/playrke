<script setup lang="ts">
  import { ref, computed } from 'vue'
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'
  import {
    updateRoundConfig,
    updateRoundPriceByCurrency,
    getRoundMaxValue
  } from '../Composables/useRoomRounds'
  import { getCurrencyLabel } from '../Composables/useRoomCurrency'
  import { sanitizeNumericInput } from '../Utils/numericInput'
  import { BINGO_ROOM_AMOUNT_LIMITS, BINGO_ROOM_INT_LIMITS } from '@/js/composables/games/bingo/room/shared/bingoRoomFormSchema'
  import type { BingoRoomFormData, RoundConfig } from '@/js/types/games/bingo/room/bingo-room'
  import type { SelectOption } from '@/js/types/common/form/form'

  const roundPrizeTypeOptions: SelectOption[] = [
    { value: 'percentage', label: '%' },
    { value: 'fixed', label: 'Fijo' },
    { value: 'prize', label: 'P/S' }
  ]

  const props = defineProps<{
    modelValue: BingoRoomFormData
    errors: Record<string, any>
    bingoPatternOptions: SelectOption[]
    allowedCurrenciesOptions: SelectOption[]
    isSingleRound: boolean
    showRoundsPct: boolean
    availablePct: number
    percentageLimits: {
      roundsPct: { min: number; max: number }
    }
    totalRoundValues: number
    isRoundValuesValid: boolean
    roundsPct: string | number
    /** Sala con ventas: rondas, premios y %Rondas no se pueden cambiar (el backend lo rechaza). */
    locked?: boolean
  }>()

  const emit = defineEmits<{
    (e: 'update:modelValue', value: BingoRoomFormData): void
    (e: 'update:roundsPct', value: string | number): void
    (e: 'open-prize-modal', index: number): void
  }>()

  const roundsConfigActive = ref(false)

  /* ==========================================================================
   * Computed
   * ========================================================================== */

  const hasRoundsConfigErrors = computed(() => {
    const err = props.errors.roundsConfig
    return err && Object.keys(err).length > 0 && !roundsConfigActive.value
  })

  const roundsConfigPopoverText = computed(() => {
    const err = props.errors.roundsConfig as Record<string, string> | undefined
    if (!err) return ''
    return 'Ajustar valores'
  })

  function getRoundFieldError(
    index: number,
    field: 'bingoPattern' | 'prizeValue'
  ): string | undefined {
    const err = props.errors.roundsConfig as Record<string, string> | undefined
    return err?.[`${index}_${field}`]
  }

  function getRoundCurrencyError(index: number, currency: string | number): string | undefined {
    const err = props.errors.roundsConfig as Record<string, string> | undefined
    return err?.[`${index}_prizeValue_${String(currency)}`]
  }

  function toggleRoundsConfig() {
    if ((props.modelValue.rounds ?? 1) < 1) return
    roundsConfigActive.value = !roundsConfigActive.value
  }

  function onUpdateRoundConfig(
    index: number,
    field: keyof RoundConfig,
    value: RoundConfig[keyof RoundConfig]
  ) {
    const updated = updateRoundConfig(
      props.modelValue.roundsConfig ?? [],
      index,
      field,
      value,
      props.modelValue.allowedCurrencies ?? [],
      Number(props.roundsPct) || 0
    )
    emit('update:modelValue', { ...props.modelValue, roundsConfig: updated })
  }

  function onUpdateRoundPriceByCurrency(
    roundIndex: number,
    currency: string | number,
    value: string | number | null
  ) {
    const updated = updateRoundPriceByCurrency(
      props.modelValue.roundsConfig ?? [],
      roundIndex,
      currency,
      value
    )
    emit('update:modelValue', { ...props.modelValue, roundsConfig: updated })
  }

  function roundMax(index: number): number {
    return getRoundMaxValue(
      index,
      props.modelValue.roundsConfig ?? [],
      Number(props.roundsPct) || 0,
      props.percentageLimits.roundsPct.max
    )
  }

  /**
   * % de una ronda: al escribir solo se sanea el DOM (tope = lo que queda de
   * %Rondas); se emite recién al confirmar (change), porque cada emisión
   * redistribuye el resto entre las demás rondas y haría saltar sus valores
   * a mitad de la escritura.
   */
  function onRoundPctInput(index: number, event: Event, commit = false) {
    const limits = { ...BINGO_ROOM_AMOUNT_LIMITS.roundPrizePct, max: roundMax(index) || BINGO_ROOM_AMOUNT_LIMITS.roundPrizePct.max }
    const value = sanitizeNumericInput(event, limits, commit)
    if (commit) onUpdateRoundConfig(index, 'prizeValue', value)
  }

  function onRoundFixedInput(index: number, cur: string | number, event: Event, commit = false) {
    const value = sanitizeNumericInput(event, BINGO_ROOM_AMOUNT_LIMITS.roundPrizeFixed, commit)
    onUpdateRoundPriceByCurrency(index, cur, value)
  }

  /** Mensajes de error de rondas en texto ("R2: …") — los inputs solo pintan el borde rojo. */
  const roundsConfigErrorList = computed<string[]>(() => {
    const err = props.errors.roundsConfig as Record<string, string> | undefined
    if (!err) return []
    const list = Object.entries(err).map(([key, msg]) => {
      if (key === 'sum') return msg
      const idx = Number(key.split('_')[0])
      const cur = key.split('_prizeValue_')[1]
      return `R${idx + 1}${cur ? ` (${currencyLabel(cur)})` : ''}: ${msg}`
    })
    return [...new Set(list)]
  })

  function currencyLabel(id: string | number) {
    return getCurrencyLabel(id, props.allowedCurrenciesOptions)
  }

  /**
   * Resumen en vivo de la distribución del bote — los 4 porcentajes que se
   * validan juntos contra 100% (ver percentageSum en validateBingoRoomForm /
   * la misma fórmula replicada en RoomOptionsSection.vue) viven repartidos en
   * 3 tabs distintos (%Sala y %Jackpot en "Datos básicos", %Rondas aquí,
   * %Seriales en "Más/Opcional"), así que el usuario nunca los ve juntos
   * hasta que la suma falla. Este resumen los muestra siempre, sin necesidad
   * de saltar entre tabs.
   */
  const distributionTotal = computed(() =>
    (Number(props.modelValue.roomCommn) || 0) +
    (Number(props.modelValue.roundsPct) || 0) +
    (props.modelValue.useSerials ? (Number(props.modelValue.serialsPct) || 0) : 0) +
    (Number(props.modelValue.jackpotPct) || 0)
  )
</script>

<template>
  <div style="display: contents">
    <div v-if="locked" class="col-12 mb-2">
      <div class="alert alert-warning mb-0 px-3 py-2" style="font-size: 0.82rem">
        <i class="fa-solid fa-lock"></i>
        La sala ya tiene cartones vendidos: las rondas, sus premios y el %Rondas no se pueden cambiar.
      </div>
    </div>

    <!-- Distribución del bote: %Sala + %Rondas + %Seriales + %Jackpot, los 4
         porcentajes que se suman entre sí, mostrados juntos aunque vivan en
         tabs distintos del formulario. -->
    <div class="col-12" style="margin-bottom: 0.75rem">
      <div
        class="d-flex align-items-center flex-wrap"
        :class="distributionTotal > 100 ? 'text-danger' : 'text-muted'"
        style="gap: 0.3rem; font-size: 0.78rem; padding: 0.4rem 0.65rem; border: 1px solid var(--ds-border); border-radius: 0.25rem; background: var(--ds-surface-2)"
      >
        <i class="fa-solid fa-chart-pie" style="opacity: 0.7"></i>
        <span>Distribución:</span>
        <span>%Sala <strong>{{ modelValue.roomCommn ?? 0 }}</strong></span>
        <span>+ %Rondas <strong>{{ modelValue.roundsPct ?? 0 }}</strong></span>
        <span v-if="modelValue.useSerials">+ %Seriales <strong>{{ modelValue.serialsPct ?? 0 }}</strong></span>
        <span>+ %Jackpot <strong>{{ modelValue.jackpotPct ?? 0 }}</strong></span>
        <span>= <strong>{{ distributionTotal.toFixed(2) }}</strong> / 100%</span>
      </div>
    </div>

    <!-- Rondas — v1: fijo en 1 (varias rondas mezclan las bolas de todas las
         rondas en la detección de bingo). Solo queda editable si una sala
         existente trae otro valor, para poder bajarlo a 1 al guardarla. -->
    <FormInput
      label="Rondas"
      name="rounds"
      :error="errors.rounds"
      :disabled="locked || Number(modelValue.rounds) === BINGO_ROOM_INT_LIMITS.rounds.max"
      type="number"
      integer
      required
      :step="1"
      :min="BINGO_ROOM_INT_LIMITS.rounds.min"
      :max="BINGO_ROOM_INT_LIMITS.rounds.max"
      hint="Por ahora las salas admiten una sola ronda."
      col="col-sm-6 col-md-4 col-lg-3"
      inputGroup
      :modelValue="modelValue.rounds"
      @update:modelValue="emit('update:modelValue', { ...modelValue, rounds: $event })"
    >
      <template #append>
        <VDropdown
          :shown="hasRoundsConfigErrors && !!roundsPct && !errors.roundsPct"
          :triggers="[]"
          placement="top-end"
          :autoHide="false"
          container="body"
          popperClass="popover-error"
        >
          <button
            class="btn btn-outline-secondary"
            type="button"
            :data-active="roundsConfigActive ? 1 : 0"
            @click="toggleRoundsConfig"
            :disabled="(modelValue.rounds ?? 1) < 1 || !roundsPct"
          >
            <i class="fa-solid fa-sliders"></i>
          </button>
          <template #popper>
            <div
              style="
                padding: 4px 6.5px;
                max-width: 245px;
                font-size: 0.77rem;
                white-space: pre-line;
              "
            >
              {{ roundsConfigPopoverText }}
            </div>
          </template>
        </VDropdown>
      </template>
    </FormInput>

    <!-- %Rondas -->
    <FormInput
      v-show="showRoundsPct"
      label="%Rondas"
      name="rounds_pct"
      :error="errors.roundsPct"
      :disabled="locked"
      type="number"
      :required="showRoundsPct"
      :step="1"
      :min="isSingleRound ? 0 : percentageLimits.roundsPct.min"
      :max="isSingleRound ? 100 : availablePct"
      :readonly="isSingleRound"
      col="col-sm-6 col-md-4 col-lg-3"
      :modelValue="roundsPct"
      @update:modelValue="emit('update:roundsPct', $event)"
    />

    <!-- Configuración avanzada de rondas -->
    <div class="col-12" v-show="roundsConfigActive" style="margin-top: 0.5rem; margin-bottom: 1rem">
      <div
        style="
          background-color: var(--ds-surface-2);
          border: 1px solid var(--ds-border);
          border-radius: 0.25rem;
          padding: 1rem;
        "
      >
        <div class="d-flex align-items-center justify-content-between mb-3">
          <div class="d-flex align-items-center">
            <h6 class="mb-0" style="font-size: 0.9rem; font-weight: 600">
              Ajustar valores de las rondas
            </h6>
            <div class="ml-2" v-if="totalRoundValues > 0 && showRoundsPct">
              <small :class="isRoundValuesValid ? 'text-success' : 'text-danger'">
                {{ isRoundValuesValid ? '✓' : '✗' }} Suma: {{ totalRoundValues }} / {{ roundsPct }}
              </small>
            </div>
          </div>
          <button
            type="button"
            class="close"
            @click="roundsConfigActive = false"
            aria-label="Cerrar"
          >
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="row">
          <template v-for="(round, index) in modelValue.roundsConfig" :key="`round_${index}`">
            <div class="col-lg-6 col-12 mb-2">
              <div class="d-flex align-items-center" style="gap: 0.5rem">
                <span class="badge badge-secondary" style="white-space: nowrap; flex-shrink: 0">
                  R{{ index + 1 }}
                </span>

                <!-- Tipo de premio -->
                <select
                  :name="`rounds_prize_type[${index}]`"
                  class="form-control form-control-sm"
                  :disabled="locked"
                  :value="round.prizeType"
                  @change="
                    onUpdateRoundConfig(
                      index,
                      'prizeType',
                      ($event.target as HTMLSelectElement).value as any
                    )
                  "
                  style="flex: 0 0 58px; min-width: 0; padding-left: 0.3rem; padding-right: 0.1rem"
                >
                  <option v-for="opt in roundPrizeTypeOptions" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </option>
                </select>

                <!-- Patrón de bingo -->
                <FormSelect
                  :name="`rounds_bingo_patterns[${index}]`"
                  :disabled="locked"
                  label="Patrón"
                  hide-label
                  inline-error
                  col=""
                  :error="getRoundFieldError(index, 'bingoPattern')"
                  :options="bingoPatternOptions"
                  optionValue="value"
                  optionLabel="label"
                  placeholder="Patrón"
                  inputGroup
                  inputGroupClass="input-group-sm"
                  :modelValue="round.bingoPattern"
                  @update:modelValue="onUpdateRoundConfig(index, 'bingoPattern', $event)"
                  style="flex: 1 1 auto; min-width: 110px; margin-bottom: 0"
                />

                <!-- Valor % -->
                <input
                  v-if="round.prizeType === 'percentage'"
                  :name="`rounds_prize_value[${index}]`"
                  type="text"
                  inputmode="decimal"
                  autocomplete="off"
                  :class="[
                    'form-control form-control-sm',
                    {
                      'is-invalid':
                        !!getRoundFieldError(index, 'prizeValue') ||
                        (!isRoundValuesValid && totalRoundValues > 0)
                    }
                  ]"
                  placeholder="%"
                  :title="getRoundFieldError(index, 'prizeValue') ?? `Máximo disponible: ${roundMax(index)}%`"
                  :value="isSingleRound ? roundsPct : round.prizeValue"
                  :readonly="isSingleRound || locked"
                  @input="!isSingleRound && onRoundPctInput(index, $event)"
                  @change="!isSingleRound && onRoundPctInput(index, $event, true)"
                  style="flex: 0 0 72px"
                />

                <!-- Botón P/S -->
                <div
                  v-else-if="round.prizeType === 'prize'"
                  style="position: relative; flex: 1 1 auto; min-width: 80px; max-width: 140px"
                >
                  <button
                    type="button"
                    :class="[
                      'btn btn-sm',
                      round.prizeId
                        ? 'btn-success'
                        : getRoundFieldError(index, 'prizeValue')
                          ? 'btn-danger'
                          : 'btn-outline-secondary'
                    ]"
                    style="
                      overflow: hidden;
                      text-overflow: ellipsis;
                      white-space: nowrap;
                      font-size: 0.75rem;
                      width: 100%;
                    "
                    :disabled="locked"
                    @click="emit('open-prize-modal', index)"
                    :title="round.prizeName ?? 'Seleccionar premio'"
                  >
                    <i class="fa-solid fa-gift" style="font-size: 0.7rem"></i>
                    {{ round.prizeName ? round.prizeName : 'Seleccionar' }}
                  </button>
                  <small
                    v-if="round.prizeId && round.prizeValue"
                    style="
                      position: absolute;
                      top: 107%;
                      left: 0;
                      right: 0;
                      font-size: 0.7rem;
                      color: #198754;
                      font-weight: 600;
                      text-align: center;
                      line-height: 1.2;
                      white-space: nowrap;
                    "
                  >
                    ${{ Number(round.prizeValue).toFixed(2) }} USD
                  </small>
                </div>
              </div>

              <!-- Valores fijos por moneda -->
              <div
                v-if="round.prizeType === 'fixed'"
                class="d-flex mt-1 flex-wrap"
                style="gap: 0.4rem; padding-left: 2rem"
              >
                <template
                  v-for="cur in modelValue.allowedCurrencies ?? []"
                  :key="`r${index}_cur_${cur}`"
                >
                  <div class="input-group input-group-sm" style="width: auto; flex: 0 0 140px">
                    <div class="input-group-prepend">
                      <span class="input-group-text">{{ currencyLabel(cur) }}</span>
                    </div>
                    <input
                      :name="`rounds_prize_value_currency[${index}][${cur}]`"
                      type="text"
                      inputmode="decimal"
                      autocomplete="off"
                      :class="[
                        'form-control form-control-sm',
                        { 'is-invalid': !!getRoundCurrencyError(index, cur) }
                      ]"
                      placeholder="0.00"
                      :title="getRoundCurrencyError(index, cur)"
                      :readonly="locked"
                      :value="round.prizeValueByCurrency?.[String(cur)] ?? ''"
                      @input="onRoundFixedInput(index, cur, $event)"
                      @change="onRoundFixedInput(index, cur, $event, true)"
                    />
                  </div>
                </template>
              </div>
            </div>
          </template>
        </div>

        <ul v-if="roundsConfigErrorList.length" class="text-danger mb-0 mt-2 pl-3" style="font-size: 0.75rem">
          <li v-for="msg in roundsConfigErrorList" :key="msg">{{ msg }}</li>
        </ul>
      </div>
    </div>

  </div>
</template>
