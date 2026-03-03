<script setup lang="ts" name="BingoCardGenerateFormRoomSection">
  import { computed } from 'vue'
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'

  import type { Room } from '@/js/types/games/bingo/card/bingo-card-generate'
  import type { SelectOption } from '@/js/types/common/form/form'

  /* ==========================================================================
   * Props
   * ========================================================================== */

  const props = defineProps<{
    rooms: Room[]
    selectedRoom: Room | null
    roomCtnPrice: number
    date: string
    roomType: string | null
    gameMode: string | null
    roomTypeOptions: SelectOption[]
    gameModeOptions: SelectOption[]
  }>()

  /* ==========================================================================
   * Emits
   * ========================================================================== */

  const emit = defineEmits<{
    (e: 'update:selectedRoom', value: Room | null): void
    (e: 'update:date', value: string): void
    (e: 'update:roomType', value: string | null): void
    (e: 'update:gameMode', value: string | null): void
  }>()

  /* ==========================================================================
   * Computed
   * ========================================================================== */

  const minDate = computed(() => new Date().toISOString().split('T')[0])

  const roomOptions = computed<SelectOption[]>(() =>
    props.rooms.map(room => ({
      value: room.id,
      label: room.uuid_code
    }))
  )

  /**
   * Evita buscar en cada render dentro del template
   */
  const selectedRoomId = computed({
    get: () => props.selectedRoom?.id ?? null,
    set: (id: string | null) => {
      const room = props.rooms.find(r => r.id === id) ?? null

      emit('update:selectedRoom', room)
    }
  })
</script>

<template>
  <div class="row">
    <!-- Fecha -->
    <FormInput
      label="Fecha"
      name="date"
      type="date"
      required
      :min="minDate"
      col="col-sm-6 col-md-4 col-lg-3"
      :modelValue="date"
      @update:modelValue="emit('update:date', $event)"
    />

    <!-- Tipo de Sala -->
    <FormSelect
      v-if="date"
      label="Tipo de Sala"
      name="room_type"
      required
      placeholder="Selecciona tipo"
      :options="roomTypeOptions"
      optionValue="value"
      optionLabel="label"
      col="col-sm-6 col-md-4 col-lg-3"
      :modelValue="roomType"
      @update:modelValue="emit('update:roomType', $event)"
    />

    <!-- Modo de Juego -->
    <FormSelect
      v-if="roomType"
      label="Modo de Juego"
      name="game_mode"
      required
      placeholder="Selecciona modo"
      :options="gameModeOptions"
      optionValue="value"
      optionLabel="label"
      col="col-sm-6 col-md-4 col-lg-3"
      :modelValue="gameMode"
      @update:modelValue="emit('update:gameMode', $event)"
    />

    <!-- Sala -->
    <FormSelect
      v-if="gameMode"
      label="Sala de Bingo"
      name="room_id"
      :options="roomOptions"
      optionValue="value"
      optionLabel="label"
      placeholder="Seleccione una sala"
      col="col-12 col-md-6"
      v-model="selectedRoomId"
    />

    <!-- Precio -->
    <FormInput
      v-if="selectedRoom"
      label="Precio Cartón (USD)"
      name="room_price"
      type="number"
      col="col-6 col-md-3"
      :modelValue="roomCtnPrice"
      readonly
    />
  </div>
</template>
