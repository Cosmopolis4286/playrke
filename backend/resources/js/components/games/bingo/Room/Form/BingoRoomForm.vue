<script setup lang="ts" name="GameBingoRoomForm">
  import { watch } from 'vue'

  import RoomBasicSection from './Sections/RoomBasicSection.vue'
  import RoomCurrencySection from './Sections/RoomCurrencySection.vue'
  import RoomRoundsSection from './Sections/RoomRoundsSection.vue'
  import RoomSerialsSection from './Sections/RoomSerialsSection.vue'

  import { useBingoRoomForm } from '@/js/composables/games/bingo/room/useBingoRoomForm'
  import { useRoomRounds } from './Composables/useRoomRounds'
  import { useRoomCurrency } from './Composables/useRoomCurrency'

  /**
   * Estado principal del formulario
   */
  const { form, errors, validate, isValid } = useBingoRoomForm()

  /**
   * Rondas (solo conectamos campos específicos)
   */
  const { rounds, roundsPct, totalPercentage, redistribute } = useRoomRounds({
    maxRounds: 15,
    percentageStep: 5
  })

  /**
   * Sincronización form <-> composable rounds
   */
  watch(
    () => form.rounds,
    v => {
      if (v !== rounds.value) rounds.value = v
    }
  )

  watch(
    () => form.roundsPct,
    v => {
      if (v !== roundsPct.value) roundsPct.value = v
    }
  )

  watch([rounds, roundsPct], () => {
    form.rounds = rounds.value
    form.roundsPct = roundsPct.value
  })

  /**
   * Monedas
   */
  const { allowedCurrencies, mainCurrency, updateCurrencyPrice } = useRoomCurrency()

  watch(
    () => form.allowedCurrencies,
    v => (allowedCurrencies.value = v),
    { deep: true }
  )

  watch(
    () => form.mainCurrency,
    code => {
      const found = allowedCurrencies.value.find(c => c.code === code)
      if (found) mainCurrency.value = found
    }
  )

  watch([allowedCurrencies, mainCurrency], () => {
    form.allowedCurrencies = allowedCurrencies.value
    form.mainCurrency = mainCurrency.value?.code ?? ''
  })

  /**
   * Submit
   */
  function submit() {
    validate()

    if (!isValid.value) return

    console.log('ROOM READY', form)
  }
</script>

<template>
  <div class="room-form">
    <RoomBasicSection v-model="form" :errors="errors" />

    <RoomCurrencySection v-model="form" :errors="errors" @update-price="updateCurrencyPrice" />

    <RoomRoundsSection
      v-model="form"
      :errors="errors"
      :total-percentage="totalPercentage"
      @redistribute="redistribute"
    />

    <RoomSerialsSection v-model="form" :errors="errors" />

    <div class="mt-4 text-end">
      <button class="btn btn-primary" :disabled="!isValid" @click="submit">Guardar sala</button>
    </div>
  </div>
</template>
