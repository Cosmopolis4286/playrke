<script setup lang="ts" name="BingoCardGenerateFormRoomSection">
  import { computed } from 'vue'

  import { useBingoCardGenerateRoomSection } from '@/js/composables/games/bingo/card/useBingoCardGenerateRoomSection'
  import FormSelect from '@/js/Components/Common/Form/FormSelect.vue'
  import FormInput from '@/js/Components/Common/Form/FormInput.vue'

  import type { SelectOption } from '@/js/types/common/form/form'

  const props = defineProps<{
    roomTypeOptions: SelectOption[]
    gameModeOptions: SelectOption[]
    prizePoolTypeOptions: SelectOption[]
  }>()

  const {
    filters,
    roomOptions,
    selectedRoom,
    selectedRoomId,
    loadingRooms,
    isLoading,
    roomAlert,
    showRoomType,
    showGameMode,
    showPrizePoolType,
    canSelectRoom
  } = useBingoCardGenerateRoomSection()

  const minDate = computed(() => new Date().toISOString().split('T')[0])

  defineExpose({ selectedRoom })
</script>

<template>
  <div class="position-relative">
    <!-- Loader -->
    <div v-if="loadingRooms" class="position-absolute" style="top: 0; right: 0">
      <div class="spinner-border spinner-border-sm text-secondary" role="status" />
    </div>

    <div class="row">
      <!-- Fecha -->

      <FormInput
        :disabled="isLoading"
        label="Fecha"
        name="date"
        type="date"
        :min="minDate"
        col="col-sm-6 col-md-3"
        required
        v-model="filters.date"
      />

      <!-- Tipo de sala -->

      <FormSelect
        v-if="showRoomType"
        :disabled="isLoading"
        label="Tipo de sala"
        name="room_type"
        :options="props.roomTypeOptions"
        option-value="value"
        option-label="label"
        col="col-sm-6 col-md-3"
        v-model="filters.roomType"
      />

      <!-- Modo de juego -->

      <FormSelect
        v-if="showGameMode"
        :disabled="isLoading"
        label="Modo de juego"
        name="game_mode"
        :options="props.gameModeOptions"
        option-value="value"
        option-label="label"
        col="col-sm-6 col-md-3"
        v-model="filters.gameMode"
      />

      <!-- Tipo de Bote -->

      <FormSelect
        v-if="showPrizePoolType"
        :disabled="isLoading"
        label="Tipo de Bote"
        name="prize_pool_type"
        :options="props.prizePoolTypeOptions"
        option-value="value"
        option-label="label"
        col="col-sm-6 col-md-3"
        v-model="filters.prizePoolType"
      />

      <!-- Alert -->

      <div v-if="roomAlert" class="col-12">
        <div class="alert alert-warning my-2">{{ roomAlert }}</div>
      </div>

      <!-- Sala -->

      <FormSelect
        v-if="canSelectRoom"
        label="Sala"
        name="room"
        :options="roomOptions"
        option-value="id"
        option-label="label"
        col="col-12"
        v-model="selectedRoomId"
      />
    </div>
  </div>
</template>
