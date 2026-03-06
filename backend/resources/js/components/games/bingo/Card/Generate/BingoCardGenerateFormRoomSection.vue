<script setup lang="ts" name="BingoCardGenerateFormRoomSection">
  import { computed } from 'vue'

  import { useBingoCardGenerateRoomSection } from '@/js/composables/games/bingo/card/useBingoCardGenerateRoomSection'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'

  import type { SelectOption } from '@/js/types/common/form/form'

  const props = defineProps<{
    roomTypeOptions: SelectOption[]
    gameModeOptions: SelectOption[]
  }>()

  const {
    filters,
    rooms,
    selectedRoom,
    selectedRoomId,
    loadingRooms,
    roomAlert,
    canSelectRoomType,
    canSelectGameMode,
    canSelectRoom
  } = useBingoCardGenerateRoomSection()

  const minDate = computed(() => new Date().toISOString().split('T')[0])

  defineExpose({ selectedRoom })
</script>

<template>
  <!-- Fecha -->

  <FormInput
    label="Fecha"
    name="date"
    type="date"
    :min="minDate"
    col="col-12 mb-3"
    required
    v-model="filters.date"
  />

  <!-- Loader -->

  <div v-if="loadingRooms" class="my-3 text-center">
    <div class="spinner-border" role="status" />
  </div>

  <!-- Alert -->

  <div v-else-if="roomAlert" class="alert alert-warning">
    {{ roomAlert }}
  </div>

  <!-- Tipo de sala -->

  <FormSelect
    v-if="canSelectRoomType"
    label="Tipo de sala"
    name="room_type"
    :options="props.roomTypeOptions"
    option-value="value"
    option-label="label"
    col="col-12 mb-3"
    v-model="filters.roomType"
  />

  <!-- Modo de juego -->

  <FormSelect
    v-if="canSelectGameMode"
    label="Modo de juego"
    name="game_mode"
    :options="props.gameModeOptions"
    option-value="value"
    option-label="label"
    col="col-12 mb-3"
    v-model="filters.gameMode"
  />

  <!-- Sala -->

  <FormSelect
    v-if="canSelectRoom"
    label="Sala"
    name="room"
    :options="rooms"
    option-value="id"
    option-label="uuid_code"
    col="col-12 mb-3"
    v-model="selectedRoomId"
  />
</template>
