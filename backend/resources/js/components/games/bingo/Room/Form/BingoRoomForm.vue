<script setup lang="ts" name="GameBingoRoomForm">
  const { form, errors, isValid } = useBingoRoomForm(...)
  const { totalPercentage, redistribute } = useRoomRounds(form)
  const { updateCurrencyPrice } = useRoomCurrency(form)
</script>
<template>
  <RoomBasicSection v-model="form" :errors="errors" />

  <RoomCurrencySection v-model="form" :errors="errors" />

  <RoomRoundsSection v-model="form" :errors="errors" />

  <RoomSerialsSection v-model="form" :errors="errors" />
</template>
