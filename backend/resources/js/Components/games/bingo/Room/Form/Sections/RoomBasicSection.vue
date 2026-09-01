<script setup lang="ts">
  import { computed } from 'vue'

  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'
  import AsyncSelect from '@/js/Components/Common/Form/AsyncSelect.vue'
  import RoomCapacitySection from './RoomCapacitySection.vue'
  import { searchUsers } from '@/js/composables/users/useUserSearch'
  import { showToast } from '@/js/utils/toastNotify'
  import { generateAccessCode, generateRoomPassword } from '../Composables/useRoomPrivateAccess'

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
    connectionTypeOptions: SelectOption[]
    gameModeOptions: SelectOption[]
    prizePoolTypeOptions: SelectOption[]
    jackpotTypeOptions?: SelectOption[]
    roomCommn: string | number
    jackpotPct: string | number
    percentageLimits: {
      roomCommn: { min: number; max: number }
      jackpotPct: { min: number; max: number }
    }
    /** Etiqueta del anfitrión ya asignado (para precargar el AsyncSelect al editar sin re-buscarlo). */
    hostUserLabel?: string | null
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

  /** true si el "Tipo de Sala" seleccionado tiene código PRIVATE (busca el código en roomTypeOptions, ya que roomType viaja como id). */
  const isPrivateRoom = computed(() => {
    const selected = props.roomTypeOptions.find(o => String(o.value) === String(props.modelValue.roomType))
    return selected?.code === 'PRIVATE'
  })

  const hostUserInitialOption = computed(() => {
    if (!props.modelValue.hostUserId) return null
    return { value: props.modelValue.hostUserId, label: props.hostUserLabel ?? String(props.modelValue.hostUserId) }
  })

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

  function onGenerateAccessCode(): void {
    emit('update:modelValue', { ...props.modelValue, accessCode: generateAccessCode() } as any)
  }

  function onGenerateRoomPassword(): void {
    emit('update:modelValue', { ...props.modelValue, roomPassword: generateRoomPassword() } as any)
  }

  function onHostUserNoResults(query: string): void {
    showToast(`No se encontraron usuarios para "${query}".`, 'info')
  }
</script>

