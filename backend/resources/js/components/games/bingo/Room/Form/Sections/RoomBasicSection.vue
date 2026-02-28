<script setup lang="ts">
  import { reactive, watch, computed } from 'vue'
  import { useRoomRounds } from '../Composables/useRoomRounds'
  import { useRoomCurrency, type CurrencyOption } from '../Composables/useRoomCurrency'

  interface RoomBasicForm {
    name: string
    date: string
    rounds: number
    roundsPct: number
    mainCurrency: string
  }

  const props = defineProps<{
    modelValue: RoomBasicForm
    currencies: CurrencyOption[]
  }>()

  const emit = defineEmits<{
    (e: 'update:modelValue', value: RoomBasicForm): void
  }>()

  const localForm = reactive({ ...props.modelValue })

  /**
   * Rondas composable
   */
  const {
    rounds,
    roundsPct,
    errors: roundsErrors
  } = useRoomRounds({
    maxRounds: 15,
    percentageStep: 5
  })

  rounds.value = localForm.rounds
  roundsPct.value = localForm.roundsPct

  /**
   * Moneda composable
   */
  const {
    allowedCurrencies,
    mainCurrency,
    errors: currencyErrors
  } = useRoomCurrency(props.currencies)

  const selectedCurrencyCode = computed({
    get: () => mainCurrency.value?.code ?? '',
    set: (code: string) => {
      const found = allowedCurrencies.value.find(c => c.code === code)
      if (found) mainCurrency.value = found
    }
  })

  /**
   * Validación simple básica
   */
  const localErrors = reactive<{ name?: string; date?: string }>({})

  function validateBasic() {
    localErrors.name = !localForm.name ? 'Nombre requerido' : undefined
    localErrors.date = !localForm.date ? 'Fecha requerida' : undefined
  }

  /**
   * Sync hacia padre
   */
  watch(
    [localForm, rounds, roundsPct, mainCurrency],
    () => {
      validateBasic()

      emit('update:modelValue', {
        ...localForm,
        rounds: rounds.value,
        roundsPct: roundsPct.value,
        mainCurrency: mainCurrency.value?.code ?? ''
      })
    },
    { deep: true }
  )
</script>

<template>
  <div class="row g-3">
    <!-- Nombre -->
    <div class="col-md-6">
      <label class="form-label">Nombre de la sala</label>
      <input
        type="text"
        class="form-control"
        :class="{ 'is-invalid': localErrors.name }"
        v-model="localForm.name"
      />
      <div class="invalid-feedback" v-if="localErrors.name">
        {{ localErrors.name }}
      </div>
    </div>

    <!-- Fecha -->
    <div class="col-md-6">
      <label class="form-label">Fecha</label>
      <input
        type="date"
        class="form-control"
        :class="{ 'is-invalid': localErrors.date }"
        v-model="localForm.date"
      />
      <div class="invalid-feedback" v-if="localErrors.date">
        {{ localErrors.date }}
      </div>
    </div>

    <!-- Rondas -->
    <div class="col-md-4">
      <label class="form-label">Rondas</label>
      <input
        type="number"
        min="1"
        class="form-control"
        :class="{ 'is-invalid': roundsErrors.rounds }"
        v-model.number="rounds"
      />
      <div class="invalid-feedback" v-if="roundsErrors.rounds">
        {{ roundsErrors.rounds }}
      </div>
    </div>

    <!-- % Premio -->
    <div class="col-md-4">
      <label class="form-label">% Premio</label>
      <input
        type="number"
        min="0"
        max="100"
        class="form-control"
        :class="{ 'is-invalid': roundsErrors.roundsPct }"
        v-model.number="roundsPct"
      />
      <div class="invalid-feedback" v-if="roundsErrors.roundsPct">
        {{ roundsErrors.roundsPct }}
      </div>
    </div>

    <!-- Moneda principal -->
    <div class="col-md-4">
      <label class="form-label">Moneda principal</label>
      <select
        class="form-select"
        :class="{ 'is-invalid': currencyErrors.mainCurrency }"
        v-model="selectedCurrencyCode"
      >
        <option v-for="c in allowedCurrencies" :key="c.code" :value="c.code">
          {{ c.code }}
        </option>
      </select>
      <div class="invalid-feedback" v-if="currencyErrors.mainCurrency">
        {{ currencyErrors.mainCurrency }}
      </div>
    </div>
  </div>
</template>
