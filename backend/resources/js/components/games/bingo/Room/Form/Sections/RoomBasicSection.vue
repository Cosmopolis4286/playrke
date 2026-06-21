<script setup lang="ts">
  import { computed } from 'vue'

  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'

  import type { BingoRoomFormData } from '@/js/types/games/bingo/room/bingo-room'
  import type {
    BingoRoomAutomaticFormData,
    DayOfWeek
  } from '@/js/types/games/bingo/room/automatic/bingo-room-automatic'
import { SelectOption } from '@/js/types/common/form/form'

  const props = defineProps<{
    modelValue: BingoRoomFormData | BingoRoomAutomaticFormData
    errors: Record<string, any>
    automaticRoom?: boolean
    roomTypeOptions: SelectOption[]
    gameModeOptions: SelectOption[]
    prizePoolTypeOptions: SelectOption[]
    roomCommn: string | number
    jackpotPct: string | number
    percentageLimits: {
      roomCommn: { min: number; max: number }
      jackpotPct: { min: number; max: number }
    }
  }>()

  const emit = defineEmits<{
    (e: 'update:modelValue', value: BingoRoomFormData | BingoRoomAutomaticFormData): void
    (e: 'update:roomCommn', value: string | number): void
    (e: 'update:jackpotPct', value: string | number): void
  }>()

  const GAME_VARIANT_OPTIONS: SelectOption[] = [
    { value: 'BINGO_75', label: 'Bingo 75 bolas (5×5, B·I·N·G·O)' },
    { value: 'BINGO_90', label: 'Bingo 90 bolas (3×9, líneas)' },
    { value: 'BINGO_80', label: 'Bingo 80 bolas (4×4)' },
    { value: 'BINGO_30', label: 'Speed Bingo 30 bolas (3×3)' },
  ]

  const START_MODE_OPTIONS: SelectOption[] = [
    { value: 'SCHEDULED', label: 'Programado' },
    { value: 'MANUAL', label: 'Manual (sin horario fijo)' }
  ]

  const DAYS_OPTIONS: { value: DayOfWeek; label: string }[] = [
    { value: 'monday', label: 'Lunes' },
    { value: 'tuesday', label: 'Martes' },
    { value: 'wednesday', label: 'Miércoles' },
    { value: 'thursday', label: 'Jueves' },
    { value: 'friday', label: 'Viernes' },
    { value: 'saturday', label: 'Sábado' },
    { value: 'sunday', label: 'Domingo' }
  ]

  /* ==========================================================================
   * Computed
   * ========================================================================== */

  const minDate = computed(() => {
    const now = new Date()
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`
  })

  const minTime = computed(() => {
    if (props.automaticRoom) return undefined
    const todayStr = minDate.value
    if (props.modelValue.date !== todayStr) return undefined
    const min15 = new Date(Date.now() + 15 * 60 * 1000)
    return `${String(min15.getHours()).padStart(2, '0')}:${String(min15.getMinutes()).padStart(2, '0')}`
  })

  const isManualStart = computed(
    () => !props.automaticRoom && (props.modelValue as BingoRoomFormData).startMode === 'MANUAL'
  )

  /* ==========================================================================
   * Handlers
   * ========================================================================== */

  function onStartModeChange(value: string | number | (string | number)[] | null): void {
    const startMode = value as 'SCHEDULED' | 'MANUAL'

    emit('update:modelValue', {
      ...props.modelValue,
      startMode,
      ...(startMode === 'MANUAL' ? { date: null, time: null } : {})
    } as BingoRoomFormData)
  }
</script>

<template>
  <div style="display: contents">
    <!-- Modo de inicio (solo salas normales) -->
    <FormSelect
      v-if="!automaticRoom"
      label="Modo de Inicio"
      name="start_mode"
      :error="(errors as any).startMode"
      required
      placeholder="Selecciona modo de inicio"
      :options="START_MODE_OPTIONS"
      optionValue="value"
      optionLabel="label"
      col="col-sm-6 col-md-4 col-lg-3"
      :modelValue="(modelValue as BingoRoomFormData).startMode ?? 'SCHEDULED'"
      @update:modelValue="onStartModeChange"
    />

    <!-- Fecha (solo salas normales programadas) -->
    <FormInput
      v-if="!automaticRoom && !isManualStart"
      label="Fecha"
      name="date"
      :error="errors.date"
      type="date"
      required
      :min="minDate"
      col="col-sm-6 col-md-4 col-lg-3"
      :modelValue="modelValue.date"
      @update:modelValue="emit('update:modelValue', { ...modelValue, date: $event })"
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
      :modelValue="(modelValue as BingoRoomAutomaticFormData).days ?? []"
      @update:modelValue="
        emit('update:modelValue', { ...modelValue, days: $event } as BingoRoomAutomaticFormData)
      "
    />

    <!-- Hora (salas automáticas siempre; salas normales solo si están programadas) -->
    <FormInput
      v-if="!isManualStart"
      label="Hora"
      name="time"
      :error="errors.time"
      type="time"
      required
      :min="minTime"
      col="col-sm-6 col-md-4 col-lg-3"
      :modelValue="modelValue.time"
      @update:modelValue="emit('update:modelValue', { ...modelValue, time: $event })"
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
      :modelValue="modelValue.roomType"
      @update:modelValue="emit('update:modelValue', { ...modelValue, roomType: $event })"
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
      :modelValue="modelValue.gameMode"
      @update:modelValue="emit('update:modelValue', { ...modelValue, gameMode: $event })"
    />

    <!-- Variante de Bingo -->
    <FormSelect
      label="Variante de Bingo"
      name="game_variant"
      :error="(errors as any).gameVariant"
      required
      placeholder="Selecciona variante"
      :options="GAME_VARIANT_OPTIONS"
      optionValue="value"
      optionLabel="label"
      col="col-sm-6 col-md-4 col-lg-3"
      :modelValue="(modelValue as any).gameVariant ?? 'BINGO_75'"
      @update:modelValue="emit('update:modelValue', { ...modelValue, gameVariant: $event } as any)"
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
      :modelValue="modelValue.prizePoolType"
      @update:modelValue="emit('update:modelValue', { ...modelValue, prizePoolType: $event })"
    />

    <!-- %Sala -->
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
      :modelValue="roomCommn"
      @update:modelValue="emit('update:roomCommn', $event)"
    />

    <!-- Tiempo Ficha -->
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
      :modelValue="modelValue.timeChip"
      @update:modelValue="emit('update:modelValue', { ...modelValue, timeChip: $event })"
    >
      <template #append>
        <span class="input-group-text">SEG</span>
      </template>
    </FormInput>

    <!-- Min. Cartones — aplica a salas normales y automáticas -->
    <FormInput
      label="Min. Cartones"
      name="min_cards_to_start"
      :error="(errors as any).minCardsToStart"
      type="number"
      placeholder="Sin condición"
      :step="1"
      :min="1"
      :max="10000"
      col="col-sm-6 col-md-4 col-lg-3"
      :modelValue="(modelValue as any).minCardsToStart ?? null"
      @update:modelValue="
        emit('update:modelValue', {
          ...modelValue,
          minCardsToStart: $event === '' || $event === null ? null : Number($event)
        } as any)
      "
    />

    <!-- %Jackpot -->
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
      :modelValue="jackpotPct"
      @update:modelValue="emit('update:jackpotPct', $event)"
    />

    <!-- Cartón personalizado -->
    <div class="col-12">
      <hr style="margin: 0.25rem 0 0.75rem" />
      <div class="d-flex align-items-center flex-wrap" style="gap: 1.25rem">
        <!-- Toggle -->
        <div class="custom-control custom-switch" style="padding-left: 2.25rem; flex-shrink: 0">
          <input
            id="allow_custom_card"
            name="allow_custom_card"
            type="checkbox"
            class="custom-control-input"
            :checked="(modelValue as any).allowCustomCard ?? false"
            @change="
              emit('update:modelValue', {
                ...modelValue,
                allowCustomCard: ($event.target as HTMLInputElement).checked,
                ...(!($event.target as HTMLInputElement).checked ? { customCardPricePct: null } : {})
              } as any)
            "
          />
          <label class="custom-control-label" for="allow_custom_card" style="font-weight: 500">
            Permitir cartón personalizado
          </label>
        </div>

        <template v-if="(modelValue as any).allowCustomCard">
          <!-- Precio cartón personalizado -->
          <div>
            <label class="d-block" style="font-size: 0.78rem; margin-bottom: 0.2rem; color: #495057">
              Precio cartón personalizado <span class="text-danger">*</span>
            </label>
            <div class="input-group input-group-sm" style="width: 130px">
              <input
                name="custom_card_price_pct"
                type="number"
                :class="['form-control', { 'is-invalid': (errors as any).customCardPricePct }]"
                placeholder="Ej: 5"
                :step="0.25"
                :min="1"
                :max="10"
                :value="(modelValue as any).customCardPricePct ?? ''"
                @change="
                  emit('update:modelValue', {
                    ...modelValue,
                    customCardPricePct: ($event.target as HTMLInputElement).value === '' ? null : Number(($event.target as HTMLInputElement).value)
                  } as any)
                "
              />
              <div class="input-group-append">
                <span class="input-group-text">%</span>
              </div>
            </div>
            <small v-if="(errors as any).customCardPricePct" class="text-danger" style="font-size: 0.72rem">
              {{ (errors as any).customCardPricePct }}
            </small>
          </div>
        </template>
      </div>
    </div>

    <!-- Cartón especial -->
    <div class="col-12">
      <hr style="margin: 0.25rem 0 0.75rem" />
      <div class="d-flex align-items-center flex-wrap" style="gap: 1.25rem">
        <!-- Toggle -->
        <div class="custom-control custom-switch" style="padding-left: 2.25rem; flex-shrink: 0">
          <input
            id="allow_special_card"
            name="allow_special_card"
            type="checkbox"
            class="custom-control-input"
            :checked="(modelValue as any).allowSpecialCard ?? false"
            @change="
              emit('update:modelValue', {
                ...modelValue,
                allowSpecialCard: ($event.target as HTMLInputElement).checked,
                ...(!($event.target as HTMLInputElement).checked ? { maxSpecialCards: null } : {})
              } as any)
            "
          />
          <label class="custom-control-label" for="allow_special_card" style="font-weight: 500">
            Permitir cartón especial
          </label>
        </div>

        <template v-if="(modelValue as any).allowSpecialCard">
          <!-- Máx. cartones especiales -->
          <div>
            <label class="d-block" style="font-size: 0.78rem; margin-bottom: 0.2rem; color: #495057">
              Máx. cartones especiales <span class="text-danger">*</span>
            </label>
            <input
              name="max_special_cards"
              type="number"
              :class="['form-control form-control-sm', { 'is-invalid': (errors as any).maxSpecialCards }]"
              placeholder="Ej: 3"
              :step="1"
              :min="1"
              :max="5"
              style="width: 110px"
              :value="(modelValue as any).maxSpecialCards ?? ''"
              @change="
                emit('update:modelValue', {
                  ...modelValue,
                  maxSpecialCards: ($event.target as HTMLInputElement).value === '' ? null : Number(($event.target as HTMLInputElement).value)
                } as any)
              "
            />
            <small v-if="(errors as any).maxSpecialCards" class="text-danger" style="font-size: 0.72rem">
              {{ (errors as any).maxSpecialCards }}
            </small>
          </div>
        </template>
      </div>
    </div>

    <!-- Días de Anticipación de Ventas (solo salas automáticas) -->
    <FormInput
      v-if="automaticRoom"
      label="Días de Anticipación"
      name="sales_start_offset_days"
      :error="(errors as any).salesStartOffsetDays"
      type="number"
      placeholder="Mismo día"
      :step="1"
      :min="1"
      :max="30"
      col="col-sm-6 col-md-4 col-lg-3"
      inputGroup
      :modelValue="(modelValue as BingoRoomAutomaticFormData).salesStartOffsetDays ?? null"
      @update:modelValue="
        emit('update:modelValue', {
          ...modelValue,
          salesStartOffsetDays: $event === '' || $event === null ? null : Number($event)
        } as BingoRoomAutomaticFormData)
      "
    >
      <template #append>
        <span class="input-group-text" title="La sala se publica este número de días antes de que inicie el juego">DÍAS</span>
      </template>
    </FormInput>

  </div>
</template>
