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
    percentageLimits: { roomCommn: { min: number; max: number } }
  }>()

  const emit = defineEmits<{
    (e: 'update:modelValue', value: BingoRoomFormData | BingoRoomAutomaticFormData): void
    (e: 'update:roomCommn', value: string | number): void
  }>()

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

    <!-- Cartones mínimos para iniciar (opcional, aplica a cualquier modo de inicio) -->
    <FormInput
      v-if="!automaticRoom"
      label="Cartones mínimos para iniciar"
      name="min_cards_to_start"
      :error="(errors as any).minCardsToStart"
      type="number"
      placeholder="Sin condición"
      :step="1"
      :min="1"
      :max="10000"
      col="col-sm-6 col-md-4 col-lg-3"
      :modelValue="(modelValue as BingoRoomFormData).minCardsToStart ?? null"
      @update:modelValue="
        emit('update:modelValue', {
          ...modelValue,
          minCardsToStart: $event === '' || $event === null ? null : Number($event)
        } as BingoRoomFormData)
      "
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
  </div>
</template>
