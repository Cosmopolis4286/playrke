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
  import { useBingoRoomConflictDetector } from '@/js/composables/games/bingo/room/shared/useBingoRoomConflictDetector'
  import { useBingoRoomDraft } from '@/js/composables/games/bingo/room/shared/useBingoRoomDraft'
  import { useBingoRoomStepper } from '@/js/composables/games/bingo/room/shared/useBingoRoomStepper'
  import BingoRoomReviewStep from './Steps/BingoRoomReviewStep.vue'
  import { router } from '@inertiajs/vue3'
  import { showToastConfirmAction } from '@/js/helpers/form/ui/crudModalUI'
  // Types
  import type { BingoRoomFormData, BingoRoom } from '@/js/types/games/bingo/room/bingo-room'
  import type { BingoRoomAutomaticFormData } from '@/js/types/games/bingo/room/automatic/bingo-room-automatic'
  import { SelectOption } from '@/js/types/common/form/form'

  /* ==========================================================================
   * Props / Emits
   * ========================================================================== */

  const props = defineProps<{
    automaticRoom?: boolean
    isEdit?: boolean
    roomTypeOptions: SelectOption[]
    gameModeOptions: SelectOption[]
    prizePoolTypeOptions: SelectOption[]
    allowedCurrenciesOptions: SelectOption[]
    bingoPatternOptions: SelectOption[]
    currenciesWithActiveRate?: string[]
    /** Salas ya cargadas en la tabla — usado solo para detectar solapamiento de horario. */
    existingRooms?: BingoRoom[]
    /** ID de la sala en edición, para excluirla del propio chequeo de solapamiento. */
    editingRoomId?: string | null
    /** true si la sala en edición ya tiene jugadores registrados. */
    editingRoomHasPlayers?: boolean
    /** IDs de monedas con al menos un cartón vendido en la sala en edición — no se pueden quitar de "Monedas Permitidas". */
    soldCurrencyIds?: string[]
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
    currenciesMissingRate,
    percentageLimits
  } = useBingoRoomForm(
    () => props.modelValue,
    emit,
    () => props.automaticRoom ?? false,
    () => useBaseUsd.value,
    () => props.isEdit ?? false,
    () => props.currenciesWithActiveRate ?? []
  )

  /* ==========================================================================
   * Borrador local (localStorage) — persiste cambios mientras el modal está
   * abierto para no perder el trabajo si se cierra por accidente.
   * ========================================================================== */

  const draftStorageKey = computed(() => {
    const scope = props.automaticRoom ? 'automatic' : 'default'
    return `bingo-room-draft:${scope}:${props.editingRoomId ?? 'create'}`
  })

  const { hasDraft, draftSavedAt, draftDateExpired, draftStatus, isDraftStale, restoreDraft, discardDraft } = useBingoRoomDraft(
    form,
    draftStorageKey.value,
    props.automaticRoom ?? false
  )

  const draftSavedAtLabel = computed(() => {
    if (!draftSavedAt.value) return ''
    const parsed = new Date(draftSavedAt.value)
    if (isNaN(parsed.getTime())) return ''
    return parsed.toLocaleString('es-VE', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })
  })

  /* ==========================================================================
   * Wizard por pasos (básico → monedas → rondas → revisión)
   * ========================================================================== */

  const {
    step,
    stepLabel,
    steps,
    stepLabels,
    canGoNext,
    canGoPrev,
    goNext,
    goPrev,
    goTo,
    stepHasErrors
  } = useBingoRoomStepper(errors)

  const onRestoreDraft = (): void => {
    restoreDraft()
    // Un borrador viejo puede traer una fecha/hora ya vencida — llevar al
    // usuario al step donde está el campo para que la actualice, en vez de
    // dejarlo con errores rojos sin contexto de por qué aparecieron.
    if (draftDateExpired.value) goTo('basic')
  }

  const onDiscardDraft = async (): Promise<void> => {
    // Si el form actual ya coincide con el borrador guardado (el usuario lo
    // restauró y no cambió nada más), no hay nada que perder — evita pedir
    // confirmación para una acción que no descarta trabajo real.
    if (isDraftStale()) {
      discardDraft()
      return
    }
    const confirmed = await showToastConfirmAction(
      '¿Descartar el borrador guardado? Esta acción no se puede deshacer.',
      'warning',
      'Descartar'
    )
    if (!confirmed) return
    discardDraft()
  }

  /** Adelanta la fecha/hora programada del borrador restaurado a "ahora + 15 min". */
  const updateDraftScheduledDate = (): void => {
    const next = new Date(Date.now() + 15 * 60 * 1000)
    const pad = (n: number) => String(n).padStart(2, '0')
    const date = `${next.getFullYear()}-${pad(next.getMonth() + 1)}-${pad(next.getDate())}`
    const time = `${pad(next.getHours())}:${pad(next.getMinutes())}`
    form.value = { ...form.value, date, time }
    draftDateExpired.value = false
    goTo('basic')
  }

  /* ==========================================================================
   * Computed derivados del form
   * ========================================================================== */

  /**
   * Con jugadores registrados se bloquean los campos que afectarían una
   * partida en curso (monedas/precios, bote, rondas/premios/seriales y el
   * estado de la sala). El resto (comisión, jackpot, tiempo ficha, mínimo de
   * cartones, modo/horario de inicio) sigue editable.
   */
  const lockedByPlayers = computed(() => props.isEdit === true && props.editingRoomHasPlayers === true)

  const soldCurrencyIds = computed(() => (props.soldCurrencyIds ?? []).map(String))

  const soldCurrencyLabels = computed(() =>
    soldCurrencyIds.value
      .map(id => props.allowedCurrenciesOptions.find(opt => String(opt.value) === id)?.label ?? id)
      .join(', ')
  )

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

  /**
   * Advertencia (no bloqueante) de solapamiento de horario con otra sala ya
   * programada — solo aplica a salas normales programadas (no automáticas ni
   * de inicio manual, que no tienen fecha/hora fija que pueda chocar).
   */
  const candidateUnixDate = computed<number | null>(() => {
    if (props.automaticRoom) return null
    const { date, time } = form.value as BingoRoomFormData
    if (!date || !time) return null
    const parsed = new Date(`${date}T${time}:00`)
    if (isNaN(parsed.getTime())) return null
    return Math.floor(parsed.getTime() / 1000)
  })

  const { conflict: scheduleConflict } = useBingoRoomConflictDetector(
    computed(() => props.existingRooms ?? []),
    candidateUnixDate,
    30,
    computed(() => props.editingRoomId ?? null)
  )

  const canAdjustCtnPriceByCurrency = computed(() => {
    const allowed = form.value.allowedCurrencies
    return Array.isArray(allowed) && allowed.length >= 1
  })

  /**
   * Monedas seleccionadas sin tasa de cambio activa (sin importar cuántas haya
   * seleccionadas) — usado para bloquear "Usar Base USD", ya que ese modo
   * siempre necesita la tasa para convertir el precio USD a cada moneda local.
   */
  const currenciesWithoutAnyRate = computed(() => {
    const activeRates = props.currenciesWithActiveRate ?? []
    return (form.value.allowedCurrencies ?? []).filter(cur => !activeRates.includes(String(cur)))
  })

  const canUseBaseUsd = computed(() => currenciesWithoutAnyRate.value.length === 0)

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

  // Abrir automáticamente el panel de precios si al editar la sala ya trae
  // errores en ese panel (ej. falta precio por moneda) — de lo contrario el
  // usuario ve el botón "Guardar" deshabilitado sin ninguna pista visible.
  if (props.isEdit && hasCardPriceByCurrencyErrors.value) {
    cardPriceByCurrencyActive.value = true
  }

  // Resetear "Salas por moneda" si las monedas bajan a 1 (ya no aplica)
  watch(canChooseRoomMode, can => {
    if (!can && form.value.roomsPerCurrency) {
      form.value = { ...form.value, roomsPerCurrency: false }
    }
  })

  // Forzar a "Usar Base USD" = false si alguna moneda seleccionada se queda sin
  // tasa activa (ej. el admin desactivó la tasa mientras editaba la sala) — ese
  // modo no puede convertir el precio USD a esa moneda sin la tasa.
  watch(canUseBaseUsd, can => {
    if (!can && useBaseUsd.value) {
      useBaseUsd.value = false
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

  /**
   * Quita del formulario las monedas seleccionadas sin tasa de cambio activa
   * (usado por el modal "Continuar sin esas monedas" en la página padre).
   */
  const removeCurrenciesMissingRate = (): void => {
    const missing = new Set(currenciesMissingRate.value)
    if (missing.size === 0) return

    form.value = {
      ...form.value,
      allowedCurrencies: (form.value.allowedCurrencies ?? []).filter(cur => !missing.has(String(cur)))
    }
  }

  defineExpose({
    submit,
    isValid,
    currenciesMissingRate,
    removeCurrenciesMissingRate,
    draftStatus,
    discardDraft,
    step,
    stepLabel,
    steps,
    stepLabels,
    canGoNext,
    canGoPrev,
    goNext,
    goPrev,
    goTo,
    stepHasErrors
  })
</script>

<template>
  <!-- Aviso: borrador local disponible (cambios no guardados de una sesión anterior) -->
  <div v-if="hasDraft" class="alert alert-info d-flex align-items-center justify-content-between mb-3" style="font-size: 0.85rem">
    <span>
      <i class="fa-solid fa-clock-rotate-left"></i> Hay un borrador sin guardar de esta sala<template v-if="draftSavedAtLabel"> ({{ draftSavedAtLabel }})</template>.
    </span>
    <div style="gap: 0.5rem" class="d-flex">
      <button type="button" class="btn btn-sm btn-outline-secondary" @click="onDiscardDraft">Descartar</button>
      <button type="button" class="btn btn-sm btn-info text-white" @click="onRestoreDraft">Restaurar</button>
    </div>
  </div>

  <!-- Aviso: el borrador restaurado trae una fecha/hora ya vencida -->
  <div v-if="draftDateExpired" class="alert alert-warning d-flex align-items-center justify-content-between mb-3" style="font-size: 0.85rem">
    <span>
      <i class="fa-solid fa-triangle-exclamation me-2"></i>
      El borrador restaurado tenía una fecha/hora programada que ya pasó. Actualízala para poder guardar.
    </span>
    <button type="button" class="btn btn-sm btn-warning" @click="updateDraftScheduledDate">
      Actualizar fecha
    </button>
  </div>

  <!-- Navegación del wizard -->
  <ul class="nav nav-tabs mb-3" style="font-size: 0.85rem">
    <li v-for="s in steps" :key="s" class="nav-item">
      <button
        type="button"
        class="nav-link"
        :class="{ active: step === s, 'text-danger': stepHasErrors(s) && step !== s }"
        @click="goTo(s)"
      >
        {{ stepLabels[s] }}
        <i v-if="stepHasErrors(s)" class="fa-solid fa-circle-exclamation ml-1" style="font-size: 0.7rem"></i>
      </button>
    </li>
  </ul>

  <form id="bingo-room-form" novalidate @submit.prevent="submit">
    <div class="row">
      <!-- Paso: Datos básicos -->
      <template v-if="step === 'basic'">
        <RoomBasicSection
          :modelValue="form"
          :errors="errors"
          :automaticRoom="automaticRoom"
          :roomTypeOptions="roomTypeOptions"
          :gameModeOptions="gameModeOptions"
          :prizePoolTypeOptions="prizePoolTypeOptions"
          :roomCommn="roomCommn"
          :jackpotPct="jackpotPct"
          :percentageLimits="percentageLimits"
          @update:modelValue="form = $event"
          @update:roomCommn="roomCommn = $event"
          @update:jackpotPct="jackpotPct = $event"
        />

        <!-- Aviso: solapamiento de horario con otra sala ya programada (no bloqueante) -->
        <div v-if="scheduleConflict.hasConflict" class="col-12">
          <div class="alert alert-warning mb-0 px-3 py-2" style="font-size: 0.82rem">
            <i class="fa-solid fa-triangle-exclamation"></i>
            Hay {{ scheduleConflict.conflictingRooms.length }} sala(s) programada(s) a menos de
            {{ scheduleConflict.gapMinutes }} minutos de este horario. Puedes continuar, pero revisa
            que no compitan por los mismos jugadores.
          </div>
        </div>

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
      </template>

      <!-- Paso: Monedas y precios -->
      <template v-if="step === 'currencies'">
        <div v-if="isEdit && soldCurrencyIds.length" class="col-12 mb-2">
          <div class="alert alert-warning mb-0 px-3 py-2" style="font-size: 0.82rem">
            <i class="fa-solid fa-triangle-exclamation"></i>
            Esta sala ya tiene cartones vendidos en: <strong>{{ soldCurrencyLabels }}</strong>.
            No puedes quitar esas monedas de "Monedas Permitidas" — el servidor rechazará el cambio.
          </div>
        </div>
        <RoomCurrencySection
          :modelValue="form"
          :errors="errors"
          :allowedCurrenciesOptions="allowedCurrenciesOptions"
          :useBaseUsd="useBaseUsd"
          :canUseBaseUsd="canUseBaseUsd"
          :cardPriceByCurrencyActive="cardPriceByCurrencyActive"
          :canAdjustCtnPriceByCurrency="canAdjustCtnPriceByCurrency"
          :hasCardPriceByCurrencyErrors="hasCardPriceByCurrencyErrors"
          :currenciesWithActiveRate="currenciesWithActiveRate ?? []"
          :soldCurrencyIds="soldCurrencyIds"
          @update:modelValue="form = $event"
          @update:useBaseUsd="useBaseUsd = $event"
          @update:cardPriceByCurrencyActive="cardPriceByCurrencyActive = $event"
        />
      </template>

      <!-- Paso: Rondas -->
      <template v-if="step === 'rounds'">
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
          @update:modelValue="form = $event"
          @update:roundsPct="roundsPct = $event"
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
      </template>

      <!-- Paso: Revisión -->
      <div v-if="step === 'review'" class="col-12">
        <BingoRoomReviewStep
          :form="form"
          :errors="errors"
          :currency-options="allowedCurrenciesOptions"
          :lock-activate-room="lockedByPlayers"
          @update:form="form = $event"
        />
      </div>
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
