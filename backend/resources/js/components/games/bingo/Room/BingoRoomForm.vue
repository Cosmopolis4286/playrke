<script setup lang="ts" name="GameBingoRoomForm">
  import { computed, watch, ref } from 'vue'

  // Components
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'
  import FormSwitch from '@/js/Components/Common/Form/FormSwitch.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import ItemSearchPicker from '@/js/Components/Common/Form/ItemSearchPicker.vue'
  // Composables
  import { useBingoRoomForm } from '@/js/composables/games/bingo/room/useBingoRoomForm'
  import backendClient from '@/js/plugins/axios'
  // Types
  import type { BingoRoomFormData, RoundConfig } from '@/js/types/games/bingo/room/bingo-room'
  import type {
    BingoRoomAutomaticFormData,
    DayOfWeek
  } from '@/js/types/games/bingo/room/automatic/bingo-room-automatic'
  import type { PrizeSearchResult } from '@/js/types/prizes/prize'

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
    { value: 'percentage', label: '%' },
    { value: 'fixed', label: 'Fijo' },
    { value: 'prize', label: 'P/S' }
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
    modelValue: BingoRoomFormData | BingoRoomAutomaticFormData
  }>()

  /* ==========================================================================
   * Emits
   * ========================================================================== */

  const emit = defineEmits<{
    (e: 'submit'): void
    (e: 'update:modelValue', value: BingoRoomFormData | BingoRoomAutomaticFormData): void
  }>()

  /* ==========================================================================
   * Form logic (computed v-model)
   * ========================================================================== */

  const {
    form,
    errors,
    isValid,
    roomCommn,
    jackpotPct,
    roundsPct,
    serialsPct,
    availablePct,
    percentageLimits
  } = useBingoRoomForm(
    () => props.modelValue,
    emit,
    () => props.automaticRoom ?? false
  )

  const DAYS_OPTIONS: { value: DayOfWeek; label: string }[] = [
    { value: 'monday', label: 'Lunes' },
    { value: 'tuesday', label: 'Martes' },
    { value: 'wednesday', label: 'Miércoles' },
    { value: 'thursday', label: 'Jueves' },
    { value: 'friday', label: 'Viernes' },
    { value: 'saturday', label: 'Sábado' },
    { value: 'sunday', label: 'Domingo' }
  ]

  const cardPriceByCurrencyActive = ref(false)
  const useBaseUsd = ref(true)
  const roundsConfigActive = ref(false)

  // Modal P/S: índice de la ronda que está seleccionando prize
  const prizeModalRoundIndex = ref<number | null>(null)
  const showPrizeModal = ref(false)
  // Buffer temporal de selección en el modal (máximo 1 item)
  const prizeModalSelected = ref<PrizeSearchResult[]>([])

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
  const showRoundsPct = computed(() => (form.value.rounds ?? 1) >= 1)

  /**
   * Cuando hay exactamente 1 ronda, %Rondas es readonly (= disponible)
   */
  const isSingleRound = computed(() => Number(form.value.rounds ?? 1) === 1)

  /**
   * Suma total solo de rondas con tipo 'percentage' (las P/S y fixed no cuentan contra %Rondas)
   */
  const totalRoundValues = computed(() => {
    return (form.value.roundsConfig ?? []).reduce((sum, round) => {
      if (round.prizeType !== 'percentage') return sum
      return sum + (Number(round.prizeValue) || 0)
    }, 0)
  })

  /**
   * Max disponible para una ronda = %Rondas - suma de las demás rondas
   */
  function getRoundMaxValue(index: number): number {
    const limit = Number(form.value.roundsPct) || percentageLimits.roundsPct.max
    const othersSum = (form.value.roundsConfig ?? []).reduce((sum, round, i) => {
      if (i === index) return sum
      return sum + (Number(round.prizeValue) || 0)
    }, 0)
    return Math.max(0, limit - othersSum)
  }

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

  function toggleCtnPriceByCurrency() {
    if (!canAdjustCtnPriceByCurrency.value) return
    cardPriceByCurrencyActive.value = !cardPriceByCurrencyActive.value
  }

  const hasCardPriceByCurrencyErrors = computed(() => {
    const err = errors.value.cardPriceByCurrency
    return err && Object.keys(err).length > 0 && !cardPriceByCurrencyActive.value
  })

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

  const hasRoundsConfigErrors = computed(() => {
    const err = errors.value.roundsConfig
    return err && Object.keys(err).length > 0 && !roundsConfigActive.value
  })

  const roundsConfigPopoverText = computed(() => {
    const err = errors.value.roundsConfig as Record<string, string> | undefined
    if (!err) return ''
    return 'Ajustar valores'
  })

  /**
   * Error por campo individual de ronda
   */
  function getRoundFieldError(
    index: number,
    field: 'bingoPattern' | 'prizeValue'
  ): string | undefined {
    const err = errors.value.roundsConfig as Record<string, string> | undefined
    return err?.[`${index}_${field}`]
  }

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

      // FULL HOUSE es el patrón por defecto cuando hay 1 sola ronda
      const fullHouseId =
        props.bingoPatternOptions.find(o => o.label.toUpperCase() === 'FULL HOUSE')?.value ?? null

      for (let i = 0; i < count; i++) {
        const defaultPattern = count === 1 ? fullHouseId : null
        updated.push(
          current[i] ?? {
            prizeType: 'percentage',
            bingoPattern: defaultPattern,
            prizeValue: null
          }
        )
      }

      // Si hay 1 sola ronda, forzar FULL HOUSE en R1 (aunque ya existiera)
      if (count === 1 && fullHouseId !== null) {
        updated[0] = { ...updated[0], bingoPattern: fullHouseId }
      }

      // Redistribuir equitativamente entre rondas % tras cambio de cantidad
      const total = Number(form.value.roundsPct) || 0
      const pctIndices = updated.reduce<number[]>((acc, r, i) => {
        if (r.prizeType === 'percentage') acc.push(i)
        return acc
      }, [])
      if (pctIndices.length > 0 && total > 0) {
        const perRound = Math.round((total / pctIndices.length) * 100) / 100
        pctIndices.forEach(i => {
          updated[i] = { ...updated[i], prizeValue: perRound }
        })
      }

      form.value = { ...form.value, roundsConfig: updated }
    },
    { immediate: true }
  )

  /**
   * Cuando cambia roundsPct (con múltiples rondas):
   * 1. Distribuir equitativamente entre rondas con tipo 'percentage'
   * 2. Ajustar %Sala para que la suma siempre sea 100
   */
  watch(
    () => form.value.roundsPct,
    newPct => {
      const total = Number(newPct) || 0
      const configs = form.value.roundsConfig ?? []
      const pctCount = configs.filter(r => r.prizeType === 'percentage').length

      // Redistribuir entre rondas %
      let nextForm = { ...form.value }
      if (pctCount > 0) {
        const perRound = total > 0 ? Math.round((total / pctCount) * 100) / 100 : null
        const updated = configs.map(r =>
          r.prizeType === 'percentage' ? { ...r, prizeValue: perRound } : r
        )
        nextForm = { ...nextForm, roundsConfig: updated }
      }

      // Con múltiples rondas: %Sala absorbe el restante para sumar 100
      const rounds = Number(form.value.rounds) || 1
      if (rounds > 1) {
        const jk = Number(nextForm.jackpotPct) || 0
        const ser = Number(nextForm.serialsPct) || 0
        const newCommn = Math.round((100 - total - jk - ser) * 100) / 100
        const clamped = Math.min(
          percentageLimits.roomCommn.max,
          Math.max(percentageLimits.roomCommn.min, newCommn)
        )
        nextForm = { ...nextForm, roomCommn: clamped }
      }

      form.value = nextForm
    }
  )

  /**
   * Cuando hay 1 sola ronda, %Rondas = disponible automáticamente
   */
  watch(
    () => [form.value.rounds, availablePct.value] as const,
    ([newRounds, avail]) => {
      if (Number(newRounds) !== 1) return
      if (Number(form.value.roundsPct) === avail) return
      form.value = { ...form.value, roundsPct: avail }
    }
  )

  /* ==========================================================================
   * Métodos
   * ========================================================================== */

  function toggleRoundsConfig() {
    if ((form.value.rounds ?? 1) < 1) return
    roundsConfigActive.value = !roundsConfigActive.value
  }

  function updateRoundConfig(
    index: number,
    field: keyof RoundConfig,
    value: RoundConfig[keyof RoundConfig]
  ) {
    const configs = [...(form.value.roundsConfig ?? [])]
    const updated = { ...configs[index], [field]: value }

    if (field === 'prizeType') {
      if (value === 'percentage') {
        // Al cambiar a %, limpiar fijo y redistribuir equitativamente incluyendo esta ronda
        updated.prizeValueByCurrency = undefined
        configs[index] = updated
        redistributePercentageRounds(configs)
        form.value = { ...form.value, roundsConfig: configs }
        return
      } else if (value === 'fixed') {
        // Al cambiar a fijo, limpiar prizeValue e inicializar mapa de monedas
        updated.prizeValue = null
        updated.prizeValueByCurrency = (form.value.allowedCurrencies ?? []).reduce<
          Record<string, null>
        >((acc, cur) => {
          acc[String(cur)] = null
          return acc
        }, {})
        configs[index] = updated
        redistributePercentageRounds(configs)
        form.value = { ...form.value, roundsConfig: configs }
        return
      } else {
        // Al cambiar a otro tipo (prize), limpiar ambos y redistribuir % restantes
        updated.prizeValue = null
        updated.prizeValueByCurrency = undefined
        configs[index] = updated
        redistributePercentageRounds(configs)
        form.value = { ...form.value, roundsConfig: configs }
        return
      }
    }

    if (field === 'prizeValue' && updated.prizeType === 'percentage') {
      // El usuario editó el valor de esta ronda %: redistribuir el resto entre las demás rondas %
      const total = Number(form.value.roundsPct) || 0
      const thisVal = Math.min(Number(value) || 0, total)
      updated.prizeValue = thisVal
      configs[index] = updated

      const others = configs.reduce<number[]>((acc, r, i) => {
        if (i !== index && r.prizeType === 'percentage') acc.push(i)
        return acc
      }, [])

      if (others.length > 0) {
        const remaining = Math.max(0, total - thisVal)
        const perOther = Math.round((remaining / others.length) * 100) / 100
        others.forEach(i => {
          configs[i] = { ...configs[i], prizeValue: perOther > 0 ? perOther : null }
        })
      }

      form.value = { ...form.value, roundsConfig: configs }
      return
    }

    if (
      field === 'bingoPattern' &&
      updated.prizeType === 'percentage' &&
      updated.prizeValue === null
    ) {
      configs[index] = updated
      redistributePercentageRounds(configs)
      form.value = { ...form.value, roundsConfig: configs }
      return
    }

    configs[index] = updated
    form.value = { ...form.value, roundsConfig: configs }
  }

  /**
   * Distribuye equitativamente roundsPct entre todas las rondas con tipo 'percentage'
   */
  function redistributePercentageRounds(configs: RoundConfig[]) {
    const total = Number(form.value.roundsPct) || 0
    const pctIndices = configs.reduce<number[]>((acc, r, i) => {
      if (r.prizeType === 'percentage') acc.push(i)
      return acc
    }, [])
    if (pctIndices.length === 0) return
    const perRound = total > 0 ? Math.round((total / pctIndices.length) * 100) / 100 : null
    pctIndices.forEach(i => {
      configs[i] = { ...configs[i], prizeValue: perRound }
    })
  }

  /**
   * Actualiza el valor fijo de una ronda para una moneda específica
   */
  function updateRoundPriceByCurrency(
    roundIndex: number,
    currency: string | number,
    value: string | number | null
  ) {
    const configs = [...(form.value.roundsConfig ?? [])]
    const key = String(currency)
    const current = { ...(configs[roundIndex].prizeValueByCurrency ?? {}) }
    const parsed = value === null || value === '' ? null : Number(value)
    current[key] = isNaN(Number(parsed)) ? null : (parsed as number)
    configs[roundIndex] = { ...configs[roundIndex], prizeValueByCurrency: current }
    form.value = { ...form.value, roundsConfig: configs }
  }

  /**
   * Obtiene el error de prizeValueByCurrency para una ronda y moneda
   */
  function getRoundCurrencyError(index: number, currency: string | number): string | undefined {
    const err = errors.value.roundsConfig as Record<string, string> | undefined
    return err?.[`${index}_prizeValue_${String(currency)}`]
  }

  /* ==========================================================================
   * Modal P/S — selección de prize para ronda
   * ========================================================================== */

  async function searchPrizes(q: string): Promise<PrizeSearchResult[]> {
    const { data } = await backendClient.get<{ data: PrizeSearchResult[] }>('/prizes/search', {
      params: { q }
    })
    return data.data ?? []
  }

  function openPrizeModal(index: number) {
    prizeModalRoundIndex.value = index
    const current = form.value.roundsConfig?.[index]
    // Pre-cargar el prize ya seleccionado si existe
    prizeModalSelected.value = current?.prizeId
      ? [
          {
            id: current.prizeId,
            name: current.prizeName ?? '',
            code: '',
            price: current.prizeValue ?? 0,
            reward_type: 'PRODUCT' as const
          }
        ]
      : []
    showPrizeModal.value = true
  }

  function confirmPrizeSelection(): boolean {
    const idx = prizeModalRoundIndex.value
    if (idx === null) return false
    const selected = prizeModalSelected.value[0] ?? null
    const configs = [...(form.value.roundsConfig ?? [])]
    configs[idx] = {
      ...configs[idx],
      prizeId: selected ? selected.id : null,
      prizeName: selected ? selected.name : null,
      prizeValue: selected ? selected.price : null
    }
    form.value = { ...form.value, roundsConfig: configs }
    prizeModalRoundIndex.value = null
    return true
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
      <!-- Fecha (solo salas normales) -->
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

      <!-- Días de publicación (solo salas automáticas) -->
      <FormSelect
        v-if="automaticRoom"
        label="Días de publicación"
        name="days"
        :error="(errors as any).days"
        required
        multiple
        placeholder="Seleccionar días"
        :options="DAYS_OPTIONS"
        optionValue="value"
        optionLabel="label"
        col="col-sm-6"
        :modelValue="(form as BingoRoomAutomaticFormData).days ?? []"
        @update:modelValue="form = { ...form, days: $event } as BingoRoomAutomaticFormData"
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
        inputGroup
        v-model="form.allowedCurrencies"
      >
        <template #append>
          <VDropdown
            :shown="hasCardPriceByCurrencyErrors"
            :triggers="[]"
            placement="top-end"
            :autoHide="false"
            container="body"
            popperClass="popover-error"
          >
            <button
              class="btn btn-outline-secondary"
              type="button"
              :data-active="cardPriceByCurrencyActive ? 1 : 0"
              :disabled="!canAdjustCtnPriceByCurrency"
              @click="toggleCtnPriceByCurrency"
              title="Ajustar precios por moneda"
            >
              <i class="fa-solid fa-sliders"></i>
            </button>
            <template #popper>
              <div style="padding: 4px 6.5px; max-width: 245px; font-size: 0.79rem; white-space: pre-line">
                Configuración pendiente
              </div>
            </template>
          </VDropdown>
        </template>
      </FormSelect>

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

      <!-- Panel de precios de cartones -->
      <div class="col-12" v-show="cardPriceByCurrencyActive" style="margin-bottom: 1rem">
        <div style="border: 1px solid #dee2e6; border-radius: 0.3rem; padding: 0.6rem 0.85rem">

          <!-- Cabecera -->
          <div class="d-flex align-items-center justify-content-between mb-1">
            <div class="d-flex align-items-center" style="gap: 0.4rem">
              <i class="fa-solid fa-coins" style="font-size: 0.75rem; color: #6c757d"></i>
              <span style="font-size: 0.8rem; font-weight: 600; color: #343a40">Precio de cartones</span>
              <span style="font-size: 0.75rem; color: #6c757d">— Base USD + ajuste por moneda</span>
            </div>
            <div class="d-flex align-items-center" style="gap: 0.75rem">
              <!-- Switch: usar precio base USD -->
              <div class="d-flex align-items-center" style="gap: 0.35rem">
                <span style="font-size: 0.74rem; color: #6c757d; white-space: nowrap">Usar Base USD</span>
                <div
                  class="custom-control custom-switch"
                  style="padding-left: 2.1rem; margin-bottom: 0"
                >
                  <input
                    type="checkbox"
                    class="custom-control-input"
                    id="useBaseUsdSwitch"
                    v-model="useBaseUsd"
                  />
                  <label class="custom-control-label" for="useBaseUsdSwitch" style="cursor: pointer" />
                </div>
              </div>
              <button type="button" class="close" style="font-size: 1rem; line-height: 1" @click="cardPriceByCurrencyActive = false" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
          </div>

          <div style="border-top: 1px solid #e9ecef; margin: 0.4rem 0 0.55rem" />

          <!-- Fila única de campos -->
          <div class="d-flex flex-wrap align-items-start" style="gap: 0.5rem">

            <!-- Precio USD + Redondeo (solo si useBaseUsd) -->
            <template v-if="useBaseUsd">
              <div>
                <div class="input-group input-group-sm" style="width: auto; flex: 0 0 130px">
                  <div class="input-group-prepend">
                    <span class="input-group-text" title="Precio base en USD">USD</span>
                  </div>
                  <input
                    name="card_price_usd"
                    type="number"
                    :class="['form-control', { 'is-invalid': errors.cardPriceUsd }]"
                    placeholder="0.00"
                    step="0.05"
                    min="0.5"
                    max="500"
                    :value="form.cardPriceUsd ?? ''"
                    @change="form = { ...form, cardPriceUsd: Number(($event.target as HTMLInputElement).value) || null }"
                  />
                </div>
                <small v-if="errors.cardPriceUsd" class="text-danger" style="font-size: 0.72rem; display: block; margin-top: 0.15rem">{{ errors.cardPriceUsd }}</small>
              </div>

              <div style="color: #ced4da; font-size: 0.85rem; margin-top: 0.35rem">·</div>

              <div>
                <div class="input-group input-group-sm" style="width: auto; flex: 0 0 155px">
                  <div class="input-group-prepend">
                    <span class="input-group-text" title="Incremento de redondeo para la tasa de cambio">
                      <i class="fa-solid fa-rotate" style="font-size: 0.7rem"></i>
                    </span>
                  </div>
                  <input
                    name="usd_exchange_round_step"
                    type="number"
                    :class="['form-control', { 'is-invalid': errors.usdExchangeRoundStep }]"
                    placeholder="Redondeo opc."
                    step="0.01"
                    min="0.01"
                    max="100"
                    :value="form.usdExchangeRoundStep ?? ''"
                    @change="form = { ...form, usdExchangeRoundStep: Number(($event.target as HTMLInputElement).value) || null }"
                  />
                </div>
                <small v-if="errors.usdExchangeRoundStep" class="text-danger" style="font-size: 0.72rem; display: block; margin-top: 0.15rem">{{ errors.usdExchangeRoundStep }}</small>
              </div>
            </template>

            <!-- Precios por moneda (solo si NO useBaseUsd y hay más de una moneda) -->
            <template v-if="!useBaseUsd && canAdjustCtnPriceByCurrency">
              <template v-for="cur in form.allowedCurrencies ?? []" :key="String(cur)">
                <div>
                  <div class="input-group input-group-sm" style="width: auto; flex: 0 0 130px">
                    <div class="input-group-prepend">
                      <span
                        class="input-group-text"
                        :class="{ 'border-danger text-danger': errors.cardPriceByCurrency?.[String(cur)] }"
                      >{{ getCurrencyLabel(cur) }}</span>
                    </div>
                    <input
                      :name="`ctn_price_${String(cur)}`"
                      type="number"
                      :class="['form-control', { 'is-invalid': errors.cardPriceByCurrency?.[String(cur)] }]"
                      placeholder="0.00"
                      step="0.05"
                      min="0"
                      max="1000"
                      :value="form.cardPriceByCurrency?.[String(cur)] ?? ''"
                      @change="updateCtnPriceForCurrency(cur, ($event.target as HTMLInputElement).value)"
                    />
                  </div>
                  <small v-if="errors.cardPriceByCurrency?.[String(cur)]" class="text-danger" style="font-size: 0.72rem; display: block; margin-top: 0.15rem">{{ errors.cardPriceByCurrency[String(cur)] }}</small>
                </div>
              </template>
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
              :disabled="(form.rounds ?? 1) < 1 || !roundsPct"
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
        type="number"
        :required="showRoundsPct"
        :step="1"
        :min="isSingleRound ? 0 : percentageLimits.roundsPct.min"
        :max="isSingleRound ? 100 : availablePct"
        :readonly="isSingleRound"
        col="col-sm-6 col-md-4 col-lg-3"
        v-model="roundsPct"
      />

      <!-- Configuración avanzada de rondas -->
      <div
        class="col-12"
        v-show="roundsConfigActive"
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
            <div class="d-flex align-items-center">
              <h6 class="mb-0" style="font-size: 0.9rem; font-weight: 600">
                Ajustar valores de las rondas
              </h6>

              <!-- Validación de suma (solo si hay más de una ronda) -->
              <div class="ml-2" v-if="totalRoundValues > 0 && showRoundsPct">
                <small :class="isRoundValuesValid ? 'text-success' : 'text-danger'">
                  {{ isRoundValuesValid ? '✓' : '✗' }} Suma: {{ totalRoundValues }} /
                  {{ roundsPct }}
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
                    style="
                      flex: 0 0 58px;
                      min-width: 0;
                      padding-left: 0.3rem;
                      padding-right: 0.1rem;
                    "
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
                  <FormSelect
                    :name="`rounds_bingo_patterns[${index}]`"
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
                    @update:modelValue="updateRoundConfig(index, 'bingoPattern', $event)"
                    style="flex: 1 1 auto; min-width: 110px; margin-bottom: 0"
                  />

                  <!-- Valor de Ronda -->
                  <input
                    v-if="round.prizeType === 'percentage'"
                    :name="`rounds_prize_value[${index}]`"
                    type="number"
                    :class="[
                      'form-control form-control-sm',
                      {
                        'is-invalid':
                          !!getRoundFieldError(index, 'prizeValue') ||
                          (!isRoundValuesValid && totalRoundValues > 0)
                      }
                    ]"
                    placeholder="%"
                    :value="isSingleRound ? roundsPct : round.prizeValue"
                    :readonly="isSingleRound"
                    @change="
                      !isSingleRound &&
                      updateRoundConfig(
                        index,
                        'prizeValue',
                        ($event.target as HTMLInputElement).value
                      )
                    "
                    step="0.5"
                    min="0"
                    :max="isSingleRound ? 100 : getRoundMaxValue(index)"
                    style="flex: 0 0 72px"
                  />

                  <!-- Botón P/S: abre modal de selección de prize -->
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
                      @click="openPrizeModal(index)"
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

                <!-- Valores fijos por moneda (solo cuando prizeType === 'fixed') -->
                <div
                  v-if="round.prizeType === 'fixed'"
                  class="d-flex mt-1 flex-wrap"
                  style="gap: 0.4rem; padding-left: 2rem"
                >
                  <template
                    v-for="cur in form.allowedCurrencies ?? []"
                    :key="`r${index}_cur_${cur}`"
                  >
                    <div class="input-group input-group-sm" style="width: auto; flex: 0 0 140px">
                      <div class="input-group-prepend">
                        <span class="input-group-text">{{ getCurrencyLabel(cur) }}</span>
                      </div>
                      <input
                        :name="`rounds_prize_value_currency[${index}][${cur}]`"
                        type="number"
                        :class="[
                          'form-control form-control-sm',
                          { 'is-invalid': !!getRoundCurrencyError(index, cur) }
                        ]"
                        placeholder="0.00"
                        step="0.5"
                        min="0"
                        :value="round.prizeValueByCurrency?.[String(cur)] ?? ''"
                        @change="
                          updateRoundPriceByCurrency(
                            index,
                            cur,
                            ($event.target as HTMLInputElement).value
                          )
                        "
                      />
                    </div>
                  </template>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>

      <FormInput
        label="%Jackpot"
        name="jackpot_pct"
        id="jackpot-pct"
        type="number"
        :step="0.5"
        :min="percentageLimits.jackpotPct.min"
        :max="percentageLimits.jackpotPct.max"
        placeholder="Opcional"
        col="col-sm-6 col-md-4 col-lg-3"
        v-model="jackpotPct"
      />

      <!-- ¿Usar Seriales? -->
      <FormSwitch
        label="¿Usar Seriales?"
        name="use_serials"
        col="col-sm-6 col-md-4 col-lg-3"
        :modelValue="form.useSerials ?? false"
        @update:modelValue="form = { ...form, useSerials: $event }"
      />

      <!-- %Bote Seriales (solo si useSerials) -->
      <FormInput
        v-if="form.useSerials"
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

      <!-- #Max. Seriales (solo si useSerials) -->
      <FormInput
        v-if="form.useSerials"
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

      <!-- Alerta suma de porcentajes -->
      <div v-if="errors.percentageSum" class="col-12">
        <div class="alert alert-danger mb-0 px-3 py-2" style="font-size: 0.82rem">
          <i class="fa-solid fa-triangle-exclamation"></i>
          {{ errors.percentageSum }}
          <small class="d-block mt-1" style="opacity: 0.85">
            %Sala ({{ form.roomCommn ?? 0 }}) + %Rondas ({{ form.roundsPct ?? 0 }})
            <template v-if="form.useSerials"> + %Seriales ({{ form.serialsPct ?? 0 }})</template>
            + %Jackpot ({{ form.jackpotPct ?? 0 }}) =
            {{
              (
                Number(form.roomCommn) +
                Number(form.roundsPct) +
                (form.useSerials ? Number(form.serialsPct) : 0) +
                Number(form.jackpotPct)
              ).toFixed(2)
            }}%
          </small>
        </div>
      </div>
    </div>
  </form>

  <!-- Modal selección de premio (P/S) -->
  <Modal
    v-model="showPrizeModal"
    title="Seleccionar Premio"
    size="sm"
    show-confirm
    confirm-text="Confirmar"
    :confirm-disabled="prizeModalSelected.length === 0"
    :on-submit="confirmPrizeSelection"
  >
    <ItemSearchPicker
      :modelValue="prizeModalSelected"
      :search="searchPrizes"
      label="Buscar premio"
      placeholder="Nombre o código del premio..."
      panelTitle="Premio seleccionado"
      :minChars="2"
      @update:modelValue="prizeModalSelected = $event.slice(-1)"
    >
      <template #result="{ item }">
        <span class="r-code">{{ (item as any).code }}</span>
        <span class="r-name">{{ item.name }}</span>
        <span class="r-price">${{ (item as any).price }}</span>
        <span class="r-add"><i class="fas fa-plus-circle"></i></span>
      </template>
      <template #chip="{ item, remove }">
        <i class="fas fa-gift chip-icon"></i>
        <span class="chip-name">{{ item.name }}</span>
        <span class="chip-price">${{ (item as any).price }}</span>
        <button type="button" class="chip-remove" @click="remove()" title="Quitar">
          <i class="fas fa-times"></i>
        </button>
      </template>
    </ItemSearchPicker>
  </Modal>
</template>
