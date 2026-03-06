<script setup lang="ts" name="BingoCardGenerateFormRoomSection">
  import { computed } from 'vue'

  import { useBingoCardGenerateRoomSection } from '@/js/composables/games/bingo/card/useBingoCardGenerateRoomSection'

  import type { SelectOption } from '@/js/types/common/form/form'

  const props = defineProps<{
    roomTypeOptions: SelectOption[]
    gameModeOptions: SelectOption[]
  }>()

  const {
    filters,
    rooms,
    selectedRoom,
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

  <div class="mb-3">
    <label class="form-label">Fecha <span class="text-danger">*</span></label>
    <input
      type="date"
      class="form-control"
      :min="minDate"
      v-model="filters.date"
    />
  </div>

  <!-- Loader -->

  <div v-if="loadingRooms" class="my-3 text-center">
    <div class="spinner-border" role="status" />
  </div>

  <!-- Alert -->

  <div v-else-if="roomAlert" class="alert alert-warning">
    {{ roomAlert }}
  </div>

  <!-- Tipo de sala -->

  <div v-if="canSelectRoomType" class="mb-3">
    <label class="form-label">Tipo de sala</label>

    <select class="form-select" v-model="filters.roomType">
      <option :value="null">Seleccionar</option>

      <option v-for="option in props.roomTypeOptions" :key="option.value" :value="option.value">
        {{ option.label }}
      </option>
    </select>
  </div>

  <!-- Modo de juego -->

  <div v-if="canSelectGameMode" class="mb-3">
    <label class="form-label">Modo de juego</label>

    <select class="form-select" v-model="filters.gameMode">
      <option :value="null">Seleccionar</option>

      <option v-for="option in props.gameModeOptions" :key="option.value" :value="option.value">
        {{ option.label }}
      </option>
    </select>
  </div>

  <!-- Sala -->

  <div v-if="canSelectRoom" class="mb-3">
    <label class="form-label">Sala</label>

    <select class="form-select" v-model="selectedRoom">
      <option :value="null">Seleccionar</option>

      <option v-for="room in rooms" :key="room.id" :value="room">
        {{ room.uuid_code }}
      </option>
    </select>
  </div>
</template>
