<script setup lang="ts" name="GameBingoRoomForm">
  import { computed, watch, ref } from 'vue'

  // Secciones
  import RoomBasicSection from './Sections/RoomBasicSection.vue'
  import RoomCurrencySection from './Sections/RoomCurrencySection.vue'
  import RoomRoundsSection from './Sections/RoomRoundsSection.vue'
  import RoomSerialsSection from './Sections/RoomSerialsSection.vue'
  // Componentes comunes
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import Modal from '@/js/Components/Common/Modal/Modal.vue'
  import ItemSearchPicker from '@/js/Components/Common/Form/ItemSearchPicker.vue'
  // Composable principal
  import { useBingoRoomForm } from '@/js/composables/games/bingo/room/default/useBingoRoomForm'
  // Composables locales (funciones puras)
  import { syncRoundsConfig, redistributePercentageRounds } from './Composables/useRoomRounds'
  // Premios
  import { searchPrizes } from '@/js/composables/prizes/usePrize'
  import { useBingoRoomPrizeRound } from '@/js/composables/games/bingo/room/shared/useBingoRoomPrizeRound'
  import { router } from '@inertiajs/vue3'
  // Types
  import type { BingoRoomFormData } from '@/js/types/games/bingo/room/bingo-room'
  import type { BingoRoomAutomaticFormData } from '@/js/types/games/bingo/room/automatic/bingo-room-automatic'
  import { SelectOption } from '@/js/types/common/form/form'

  /* ==========================================================================
   * Props / Emits
   * ========================================================================== */

  const props = defineProps<{
    automaticRoom?: boolean
    roomTypeOptions: SelectOption[]
    gameModeOptions: SelectOption[]
    prizePoolTypeOptions: SelectOption[]
    allowedCurrenciesOptions: SelectOption[]
    bingoPatternOptions: SelectOption[]
    currenciesWithActiveRate?: string[]
    modelValue: BingoRoomFormData | BingoRoomAutomaticFormData
  }>()

  const emit = defineEmits<{
    (e: 'submit'): void
    (e: 'update:modelValue', value: BingoRoomFormData | BingoRoomAutomaticFormData): void
  }>()

  /* ==========================================================================
   * Estado principal (única fuente de verdad)
   * ========================================================================== */

  // useBaseUsd: inicializa desde el modelo (edición) o true por defecto
  // Se sincroniza bidirecccionalmente con form.value.useBaseUsd via watcher
  const useBaseUsd = ref(props.modelValue.useBaseUsd ?? true)
  const cardPriceByCurrencyActive = ref(false)

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
    () => props.automaticRoom ?? false,
    () => useBaseUsd.value
  )

  /* ==========================================================================
   * Computed derivados del form
   * ========================================================================== */

  const showRoundsPct = computed(() => (form.value.rounds ?? 1) >= 1)

  const isSingleRound = computed(() => Number(form.value.rounds ?? 1) === 1)

  const totalRoundValues = computed(() =>
    (form.value.roundsConfig ?? []).reduce((sum, round) => {
      if (round.prizeType !== 'percentage') return sum
      return sum + (Number(round.prizeValue) || 0)
    }, 0)
  )

  const isRoundValuesValid = computed(() => {
    const pct = Number(form.value.roundsPct) || 0
    if (pct === 0) return true
    return Math.abs(totalRoundValues.value - pct) < 0.01
  })

  const showAmountAddedPrizePool = computed(() => {
    const selected = props.prizePoolTypeOptions.find(
      opt => String(opt.value) === String(form.value.prizePoolType)
    )
    return selected ? selected.code !== 'ACCUMULATIVE' : false
  })

  const canAdjustCtnPriceByCurrency = computed(() => {
    const allowed = form.value.allowedCurrencies
    return Array.isArray(allowed) && allowed.length >= 1
  })

  const canChooseRoomMode = computed(() => (form.value.allowedCurrencies?.length ?? 0) > 1)

  const hasCardPriceByCurrencyErrors = computed(() => {
    if (cardPriceByCurrencyActive.value) return false
    if (errors.value.cardPriceUsd || errors.value.usdExchangeRoundStep) return true
    const err = errors.value.cardPriceByCurrency
    return !!(err && Object.keys(err).length > 0)
  })

  /* ==========================================================================
   * Watchers de sincronización
   * ========================================================================== */

  // Sincronizar roundsConfig al cambiar el nº de rondas
  watch(
    () => form.value.rounds,
    newRounds => {
      const count = Math.max(1, Number(newRounds) || 1)
      const fullHouseId =
        props.bingoPatternOptions.find(o => o.label.toUpperCase() === 'FULL HOUSE')?.value ?? null
      const updated = syncRoundsConfig(
        count,
        Number(form.value.roundsPct) || 0,
        form.value.roundsConfig ?? [],
        fullHouseId
      )
      form.value = { ...form.value, roundsConfig: updated }
    },
    { immediate: true }
  )

  // Al cambiar roundsPct: redistribuir entre rondas % y ajustar %Sala
  watch(
    () => form.value.roundsPct,
    newPct => {
      const total = Number(newPct) || 0
      const configs = form.value.roundsConfig ?? []
      let nextForm = {
        ...form.value,
        roundsConfig: redistributePercentageRounds(configs, total)
      }

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

  // 1 sola ronda → %Rondas = disponible automáticamente
  watch(
    () => [form.value.rounds, availablePct.value] as const,
    ([newRounds, avail]) => {
      if (Number(newRounds) !== 1) return
      if (Number(form.value.roundsPct) === avail) return
      form.value = { ...form.value, roundsPct: avail }
    }
  )

  // Sincronizar useBaseUsd ↔ form.useBaseUsd y limpiar campos del modo desactivado
  watch(useBaseUsd, isBaseUsd => {
    if (isBaseUsd) {
      form.value = { ...form.value, useBaseUsd: true, cardPriceByCurrency: {} }
    } else {
      // Inicializar mapa de precios por moneda con las monedas actualmente seleccionadas
      const currencies = form.value.allowedCurrencies ?? []
      const cardPriceByCurrency = currencies.reduce<Record<string, number | null>>((acc, cur) => {
        acc[String(cur)] = form.value.cardPriceByCurrency?.[String(cur)] ?? null
        return acc
      }, {})
      form.value = {
        ...form.value,
        useBaseUsd: false,
        cardPriceUsd: null,
        usdExchangeRoundStep: null,
        cardPriceByCurrency
      }
    }
  })

  // Cerrar panel de precios si las monedas bajan a 1
  watch(canAdjustCtnPriceByCurrency, can => {
    if (!can) cardPriceByCurrencyActive.value = false
  })

  // Resetear "Salas por moneda" si las monedas bajan a 1 (ya no aplica)
  watch(canChooseRoomMode, can => {
    if (!can && form.value.roomsPerCurrency) {
      form.value = { ...form.value, roomsPerCurrency: false }
    }
  })

  // Sincronizar claves de cardPriceByCurrency cuando cambian las monedas (modo !useBaseUsd)
  watch(
    () => form.value.allowedCurrencies,
    currencies => {
      if (useBaseUsd.value) return
      const current = form.value.cardPriceByCurrency ?? {}
      const updated = (currencies ?? []).reduce<Record<string, number | null>>((acc, cur) => {
        acc[String(cur)] = current[String(cur)] ?? null
        return acc
      }, {})
      form.value = { ...form.value, cardPriceByCurrency: updated }
    }
  )

  /* ==========================================================================
   * Modal P/S — selección de prize para ronda
   * ========================================================================== */
  const {
    showPrizeModal,
    showNoPrizesAlert,
    prizeModalSelected,
    openPrizeModal,
    confirmPrizeSelection
  } = useBingoRoomPrizeRound(form)

  /* ==========================================================================
   * Submit / Expose
   * ========================================================================== */

  const submit = (): boolean => {
    if (!isValid.value) return false
    emit('submit')
    return true
  }

  defineExpose({ submit, isValid })
</script>

<template>
  <form id="bingo-room-form" novalidate @submit.prevent="submit">
    <div class="row">
      <!-- Sección básica: fecha/días, hora, tipo sala, modo, bote, %Sala, tiempo ficha -->
      <RoomBasicSection
        :modelValue="form"
        :errors="errors"
        :automaticRoom="automaticRoom"
        :roomTypeOptions="roomTypeOptions"
        :gameModeOptions="gameModeOptions"
        :prizePoolTypeOptions="prizePoolTypeOptions"
        :roomCommn="roomCommn"
        :percentageLimits="percentageLimits"
        @update:modelValue="form = $event"
        @update:roomCommn="roomCommn = $event"
      />

      <!-- Sección monedas: monedas permitidas + panel precios cartones -->
      <RoomCurrencySection
        :modelValue="form"
        :errors="errors"
        :allowedCurrenciesOptions="allowedCurrenciesOptions"
        :useBaseUsd="useBaseUsd"
        :cardPriceByCurrencyActive="cardPriceByCurrencyActive"
        :canAdjustCtnPriceByCurrency="canAdjustCtnPriceByCurrency"
        :hasCardPriceByCurrencyErrors="hasCardPriceByCurrencyErrors"
        :currenciesWithActiveRate="currenciesWithActiveRate ?? []"
        @update:modelValue="form = $event"
        @update:useBaseUsd="useBaseUsd = $event"
        @update:cardPriceByCurrencyActive="cardPriceByCurrencyActive = $event"
      />

      <!-- Valor extra al bote (fuera de RoomCurrencySection para mantener col grid) -->
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

      <!-- Sección rondas: rondas + config avanzada + %Jackpot -->
      <RoomRoundsSection
        :modelValue="form"
        :errors="errors"
        :bingoPatternOptions="bingoPatternOptions"
        :allowedCurrenciesOptions="allowedCurrenciesOptions"
        :isSingleRound="isSingleRound"
        :showRoundsPct="showRoundsPct"
        :availablePct="availablePct"
        :percentageLimits="percentageLimits"
        :totalRoundValues="totalRoundValues"
        :isRoundValuesValid="isRoundValuesValid"
        :roundsPct="roundsPct"
        :jackpotPct="jackpotPct"
        @update:modelValue="form = $event"
        @update:roundsPct="roundsPct = $event"
        @update:jackpotPct="jackpotPct = $event"
        @open-prize-modal="openPrizeModal"
      />

      <!-- Sección seriales + alerta porcentajes -->
      <RoomSerialsSection
        :modelValue="form"
        :errors="errors"
        :percentageLimits="percentageLimits"
        :serialsPct="serialsPct"
        @update:modelValue="form = $event"
        @update:serialsPct="serialsPct = $event"
      />
    </div>
  </form>

  <!-- Alerta: sin premios registrados -->
  <Modal
    v-model="showNoPrizesAlert"
    title="Sin premios disponibles"
    size="sm"
    show-confirm
    confirm-text="Ir a Premios"
    :on-submit="
      () => {
        router.visit('/prizes')
        return true
      }
    "
  >
    <div class="py-2 text-center">
      <i class="fas fa-gift fa-3x text-muted d-block mb-3"></i>
      <p class="mb-0">
        No hay premios registrados. Crea al menos un premio antes de asignarlo a una ronda.
      </p>
    </div>
  </Modal>

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
