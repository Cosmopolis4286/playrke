<script setup lang="ts" name="BingoCardGenerateFormRoomSection">
  import { computed } from 'vue'
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'
  import type { SelectOption } from '@/js/types/common/form/form'
  import type { Room } from '@/js/types/games/bingo/card/bingo-card-generate'

  const props = defineProps<{
    date: string
    roomType: string | null
    gameMode: string | null

    rooms: Room[]
    selectedRoom: Room | null

    roomTypeOptions: SelectOption[]
    gameModeOptions: SelectOption[]

    canSelectRoomType: boolean
    canSelectGameMode: boolean
    canSelectRoom: boolean

    loadingRooms: boolean
    noRoomsForDate: boolean
  }>()

  const emit = defineEmits<{
    (e: 'update:date', value: string): void
    (e: 'update:roomType', value: string | null): void
    (e: 'update:gameMode', value: string | null): void
    (e: 'update:selectedRoom', value: Room | null): void
  }>()

  const minDate = computed(() => new Date().toISOString().split('T')[0])

  /* helpers para evitar errores TS en template */

  function handleDate(e: Event) {
    const value = (e.target as HTMLInputElement).value
    emit('update:date', value)
  }

  function handleRoomType(e: Event) {
    const value = (e.target as HTMLSelectElement).value || null
    emit('update:roomType', value)
  }

  function handleGameMode(e: Event) {
    const value = (e.target as HTMLSelectElement).value || null
    emit('update:gameMode', value)
  }

  function handleRoom(e: Event) {
    const value = (e.target as HTMLSelectElement).value
    const room = props.rooms.find(r => r.id === value) ?? null
    emit('update:selectedRoom', room)
  }
</script>

<template>
  <!-- Fecha -->

  <FormInput
    label="Fecha"
    name="date"
    type="date"
    required
    :min="minDate"
    col="col-sm-6 col-md-4 col-lg-3"
    :modelValue="date"
    @input="handleDate"
  />

  <!-- Loading -->

  <div v-if="loadingRooms" class="my-3 text-center">
    <div class="spinner-border" role="status" />
  </div>

  <!-- Sin salas -->

  <div v-else-if="noRoomsForDate" class="alert alert-warning">
    No hay salas disponibles para la fecha seleccionada.
  </div>

  <!-- Tipo de sala -->

  <div v-if="canSelectRoomType" class="mb-3">
    <label class="form-label">Tipo de sala</label>

    <select class="form-select" :value="roomType" @change="handleRoomType">
      <option :value="null">Seleccionar</option>

      <option v-for="option in roomTypeOptions" :key="option.value" :value="option.value">
        {{ option.label }}
      </option>
    </select>
  </div>

  <!-- Modo de juego -->

  <div v-if="canSelectGameMode" class="mb-3">
    <label class="form-label">Modo de juego</label>

    <select class="form-select" :value="gameMode" @change="handleGameMode">
      <option :value="null">Seleccionar</option>

      <option v-for="option in gameModeOptions" :key="option.value" :value="option.value">
        {{ option.label }}
      </option>
    </select>
  </div>

  <!-- Selección de sala -->

  <div v-if="canSelectRoom" class="mb-3">
    <label class="form-label">Sala</label>

    <select class="form-select" :value="selectedRoom?.id ?? null" @change="handleRoom">
      <option :value="null">Seleccionar</option>

      <option v-for="room in rooms" :key="room.id" :value="room.id">
        {{ room.uuid_code }}
      </option>
    </select>
  </div>
</template>