<template>
  <div style="display: contents">
    <!-- Horario -->
    <div class="col-12">
      <p class="mb-2" style="font-weight: 600; font-size: 0.85rem">
        <i class="fa-solid fa-calendar-days mr-1" style="opacity: 0.7"></i>
        Horario
      </p>
      <p class="text-muted mb-2" style="font-size: 0.78rem">
        Cuándo y cómo inicia el sorteo — programado a fecha/hora fija, o manual (el anfitrión lo activa cuando quiera).
      </p>
    </div>

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

    <!-- Tipo y acceso -->
    <div class="col-12">
      <hr style="margin: 0.25rem 0 0.75rem" />
      <p class="mb-2" style="font-weight: 600; font-size: 0.85rem">
        <i class="fa-solid fa-door-open mr-1" style="opacity: 0.7"></i>
        Tipo y acceso
      </p>
      <p class="text-muted mb-2" style="font-size: 0.78rem">
        Pública (cualquiera puede entrar) o privada (requiere código + contraseña, con un anfitrión responsable).
      </p>
    </div>

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

    <!-- Configuración de sala privada (solo si Tipo de Sala = Privada) -->
    <template v-if="isPrivateRoom">
      <FormInput
        label="Código de Acceso"
        name="access_code"
        :error="(errors as any).accessCode"
        required
        placeholder="Ej. FAMILIA2026"
        col="col-sm-6 col-md-4 col-lg-3"
        inputGroup
        :modelValue="(modelValue as any).accessCode ?? null"
        @update:modelValue="emit('update:modelValue', { ...modelValue, accessCode: $event } as any)"
      >
        <template #append>
          <button
            type="button"
            class="btn btn-outline-secondary"
            title="Generar código automáticamente"
            @click="onGenerateAccessCode"
          >
            <i class="fa-solid fa-dice"></i>
          </button>
        </template>
      </FormInput>

      <FormInput
        label="Contraseña"
        name="room_password"
        :error="(errors as any).roomPassword"
        :required="!automaticRoom"
        type="password"
        :placeholder="props.modelValue.hostUserId ? 'Dejar en blanco para no cambiar' : 'Contraseña de acceso'"
        col="col-sm-6 col-md-4 col-lg-3"
        inputGroup
        :modelValue="(modelValue as any).roomPassword ?? null"
        @update:modelValue="emit('update:modelValue', { ...modelValue, roomPassword: $event } as any)"
      >
        <template #append>
          <button
            type="button"
            class="btn btn-outline-secondary"
            title="Generar contraseña automáticamente"
            @click="onGenerateRoomPassword"
          >
            <i class="fa-solid fa-key"></i>
          </button>
        </template>
      </FormInput>

      <AsyncSelect
        label="Anfitrión"
        name="host_user_id"
        :error="(errors as any).hostUserId"
        required
        placeholder="Buscar usuario..."
        col="col-sm-6 col-md-4 col-lg-3"
        :search="searchUsers"
        :initial-option="hostUserInitialOption"
        :modelValue="(modelValue as any).hostUserId ?? null"
        @update:modelValue="emit('update:modelValue', { ...modelValue, hostUserId: $event } as any)"
        @no-results="onHostUserNoResults"
      />
    </template>

    <!-- Capacidad de la sala: min/máx cartones, min/máx jugadores -->
    <div class="col-12">
      <hr style="margin: 0.25rem 0 0.75rem" />
      <p class="mb-2" style="font-weight: 600; font-size: 0.85rem">
        <i class="fa-solid fa-gauge-high mr-1" style="opacity: 0.7"></i>
        Capacidad de la sala
      </p>
      <p class="text-muted mb-2" style="font-size: 0.78rem">
        Límites opcionales de cartones y jugadores — dejalos vacíos si no querés restricción.
      </p>
    </div>
    <RoomCapacitySection
      :modelValue="modelValue as BingoRoomFormData"
      :errors="errors"
      @update:modelValue="emit('update:modelValue', $event as any)"
    />

    <!-- Configuración general -->
    <div class="col-12">
      <hr style="margin: 0.25rem 0 0.75rem" />
      <p class="mb-2" style="font-weight: 600; font-size: 0.85rem">
        <i class="fa-solid fa-sliders mr-1" style="opacity: 0.7"></i>
        Configuración general
      </p>
      <p class="text-muted mb-2" style="font-size: 0.78rem">
        Conexión, modo y variante de juego, tipo de bote, comisiones y tiempo entre fichas.
      </p>
    </div>

    <!-- Tipo de Conexión -->
    <FormSelect
      label="Tipo de Conexión"
      name="connection_type"
      :error="errors.connectionType"
      id="connection-type"
      required
      placeholder="Selecciona tipo de conexión"
      :options="connectionTypeOptions"
      optionValue="value"
      optionLabel="label"
      col="col-sm-6 col-md-4 col-lg-3"
      :modelValue="modelValue.connectionType"
      @update:modelValue="emit('update:modelValue', { ...modelValue, connectionType: $event })"
    />

    <!-- Modo de Juego (Solitario, Múltiple, Ranking...) -->
    <FormSelect
      label="Modo de Juego"
      name="game_mode"
      :error="(errors as any).gameMode"
      required
      placeholder="Selecciona modo de juego"
      :options="gameModeOptions"
      optionValue="value"
      optionLabel="label"
      col="col-sm-6 col-md-4 col-lg-3"
      :modelValue="(modelValue as any).gameMode ?? null"
      @update:modelValue="emit('update:modelValue', { ...modelValue, gameMode: $event } as any)"
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
      :error="errors.roomCommn"
      hint="% del bote que se queda la casa como comisión."
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
      hint="Segundos entre cada ficha cantada durante el sorteo."
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

    <!-- Jackpot: a cuál pozo va el %Jackpot configurado abajo. Va primero
         porque %Jackpot depende de que este tenga un valor seleccionado. -->
    <FormSelect
      label="Jackpot"
      name="jackpot_type"
      :error="(errors as any).jackpotType"
      placeholder="Sin jackpot (opcional)"
      :options="jackpotTypeOptions ?? []"
      optionValue="value"
      optionLabel="label"
      col="col-sm-6 col-md-4 col-lg-3"
      :modelValue="(modelValue as any).jackpotType ?? null"
      @update:modelValue="
        emit('update:modelValue', { ...modelValue, jackpotType: $event } as any);
        if (!$event) emit('update:jackpotPct', '')
      "
    />

    <!-- %Jackpot: deshabilitado hasta elegir un tipo de jackpot arriba, ya que
         el % no tiene sentido sin saber a qué pozo se destina. -->
    <FormInput
      label="%Jackpot"
      name="jackpot_pct"
      id="jackpot-pct"
      :hint="(modelValue as any).jackpotType ? '% del bote destinado al jackpot (opcional).' : 'Elige primero un tipo de Jackpot para poder configurar el %.'"
      type="number"
      :disabled="!(modelValue as any).jackpotType"
      :step="0.5"
      :min="percentageLimits.jackpotPct.min"
      :max="percentageLimits.jackpotPct.max"
      placeholder="Opcional"
      col="col-sm-6 col-md-4 col-lg-3"
      :modelValue="jackpotPct"
      @update:modelValue="emit('update:jackpotPct', $event)"
    />

    <!-- Días de Anticipación de Ventas (solo salas automáticas) -->
    <FormInput
      v-if="automaticRoom"
      label="Días de Anticipación"
      name="sales_start_offset_days"
      :error="(errors as any).salesStartOffsetDays"
      hint="Días antes del sorteo en que la sala se publica y empieza a vender."
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
